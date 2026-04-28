<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

#[Layout('components.layouts.admin')]
#[Title('Calendar View | BigFun Admin')]
class Calendar extends Component
{
    // 1. FIXED: Remove strict types (int, string, bool) to prevent Livewire crashes
    public int $currentMonth;
    public int $currentYear;
    public string $statusFilter = 'All';
    public bool $showOnlyBooked = false;
    public bool $showWholeYear = false;

    public float $global_outstanding_balance = 0;

    public function mount()
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
    }

    public function nextMonth()
    {
        if ($this->currentMonth == 12) {
            $this->currentMonth = 1;
            $this->currentYear++;
        } else {
            $this->currentMonth++;
        }
    }

    public function previousMonth()
    {
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
        $searchMonth = (int) $this->currentMonth;
        $searchYear = (int) $this->currentYear;

        // Fetch bookings with items and pre-calculated payments sum
        $query = Booking::with(['items' => function($q) {
                $q->select('id', 'booking_id', 'item_name');
            }])
            ->withSum('payments as real_paid', 'amount')
            ->select('id', 'event_date', 'start_time', 'end_time', 'customer_first_name', 'customer_last_name', 'status', 'total_amount', 'payment_type', 'lead_operator', 'lead_deliverer', 'address_line_1', 'duration', 'duration_cost', 'terms_agreed', 'installation_plan', 'booked_by')
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
            'mCount' => 0,
            'mRev' => 0,
            'mPaid' => 0,
            'mBal' => 0,
            'satCount' => 0,
            'satBookings' => 0,
            'satRev' => 0,
            'ytdCount' => 0,
            'ytdRev' => 0
        ];

        $processedBookings = $rawBookings->map(function ($booking) use (&$stats) {
            $realPaid = (float) ($booking->real_paid ?? 0);
            $totalAmount = (float) $booking->total_amount;
            $balanceDue = $totalAmount - $realPaid;

            $services = $booking->items->pluck('item_name')->unique()->implode(', ');

            // Determine display color/label (Standardized Logic)
            $paymentColor = 'red';
            $paymentStatusLabel = 'No Deposit';

            if ($totalAmount > 0) {
                $percent = ($realPaid / $totalAmount) * 100;
                if ($percent >= 100) $paymentColor = 'green';
                elseif ($percent >= 80) $paymentColor = 'purple';
                elseif ($percent >= 40) $paymentColor = 'blue';
                elseif ($percent > 0) $paymentColor = 'orange';

                if ($percent >= 100) $paymentStatusLabel = 'Paid';
                elseif ($percent >= 80) $paymentStatusLabel = 'Finalizing (>80%)';
                elseif ($percent >= 40) $paymentStatusLabel = 'Partial (>40%)';
                elseif ($percent > 0) $paymentStatusLabel = 'Deposit Paid';
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

            // Stats Incrementing (Excluding Cancelled/Draft)
            if (!in_array($booking->status, ['Cancelled', 'Draft'])) {
                $stats['mCount']++;
                $stats['mRev'] += $totalAmount;
                $stats['mPaid'] += $realPaid;
                
                if (Carbon::parse($booking->event_date)->isSaturday()) {
                    $stats['satBookings']++;
                    $stats['satRev'] += $totalAmount;
                }
            }

            // View Data Wrapper to match template expectations
            $booking->viewData = [
                'color' => $paymentColor,
                'label' => $paymentStatusLabel,
                'pay_icon' => (stripos($booking->payment_type ?? '', 'Card') !== false) ? 'credit_card' : 'account_balance',
                'pay_label' => (stripos($booking->payment_type ?? '', 'Card') !== false) ? 'Credit Card' : 'EFT',
                'op_name' => $booking->lead_operator ? explode(' ', $booking->lead_operator)[0] : 'Team',
                'del_name' => $booking->lead_deliverer ? explode(' ', $booking->lead_deliverer)[0] : 'Team',
                'balance_due' => $balanceDue
            ];
            
            $booking->services_booked = $services;

            return $booking;
        });

        $stats['mBal'] = $stats['mRev'] - $stats['mPaid'];

        // Saturday Baseline
        $stats['satCount'] = collect(range(1, Carbon::create($searchYear, $searchMonth)->daysInMonth))->filter(fn($d) => Carbon::create($searchYear, $searchMonth, $d)->isSaturday())->count();

        // YTD Calculation - Quick select instead of full model
        $ytd = DB::table('bookings')
            ->whereYear('event_date', $searchYear)
            ->whereNotIn('status', ['Cancelled', 'Draft'])
            ->selectRaw('COUNT(*) as c, SUM(total_amount) as r')
            ->first();
        $stats['ytdCount'] = $ytd->c ?? 0;
        $stats['ytdRev'] = $ytd->r ?? 0;

        // Global Financials - Caching for 10 minutes to save DB load
        $this->global_outstanding_balance = \Illuminate\Support\Facades\Cache::remember('global_outstanding_balance', 600, function() {
            $globalRev = DB::table('bookings')->whereNotIn('status', ['Cancelled', 'Draft'])->sum('total_amount');
            $globalPaid = DB::table('booking_payments')
                ->whereIn('booking_id', function($q) {
                    $q->select('id')->from('bookings')->whereNotIn('status', ['Cancelled', 'Draft']);
                })->sum('amount');
            return $globalRev - $globalPaid;
        });

        // Grouping Logic
        $grouped = $processedBookings->groupBy('event_date');
        $calendarDataDays = [];

        if (!$this->showWholeYear && !$this->showOnlyBooked && $this->statusFilter === 'All') {
            $daysInMonth = Carbon::create($searchYear, $searchMonth)->daysInMonth;
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dateStr = Carbon::create($searchYear, $searchMonth, $day)->toDateString();
                $calendarDataDays[$dateStr] = [
                    'date' => Carbon::parse($dateStr)->format('l, F jS'),
                    'bookings' => $grouped->get($dateStr, collect())
                ];
            }
        } else {
            foreach ($grouped as $date => $group) {
                $calendarDataDays[$date] = [
                    'date' => Carbon::parse($date)->format('l, F jS'),
                    'bookings' => $group
                ];
            }
            ksort($calendarDataDays);
        }

        return view('livewire.admin.calendar', [
            'calendarData' => [
                'days' => $calendarDataDays,
                'stats' => $stats
            ],
            'months' => [1=>'January', 2=>'February', 3=>'March', 4=>'April', 5=>'May', 6=>'June', 7=>'July', 8=>'August', 9=>'September', 10=>'October', 11=>'November', 12=>'December'],
            'yearRange' => range(now()->year - 2, now()->year + 5)
        ]);
    }
}
