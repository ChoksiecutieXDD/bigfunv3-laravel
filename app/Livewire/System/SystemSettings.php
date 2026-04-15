<?php

namespace App\Livewire\System;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

#[Layout('components.layouts.settings-layout')]
class SystemSettings extends Component
{
    public $isMaintenance;
    public $currentEnv;

    /**
     * Active application mailer: smtp (Brevo) or google (Gmail). Synced to MAIL_MAILER in .env.
     * Empty string when current config is another mailer (e.g. log) so neither radio appears forced.
     */
    public string $defaultMailer = '';

    public function mount()
    {
        $this->isMaintenance = app()->isDownForMaintenance();
        $this->currentEnv = config('app.env');
        $m = (string) config('mail.default');
        $this->defaultMailer = in_array($m, ['smtp', 'google'], true) ? $m : '';
    }

    public function updatedDefaultMailer(?string $value): void
    {
        // We do nothing here to prevent automatic updates. 
        // The update is triggered by the 'execute-change-mailer' event after confirmation.
    }

    #[On('execute-change-mailer')]
    public function executeChangeMailer(string $value): void
    {
        if (! in_array($value, ['smtp', 'google'], true)) {
            return;
        }

        $previous = (string) config('mail.default');
        
        try {
            $path = base_path('.env');
            if (! file_exists($path)) {
                $this->dispatch('show-toast', message: 'Cannot find .env file.', type: 'error');
                return;
            }

            $envContent = file_get_contents($path);
            if ($envContent === false) {
                throw new \RuntimeException('Could not read .env file.');
            }

            $newEnvContent = $envContent;
            if (preg_match('/^MAIL_MAILER=/m', $newEnvContent)) {
                $newEnvContent = preg_replace('/^MAIL_MAILER=.*/m', 'MAIL_MAILER='.$value, $newEnvContent);
            } else {
                $newEnvContent .= "\nMAIL_MAILER={$value}\n";
            }

            // Persist changes to .env
            if (file_put_contents($path, $newEnvContent, LOCK_EX) === false) {
                throw new \RuntimeException('Could not write .env file.');
            }

            // Clear configuration cache so the new .env value is picked up
            Artisan::call('config:clear');
            
            $this->dispatch('show-toast', message: 'Mailer updated successfully. Refreshing...', type: 'success');
            
            // Redirect to the same page to ensure a clean state
            $this->redirect(route('system.settings'), navigate: true);

        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Failed to update default mailer: '.$e->getMessage(), type: 'error');
        }
    }

    private function syncDefaultMailerFromConfig(): void
    {
        $m = (string) config('mail.default');
        $this->defaultMailer = in_array($m, ['smtp', 'google'], true) ? $m : '';
    }

    private function revertDefaultMailerUi(string $previous): void
    {
        $this->defaultMailer = in_array($previous, ['smtp', 'google'], true) ? $previous : '';
    }

    // ==========================================
    // 1. EXPORT DATABASE
    // ==========================================
    public function exportDb()
    {
        $this->dispatch('show-toast', message: 'Preparing database backup...', type: 'success');

        $dbName = env('DB_DATABASE', 'bigfun_backup');
        $date = now()->format('Y-m-d_H-i-s');
        $filename = "{$dbName}_{$date}.sql";

        $sqlScript = "-- BigFun Database Backup\n";
        $sqlScript .= "-- Generated: " . now()->format('Y-m-d H:i:s') . "\n\n";

        $tables = DB::select('SHOW TABLES');

        foreach ($tables as $tableObj) {
            $table = array_values((array)$tableObj)[0];
            $sqlScript .= "-- --------------------------------------------------------\n";
            $sqlScript .= "-- Table structure for `$table`\n";
            $sqlScript .= "-- --------------------------------------------------------\n";
            $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";

            $createTable = DB::select("SHOW CREATE TABLE `$table`")[0];
            $sqlScript .= array_values((array)$createTable)[1] . ";\n\n";

            $rows = DB::select("SELECT * FROM `$table`");
            if (count($rows) > 0) {
                $sqlScript .= "-- Dumping data for table `$table`\n";
                foreach ($rows as $row) {
                    $sqlScript .= "INSERT INTO `$table` VALUES(";
                    $values = array_values((array)$row);
                    $escapedValues = array_map(function ($val) {
                        if (is_null($val)) return 'NULL';
                        return '"' . str_replace("\n", "\\n", addslashes($val)) . '"';
                    }, $values);
                    $sqlScript .= implode(',', $escapedValues) . ");\n";
                }
                $sqlScript .= "\n";
            }
        }

        return response()->streamDownload(function () use ($sqlScript) {
            echo $sqlScript;
        }, $filename, ['Content-Type' => 'application/sql']);
    }

