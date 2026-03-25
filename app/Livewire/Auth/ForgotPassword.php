<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

#[Layout('layouts.auth.auth-layout')]
class ForgotPassword extends Component
{
    public $email = '';
    public $mailSent = false;

    public function sendResetLink()
    {
        $this->validate([
            'email' => 'required|email',
        ]);

        // 1. Check if user exists (Mirrors legacy SELECT logic)
        $user = User::where('email', $this->email)->first();

        if ($user) {
            // 2. Generate Token (Mirrors legacy bin2hex logic)
            $token = Str::random(64);

            // 3. Save Token to Database (Mirrors legacy DATE_ADD logic)
            $user->update([
                'reset_token'   => $token,
                'reset_expires' => now()->addHour(),
            ]);

            // 4. Generate Link (Clean Laravel routing)
            $resetLink = route('password.reset', ['token' => $token, 'email' => $this->email]);

            // 5. Send Email (Replacing PHPMailer with Laravel Mailer)
            Mail::send([], [], function ($message) use ($user, $resetLink) {
                $message->to($this->email)
                    ->subject('Password Reset Request')
                    ->html("
                        <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                            <h2 style='color: #9E6B73;'>Password Reset</h2>
                            <p>Hi {$user->first_name},</p>
                            <p>We received a request to reset your password. Click the button below to choose a new one:</p>
                            <p style='text-align: center; margin: 30px 0;'>
                                <a href='{$resetLink}' style='background-color: #9E6B73; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Reset Password</a>
                            </p>
                            <p style='font-size: 12px; color: #999;'>This link will expire in 1 hour.</p>
                        </div>
                    ");
            });
        }

        // Always show success to prevent email enumeration (Matches your legacy logic)
        $this->mailSent = true;
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
