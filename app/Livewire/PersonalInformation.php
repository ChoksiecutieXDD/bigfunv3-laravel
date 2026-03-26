<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class PersonalInformation extends Component
{
    // Profile Fields
    public $first_name, $last_name, $address, $age, $birthday, $gender, $contact_no;

    // Password Fields
    public $current_password, $new_password, $confirm_password;

    public function mount()
    {
        $user = Auth::user();
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->address = $user->address;
        $this->age = $user->age;
        $this->birthday = $user->birthday;
        $this->gender = $user->gender;
        $this->contact_no = $user->contact_no;
    }

    // Dynamically determines where the "Back" button goes based on role
    public function getBackLinkProperty()
    {
        $role = Auth::user()->role;

        if (in_array($role, ['Staff', 'Operator', 'Deliverer'])) {
            return '/staff/dashboard';
        } elseif ($role === 'Administrator') {
            return '/admin/dashboard';
        } else {
            return route('supervisor.calendar');
        }
    }

    public function getInitialsProperty()
    {
        $first = mb_substr(trim($this->first_name ?? ''), 0, 1);
        $last = mb_substr(trim($this->last_name ?? ''), 0, 1);
        return strtoupper($first . $last) ?: 'U';
    }

    public function updateProfile()
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'age' => 'nullable|integer|min:1',
            'birthday' => 'nullable|date',
            'gender' => 'nullable|string',
            'contact_no' => 'nullable|string',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'address' => $this->address,
            'age' => $this->age,
            'birthday' => $this->birthday,
            'gender' => $this->gender,
            'contact_no' => $this->contact_no,
        ]);

        session()->flash('profile_message', 'Profile updated successfully!');
        session()->flash('profile_type', 'success');
    }

    public function changePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|same:confirm_password',
            'confirm_password' => 'required',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!Hash::check($this->current_password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => 'Current password is incorrect.'
            ]);
        }

        $user->update([
            'password_hash' => Hash::make($this->new_password),
            'change_passtime' => now(),
        ]);

        $this->reset(['current_password', 'new_password', 'confirm_password']);

        session()->flash('password_message', 'Password changed successfully!');
        session()->flash('password_type', 'success');
    }

    public function render()
    {
        return view('livewire.personal-information');
    }
}
