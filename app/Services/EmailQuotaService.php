<?php

namespace App\Services;

class EmailQuotaService
{
    public function statusForDefaultMailer(): array
    {
        $mailer = (string) config('mail.default', 'smtp');
        if (! in_array($mailer, ['smtp', 'google'], true)) {
            $mailer = 'smtp';
        }

        return $this->statusForMailer($mailer);
    }

    public function statusForMailer(string $mailer): array
    {
        $isGoogle = $mailer === 'google';
        $cacheKey = "email_quota_used_{$mailer}_" . now()->format('Y-m-d');
        
        $usedRaw = \Illuminate\Support\Facades\Cache::get($cacheKey);
        
        // Fallback to config if not in cache (represents the starting state in .env)
        if ($usedRaw === null) {
            $usedRaw = config($isGoogle ? 'mail.google_quota.daily_email_used' : 'mail.brevo.daily_email_used');
        }
        
        $limitRaw = config($isGoogle ? 'mail.google_quota.daily_email_limit' : 'mail.brevo.daily_email_limit');

        $used = is_numeric($usedRaw) ? (int) $usedRaw : 0;
        $limit = is_numeric($limitRaw) ? (int) $limitRaw : ($isGoogle ? 500 : 300);

        $used = max(0, $used);
        $limit = max(1, $limit);
        $remaining = max(0, $limit - $used);
        $lowThreshold = max(10, (int) ceil($limit * 0.1));

        return [
            'mailer' => $mailer,
            'label' => $isGoogle ? 'Gmail (Secondary)' : 'Brevo (Primary)',
            'used' => $used,
            'limit' => $limit,
            'remaining' => $remaining,
            'is_low' => $remaining > 0 && $remaining <= $lowThreshold,
            'is_limit_reached' => $used >= $limit,
        ];
    }
}
