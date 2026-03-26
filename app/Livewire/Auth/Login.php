<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

// 👇 Here is the fixed layout path!
#[Layout('components.layouts.auth-layout')]
class Login extends Component
{
    public $role = '';
    public $email = '';
    public $password = '';
    public $remember = false;

    // This allows you to set the role when the component loads
    public function mount()
    {
        // If they visited /supervisor/login, set the role automatically
        if (request()->routeIs('supervisor.login')) {
            $this->role = 'Supervisor';
        }
    }

    public function login()
    {
        // 1. Basic Input Validation
        $this->validate([
            'role'     => 'required|string',
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // 2. Database Verification & Attempt
        // We still use 'password' here in the array because Laravel translates it 
        // to 'password_hash' behind the scenes using the User model override.
        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {

            $user = Auth::user();
            $db_role = $user->role;
            $requested_role = $this->role;

            // --- 3. ACCOUNT STATUS CHECK ---
            if (isset($user->is_active) && (int)$user->is_active === 0) {
                Auth::logout();
                $this->addError('auth', 'Account is inactive. Please contact the Administrator.');
                return;
            }

            // --- 4. ROLE & ACCESS CONTROL CHECK ---
            $access_granted = false;

            if ($requested_role === $db_role) {
                $access_granted = true;
            } elseif ($requested_role === 'Staff' && in_array($db_role, ['Operator', 'Deliverer'])) {
                $access_granted = true;
            }

            if (!$access_granted) {
                Auth::logout();
                $this->addError('auth', 'This account does not have ' . $requested_role . ' privileges.');
                return;
            }

            // --- 5. EXACT REDIRECTS ---
            if ($db_role === 'Administrator' || $db_role === 'Admin') {
                return redirect('/admin/dashboard');
            } elseif ($db_role === 'Supervisor') {
                return redirect('/supervisor/calendar');
            } else {
                return redirect('/staff/dashboard');
            }
        }

        // --- 6. INVALID CREDENTIALS ---
        $this->addError('auth', 'Invalid credentials. Please try again.');
        $this->reset('password');
    }

    public function render()
    {
        // Dynamically return the view based on the role to keep logic in one component
        if ($this->role === 'Supervisor') {
            return view('livewire.auth.supervisor-login');
        }

        return view('livewire.auth.login');
    }
}
