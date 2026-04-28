<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.auth-layout')]
class ResetPassword extends Component
{
    public ?string $token = null;
    public string $password = '';
    public string $password_confirmation = '';
    public bool $isValid = false;
    public string $errorMsg = '';

    public function mount(?string $token)
    {
        $this->token = $token;

        // 1. Verify token exists and hasn't expired (mirrors legacy SELECT logic)
        $user = User::where('reset_token', $this->token)
            ->where('reset_expires', '>', now())
            ->first();

        if ($user) {
            $this->isValid = true;
        } else {
            $this->errorMsg = "This link has expired or is invalid. Please request a new one.";
        }
    }

    public function updatePassword()
    {
        // 2. Validate input
        $this->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        // 3. Find user by token again for security
        $user = User::where('reset_token', $this->token)
            ->where('reset_expires', '>', now())
            ->first();

        if ($user) {
            // 4. Update Password & Clear Token (mirrors legacy UPDATE logic)
            // Using password_hash equivalent in Laravel: Hash::make
            $user->update([
                'password_hash' => Hash::make($this->password),
                'reset_token'   => null,
                'reset_expires' => null,
            ]);

            // 5. Success - Redirect to login with success message
            if ($user->role === 'Supervisor') {
                return redirect('/supervisor/login')->with('password_reset_success', true);
            }

            return redirect('/login')->with('password_reset_success', true);
        }

        $this->addError('password', 'Session expired. Please try again.');
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
