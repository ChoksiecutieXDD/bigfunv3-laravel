<?php

namespace App\Livewire\Supervisor;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

#[Layout('components.layouts.plain')]
class BookingOverview extends Component
{
    public Booking $booking;

    // --- General Properties ---
    public $newStatus;
    public $newDate;

    // --- Payment Properties ---
    public $payAmount;
    public $payType = 'Remaining Balance';
    public $payMethod = 'EFT';
    public $eftMethod = 'Direct Deposit';
    public $cardNum;
    public $cardExpiry;
    public $cardCvv;
    public $cardCategory = 'Debit Card';
    public $cardNetwork = 'Visa';
    public $payDate;
    public $payRef;
    public $payNotes;

    // --- Email Properties ---
    public $emailType = 'invoice';
    public $emailTo;
    public $emailCc;
    public $emailBcc = 'hire.enquiries@bigfunqld.com.au';
    public $emailSubject;
    public $emailBody;
    public $emailAttachment;

    // --- Calendar Modals Properties ---
    public $calMonth;
    public $calYear;
    public $calDays = [];
    public $tempSelectedDate;

    public function mount($id)
    {
        $this->booking = Booking::findOrFail($id);
        $this->newStatus = $this->booking->status;
        $this->newDate = Carbon::parse($this->booking->event_date)->format('Y-m-d');
        $this->payDate = now()->format('Y-m-d');

        $this->calMonth = Carbon::parse($this->newDate)->month;
        $this->calYear = Carbon::parse($this->newDate)->year;
    }

    // --- Core Updates ---
    public function updateStatus()
    {
        // 1. If it is currently a draft, pop open the warning modal instead of saving
        if ($this->booking->status === 'Draft' && $this->newStatus !== 'Draft') {
            $this->dispatch('open-modal', 'draftModal');
            return;
        }

        // 2. If it's not a draft, just update it normally
        $this->executeStatusUpdate();
    }

    public function executeStatusUpdate()
    {
        $this->booking->update(['status' => $this->newStatus]);

        // Refresh the component state so the UI colors change immediately
        $this->booking->refresh();

        $this->dispatch('close-modal', 'draftModal');
        $this->dispatch('notify', title: 'Success', message: 'Status updated to ' . $this->newStatus);
    }

    public function toggleTerms()
    {
        // Safe boolean toggle for database
        $this->booking->terms_agreed = $this->booking->terms_agreed ? 0 : 1;

        if ($this->booking->terms_agreed && in_array($this->booking->status, ['Pending', 'Draft'])) {
            $this->booking->status = 'Confirmed';
            $this->newStatus = 'Confirmed';
        }
        $this->booking->save();
        $this->dispatch('notify', title: 'Success', message: 'Terms agreement updated.');
    }

    public function moveDate()
    {
        // Capture original date on FIRST move only
        if (!$this->booking->original_event_date) {
            $this->booking->original_event_date = $this->booking->event_date;
        }

        $this->booking->event_date = $this->newDate;
        $this->booking->save();

        $this->dispatch('notify', title: 'Success', message: 'Booking moved to ' . $this->newDate);
    }

    public function deleteBooking()
    {
        $this->booking->delete();
        return redirect()->to('/supervisor/calendar');
    }

    // --- Payment Logic ---
    public function openPaymentModal()
    {
        $amountPaid = BookingPayment::where('booking_id', $this->booking->id)->sum('amount');
        $totalAmount = (float) $this->booking->total_amount;
        $balanceDue = max(0, $totalAmount - $amountPaid);
        $depositReq = $this->booking->deposit_required > 0 ? $this->booking->deposit_required : ($totalAmount / 2);

        if ($balanceDue <= 0) {
            $this->payType = 'Remaining Balance';
            $this->payAmount = 0;
        } elseif ($amountPaid == 0 && $balanceDue >= $depositReq) {
            $this->payType = 'Deposit';
            $this->payAmount = $depositReq;
        } else {
            $this->payType = 'Remaining Balance';
            $this->payAmount = $balanceDue;
        }

        $this->payMethod = in_array($this->booking->payment_type, ['credit_card', 'Card Holder']) ? 'Card Holder' : 'EFT';
        $this->dispatch('open-modal', 'paymentModal');
    }

