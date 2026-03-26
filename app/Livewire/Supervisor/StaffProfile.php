<?php

namespace App\Livewire\Supervisor;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\User;

#[Layout('components.layouts.supervisor')]
class StaffProfile extends Component
{
    public User $user;

    // The $id parameter comes directly from your route, e.g., Route::get('/staff/{id}')
    public function mount($id)
    {
        // findOrFail will automatically show a 404 page if the user doesn't exist
        $this->user = User::findOrFail($id);
    }

    public function getInitials()
    {
        return strtoupper(mb_substr(trim($this->user->first_name), 0, 1) . mb_substr(trim($this->user->last_name), 0, 1));
    }

    public function render()
    {
        return view('livewire.supervisor.staff-profile');
    }
}
