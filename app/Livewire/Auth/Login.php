<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('components.auth.auth-layout')]
class Login extends Component
{
    public $role = '';
    public $email = '';
    public $password = '';
    public $remember = false;

    public function login()
    {
        // 1. Basic Input Validation
        $this->validate([
            'role'     => 'required|string',
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // 2. Database Verification & Attempt
        // Auth::attempt handles password_verify(), session generation, and the remember_me cookie!
        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {

            $user = Auth::user();
            $db_role = $user->role;
            $requested_role = $this->role;

            // --- 3. ACCOUNT STATUS CHECK ---
            if ((int)$user->is_active === 0) {
                Auth::logout(); // Log them out immediately
                $this->addError('auth', 'Account is inactive. Please contact the Administrator.');
                return;
            }

            // --- 4. ROLE & ACCESS CONTROL CHECK ---
            $access_granted = false;

            // Exact match (e.g., Admin -> Admin, Staff -> Staff)
            if ($requested_role === $db_role) {
                $access_granted = true;
            }
            // Grouped match: Operators and Deliverers use the "Staff" dropdown option
            elseif ($requested_role === 'Staff' && in_array($db_role, ['Operator', 'Deliverer'])) {
                $access_granted = true;
            }

            if (!$access_granted) {
                Auth::logout(); // Log them out immediately
                $this->addError('auth', 'This account does not have ' . $requested_role . ' privileges.');
                return;
            }

            // --- 5. LOGIN SUCCESSFUL ---
            // session()->regenerate() is handled securely by Laravel on login

            if ($db_role === 'Administrator') {
                return redirect()->intended('/admin/dashboard');
            } else {
                // This covers Staff, Operator, and Deliverer
                return redirect()->intended('/staff/dashboard');
            }
        }

        // --- 6. INVALID CREDENTIALS ---
        $this->addError('auth', 'Invalid credentials. Please try again.');

        // Clear the password field on failure for security
        $this->reset('password');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
