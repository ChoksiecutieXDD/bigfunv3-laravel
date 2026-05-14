<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

#[Layout('components.layouts.admin')]
class AdminDashboard extends Component
{
    // Stats
    public $total_bookings = 0;
    public $pending_bookings = 0;
    public $active_staff = 0;
    public $todays_revenue = 0;

    // Data Arrays
    public $recent_activities = [];
    public $payment_breakdown = ['Paid' => 0, 'Partial' => 0, 'Unpaid' => 0];

    // Chart Data
    public $chartLabels = [];
    public $chartData = [];

    // Chart Filter
    public $chartPeriod = 'This Week';

    public function mount()
    {
        $this->loadStats();
        $this->loadRecentActivities();
        $this->loadPaymentBreakdown();
        
        // Load initial chart data for the first render
        $this->fetchChartData($this->chartPeriod);
    }

    public function loadStats()
    {
        $today = Carbon::today();

        $this->total_bookings = DB::table('bookings')
            ->whereMonth('event_date', $today->month)
            ->whereYear('event_date', $today->year)
            ->whereNotIn('status', ['Cancelled', 'Deleted'])
            ->count();

        $this->pending_bookings = DB::table('bookings')
            ->where('status', 'Pending')
            ->count();

        $this->active_staff = DB::table('users')
            ->whereIn('role', ['Staff', 'Operator', 'Deliverer'])
            ->count();

        $this->todays_revenue = DB::table('bookings')
            ->whereDate('event_date', $today->toDateString())
            ->whereIn('status', ['Confirmed', 'Completed'])
            ->sum('total_amount') ?? 0;
    }

    public function loadRecentActivities()
    {
        $this->recent_activities = DB::table('bookings')
            ->select('id', 'customer_first_name', 'customer_last_name', 'status', 'event_date', 'booked_by')
            ->whereNotIn('status', ['Cancelled', 'Deleted'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    public function loadPaymentBreakdown()
    {
        $payments = DB::table('bookings as b')
            ->leftJoin('booking_payments as p', 'b.id', '=', 'p.booking_id')
            ->select('b.id', 'b.total_amount', DB::raw('COALESCE(SUM(p.amount), 0) as paid'))
            ->whereNotIn('b.status', ['Cancelled', 'Deleted']);

        // Optional: Filter by period if you want the pie chart to be relative to the dropdown
        // For now, let's keep it global but refresh it properly
        
        $results = $payments->groupBy('b.id', 'b.total_amount')->get();

        $this->payment_breakdown = ['Paid' => 0, 'Partial' => 0, 'Unpaid' => 0];
        foreach ($results as $row) {
            $total = (float) $row->total_amount;
            $paid = (float) $row->paid;

            if ($total <= 0) continue;

            if ($paid >= $total) {
                $this->payment_breakdown['Paid']++;
            } elseif ($paid > 0) {
                $this->payment_breakdown['Partial']++;
            } else {
                $this->payment_breakdown['Unpaid']++;
            }
        }

        $this->dispatch('update-payment-chart', 
            paid: $this->payment_breakdown['Paid'], 
            partial: $this->payment_breakdown['Partial'], 
            unpaid: $this->payment_breakdown['Unpaid']
        );
    }

    // Livewire automatically calls this when the dropdown changes
    public function updatedChartPeriod(string $value)
    {
        $this->fetchChartData($value);
        $this->loadPaymentBreakdown(); // Refresh the pie chart too
    }

    // A standard public method that Alpine CAN call directly to load the initial chart
    public function fetchChartData(string $value)
    {
        $labels = [];
        $data = [];

        if ($value === 'This Week') {
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $labels[] = $date->format('D');
                $data[] = DB::table('bookings')
                    ->whereDate('event_date', $date->toDateString())
                    ->whereNotIn('status', ['Cancelled', 'Deleted'])
                    ->count();
            }
        } elseif ($value === 'This Month') {
            $labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'];
            $data = [0, 0, 0, 0, 0];

            $bookings = DB::table('bookings')
                ->select(DB::raw('DAY(event_date) as day'), DB::raw('COUNT(*) as count'))
                ->whereMonth('event_date', Carbon::now()->month)
                ->whereYear('event_date', Carbon::now()->year)
                ->whereNotIn('status', ['Cancelled', 'Deleted'])
                ->groupBy('day')
                ->get();

            foreach ($bookings as $b) {
                $weekIndex = floor(($b->day - 1) / 7);
                if ($weekIndex > 4) $weekIndex = 4;
                $data[$weekIndex] += $b->count;
            }
        } elseif ($value === 'This Year') {
            $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $data = array_fill(0, 12, 0);

            $bookings = DB::table('bookings')
                ->select(DB::raw('MONTH(event_date) as m'), DB::raw('COUNT(*) as count'))
                ->whereYear('event_date', Carbon::now()->year)
                ->whereNotIn('status', ['Cancelled', 'Deleted'])
                ->groupBy('m')
                ->get();

            foreach ($bookings as $b) {
                $data[$b->m - 1] = $b->count;
            }
        }

        // Update properties for initial load/Blade refresh
        $this->chartLabels = $labels;
        $this->chartData = $data;

        // Dispatch an event to Alpine/Chart.js to redraw the chart
        $this->dispatch('update-booking-chart', labels: $labels, data: $data);
    }

    public function render()
    {
        return view('livewire.admin.admin-dashboard');
    }
}
