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
        $today = Carbon::today()->toDateString();

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

        // Pending Actions
        $pendingDeliveries = (clone $baseQuery)
            ->where('status', 'Pending')
            ->orderBy('event_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(5, ['*'], 'pend_page');

        // Scheduled & Confirmed
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
