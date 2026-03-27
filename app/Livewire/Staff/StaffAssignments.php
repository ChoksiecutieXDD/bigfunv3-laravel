<?php

namespace App\Livewire\Staff;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

#[Layout('components.layouts.staff')]
#[Title('My Assignments | BigFun')]
class StaffAssignments extends Component
{
    use WithPagination;

    public $search = '';
    public $activeTab = 'upcoming';

    protected $queryString = [
        'search' => ['except' => ''],
        'activeTab' => ['except' => 'upcoming'],
    ];

    public function updatingSearch()
    {
        $this->resetPage('up_page');
        $this->resetPage('hist_page');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $user = Auth::user();
        $fullName = $user->first_name . ' ' . $user->last_name;
        $shortName = $user->first_name . ' ' . substr($user->last_name, 0, 1) . '.';
        $today = Carbon::today()->toDateString();

        // Base query for assignments
        $baseQuery = DB::table('bookings')
            ->where(function ($query) use ($fullName, $shortName) {
                $query->where('lead_operator', $fullName)
                    ->orWhere('lead_operator', $shortName);
            });

        // Apply Search
        if (!empty($this->search)) {
            $s = '%' . $this->search . '%';
            $baseQuery->where(function ($query) use ($s) {
                $query->where('id', 'like', $s)
                    ->orWhere('customer_first_name', 'like', $s)
                    ->orWhere('customer_last_name', 'like', $s)
                    ->orWhere('address_line_1', 'like', $s)
                    ->orWhere('event_type', 'like', $s);
            });
        }

        // Upcoming Assignments (Confirmed Only, Today or Future)
        $upcomingAssignments = (clone $baseQuery)
            ->where('event_date', '>=', $today)
            ->where('status', 'Confirmed')
            ->orderBy('event_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(9, ['*'], 'up_page');

        // Past Assignments (Past Dates OR Completed/Cancelled)
        $pastAssignments = (clone $baseQuery)
            ->where(function($query) use ($today) {
                $query->where('event_date', '<', $today)
                    ->orWhereIn('status', ['Completed', 'Cancelled']);
            })
            ->orderBy('event_date', 'desc')
            ->orderBy('start_time', 'asc')
            ->paginate(10, ['*'], 'hist_page');

        return view('livewire.staff.staff-assignments', [
            'fullName' => $fullName,
            'upcomingAssignments' => $upcomingAssignments,
            'pastAssignments' => $pastAssignments,
        ]);
    }
}
