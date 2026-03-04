<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

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
        $this->dispatch('notify', message: 'Preparing database backup...', type: 'success');

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
    public function clearCache()
    {
        try {
            // Use safe clearing commands instead of optimize:clear
            Artisan::call('cache:clear');
            Artisan::call('route:clear');
            Artisan::call('config:clear');

            $this->dispatch('notify', message: 'System cache cleared successfully.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Error: ' . $e->getMessage(), type: 'error');
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
                $this->dispatch('notify', message: 'Maintenance mode turned OFF.', type: 'success');
            } else {
                // Generates the Laravel maintenance file. 
                // You can bypass the lock screen by visiting: yoursite.com/bigfun-admin
                Artisan::call('down', ['--secret' => 'bigfun-admin']);
                $this->isMaintenance = true;
                $this->dispatch('notify', message: 'Maintenance mode turned ON.', type: 'info');
            }
        } catch (\Exception $e) {
            // If it fails, revert the toggle property so the UI checkbox unchecks itself
            $this->isMaintenance = app()->isDownForMaintenance();
            $this->dispatch('notify', message: 'Connection error. Check console for details.', type: 'error');
        }
    }

    // ==========================================
    // 4. ENVIRONMENT TOGGLE
    // ==========================================
    public function changeEnvironment($env)
    {
        $validEnvs = ['local', 'development', 'staging', 'production'];

        if (in_array($env, $validEnvs)) {
            try {
                $path = base_path('.env');
                if (file_exists($path)) {
                    $envContent = file_get_contents($path);
                    $envContent = preg_replace("/^APP_ENV=.*/m", "APP_ENV={$env}", $envContent);
                    file_put_contents($path, $envContent);

                    $this->currentEnv = $env;
                    $this->dispatch('notify', message: 'Environment set to ' . strtoupper($env), type: 'success');
                }
            } catch (\Exception $e) {
                $this->dispatch('notify', message: 'Failed to save the environment setting.', type: 'error');
            }
        }
    }

    // ==========================================
    // 5. TEST SMTP
    // ==========================================
    public function testSmtp()
    {
        try {
            // Temporarily override config with your hardcoded values for the test
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

            $this->dispatch('notify', message: 'Test email sent! Please check your Gmail inbox.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: "Failed to connect to server. Check console for details.", type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.system-settings');
    }
}
