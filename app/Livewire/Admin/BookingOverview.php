<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

#[Layout('components.layouts.overview')]
class BookingOverview extends Component
{
    public Booking $booking;

    // --- General Properties (Restored for View Compatibility) ---
    public string $newStatus;
    public string $newDate;

    // --- Email Properties ---
    public $emailType = 'invoice';
    public ?string $emailTo = null;
    public ?string $emailCc = null;
    public $emailBcc = 'hire.enquiries@bigfunqld.com.au';
    public ?string $emailSubject = null;
    public ?string $emailBody = null;
    public ?string $emailAttachment = null;
    public $emailFrom = 'bigfun.qld.au@gmail.com';
    public $isSentSuccessfully = false;

    // --- Calendar Modals Properties (Restored for View Compatibility) ---
    public int $calMonth;
    public int $calYear;
    public ?string $tempSelectedDate = null;

    // --- Detail Selection ---
    public ?BookingPayment $selectedPayment = null;
    public ?string $from_url = null;

    public function mount(int|string $id)
    {
        $this->from_url = request()->query('back');
        $this->booking = Booking::findOrFail($id);
        $this->newStatus = $this->booking->status;
        $this->newDate = Carbon::parse($this->booking->event_date)->format('Y-m-d');
        
        $this->calMonth = Carbon::parse($this->newDate)->month;
        $this->calYear = Carbon::parse($this->newDate)->year;
    }

    // --- Read-Only Method Stubs (Prevent 500s) ---
    public function updateStatus() { /* Read Only */ }
    public function executeStatusUpdate() { /* Read Only */ }
    public function toggleTerms() { /* Read Only */ }
    public function toggleAttractionCost() { /* Read Only */ }
    public function moveDate() { /* Read Only */ }
    public function deleteBooking() { /* Read Only */ }
    public function manualSync() { /* Read Only */ }
    public function calPrev() { /* Read Only */ }
    public function calNext() { /* Read Only */ }
    public function applySelectedDate() { /* Read Only */ }

    public function selectPayment(int|string $id)
    {
        $this->selectedPayment = BookingPayment::find($id);
        $this->dispatch('open-modal', 'paymentDetailsModal');
    }

    // --- Email Logic ---
    public function openEmailModal(string $type)
    {
        $this->emailType = $type;
        $this->emailTo = $this->booking->customer_email;
        $amountPaid = BookingPayment::where('booking_id', $this->booking->id)->sum('amount');
        $totalAmount = (float) $this->booking->total_amount;
        $balanceDue = max(0, $totalAmount - $amountPaid);

        $fullName = $this->booking->customer_first_name . ' ' . $this->booking->customer_last_name;
        $eventDate = Carbon::parse($this->booking->event_date)->format('d/m/Y');

        if ($type === 'receipt') {
            $this->emailSubject = "Payment Receipt - Booking #{$this->booking->id}";
            $this->emailBody = "$fullName\n\nThank you for your payment regarding your booking on $eventDate.\n\nTotal Paid: $" . number_format($amountPaid, 2) . "\nBalance Due: $" . number_format($balanceDue, 2) . "\n\nRegards\nBIG FUN";
            $this->emailAttachment = "BigFunReceipt-{$this->booking->id}.pdf";
        } elseif ($type === 'po') {
            $this->emailSubject = "Purchase Order Reference - Booking #{$this->booking->id}";
            $this->emailBody = "Hello,\n\nPlease find attached the Purchase Order Reference for your booking on $eventDate.\n\nTotal Cost: $" . number_format($totalAmount, 2) . "\n\nKind regards,\nBIG FUN";
            $this->emailAttachment = "BigFunPurchaseOrder-{$this->booking->id}.pdf";
        } elseif ($type === 'debt') {
            $this->emailSubject = "Outstanding Balance Reminder - Booking #{$this->booking->id}";
            $this->emailBody = "Hello,\n\nThis is a reminder regarding your outstanding balance of $" . number_format($balanceDue, 2) . " for your booking on $eventDate.\n\nRegards\nBIG FUN";
            $this->emailAttachment = "BigFunDebt-{$this->booking->id}.pdf";
        } else {
            $this->emailSubject = "Big Fun Invoice - Booking #{$this->booking->id}";
            $this->emailBody = "Hello,\n\nPlease find attached the invoice for your booking on $eventDate.\n\nTotal Amount: $" . number_format($totalAmount, 2) . "\nBalance Due: $" . number_format($balanceDue, 2) . "\n\nKind regards,\nBIG FUN";
            $this->emailAttachment = "BigFunInvoice-{$this->booking->id}.pdf";
        }

        $this->dispatch('open-modal', 'emailModal');
    }

