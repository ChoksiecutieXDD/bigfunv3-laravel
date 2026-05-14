<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Booking;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

#[Layout('components.layouts.admin')]
class FinancialReports extends Component
{
    public string $period = 'this_month';
    public string $customStart;
    public string $customEnd;

    public function mount()
    {
        $this->customStart = now()->startOfMonth()->toDateString();
        $this->customEnd = now()->endOfMonth()->toDateString();
    }

    public function updatedPeriod()
    {
        if ($this->period !== 'custom') {
            $this->updateCharts();
        }
    }

    public function applyCustomDate()
    {
        $this->updateCharts();
    }

    public function updateCharts()
    {
        $data = $this->getReportData();

        $this->dispatch(
            'update-charts',
            chartLabels: $data['chartLabels'],
            chartData: $data['chartData'],
            catLabels: $data['catLabels'],
            catData: $data['catData']
        );
    }

    public function exportCsv()
    {
        $data = $this->getReportData();
        $fileName = 'Financial_Report_' . date('Ymd') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['FINANCIAL REPORT', $data['periodLabel']]);
            fputcsv($file, ['Date Range', "{$data['startDate']} to {$data['endDate']}"]);
            fputcsv($file, []);

            fputcsv($file, ['SUMMARY METRICS']);
            fputcsv($file, ['Gross Revenue', number_format($data['summary']['revenue'], 2)]);
            fputcsv($file, ['Expenses (Est)', number_format($data['summary']['expenses'], 2)]);
            fputcsv($file, ['Net Profit', number_format($data['summary']['profit'], 2)]);
            fputcsv($file, ['Unpaid Balances', number_format($data['summary']['unpaid'], 2)]);
            fputcsv($file, []);

            fputcsv($file, ['UNPAID CUSTOMERS']);
            fputcsv($file, ['ID', 'Date', 'Customer', 'Total', 'Paid', 'Due']);
            foreach ($data['unpaidList'] as $u) {
                fputcsv($file, [$u['id'], $u['event_date'], $u['name'], $u['total_amount'], $u['paid_amount'], $u['balance']]);
            }
            fputcsv($file, []);

