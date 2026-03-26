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
    }

    public function render()
    {
        // 3. FIXED: Cast to integer right before querying the database
        $searchMonth = (int) $this->currentMonth;
        $searchYear = (int) $this->currentYear;

        $query = Booking::with(['items', 'payments'])
            ->whereYear('event_date', $searchYear)
            ->whereMonth('event_date', $searchMonth)
            ->orderBy('event_date', 'asc')
            ->orderBy('start_time', 'asc');

        if ($this->statusFilter !== 'All') {
            $query->where('status', $this->statusFilter);
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
                'color_code' => $paymentColor,
                'status_label' => $paymentStatusLabel,
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

        $upcomingEvents = Booking::where('event_date', '>=', now()->toDateString())
            ->where('event_date', '<=', now()->addDays(7)->toDateString())
            ->whereNotIn('status', ['Cancelled', 'Draft'])
            ->orderBy('event_date', 'asc')
            ->get();

        $groupedBookings = $processedBookings->groupBy('event_date');
        $calendarDays = [];
        $daysInMonth = Carbon::create($searchYear, $searchMonth)->daysInMonth;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateString = Carbon::create($searchYear, $searchMonth, $day)->toDateString();
            $dayBookings = $groupedBookings->get($dateString, collect());

            if ($this->showOnlyBooked && $dayBookings->isEmpty()) {
                continue;
            }

            $calendarDays[$dateString] = $dayBookings;
        }

        return view('livewire.supervisor.calendar', [
            'calendarDays' => $calendarDays,
            'stats' => $stats,
            'globalOutstandingBalance' => $globalOutstandingBalance,
            'upcomingEvents' => $upcomingEvents,
            'urgentAlerts' => [],
            'yearRange' => range(now()->year - 2, now()->year + 5),
        ]);
    }
}
