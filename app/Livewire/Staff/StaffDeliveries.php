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

    public string $search = '';

    protected array $queryString = [
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

        // Base query for deliveries: Upcoming only
        $baseQuery = DB::table('bookings')
            ->whereNotIn('status', ['Cancelled', 'Deleted', 'Draft'])
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

        // My Assignments (Confirmed & Upcoming) - Filtered for current user as Driver or Operator
        $pendingDeliveries = (clone $baseQuery)
            ->where('status', 'Confirmed')
            ->where(function($q) use ($fullName, $firstName, $user) {
                $lastNamePattern = '%' . $user->last_name . '%';
                $firstNamePattern = '%' . $user->first_name . '%';
                
                $q->where('lead_deliverer', $fullName)
                  ->orWhere('lead_deliverer', 'like', $firstNamePattern)
                  ->orWhere('lead_deliverer', 'like', $lastNamePattern)
                  ->orWhere('lead_operator', $fullName)
                  ->orWhere('lead_operator', 'like', $firstNamePattern)
                  ->orWhere('lead_operator', 'like', $lastNamePattern);
            })
            ->orderBy('event_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(10, ['*'], 'pend_page');

        // All Other Assignments (General Overview) - To see what else is happening
        $confirmedDeliveries = (clone $baseQuery)
            ->whereIn('status', ['Confirmed', 'Completed'])
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