    // ==========================================
    // 2. SYSTEM CACHE
    // ==========================================
    #[On('execute-clear-cache')]
    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('route:clear');
            Artisan::call('config:clear');

            $this->dispatch('show-toast', message: 'System cache cleared successfully.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('show-alert', message: 'Error clearing cache: ' . $e->getMessage(), type: 'error');
        }
    }

    // ==========================================
    // 3. MAINTENANCE MODE
    // ==========================================
    public function toggleMaintenance()
    {
        try {
            if ($this->isMaintenance) {
                Artisan::call('up');
                $this->isMaintenance = false;
                // Changed to show-alert
                $this->dispatch('show-alert', message: 'Maintenance mode turned OFF. Site is live.', type: 'success');
            } else {
                Artisan::call('down', ['--secret' => 'bigfun-admin']);
                $this->isMaintenance = true;
                // Changed to show-alert
                $this->dispatch('show-alert', message: 'Maintenance mode turned ON. Public access restricted.', type: 'info');
            }
        } catch (\Exception $e) {
            $this->isMaintenance = app()->isDownForMaintenance();
            $this->dispatch('show-alert', message: 'Connection error changing maintenance status.', type: 'error');
        }
    }

    // ==========================================
    // 4. ENVIRONMENT TOGGLE
    // ==========================================
    #[On('execute-change-environment')]
    public function changeEnvironment($id = null)
    {
        // Extract the target environment string safely
        $targetEnv = is_array($id) ? ($id['id'] ?? $id[0] ?? null) : $id;

        if (!$targetEnv) {
            $this->dispatch('show-toast', message: 'Invalid environment selected.', type: 'error');
            return;
        }

        $validEnvs = ['local', 'development', 'staging', 'production'];

        if (in_array($targetEnv, $validEnvs)) {
            try {
                $path = base_path('.env');

                if (file_exists($path)) {
                    $envContent = file_get_contents($path);

                    // 1. Update APP_ENV
                    if (preg_match("/^APP_ENV=.*/m", $envContent)) {
                        $envContent = preg_replace("/^APP_ENV=.*/m", "APP_ENV={$targetEnv}", $envContent);
                    } else {
                        $envContent .= "\nAPP_ENV={$targetEnv}\n";
                    }

                    // 2. Update APP_DEBUG (True for local/dev, False for staging/prod)
                    $debugValue = ($targetEnv === 'production' || $targetEnv === 'staging') ? 'false' : 'true';
                    if (preg_match("/^APP_DEBUG=.*/m", $envContent)) {
                        $envContent = preg_replace("/^APP_DEBUG=.*/m", "APP_DEBUG={$debugValue}", $envContent);
                    } else {
                        $envContent .= "\nAPP_DEBUG={$debugValue}\n";
                    }

                    // Save the file
                    file_put_contents($path, $envContent);

                    // FORWARD THE NEW CONFIG TO LARAVEL IMMEDIATELY
                    Config::set('app.env', $targetEnv);
                    Config::set('app.debug', $debugValue === 'true');
                    
                    // Update component state for immediate UI feedback
                    $this->currentEnv = $targetEnv;

                    // Clear config cache to ensure the changes take effect globally
                    Artisan::call('config:clear');

                    $this->dispatch('show-alert', message: 'Environment successfully set to ' . strtoupper($targetEnv) . ' (Debug: ' . ($debugValue === 'true' ? 'ON' : 'OFF') . ')', type: 'success');
                } else {
                    $this->dispatch('show-toast', message: 'Cannot find .env file.', type: 'error');
                }
            } catch (\Exception $e) {
                $this->dispatch('show-toast', message: 'Failed to update .env: ' . $e->getMessage(), type: 'error');
            }
        } else {
            $this->dispatch('show-toast', message: 'Environment name not allowed.', type: 'error');
        }
    }

    // ==========================================
    // FORCE LOGOUT ALL
    // ==========================================
    #[On('execute-force-logout')]
    public function forceLogout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    }

    // ==========================================
    // 5. TEST SMTP
    // ==========================================
    #[On('execute-test-smtp')]
    public function testSmtp()
    {
        try {
            $to = config('mail.from.address');
            $host = config('mail.mailers.smtp.host');

            Mail::mailer('smtp')->send([], [], function ($message) use ($to, $host) {
                $message->to($to)
                    ->subject('SMTP primary (Brevo) test')
                    ->html(
                        '<div style="font-family: sans-serif; padding: 20px; background: #0f172a; color: #e2e8f0; border-radius: 10px;">'
                        . '<h2 style="color: #34d399;">Primary mailer OK</h2>'
                        . '<p>This message was sent using the <strong>smtp</strong> mailer from system settings.</p>'
                        . '<p style="font-size: 12px; color: #94a3b8;"><strong>Host:</strong> ' . e($host) . '</p>'
                        . '</div>'
                    );
            });

            $this->dispatch('show-toast', message: 'Test email sent! Please check your inbox.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('show-alert', message: 'SMTP Failed: ' . $e->getMessage(), type: 'error');
        }
    }

    #[On('execute-test-google-smtp')]
    public function testGoogleSmtp()
    {
        try {
            $to = config('mail.from.address');
            $host = config('mail.mailers.google.host');

            Mail::mailer('google')->send([], [], function ($message) use ($to, $host) {
                $message->to($to)
                    ->subject('SMTP secondary (Google) test')
                    ->html(
                        '<div style="font-family: sans-serif; padding: 20px; background: #0f172a; color: #e2e8f0; border-radius: 10px;">'
                        . '<h2 style="color: #60a5fa;">Secondary mailer OK</h2>'
                        . '<p>This message was sent using the <strong>google</strong> mailer from system settings.</p>'
                        . '<p style="font-size: 12px; color: #94a3b8;"><strong>Host:</strong> ' . e($host) . '</p>'
                        . '</div>'
                    );
            });

            $this->dispatch('show-toast', message: 'Test email sent! Please check your inbox.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('show-alert', message: 'SMTP Failed: ' . $e->getMessage(), type: 'error');
        }
    }

    // ==========================================
    // 6. QUOTA MANAGEMENT
    // ==========================================
    #[On('execute-reset-quota')]
    public function executeResetQuota(string $mailer)
    {
        if (! in_array($mailer, ['brevo', 'google'], true)) {
            return;
        }

        try {
            $path = base_path('.env');
            if (! file_exists($path)) {
                $this->dispatch('show-toast', message: 'Cannot find .env file.', type: 'error');
                return;
            }

            $envContent = file_get_contents($path);
            if ($envContent === false) {
                throw new \RuntimeException('Could not read .env file.');
            }

            $key = ($mailer === 'brevo') ? 'MAIL_BREVO_DAILY_EMAIL_USED' : 'MAIL_GOOGLE_DAILY_EMAIL_USED';
            
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}=0", $envContent);
            } else {
                $envContent .= "\n{$key}=0\n";
            }

            if (file_put_contents($path, $envContent, LOCK_EX) === false) {
                throw new \RuntimeException('Could not write .env file.');
            }

            Artisan::call('config:clear');
            
            $this->dispatch('show-alert', message: ucfirst($mailer) . ' daily counter has been reset to 0.', type: 'success');
            
            // Refresh state
            $this->redirect(route('system.settings'), navigate: true);

        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Failed to reset quota: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.system.system-settings');
    }
}
