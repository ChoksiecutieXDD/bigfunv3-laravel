<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.admin')]
class StaffManagement extends Component
{
    public $stats = ['Administrator' => 0, 'Supervisor' => 0, 'Staff' => 0, 'Total' => 0];

    // Add Form Properties
    public $first_name, $last_name, $email, $role = 'Staff', $contact_no;

    // Edit Form Properties
    public $edit_id, $edit_first_name, $edit_last_name, $edit_email, $edit_role, $edit_contact_no;
    public $edit_is_active = true;

    public function addStaff()
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:Administrator,Supervisor,Staff',
            'contact_no' => 'nullable|string',
        ]);

        User::create([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'password_hash' => Hash::make('BigFun2025'),
            'role' => $this->role,
            'contact_no' => $this->contact_no,
            'is_active' => 1,
        ]);

        session()->flash('message', 'New staff member added!');
        session()->flash('message_type', 'success');

        $this->reset(['first_name', 'last_name', 'email', 'role', 'contact_no']);
        $this->dispatch('close-modal', modal: 'addModal');
    }

    public function loadEditStaff($id)
    {
        $user = User::findOrFail($id);

        $this->edit_id = $user->user_id;
        $this->edit_first_name = $user->first_name;
        $this->edit_last_name = $user->last_name;
        $this->edit_email = $user->email;
        $this->edit_role = in_array($user->role, ['Deliverer', 'Operator']) ? 'Staff' : $user->role;
        $this->edit_contact_no = $user->contact_no;
        $this->edit_is_active = (bool) $user->is_active;

        $this->dispatch('open-modal', modal: 'editModal');
    }

    public function updateStaff()
    {
        $this->validate([
            'edit_first_name' => 'required|string|max:255',
            'edit_last_name' => 'required|string|max:255',
            'edit_email' => 'required|email|unique:users,email,' . $this->edit_id . ',user_id',
            'edit_role' => 'required|in:Administrator,Supervisor,Staff',
        ]);

        $user = User::findOrFail($this->edit_id);
        $user->update([
            'first_name' => $this->edit_first_name,
            'last_name' => $this->edit_last_name,
            'email' => $this->edit_email,
            'role' => $this->edit_role,
            'contact_no' => $this->edit_contact_no,
            'is_active' => $this->edit_is_active ? 1 : 0,
        ]);

        session()->flash('message', 'Staff details updated successfully.');
        session()->flash('message_type', 'success');

        $this->dispatch('close-modal', modal: 'editModal');
    }

    public function deleteStaff($id)
    {
        if ($id == Auth::id()) {
            session()->flash('message', 'You cannot delete your own account.');
            session()->flash('message_type', 'error');
            return;
        }

        User::findOrFail($id)->delete();

        session()->flash('message', 'Staff member deleted permanently.');
        session()->flash('message_type', 'success');
    }

    public function getInitials($first, $last)
    {
        return strtoupper(mb_substr(trim($first), 0, 1) . mb_substr(trim($last), 0, 1));
    }

    public function getRoleBadgeClass($role)
    {
        return match ($role) {
            'Administrator' => 'bg-purple-50 text-purple-700 border-purple-100',
            'Supervisor' => 'bg-rose-50 text-rose-700 border-rose-100',
            default => 'bg-blue-50 text-blue-700 border-blue-100',
        };
    }

    public function render()
    {
        $users = User::orderBy('role', 'asc')->orderBy('first_name', 'asc')->get();

        $this->stats = [
            'Total' => $users->count(),
            'Administrator' => $users->where('role', 'Administrator')->count(),
            'Supervisor' => $users->where('role', 'Supervisor')->count(),
            'Staff' => $users->whereIn('role', ['Staff', 'Operator', 'Deliverer'])->count(),
        ];

        return view('livewire.admin.staff-management', [
            'users' => $users
        ]);
    }
}