    public function updatedPayType($value)
    {
        $amountPaid = BookingPayment::where('booking_id', $this->booking->id)->sum('amount');
        $totalAmount = (float) $this->booking->total_amount;
        $balanceDue = max(0, $totalAmount - $amountPaid);
        $depositReq = $this->booking->deposit_required > 0 ? $this->booking->deposit_required : ($totalAmount / 2);

        if ($value === 'Remaining Balance') $this->payAmount = $balanceDue;
        elseif ($value === 'Full Amount') $this->payAmount = $totalAmount;
        elseif ($value === 'Deposit') $this->payAmount = $depositReq;
        else $this->payAmount = null;
    }

    public function savePayment()
    {
        $this->validate([
            'payAmount' => 'required|numeric|min:0.01',
            'payMethod' => 'required|string',
        ]);

        BookingPayment::create([
            'booking_id' => $this->booking->id,
            'amount' => $this->payAmount,
            'payment_method' => $this->payMethod,
            'payment_type' => $this->payType,
            'payment_date' => $this->payDate,
            'reference' => $this->payRef,
            'notes' => $this->payNotes,
            'card_number' => $this->payMethod === 'Card Holder' ? $this->cardNum : null,
            'card_expiry' => $this->payMethod === 'Card Holder' ? $this->cardExpiry : null,
            'card_cvv' => $this->payMethod === 'Card Holder' ? $this->cardCvv : null,
            'card_network' => $this->payMethod === 'Card Holder' ? $this->cardNetwork : null,
        ]);

        $this->reset(['payAmount', 'payRef', 'payNotes']);
        $this->dispatch('close-modal', 'paymentModal');
        $this->dispatch('notify', title: 'Success', message: 'Payment recorded.');
    }

    // --- Email Logic ---
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
        $invNum = $this->booking->invoice_number ?? $this->booking->id;
        $fullAddress = $this->booking->address_line_1 . ', ' . $this->booking->suburb . ' ' . $this->booking->state . ' ' . $this->booking->postcode;
        
        $startTime = Carbon::parse($this->booking->start_time);
        $timeString = $startTime->format('g:i A');
        if (!empty($this->booking->end_time) && $this->booking->end_time != '00:00:00') {
            $timeString .= ' - ' . Carbon::parse($this->booking->end_time)->format('g:i A');
        }

        $paymentMethod = $this->booking->payment_type ?: 'None';
        if ($paymentMethod === 'Card Holder' || $paymentMethod === 'credit_card') {
            $paymentMethod = 'Credit/Debit Card';
        }

        if ($type === 'receipt') {
            // Payment Receipt
            $this->emailSubject = "Payment Receipt - Booking #{$this->booking->id}";
            $this->emailBody = "$fullName\n\nBIG FUN INVOICE No.: $invNum\n\nThank you for your payment for your booking on $eventDate. Do not hesitate to contact us if you have any questions.\n\nInvoice Amount: $" . number_format($totalAmount, 2) . "\n\nAmount Paid:  $" . number_format($amountPaid, 2) . "\nPayment Method: $paymentMethod\n\nAmount Owing: $" . number_format($balanceDue, 2) . "\n\nREMEMBER: Your final payment is due PRIOR to your event.\n\nRegards\n\nBIG FUN\nwww.bigfun.com.au\n1800 244 386";
            $this->emailAttachment = "BigFunReceipt-{$this->booking->id}.pdf";
        } elseif ($type === 'po') {
            // Purchase Order Reference
            $this->emailSubject = "Purchase Order Reference - Booking #{$this->booking->id}";
            $this->emailBody = "Hello $fName,\n\nThank you for your inquiry regarding the event on $eventDate.\n\nPlease find attached the Purchase Order Reference / Quotation for your internal approval process. This document outlines the rides, logistics, and total costs associated with your request.\n\nThis document serves as a reference to help you decide if you wish to proceed with the booking. It is not a demand for payment.\n\nBooking Proposal:\nDate: $eventDate\nTime: $timeString\nLocation: $fullAddress\n\nFinancial Summary:\nTotal Proposed Cost: $" . number_format($totalAmount, 2) . "\n\nIf you decide to proceed, please let us know so we can finalize the details and issue a formal invoice.\n\nIf you have any questions or require assistance, please feel free to contact us on 1800 244 386.\n\nKind regards,\nBIG FUN\n1800 244 386";
            $this->emailAttachment = "BigFunPurchaseOrder-{$this->booking->id}.pdf";
        } else {
            // Big Fun Invoice (Paperwork/Deposit)
            $this->emailSubject = "Big Fun Invoice - $invNum";
            $this->emailBody = "Hello,\n\nPlease find attached the paperwork for your booking on $eventDate. Kindly review the document to ensure all contact and delivery details are correct, then sign and return the form to us via email.\n\nBooking Details:\nDate: $eventDate\nTime: $timeString\nLocation: $fullAddress\n\nPayment Details:\nYour deposit is now due. The remaining balance is payable during the week of your event via direct deposit or Electronic Funds Transfer (EFT). Please note that our drivers do not accept payments.\n\nTotal Amount: $" . number_format($totalAmount, 2) . "\nBalance Due: $" . number_format($balanceDue, 2) . "\n\nAll payments should be made to Big Fun. Please ensure your invoice number is quoted as the payment reference.\n\nIf you have any questions or require assistance, please feel free to contact us on 1800 244 386.\n\nThank you again for booking with us.\n\nKind regards,\nBIG FUN\n1800 244 386";
            $this->emailAttachment = "BigFunInvoice-{$this->booking->id}.pdf";
        }

