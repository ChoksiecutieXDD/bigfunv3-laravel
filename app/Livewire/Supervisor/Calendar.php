<?php

namespace App\Livewire\Supervisor;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

#[Layout('components.layouts.supervisor')]
class Calendar extends Component
{
    // 1. FIXED: Remove strict types (int, string, bool) to prevent Livewire crashes
    public $currentMonth;
    public $currentYear;
    public $statusFilter = 'All';
    public $showOnlyBooked = false;
    public $showWholeYear = false;

    public function mount()
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
    }

    public function nextMonth()
    {
        // 2. FIXED: Use == instead of === so string "12" from dropdowns still works
        if ($this->currentMonth == 12) {
            $this->currentMonth = 1;
            $this->currentYear++;
        } else {
            $this->currentMonth++;
        }
    }

    public function previousMonth()
    {
        // 2. FIXED: Use == instead of ===
        if ($this->currentMonth == 1) {
            $this->currentMonth = 12;
            $this->currentYear--;
        } else {
            $this->currentMonth--;
        }
    }

    public function goToToday()
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
        $this->statusFilter = 'All';
        $this->showOnlyBooked = false;
        $this->showWholeYear = false;
    }

    public function render()
    {
        // 3. FIXED: Cast to integer right before querying the database
        $searchMonth = (int) $this->currentMonth;
        $searchYear = (int) $this->currentYear;

        $query = Booking::with(['items', 'payments'])
            ->whereYear('event_date', $searchYear)
            ->orderBy('event_date', 'asc')
            ->orderBy('start_time', 'asc');

        if (!$this->showWholeYear) {
            $query->whereMonth('event_date', $searchMonth);
        }

        if ($this->statusFilter !== 'All') {
            if ($this->statusFilter === 'Booked') {
                $query->whereIn('status', ['Booked', 'Confirmed']);
            } else {
                $query->where('status', $this->statusFilter);
            }
        }

        $rawBookings = $query->get();

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

        $processedBookings = $rawBookings->map(function ($booking) use (&$stats) {
            $realPaid = $booking->payments->sum('amount');
            $totalAmount = (float) $booking->total_amount;
            $balanceDue = $totalAmount - $realPaid;

            $services = $booking->items->pluck('item_name')->unique()->implode(', ');

            if (!in_array($booking->status, ['Cancelled', 'Draft'])) {
                $stats['monthBookings']++;
                $stats['monthRevenue'] += $totalAmount;
                $stats['monthCollected'] += $realPaid;

                if (Carbon::parse($booking->event_date)->isSaturday()) {
                    $stats['saturdayBookings']++;
                    $stats['saturdayRevenue'] += $totalAmount;
                }
            }

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

            return (object) [
                'id' => $booking->id,
                'event_date' => Carbon::parse($booking->event_date)->toDateString(),
                'start_time' => $booking->start_time,
                'customer_first_name' => $booking->customer_first_name,
                'customer_last_name' => $booking->customer_last_name,
                'suburb' => $booking->suburb,
                'status' => $booking->status,
                'total_amount' => $totalAmount,
                'real_paid' => $realPaid,
                'services_booked' => $services,
                'lead_operator' => $booking->lead_operator,
                'lead_deliverer' => $booking->lead_deliverer,
                'color_code' => $paymentColor,
                'status_label' => $paymentStatusLabel,
                'terms_agreed' => $booking->terms_agreed,
                'payment_type' => $booking->payment_type,
                'booked_by' => $booking->booked_by,
                'end_time' => $booking->end_time,
                'duration' => $booking->duration,
                'custom_duration_text' => $booking->custom_duration_text,
            ];
        });

        $stats['monthBalance'] = $stats['monthRevenue'] - $stats['monthCollected'];

        $stats['saturdayCount'] = Carbon::create($searchYear, $searchMonth)->daysInMonth;
        $stats['saturdayCount'] = collect(range(1, $stats['saturdayCount']))->filter(function ($day) use ($searchYear, $searchMonth) {
            return Carbon::create($searchYear, $searchMonth, $day)->isSaturday();
        })->count();

        $ytdData = Booking::whereYear('event_date', $searchYear)
            ->whereNotIn('status', ['Cancelled', 'Draft'])
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as revenue')
            ->first();

        $stats['ytdBookings'] = $ytdData->count ?? 0;
        $stats['ytdRevenue'] = $ytdData->revenue ?? 0;

        $globalRevenue = Booking::whereNotIn('status', ['Cancelled', 'Draft'])->sum('total_amount');
        $globalCollected = DB::table('booking_payments')
            ->whereIn('booking_id', function ($q) {
                $q->select('id')->from('bookings')->whereNotIn('status', ['Cancelled', 'Draft']);
            })->sum('amount');

        $globalOutstandingBalance = $globalRevenue - $globalCollected;

        $upcomingEvents3Days = Booking::with('payments')->where('event_date', '>=', now()->toDateString())
            ->where('event_date', '<=', now()->addDays(3)->toDateString())
            ->whereNotIn('status', ['Cancelled', 'Draft'])
            ->orderBy('event_date', 'asc')
            ->get();

        $upcomingEvents7Days = Booking::with('payments')->where('event_date', '>=', now()->addDays(4)->toDateString())
            ->where('event_date', '<=', now()->addDays(7)->toDateString())
            ->whereNotIn('status', ['Cancelled', 'Draft'])
            ->orderBy('event_date', 'asc')
            ->get();

        $pendingCompletionAlerts = Booking::with('payments')->whereIn('status', ['Booked', 'Confirmed'])
            ->where('event_date', '<=', now()->subDays(7)->toDateString())
            ->orderBy('event_date', 'desc')
            ->get();

        $groupedBookings = $processedBookings->groupBy('event_date');
        $calendarDays = [];

        $groupedBookings = $processedBookings->groupBy('event_date');
        $calendarDays = [];

        // ONLY show the full month grid (with empty days) if:
        // 1. We are in Month View
        // 2. 'Booked Only' is OFF
        // 3. 'All Status' is selected
        if (!$this->showWholeYear && !$this->showOnlyBooked && $this->statusFilter === 'All') {
            $daysInMonth = Carbon::create($searchYear, $searchMonth)->daysInMonth;
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dateString = Carbon::create($searchYear, $searchMonth, $day)->toDateString();
                $calendarDays[$dateString] = $groupedBookings->get($dateString, collect());
            }
        } else {
            // Filter is active OR Booked Only OR Whole Year: Only show days with results
            $calendarDays = $groupedBookings->toArray();
            ksort($calendarDays);
        }

        $rawDebtAlerts = Booking::with('payments')
            ->whereNotIn('status', ['Cancelled', 'Draft'])
            ->where('event_date', '<', now()->toDateString())
            ->get();
            
        $debtAlerts = $rawDebtAlerts->map(function ($b) {
            $b->balance = $b->total_amount - $b->payments->sum('amount');
            return $b;
        })->filter(function ($b) {
            return $b->balance > 0;
        })->sortByDesc('event_date');
        
        $stats['urgentAlertsCount'] = $debtAlerts->count() + $pendingCompletionAlerts->count() + $upcomingEvents3Days->count();

        return view('livewire.supervisor.calendar', [
            'calendarDays' => $calendarDays,
            'stats' => $stats,
            'globalOutstandingBalance' => $globalOutstandingBalance,
            'upcomingEvents3Days' => $upcomingEvents3Days,
            'upcomingEvents7Days' => $upcomingEvents7Days,
            'pendingCompletionAlerts' => $pendingCompletionAlerts,
            'debtAlerts' => $debtAlerts,
            'yearRange' => range(now()->year - 2, now()->year + 5),
        ]);
    }
}