    public function sendEmail()
    {
        DB::table('email_logs')->insert([
            'booking_id' => $this->booking->id,
            'type' => $this->emailType,
            'sent_to' => $this->emailTo,
            'sent_at' => now()
        ]);

        $this->dispatch('close-modal', 'emailModal');
        $this->dispatch('notify', title: 'Success', message: 'Email sent successfully!');
    }

    public function render()
    {
        $items = BookingItem::where('booking_id', $this->booking->id)
            ->leftJoin('products', function($join) {
                $join->on('booking_items.item_name', '=', 'products.name')
                     ->where('booking_items.is_custom', '=', 0);
            })
            ->selectRaw('booking_items.item_name, booking_items.is_custom, SUM(booking_items.qty) as total_qty, products.specification, COALESCE(NULLIF(booking_items.item_price, 0), products.price, 0) as unit_price, products.category')
            ->groupBy('booking_items.item_name', 'booking_items.is_custom', 'products.specification', 'booking_items.item_price', 'products.price', 'products.category')
            ->get();

        $payments = BookingPayment::where('booking_id', $this->booking->id)->orderBy('payment_date', 'asc')->get();
        $emailLogs = DB::table('email_logs')->where('booking_id', $this->booking->id)->orderBy('sent_at', 'desc')->get();

        $amountPaid = $payments->sum('amount');
        $totalAmount = (float) $this->booking->total_amount;
        $balanceDue = max(0, $totalAmount - $amountPaid);
        $depositReq = $this->booking->deposit_required > 0 ? $this->booking->deposit_required : ($totalAmount / 2);

        $isDebt = ($balanceDue > 0 && Carbon::parse($this->booking->event_date)->startOfDay()->lte(now()->startOfDay()));

        $extrasList = array_merge(json_decode($this->booking->general_extra ?? '[]', true) ?? [], json_decode($this->booking->specific_extra ?? '[]', true) ?? []);
        $calculatedExtrasTotal = empty($extrasList) ? ($this->booking->extra_logistics_cost ?? 0) : array_sum($extrasList);

        $isCard = in_array($this->booking->payment_type, ['credit_card', 'Card Holder']);
        $baseAmount = $totalAmount;
        $surcharge = 0;
        if ($isCard && $totalAmount > 0) {
            $baseAmount = $totalAmount / 1.029;
            $surcharge = $totalAmount - $baseAmount;
        }

        $deliveryCost = $this->booking->delivery_cost ?? $this->booking->delivery_fee ?? 0;
        $ridesCost = max(0, $baseAmount - $calculatedExtrasTotal - $deliveryCost);

        $statusColor = match ($this->booking->status) {
            'Completed' => 'bg-green-100 text-green-700 border-green-200',
            'Cancelled' => 'bg-red-100 text-red-700 border-red-200',
            'Hold'      => 'bg-yellow-100 text-yellow-700 border-yellow-200',
            'Draft'     => 'bg-orange-100 text-orange-700 border-orange-200',
            default     => 'bg-[#9D686E]/10 text-[#9D686E] border-[#9D686E]/20',
        };

        $activeCategories = ['General Logistics'];
        foreach ($items as $item) { if ($item->category) { $activeCategories[] = $item->category; } }
        $activeCategories = array_unique($activeCategories);

        $config = [
            'addons' => DB::table('category_addons')->orderBy('category_target')->get()->groupBy('category_target')->map(function ($g) use ($extrasList) {
                // Lowercase keys for case-insensitive lookup
                $extrasListLower = array_combine(
                    array_map(fn($k) => strtolower(trim($k)), array_keys($extrasList)),
                    array_values($extrasList)
                );
                
                return $g->map(function($v) use ($extrasListLower) {
                    $arr = (array)$v;
                    $label = strtolower(trim($arr['addon_label']));
                    $catLabel = strtolower(trim($arr['category_target'] . ': ' . $arr['addon_label']));
                    if (isset($extrasListLower[$label])) $arr['addon_price'] = (float)$extrasListLower[$label];
                    elseif (isset($extrasListLower[$catLabel])) $arr['addon_price'] = (float)$extrasListLower[$catLabel];
                    return $arr;
                })->toArray();
            })->toArray(),
            'questions' => DB::table('product_extras')->orderBy('category_target')->get()->groupBy('category_target')->map(function ($g) use ($extrasList) {
                return $g->map(function($v) use ($extrasList) {
                    $arr = (array)$v;
                    $qText = strtolower(trim($arr['question_text']));
                    foreach ($extrasList as $label => $price) {
                        if (str_contains(strtolower(trim($label)), $qText)) {
                            $arr['yes_price'] = (float)$price;
                            break;
                        }
                    }
                    return $arr;
                })->toArray();
            })->toArray(),
            'dropdowns' => []
        ];

        $rawDropdowns = DB::table('product_dropdowns')->orderBy('sort_order')->get();
        $rawOpts = DB::table('dropdown_options')->get()->groupBy('dropdown_id');
        foreach ($rawDropdowns as $dd) {
            $ddArray = (array)$dd;
            $opts = $rawOpts->get($dd->id) ?? collect([]);
            $ddArray['options'] = $opts->map(function ($o) use ($extrasList, $dd) {
                $arr = (array)$o;
                $optLabel = strtolower(trim($arr['option_label']));
                $ddLabel = strtolower(trim($dd->label));
                $targetLabel = strtolower(trim($dd->category_target));

                foreach ($extrasList as $label => $price) {
                    $lowLabel = strtolower(trim($label));
                    if (str_contains($lowLabel, $optLabel) && (str_contains($lowLabel, $ddLabel) || str_contains($lowLabel, $targetLabel))) {
                        $arr['option_price'] = (float)$price;
                        break;
                    }
                }
                return $arr;
            })->toArray();
            $config['dropdowns'][$dd->category_target][] = $ddArray;
        }

        $selectedExtras = json_decode($this->booking->extras_json ?? '[]', true) ?? [];

        $rawStartTime = $this->booking->start_time;
        $rawEndTime = $this->booking->end_time;
        $isFullDay = ($rawStartTime === '00:00:00' && ($rawEndTime === '23:59:59' || $rawEndTime === '23:59:00' || $rawEndTime === '23:30:00'));

        if (empty($rawStartTime) && empty($rawEndTime)) {
            $timeString = !empty($this->booking->duration) ? 'Duration Selected' : 'TBC';
        } elseif ($isFullDay && !empty($this->booking->duration) && !in_array($this->booking->duration, ['4 Hours', '7 Hours'])) {
            $timeString = 'Duration Selected';
        } else {
            $startTime = Carbon::parse($rawStartTime);
            $timeString = $startTime->format('g:i A');
            if (!empty($rawEndTime) && $rawEndTime != '00:00:00') {
                $timeString .= ' - ' . Carbon::parse($rawEndTime)->format('g:i A');
            }
        }

        $galleryFiles = collect([$this->booking->delivery_attachment, $this->booking->delivery_attachment_2, $this->booking->delivery_attachment_3, $this->booking->delivery_attachment_4, $this->booking->delivery_attachment_5])->filter()->toArray();

        // --- FILTER DUPLICATES ---
        $allAddonLabels = DB::table('category_addons')->select('addon_label', 'category_target')->get()->flatMap(function($a) {
            return [strtolower(trim($a->addon_label)), strtolower(trim($a->category_target . ': ' . $a->addon_label))];
        })->toArray();
        $allQuestionLabels = DB::table('product_extras')->pluck('question_text')->map(fn($l) => strtolower(trim($l)))->toArray();
        $allDropdownOptions = DB::table('dropdown_options as do')
            ->join('product_dropdowns as pd', 'do.dropdown_id', '=', 'pd.id')
            ->select('do.option_label', 'pd.label as dd_label')
            ->get()
            ->flatMap(function($o) {
                return [strtolower(trim($o->option_label)), strtolower(trim($o->dd_label . ' - ' . $o->option_label))];
            })->toArray();
            
        $allExtraLabels = array_unique(array_merge($allAddonLabels, $allQuestionLabels, $allDropdownOptions));

        $items = $items->reject(function($item) use ($allExtraLabels) {
            return in_array(strtolower(trim($item->item_name)), $allExtraLabels);
        });

        return view('livewire.admin.booking-overview', compact(
            'items', 'payments', 'emailLogs', 'amountPaid', 'totalAmount', 'balanceDue', 'depositReq', 'calculatedExtrasTotal', 'isDebt', 'deliveryCost', 'ridesCost', 'statusColor', 'timeString', 'galleryFiles', 'isCard', 'surcharge', 'baseAmount', 'activeCategories', 'config', 'selectedExtras'
        ));
    }
}
