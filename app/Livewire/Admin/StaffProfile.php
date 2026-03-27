<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\User;

#[Layout('components.layouts.admin')]
class StaffProfile extends Component
{
    public User $user;

    public function mount($id)
    {
        $this->user = User::findOrFail($id);
    }

    public function getInitials()
    {
        return strtoupper(mb_substr(trim($this->user->first_name), 0, 1) . mb_substr(trim($this->user->last_name), 0, 1));
    }

    public function render()
    {
        return view('livewire.admin.staff-profile');
    }
}
