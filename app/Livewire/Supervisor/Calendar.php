<?php

namespace App\Livewire\Supervisor;

use Livewire\Component;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Calendar extends Component
{
    // Public properties bound to the view (wire:model.live)
    public $currentMonth;
    public $currentYear;
    public $statusFilter = 'All';

    public function mount()
    {
        $this->currentMonth = (int) now()->month;
        $this->currentYear = (int) now()->year;
    }

    public function nextMonth()
    {
        if ($this->currentMonth === 12) {
            $this->currentMonth = 1;
            $this->currentYear++;
        } else {
            $this->currentMonth++;
        }
    }

    public function previousMonth()
    {
        if ($this->currentMonth === 1) {
            $this->currentMonth = 12;
            $this->currentYear--;
        } else {
            $this->currentMonth--;
        }
    }

    public function goToToday()
    {
        $this->currentMonth = (int) now()->month;
        $this->currentYear = (int) now()->year;
        $this->statusFilter = 'All';
    }

    public function render()
    {
        // 1. Fetch Bookings for the selected Month/Year using Eloquent
        $query = Booking::with(['items', 'payments'])
            ->whereYear('event_date', $this->currentYear)
            ->whereMonth('event_date', $this->currentMonth)
            ->orderBy('event_date', 'asc')
            ->orderBy('start_time', 'asc');

        if ($this->statusFilter !== 'All') {
            $query->where('status', $this->statusFilter);
        }

        $rawBookings = $query->get();

        // 2. Initialize Stats Variables
        $stats = [
            'monthBookings' => 0,
            'monthRevenue' => 0,
            'monthCollected' => 0,
            'monthBalance' => 0,
            'saturdayCount' => 0,
            'saturdayBookings' => 0,
            'saturdayRevenue' => 0,
            'ytdBookings' => 0,
            'ytdRevenue' => 0,
            'urgentAlertsCount' => 0,
        ];

        // 3. Process Bookings & Apply Visual Logic
        $processedBookings = $rawBookings->map(function ($booking) use (&$stats) {
            // Calculate actual paid from the payments relationship
            $realPaid = $booking->payments->sum('amount');
            $totalAmount = (float) $booking->total_amount;
            $balanceDue = $totalAmount - $realPaid;

            // Extract services into a comma-separated string
            $services = $booking->items->pluck('item_name')->unique()->implode(', ');

            // Monthly Stats Calculation (excluding drafts/cancelled)
            if (!in_array($booking->status, ['Cancelled', 'Draft'])) {
                $stats['monthBookings']++;
                $stats['monthRevenue'] += $totalAmount;
                $stats['monthCollected'] += $realPaid;

                // Check if it's a Saturday
                if (Carbon::parse($booking->event_date)->isSaturday()) {
                    $stats['saturdayBookings']++;
                    $stats['saturdayRevenue'] += $totalAmount;
                }
            }

            // Status Visual Logic
            $paymentColor = 'red';
            $paymentStatusLabel = 'No Deposit';

            if ($totalAmount > 0) {
                $percent = ($realPaid / $totalAmount) * 100;

                if ($percent >= 100) {
                    $paymentColor = 'green';
                    $paymentStatusLabel = 'Paid';
                } elseif ($percent >= 80) {
                    $paymentColor = 'purple';
                    $paymentStatusLabel = 'Finalizing (>80%)';
                } elseif ($percent >= 40) {
                    $paymentColor = 'blue';
                    $paymentStatusLabel = 'Partial (>40%)';
                } elseif ($percent > 0) {
                    $paymentColor = 'orange';
                    $paymentStatusLabel = 'Deposit Paid';
                }
            } else {
                $paymentColor = 'gray';
                $paymentStatusLabel = 'N/A';
            }

            if ($booking->status === 'Cancelled') {
                $paymentColor = 'gray';
                $paymentStatusLabel = 'Cancelled';
            } elseif ($booking->status === 'Draft') {
                $paymentColor = 'orange';
                $paymentStatusLabel = 'Draft Mode';
            }

            // Map standard properties plus our newly calculated ones
            return (object) [
                'id' => $booking->id,
                'event_date' => $booking->event_date,
                'start_time' => $booking->start_time,
                'customer_first_name' => $booking->customer_first_name,
                'customer_last_name' => $booking->customer_last_name,
                'suburb' => $booking->suburb,
                'status' => $booking->status,
                'total_amount' => $totalAmount,
                'real_paid' => $realPaid,
                'services_booked' => $services,
                'lead_operator' => $booking->lead_operator,
                'color_code' => $paymentColor,
                'status_label' => $paymentStatusLabel,
            ];
        });

        // 4. Calculate Final Aggregates
        $stats['monthBalance'] = $stats['monthRevenue'] - $stats['monthCollected'];

        // Count actual Saturdays in the given month (for the UI label)
        $stats['saturdayCount'] = Carbon::create($this->currentYear, $this->currentMonth)->daysInMonth;
        $stats['saturdayCount'] = collect(range(1, $stats['saturdayCount']))->filter(function ($day) {
            return Carbon::create($this->currentYear, $this->currentMonth, $day)->isSaturday();
        })->count();

        // YTD Query (Optimized aggregate)
        $ytdData = Booking::whereYear('event_date', $this->currentYear)
            ->whereNotIn('status', ['Cancelled', 'Draft'])
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as revenue')
            ->first();

        $stats['ytdBookings'] = $ytdData->count ?? 0;
        $stats['ytdRevenue'] = $ytdData->revenue ?? 0;

        // Global Outstanding Balance (Optimized)
        $globalRevenue = Booking::whereNotIn('status', ['Cancelled', 'Draft'])->sum('total_amount');
        $globalCollected = DB::table('booking_payments')
            ->whereIn('booking_id', function ($q) {
                $q->select('id')->from('bookings')->whereNotIn('status', ['Cancelled', 'Draft']);
            })->sum('amount');

        $globalOutstandingBalance = $globalRevenue - $globalCollected;

        // 5. Sidebar Reminders Data
        $upcomingEvents = Booking::where('event_date', '>=', now()->toDateString())
            ->where('event_date', '<=', now()->addDays(7)->toDateString())
            ->whereNotIn('status', ['Cancelled', 'Draft'])
            ->orderBy('event_date', 'asc')
            ->get();

        // 6. Group by Date for the View
        $groupedBookings = $processedBookings->groupBy('event_date');

        return view('livewire.supervisor.calendar', [
            'groupedBookings' => $groupedBookings,
            'stats' => $stats,
            'globalOutstandingBalance' => $globalOutstandingBalance,
            'upcomingEvents' => $upcomingEvents,
            'urgentAlerts' => [], // You can add your urgent alerts logic here
            'yearRange' => range(now()->year - 2, now()->year + 5),
        ]);
    }
}