            fputcsv($file, ['FULLY PAID CUSTOMERS']);
            fputcsv($file, ['ID', 'Date', 'Customer', 'Total']);
            foreach ($data['paidList'] as $p) {
                fputcsv($file, [$p['id'], $p['event_date'], $p['name'], $p['total_amount']]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function getReportData()
    {
        $currentDate = now();

        switch ($this->period) {
            case 'custom':
                $startDate = Carbon::parse($this->customStart);
                $endDate = Carbon::parse($this->customEnd);
                $periodLabel = "Custom: " . $startDate->format('M d') . " - " . $endDate->format('M d');
                $diffDays = $startDate->diffInDays($endDate);
                $prevStartDate = $startDate->copy()->subDays($diffDays + 1);
                break;
            case 'last_month':
                $startDate = now()->subMonth()->startOfMonth();
                $endDate = now()->subMonth()->endOfMonth();
                $prevStartDate = now()->subMonths(2)->startOfMonth();
                $periodLabel = "Last Month";
                break;
            case 'last_3_months':
                $startDate = now()->subMonths(3);
                $endDate = $currentDate;
                $prevStartDate = now()->subMonths(6);
                $periodLabel = "Last 3 Months";
                break;
            case 'this_year':
                $startDate = now()->startOfYear();
                $endDate = $currentDate;
                $prevStartDate = now()->subYear()->startOfYear();
                $periodLabel = "This Year (YTD)";
                break;
            case 'this_month':
            default:
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                $prevStartDate = now()->subMonth()->startOfMonth();
                $periodLabel = "This Month";
                break;
        }

        $startDateString = $startDate->toDateString();
        $endDateString = $endDate->toDateString();
        $prevStartDateString = $prevStartDate->toDateString();

        $revenueCurrent = Booking::whereBetween('event_date', [$startDateString, $endDateString])
            ->whereNotIn('status', ['Cancelled', 'Deleted'])->sum('total_amount');

        $revenueLast = Booking::whereBetween('event_date', [$prevStartDateString, $startDateString])
            ->whereNotIn('status', ['Cancelled', 'Deleted'])->sum('total_amount');

        $growth = $revenueLast > 0 ? (($revenueCurrent - $revenueLast) / $revenueLast) * 100 : ($revenueCurrent > 0 ? 100 : 0);
        $expenses = $revenueCurrent * 0.30;
        $profit = $revenueCurrent - $expenses;

        $bookings = Booking::withSum('payments', 'amount')
            ->whereBetween('event_date', [$startDateString, $endDateString])
            ->whereNotIn('status', ['Cancelled', 'Deleted'])
            ->orderBy('event_date', 'asc')
            ->get()
            ->map(function ($b) {
                $paid = $b->payments_sum_amount ?? 0;
                return [
                    'id' => $b->id,
                    'event_date' => Carbon::parse($b->event_date)->format('M d, Y'),
                    'name' => trim($b->customer_first_name . ' ' . $b->customer_last_name),
                    'event_type' => $b->event_type,
                    'payment_type' => $b->payment_type,
                    'card_network' => $b->card_network,
                    'total_amount' => (float) $b->total_amount,
                    'paid_amount' => (float) $paid,
                    'balance' => (float) ($b->total_amount - $paid),
                ];
            });

        $unpaidList = $bookings->where('balance', '>', 0.01)->values()->toArray();
        $paidList = $bookings->where('balance', '<=', 0.01)->values()->toArray();
        $transactionList = $bookings->toArray();
        $totalUnpaidAmount = collect($unpaidList)->sum('balance');

        $chartLabels = [];
        $chartData = [];
        $daysCount = $startDate->diffInDays($endDate);

        if ($daysCount <= 60) {
            $periodObj = CarbonPeriod::create($startDate, $endDate);
            $dataMap = [];
            foreach ($periodObj as $date) {
                $dataMap[$date->toDateString()] = 0;
            }
            $daily = Booking::whereBetween('event_date', [$startDateString, $endDateString])
                ->whereNotIn('status', ['Cancelled', 'Deleted'])
                ->selectRaw('DATE(event_date) as date_val, SUM(total_amount) as total')
                ->groupBy('date_val')->pluck('total', 'date_val');

            foreach ($dataMap as $date => $val) {
                $chartLabels[] = Carbon::parse($date)->format('M d');
                $chartData[] = (float) ($daily[$date] ?? 0);
            }
        } else {
            $periodObj = CarbonPeriod::create($startDate->startOfMonth(), '1 month', $endDate->startOfMonth());
            $dataMap = [];
            foreach ($periodObj as $date) {
                $dataMap[$date->format('Y-m')] = 0;
            }
            $monthly = Booking::whereBetween('event_date', [$startDateString, $endDateString])
                ->whereNotIn('status', ['Cancelled', 'Deleted'])
                ->selectRaw('DATE_FORMAT(event_date, "%Y-%m") as month_key, SUM(total_amount) as total')
                ->groupBy('month_key')->pluck('total', 'month_key');

            foreach ($dataMap as $key => $val) {
                $chartLabels[] = Carbon::createFromFormat('Y-m', $key)->format('M Y');
                $chartData[] = (float) ($monthly[$key] ?? 0);
            }
        }

        $catQuery = Booking::whereBetween('event_date', [$startDateString, $endDateString])
            ->whereNotIn('status', ['Cancelled', 'Deleted'])
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $catLabels = $catQuery->pluck('event_type')->map(fn($t) => ucfirst($t))->toArray();
        $catData = $catQuery->pluck('count')->toArray();

        if (empty($catData)) {
            $catLabels = ['No Data'];
            $catData = [1];
        }

        return [
            'periodLabel' => $periodLabel,
            'startDate' => $startDateString,
            'endDate' => $endDateString,
            'summary' => [
                'revenue' => $revenueCurrent,
                'expenses' => $expenses,
                'profit' => $profit,
                'growth' => round($growth, 1),
                'unpaid' => $totalUnpaidAmount,
            ],
            'unpaidList' => $unpaidList,
            'paidList' => $paidList,
            'transactionList' => $transactionList,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
            'catLabels' => $catLabels,
            'catData' => $catData,
        ];
    }

    public function render()
    {
        return view('livewire.admin.financial-reports', $this->getReportData());
    }
}
