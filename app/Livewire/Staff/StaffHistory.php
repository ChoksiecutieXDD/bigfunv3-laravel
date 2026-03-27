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
#[Title('Lead Operator Log | BigFun')]
class StaffHistory extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $specificDate = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'specificDate' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingSpecificDate()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'statusFilter', 'specificDate']);
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();
        $fullName = $user->first_name . ' ' . $user->last_name;
        $shortName = $user->first_name . ' ' . substr($user->last_name, 0, 1) . '.';

        $query = DB::table('bookings')
            ->where(function ($query) use ($fullName, $shortName) {
                $query->where('lead_operator', $fullName)
                    ->orWhere('lead_operator', $shortName);
            });

        // Apply Search
        if (!empty($this->search)) {
            $s = '%' . $this->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('id', 'like', $s)
                    ->orWhere('customer_first_name', 'like', $s)
                    ->orWhere('customer_last_name', 'like', $s)
                    ->orWhere('address_line_1', 'like', $s);
            });
        }

        // Status Filter
        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        // Specific Date Filter
        if (!empty($this->specificDate)) {
            $query->whereDate('event_date', $this->specificDate);
        }

        $historyData = $query->orderBy('event_date', 'desc')
            ->orderBy('start_time', 'asc')
            ->paginate(15);

        return view('livewire.staff.staff-history', [
            'fullName' => $fullName,
            'historyData' => $historyData,
        ]);
    }
}
