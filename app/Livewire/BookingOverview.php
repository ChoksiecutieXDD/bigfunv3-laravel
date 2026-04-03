<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

#[Layout('components.layouts.overview')]
#[Title('Booking Overview')]
class BookingOverview extends Component
{
    public Booking $booking;

    // --- Email Properties ---
    public $emailType = 'invoice';
    public $emailTo;
    public $emailCc;
    public $emailBcc = 'hire.enquiries@bigfunqld.com.au';
    public $emailSubject;
    public $emailBody;
    public $emailAttachment;

    public $from_url;

    public function mount($id)
    {
        $this->from_url = request()->query('back');
        $this->booking = Booking::findOrFail($id);
    }

    // --- Email Logic (Read-Only allows emailing) ---
    public function openEmailModal($type)
    {
        $this->emailType = $type;
        $this->emailTo = $this->booking->customer_email;
        $amountPaid = BookingPayment::where('booking_id', $this->booking->id)->sum('amount');
        $totalAmount = (float) $this->booking->total_amount;
        $balanceDue = max(0, $totalAmount - $amountPaid);

        $fName = $this->booking->customer_first_name;
        $fullName = $fName . ' ' . $this->booking->customer_last_name;
        $eventDate = Carbon::parse($this->booking->event_date)->format('d/m/Y');

        if ($type === 'receipt') {
            $this->emailSubject = "Payment Receipt - Booking #{$this->booking->id}";
            $this->emailBody = "$fullName\n\nThank you for your payment for your booking on $eventDate.\n\nInvoice Amount: $" . number_format($totalAmount, 2) . "\nAmount Paid: $" . number_format($amountPaid, 2) . "\nAmount Owing: $" . number_format($balanceDue, 2) . "\n\nRegards\nBIG FUN";
            $this->emailAttachment = "BigFunReceipt-{$this->booking->id}.pdf";
        } elseif ($type === 'po') {
            $this->emailSubject = "Purchase Order Reference - Booking #{$this->booking->id}";
            $this->emailBody = "Hello $fName,\n\nPlease find attached the Purchase Order Reference / Quotation for your internal approval process.\n\nTotal Proposed Cost: $" . number_format($totalAmount, 2) . "\n\nKind regards,\nBIG FUN";
            $this->emailAttachment = "BigFunPurchaseOrder-{$this->booking->id}.pdf";
        } else {
            $this->emailSubject = "Big Fun Invoice - Booking #{$this->booking->id}";
            $this->emailBody = "Hello,\n\nPlease find attached the paperwork for your booking on $eventDate. Kindly review the document to ensure all contact and delivery details are correct.\n\nTotal Amount: $" . number_format($totalAmount, 2) . "\nBalance Due: $" . number_format($balanceDue, 2) . "\n\nKind regards,\nBIG FUN";
            $this->emailAttachment = "BigFunInvoice-{$this->booking->id}.pdf";
        }

        $this->dispatch('open-modal', 'emailModal');
    }

    public function toggleAttractionCost()
    {
        $this->booking->include_attraction_cost = !$this->booking->include_attraction_cost;
        $this->booking->save();
        $this->dispatch('notify', title: 'Success', message: 'Attraction costing display updated.');
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
            ->selectRaw('booking_items.item_name, booking_items.is_custom, SUM(booking_items.qty) as total_qty, products.specification, products.price as unit_price, products.category')
            ->groupBy('booking_items.item_name', 'booking_items.is_custom', 'products.specification', 'products.price', 'products.category')
            ->get();

        $payments = BookingPayment::where('booking_id', $this->booking->id)->orderBy('payment_date', 'asc')->get();
        $emailLogs = DB::table('email_logs')->where('booking_id', $this->booking->id)->orderBy('sent_at', 'desc')->get();

        $amountPaid = $payments->sum('amount');
        $totalAmount = (float) $this->booking->total_amount;
        $balanceDue = max(0, $totalAmount - $amountPaid);

        $extrasList = array_merge(
            json_decode($this->booking->general_extra ?? '[]', true) ?? [],
            json_decode($this->booking->specific_extra ?? '[]', true) ?? []
        );
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
        foreach ($items as $item) {
            if ($item->category) {
                $activeCategories[] = $item->category;
            }
        }
        $activeCategories = array_unique($activeCategories);

        // Fetch configs for Extra Configurations display matching new-booking/edit-booking logic
        $config = [
            'addons' => DB::table('category_addons')->orderBy('category_target')->get()->groupBy('category_target')->map(function($g) { return $g->toArray(); })->toArray(),
            'questions' => DB::table('product_extras')->orderBy('category_target')->get()->groupBy('category_target')->map(function($g) { return $g->toArray(); })->toArray(),
            'dropdowns' => []
        ];

        $rawDropdowns = DB::table('product_dropdowns')->orderBy('sort_order')->get();
        $rawOpts = DB::table('dropdown_options')->get()->groupBy('dropdown_id');
        foreach ($rawDropdowns as $dd) {
            $ddArray = (array)$dd;
            $opts = $rawOpts->get($dd->id) ?? collect([]);
            $ddArray['options'] = $opts->map(function($o) { return (array)$o; })->toArray();
            $config['dropdowns'][$dd->category_target][] = $ddArray;
        }

        $selectedExtras = json_decode($this->booking->extras_json ?? '[]', true) ?? [];

        $startTime = Carbon::parse($this->booking->start_time);
        $timeString = $startTime->format('g:i A');
        if (!empty($this->booking->end_time) && $this->booking->end_time != '00:00:00') {
            $timeString .= ' - ' . Carbon::parse($this->booking->end_time)->format('g:i A');
        }

        $galleryFiles = collect([
            $this->booking->delivery_attachment,
            $this->booking->delivery_attachment_2,
            $this->booking->delivery_attachment_3,
            $this->booking->delivery_attachment_4,
            $this->booking->delivery_attachment_5
        ])->filter()->toArray();

        return view('livewire.booking-overview', compact(
            'items',
            'payments',
            'emailLogs',
            'amountPaid',
            'totalAmount',
            'balanceDue',
            'calculatedExtrasTotal',
            'deliveryCost',
            'ridesCost',
            'statusColor',
            'timeString',
            'galleryFiles',
            'isCard',
            'surcharge',
            'activeCategories',
            'config',
            'selectedExtras'
        ));
    }
}
