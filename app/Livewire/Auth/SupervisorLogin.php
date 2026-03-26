<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.auth-layout')]
class SupervisorLogin extends Component
{
    public $email = '';
    public $password = '';
    public $role = ''; // <-- 1. ADD THIS PROPERTY
    public $remember = false;

    public function login()
    {
        $this->validate([
            'email'    => 'required|email',
            'password' => 'required',
            'role'     => 'required', // <-- 2. VALIDATE IT
        ]);

        // 3. USE $this->role INSTEAD OF HARDCODING 'Supervisor'
        if (Auth::attempt(['email' => $this->email, 'password' => $this->password, 'role' => $this->role], $this->remember)) {

            $user = Auth::user();

            // Account Status Check
            if ((int)$user->is_active === 0) {
                Auth::logout();
                $this->addError('auth', 'Account is inactive. Please contact the Administrator.');
                return;
            }

            session()->regenerate();
            return redirect()->intended('/calendar');
        }

        $this->addError('auth', 'Invalid credentials. Please try again.');
        $this->reset('password');
    }

    public function render()
    {
        return view('livewire.auth.supervisor-login');
    }
}
