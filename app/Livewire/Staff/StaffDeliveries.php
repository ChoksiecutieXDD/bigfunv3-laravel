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
#[Title('Logistics Overview | BigFun')]
class StaffDeliveries extends Component
{
    use WithPagination;

    public $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage('pend_page');
        $this->resetPage('conf_page');
    }

    public function render()
    {
        $user = Auth::user();
        $fullName = $user->first_name . ' ' . $user->last_name;
        $firstName = $user->first_name;
        $today = Carbon::today()->toDateString();

        // Build a LIKE pattern that matches the first name at minimum.
        // This handles abbreviated last names like "Trishtan Alexis R." vs "Trishtan Alexis Reyes"
        $nameLike = '%' . $firstName . '%';

        // Base query for deliveries
        $baseQuery = DB::table('bookings')
            ->where('status', '!=', 'Cancelled')
            ->whereDate('event_date', '>=', $today);

        // Apply Search
        if (!empty($this->search)) {
            $s = '%' . $this->search . '%';
            $baseQuery->where(function ($query) use ($s) {
                $query->where('id', 'like', $s)
                    ->orWhere('customer_first_name', 'like', $s)
                    ->orWhere('customer_last_name', 'like', $s)
                    ->orWhere('suburb', 'like', $s);
            });
        }

        // My Assignments (Pending) - Filtered for current user as Driver or Operator
        // Uses LIKE matching to handle abbreviated/stored name variants
        $pendingDeliveries = (clone $baseQuery)
            ->where('status', 'Pending')
            ->where(function($q) use ($fullName, $nameLike) {
                $q->where('lead_deliverer', $fullName)
                  ->orWhere('lead_deliverer', 'like', $nameLike)
                  ->orWhere('lead_operator', $fullName)
                  ->orWhere('lead_operator', 'like', $nameLike);
            })
            ->orderBy('event_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(5, ['*'], 'pend_page');

        // My Scheduled & Confirmed - Filtered for current user as Driver or Operator
        $confirmedDeliveries = (clone $baseQuery)
            ->whereIn('status', ['Confirmed', 'Completed'])
            ->where(function($q) use ($fullName, $nameLike) {
                $q->where('lead_deliverer', $fullName)
                  ->orWhere('lead_deliverer', 'like', $nameLike)
                  ->orWhere('lead_operator', $fullName)
                  ->orWhere('lead_operator', 'like', $nameLike);
            })
            ->orderBy('event_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(5, ['*'], 'conf_page');

        return view('livewire.staff.staff-deliveries', [
            'fullName' => $fullName,
            'pendingDeliveries' => $pendingDeliveries,
            'confirmedDeliveries' => $confirmedDeliveries,
        ]);
    }
}
