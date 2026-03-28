<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

#[Layout('components.layouts.admin')]
#[Title('Calendar View | BigFun Admin')]
class Calendar extends Component
{
    // Calendar State
    public $currentMonth;
    public $currentYear;

    // Filter State
    public $statusFilter = 'All';

    // Global Financials
    public $global_outstanding_balance = 0;

    public function mount()
    {
        // Initialize to current month/year
        $this->currentMonth = Carbon::now()->month;
        $this->currentYear = Carbon::now()->year;

        $this->calculateGlobalFinancials();
    }

    public function calculateGlobalFinancials()
    {
        // Total All-Time Revenue (Excluding Cancelled/Drafts)
        $total_revenue = DB::table('bookings')
            ->whereNotIn('status', ['Cancelled', 'Draft'])
            ->sum('total_amount') ?? 0;

        // Total All-Time Payments Collected
        $total_collected = DB::table('booking_payments')
            ->whereIn('booking_id', function ($query) {
                $query->select('id')->from('bookings')->whereNotIn('status', ['Cancelled', 'Draft']);
            })
            ->sum('amount') ?? 0;

        $this->global_outstanding_balance = $total_revenue - $total_collected;
    }

    // --- NAVIGATION METHODS ---
    public function previousMonth()
    {
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function nextMonth()
    {
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function goToToday()
    {
        $this->currentMonth = Carbon::now()->month;
        $this->currentYear = Carbon::now()->year;
    }

    // --- DATA FETCHING ---
    public function getCalendarDataProperty()
    {
        // Calculate days in the selected month
        $daysInMonth = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->daysInMonth;

        // Fetch all bookings for the selected month/year
        $query = DB::table('bookings as b')
            ->leftJoin('booking_items as bi', 'b.id', '=', 'bi.booking_id')
            ->leftJoin(DB::raw('(SELECT booking_id, SUM(amount) as total_paid FROM booking_payments GROUP BY booking_id) pay'), 'b.id', '=', 'pay.booking_id')
            ->select(
                'b.id',
                'b.event_date',
                'b.start_time',
                'b.end_time',
                'b.customer_first_name',
                'b.customer_last_name',
                'b.customer_email',
                'b.address_line_1',
                'b.suburb',
                'b.event_type',
                'b.total_amount',
                'b.deposit_required',
                'b.status',
                'b.lead_operator',
                'b.lead_deliverer',
                'b.installation_plan',
                'b.payment_type',
                'b.terms_agreed',
                'b.created_at',
                'b.booked_by',
                DB::raw("GROUP_CONCAT(DISTINCT bi.item_name SEPARATOR ', ') as services_booked"),
                DB::raw("COALESCE(pay.total_paid, 0) as real_paid")
            )
            ->whereMonth('b.event_date', $this->currentMonth)
            ->whereYear('b.event_date', $this->currentYear)
            // THE FIX: We explicitly group by ALL non-aggregated columns to satisfy MySQL Strict Mode
            ->groupBy(
                'b.id',
                'b.event_date',
                'b.start_time',
                'b.end_time',
                'b.customer_first_name',
                'b.customer_last_name',
                'b.customer_email',
                'b.address_line_1',
                'b.suburb',
                'b.event_type',
                'b.total_amount',
                'b.deposit_required',
                'b.status',
                'b.lead_operator',
                'b.lead_deliverer',
                'b.installation_plan',
                'b.payment_type',
                'b.terms_agreed',
                'b.created_at',
                'b.booked_by',
                'pay.total_paid'
            )
            ->orderBy('b.event_date', 'ASC');

        // Apply Status Filter if not "All"
        if ($this->statusFilter !== 'All') {
            $query->where('b.status', $this->statusFilter);
        }

        $bookings = $query->get();

        // Organize bookings by Day and calculate monthly financials
        $calendarData = [
            'days' => [],
            'stats' => [
                'mCount' => 0,
                'mRev' => 0,
                'mPaid' => 0,
                'mBal' => 0,
                'satCount' => 0,
                'satBookings' => 0,
                'satRev' => 0,
                'ytdCount' => 0,
                'ytdRev' => 0
            ]
        ];

        // Pre-fill all days of the month to ensure empty days render too
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $dateObj = Carbon::createFromDate($this->currentYear, $this->currentMonth, $i);
            $calendarData['days'][$i] = [
                'date' => $dateObj->format('D, M j'),
                'isSaturday' => $dateObj->isSaturday(),
                'bookings' => []
            ];

            if ($dateObj->isSaturday()) {
                $calendarData['stats']['satCount']++;
            }
        }

        // Map bookings to their specific day
        foreach ($bookings as $b) {
            $day = (int) Carbon::parse($b->event_date)->format('j');

            // Format data for the view
            $total_amount = (float)$b->total_amount;
            $real_paid = (float)$b->real_paid;
            $balance_due = $total_amount - $real_paid;
            $percent = $total_amount > 0 ? ($real_paid / $total_amount) * 100 : 0;

            // Determine Color and Status Label
            $color = 'red';
            $label = 'No Deposit';
            if ($b->status === 'Cancelled') {
                $color = 'gray';
                $label = 'Cancelled';
            } elseif ($b->status === 'Draft') {
                $color = 'orange';
                $label = 'Draft Mode';
            } elseif ($percent >= 100) {
                $color = 'green';
                $label = 'Paid';
            } elseif ($percent >= 80) {
                $color = 'purple';
                $label = 'Finalizing (>80%)';
            } elseif ($percent >= 40) {
                $color = 'blue';
                $label = 'Partial (>40%)';
            } elseif ($percent > 0) {
                $color = 'orange';
                $label = 'Deposit Paid';
            }

            // Icons
            $isCard = stripos($b->payment_type ?? '', 'Card') !== false || stripos($b->payment_type ?? '', 'credit') !== false;
            $pay_icon = $isCard ? 'credit_card' : 'account_balance';
            $pay_label = $isCard ? 'Credit Card' : 'EFT';

            $b->viewData = [
                'color' => $color,
                'label' => $label,
                'balance_due' => $balance_due,
                'pay_icon' => $pay_icon,
                'pay_label' => $pay_label,
                'op_name' => $b->lead_operator ? explode(' ', $b->lead_operator)[0] : 'Team',
                'del_name' => $b->lead_deliverer ? explode(' ', $b->lead_deliverer)[0] : 'Team',
            ];

            // Add to specific day
            $calendarData['days'][$day]['bookings'][] = $b;

            // Increment Monthly Stats (Excluding Cancelled/Draft)
            if ($b->status !== 'Cancelled' && $b->status !== 'Draft') {
                $calendarData['stats']['mCount']++;
                $calendarData['stats']['mRev'] += $total_amount;
                $calendarData['stats']['mPaid'] += $real_paid;
                $calendarData['stats']['mBal'] += $balance_due;

                if (Carbon::parse($b->event_date)->isSaturday()) {
                    $calendarData['stats']['satBookings']++;
                    $calendarData['stats']['satRev'] += $total_amount;
                }
            }
        }

        // Calculate YTD stats (All months for the current year)
        $ytdData = DB::table('bookings')
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as rev')
            ->whereYear('event_date', $this->currentYear)
            ->whereNotIn('status', ['Cancelled', 'Draft'])
            ->first();

        $calendarData['stats']['ytdCount'] = $ytdData->count ?? 0;
        $calendarData['stats']['ytdRev'] = $ytdData->rev ?? 0;

        return $calendarData;
    }

    public function render()
    {
        $yearRange = range(Carbon::now()->year - 2, Carbon::now()->year + 5);
        $months = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        ];
        return view('livewire.admin.calendar', [
            'calendarData' => $this->calendarData, // Uses the computed property
            'months' => $months,
            'yearRange' => $yearRange
        ]);
    }
}
