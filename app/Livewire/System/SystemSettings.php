<?php

namespace App\Livewire\System;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.settings-layout')]
class SystemSettings extends Component
{
    public $isMaintenance;
    public $currentEnv;

    public function mount()
    {
        $this->isMaintenance = app()->isDownForMaintenance();
        $this->currentEnv = config('app.env');
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

                    // Update APP_ENV, or add it if it somehow doesn't exist
                    if (preg_match("/^APP_ENV=/m", $envContent)) {
                        $envContent = preg_replace("/^APP_ENV=.*/m", "APP_ENV={$targetEnv}", $envContent);
                    } else {
                        $envContent .= "\nAPP_ENV={$targetEnv}\n";
                    }

                    // Save the file
                    file_put_contents($path, $envContent);

                    // FORWARD THE NEW CONFIG TO LARAVEL
                    Artisan::call('config:clear');

                    $this->currentEnv = config('app.env'); // Fetch the fresh config to confirm

                    $this->dispatch('show-alert', message: 'Environment successfully set to ' . strtoupper($targetEnv), type: 'success');
                } else {
                    $this->dispatch('show-toast', message: 'Cannot find .env file.', type: 'error');
                }
            } catch (\Exception $e) {
                $this->dispatch('show-toast', message: 'Failed to update .env file. Please check file permissions.', type: 'error');
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
    public function testSmtp()
    {
        try {
            config([
                'mail.mailers.smtp.host' => 'smtp.gmail.com',
                'mail.mailers.smtp.port' => 587,
                'mail.mailers.smtp.encryption' => 'tls',
                'mail.mailers.smtp.username' => 'bigfun.qld.au@gmail.com',
                'mail.mailers.smtp.password' => 'fkpu ptou jdnc zduk',
            ]);

            Mail::send([], [], function ($message) {
                $message->to('bigfun.qld.au@gmail.com')
                    ->subject('SMTP Configuration Test - Success!')
                    ->html("
                        <div style='font-family: sans-serif; padding: 20px; background: #fdf2f4; border-radius: 10px;'>
                            <h2 style='color: #9E6B73;'>Connection Successful! 🎉</h2>
                            <p style='color: #333;'>Your local SMTP configuration is working perfectly via Google App Passwords.</p>
                            <hr style='border: none; border-top: 1px solid #ccc; margin: 15px 0;' />
                            <p style='color: #666; font-size: 12px;'><strong>Host:</strong> smtp.gmail.com <br> <strong>Port:</strong> 587</p>
                        </div>
                    ");
            });

            $this->dispatch('show-toast', message: 'Test email sent! Please check your inbox.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('show-alert', message: "SMTP Failed: " . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.system.system-settings');
    }
}