        $this->dispatch('open-modal', 'emailModal');
    }

    public function sendEmail()
    {
        // Mock sending email - connect this to your mail logic later
        DB::table('email_logs')->insert([
            'booking_id' => $this->booking->id,
            'type' => $this->emailType,
            'sent_to' => $this->emailTo,
            'sent_at' => now()
        ]);

        $this->dispatch('close-modal', 'emailModal');
        $this->dispatch('notify', title: 'Success', message: 'Email sent successfully!');
    }

    // --- Live Calendar Logic ---
    public function openCalendarModal()
    {
        $this->loadCalendar();
        $this->dispatch('open-modal', 'calendarModal');
    }

    public function calPrev()
    {
        $date = Carbon::create($this->calYear, $this->calMonth, 1)->subMonth();
        $this->calMonth = $date->month;
        $this->calYear = $date->year;
        $this->loadCalendar();
    }

    public function calNext()
    {
        $date = Carbon::create($this->calYear, $this->calMonth, 1)->addMonth();
        $this->calMonth = $date->month;
        $this->calYear = $date->year;
        $this->loadCalendar();
    }

    public function loadCalendar()
    {
        $start = Carbon::create($this->calYear, $this->calMonth, 1);
        $end = $start->copy()->endOfMonth();

        $counts = Booking::whereBetween('event_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->whereNotIn('status', ['Cancelled'])
            ->selectRaw('event_date, COUNT(*) as cnt')
            ->groupBy('event_date')
            ->pluck('cnt', 'event_date')
            ->toArray();

        $daysInMonth = $start->daysInMonth;
        $startDow = $start->dayOfWeek;

        $this->calDays = [];
        // Empty slots for start of month
        for ($i = 0; $i < $startDow; $i++) {
            $this->calDays[] = null;
        }

        // Actual days
        $dailyLimit = 7;
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateStr = $start->copy()->day($day)->format('Y-m-d');
            $used = $counts[$dateStr] ?? 0;
            $this->calDays[] = [
                'date' => $dateStr,
                'day' => $day,
                'left' => max(0, $dailyLimit - $used)
            ];
        }
    }

    public function applySelectedDate()
    {
        if ($this->tempSelectedDate) {
            $this->newDate = $this->tempSelectedDate;
            $this->dispatch('close-modal', 'calendarModal');
        }
    }

    public function render()
    {
        $items = BookingItem::where('booking_id', $this->booking->id)
            ->selectRaw('item_name, is_custom, SUM(qty) as total_qty')
            ->groupBy('item_name', 'is_custom')
            ->get();

        $payments = BookingPayment::where('booking_id', $this->booking->id)->orderBy('payment_date', 'asc')->get();
        $emailLogs = DB::table('email_logs')->where('booking_id', $this->booking->id)->orderBy('sent_at', 'desc')->get();

        $amountPaid = $payments->sum('amount');
        $totalAmount = (float) $this->booking->total_amount;
        $balanceDue = max(0, $totalAmount - $amountPaid);
        $depositReq = $this->booking->deposit_required > 0 ? $this->booking->deposit_required : ($totalAmount / 2);

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
            default     => 'bg-plum/10 text-[#9D686E] border-[#9D686E]/20',
        };

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

        return view('livewire.supervisor.booking-overview', compact(
            'items',
            'payments',
            'emailLogs',
            'amountPaid',
            'totalAmount',
            'balanceDue',
            'depositReq',
            'calculatedExtrasTotal',
            'isCard',
            'baseAmount',
            'surcharge',
            'deliveryCost',
            'ridesCost',
            'statusColor',
            'timeString',
            'galleryFiles'
        ));
    }
}
