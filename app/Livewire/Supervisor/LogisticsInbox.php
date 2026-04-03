<?php

namespace App\Livewire\Supervisor;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

#[Layout('components.layouts.supervisor')]
class LogisticsInbox extends Component
{
    use WithPagination;

    // --- Search Filters ---
    public $search_pay = '';
    public $search_inv = '';
    public $search_ord = '';
    public $search_deb = '';
    public $search_op = '';

    // --- Modal Forms ---
    // Payment Processing
    public $pay_booking_id;
    public $pay_amount;
    public $pay_type = 'Remaining Balance';
    public $pay_method = 'EFT';
    public $pay_date;
    public $pay_ref;
    public $pay_notes;
    public $eft_specific_method = 'Direct Deposit';
    public $modal_card_category = 'Debit Card';
    public $modal_card_network = 'Visa';
    public $pay_card_number;
    public $pay_card_expiry;
    public $pay_card_cvv;
    public $pay_context = [];

    // Edit Card
    public $card_booking_id;
    public $edit_card_category;
    public $edit_card_type;
    public $edit_card_number;
    public $edit_card_expiry;
    public $edit_card_cvv;

    // Edit EFT
    public $eft_booking_id;
    public $edit_eft_method;

    // Email
    public $email_booking_id;
    public $email_type = 'invoice';
    public $email_to;
    public $email_cc;
    public $email_bcc = 'hire.enquiries@bigfunqld.com.au';
    public $email_subject;
    public $email_body;
    public $email_attachment;
    public $is_sent_successfully = false;


    // View Details (from model directly)
    public $view_payment_details = null;

    // Define unique query string keys for multiple paginators
    protected $queryString = [
        'search_pay' => ['except' => ''],
        'search_inv' => ['except' => ''],
        'search_ord' => ['except' => ''],
        'search_deb' => ['except' => ''],
        'search_op' => ['except' => ''],
    ];

    public function mount()
    {
        $this->pay_date = now()->format('Y-m-d');
    }

    // Reset pagination when search changes
    public function updatingSearchPay()
    {
        $this->resetPage('page_pay');
    }
    public function updatingSearchInv()
    {
        $this->resetPage('page_inv');
    }
    public function updatingSearchOrd()
    {
        $this->resetPage('page_ord');
    }
    public function updatingSearchDeb()
    {
        $this->resetPage('page_deb');
    }
    public function updatingSearchOp()
    {
        $this->resetPage('page_op');
    }

    // --- Helpers ---
    public function getCardNetwork($number, $storedType)
    {
        if (!empty($storedType) && $storedType !== 'Unknown') return $storedType;
        $number = preg_replace('/\D/', '', $number ?? '');
        if (empty($number)) return 'Unknown';
        if (preg_match('/^4/', $number)) return 'Visa';
        if (preg_match('/^5[1-5]/', $number) || preg_match('/^2[2-7]/', $number)) return 'MasterCard';
        if (preg_match('/^3[47]/', $number)) return 'Amex';
        if (preg_match('/^6/', $number)) return 'Discover';
        return 'Unknown';
    }

    public function getCardStyle($network)
    {
        return match ($network) {
            'Visa' => ['icon' => 'payments', 'color' => 'text-blue-700', 'bg' => 'bg-blue-50', 'label' => 'VISA'],
            'MasterCard' => ['icon' => 'credit_card', 'color' => 'text-orange-600', 'bg' => 'bg-orange-50', 'label' => 'MASTERCARD'],
            'Amex' => ['icon' => 'stars', 'color' => 'text-cyan-600', 'bg' => 'bg-cyan-50', 'label' => 'AMEX'],
            'Discover' => ['icon' => 'search', 'color' => 'text-purple-600', 'bg' => 'bg-purple-50', 'label' => 'DISCOVER'],
            'Bartercard' => ['icon' => 'bar_chart', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-50', 'label' => 'BARTERCARD'],
            default => ['icon' => 'credit_card', 'color' => 'text-gray-500', 'bg' => 'bg-gray-100', 'label' => 'UNKNOWN'],
        };
    }

    // --- Actions ---

    public function savePaymentType($bookingId, $method)
    {
        Booking::where('id', $bookingId)->update(['payment_type' => $method]);
        $this->dispatch('notify', title: 'Success', message: 'Payment method saved!');
    }

    public function openPaymentModal($bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        $total = (float)$booking->total_amount;
        $paid = (float)$booking->amount_paid;
        $owing = max(0, $total - $paid);
        $deposit = (float)$booking->deposit_required;

        if ($owing <= 0) {
            $this->dispatch('notify-error', title: 'Payment Complete', message: 'This booking is already fully paid.');
            return;
        }

        $this->pay_booking_id = $bookingId;
        $this->pay_context = [
            'total' => $total,
            'paid' => $paid,
            'owing' => $owing,
            'deposit' => $deposit,
            'customer_name' => $booking->customer_first_name . ' ' . $booking->customer_last_name,
            'invoice_num' => $booking->invoice_number ?? $booking->id,
        ];

        // Smart defaults
        if ($paid <= 0 && $deposit > 0) {
            $this->pay_type = 'Deposit';
            $this->pay_amount = $deposit;
        } else {
            $this->pay_type = 'Remaining Balance';
            $this->pay_amount = $owing;
        }

        $this->pay_method = match($booking->payment_type) {
            'Card Holder', 'credit_card' => 'Card Holder',
            'Cash' => 'Cash',
            default => 'EFT',
        };

        $this->reset(['pay_ref', 'pay_notes']);
        $this->dispatch('open-modal', 'paymentModal');
    }

    public function updatedPayType($value)
    {
        if (empty($this->pay_context)) return;

        if ($value === 'Remaining Balance') $this->pay_amount = $this->pay_context['owing'];
        elseif ($value === 'Full Amount') $this->pay_amount = $this->pay_context['total'];
        elseif ($value === 'Deposit') $this->pay_amount = $this->pay_context['deposit'];
        else $this->pay_amount = null;
    }

    public function processPayment()
    {
        $this->validate([
            'pay_amount' => 'required|numeric|min:0.01',
            'pay_method' => 'required|string',
        ]);

        $combinedNotes = $this->pay_notes;
        if (!empty($this->pay_ref)) {
            $combinedNotes = 'Ref: ' . $this->pay_ref . (!empty($this->pay_notes) ? ' | ' . $this->pay_notes : '');
        }

        BookingPayment::create([
            'booking_id' => $this->pay_booking_id,
            'amount' => $this->pay_amount,
            'payment_method' => $this->pay_method,
            'payment_date' => $this->pay_date,
            'notes' => $combinedNotes,
            'card_number' => $this->pay_method === 'Card Holder' ? $this->pay_card_number : null,
            'card_expiry' => $this->pay_method === 'Card Holder' ? $this->pay_card_expiry : null,
            'card_cvv' => $this->pay_method === 'Card Holder' ? $this->pay_card_cvv : null,
            'card_network' => $this->pay_method === 'Card Holder' ? $this->modal_card_network : null,
        ]);

        $booking = Booking::find($this->pay_booking_id);
        $booking->amount_paid += $this->pay_amount;
        $booking->owing_amount = max(0, $booking->total_amount - $booking->amount_paid);

        if ($this->pay_method === 'Card Holder') {
            $booking->card_category = $this->modal_card_category;
            $booking->card_type = $this->modal_card_network;
            $booking->card_number = $this->pay_card_number;
            $booking->card_expiry = $this->pay_card_expiry;
            $booking->card_cvv = $this->pay_card_cvv;
        } elseif ($this->pay_method === 'EFT') {
            $booking->eft_method = $this->eft_specific_method;
        }
        $booking->save();

        $this->dispatch('close-modal', 'paymentModal');
        // Dispatch the success modal instead of a notification
        $this->dispatch('open-modal', 'paymentSuccessModal');
    }

    public function openCardModal($bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        $this->card_booking_id = $bookingId;
        $this->edit_card_category = $booking->card_category ?? 'Credit Card';
        $this->edit_card_type = $booking->card_type ?? 'Visa';
        $this->edit_card_number = $booking->card_number;
        $this->edit_card_expiry = $booking->card_expiry;
        $this->edit_card_cvv = $booking->card_cvv;

        $this->dispatch('open-modal', 'cardModal');
    }

    public function saveCardDetails()
    {
        Booking::where('id', $this->card_booking_id)->update([
            'card_category' => $this->edit_card_category,
            'card_type' => $this->edit_card_type,
            'card_number' => $this->edit_card_number,
            'card_expiry' => $this->edit_card_expiry,
            'card_cvv' => $this->edit_card_cvv,
        ]);
        $this->dispatch('close-modal', 'cardModal');
        $this->dispatch('notify', title: 'Success', message: 'Card details updated!');
    }

    public function openEftModal($bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        $this->eft_booking_id = $bookingId;
        $this->edit_eft_method = $booking->eft_method ?? 'Direct Deposit';

        $this->dispatch('open-modal', 'eftModal');
    }

    public function saveEftDetails()
    {
        Booking::where('id', $this->eft_booking_id)->update([
            'eft_method' => $this->edit_eft_method,
        ]);
        $this->dispatch('close-modal', 'eftModal');
        $this->dispatch('notify', title: 'Success', message: 'EFT details updated!');
    }

    public function viewPaymentDetails($paymentId)
    {
        $this->view_payment_details = BookingPayment::with('booking')->findOrFail($paymentId);
        $this->dispatch('open-modal', 'paymentDetailsModal');
    }

    public function prepareEmail($bookingId, $type)
    {
        $booking = Booking::findOrFail($bookingId);
        $this->email_booking_id = $bookingId;
        $this->email_type = $type;
        $this->email_to = $booking->customer_email;

        $amountPaid = BookingPayment::where('booking_id', $bookingId)->sum('amount');
        $totalAmountValue = (float)$booking->total_amount;
        $balanceDue = max(0, $totalAmountValue - $amountPaid);

        $fName = $booking->customer_first_name;
        $fullName = $fName . ' ' . $booking->customer_last_name;
        $eventDate = Carbon::parse($booking->event_date)->format('d/m/Y');
        $invNum = $booking->invoice_number ?? $booking->id;
        $totalAmount = number_format($totalAmountValue, 2);
        
        $fullAddress = $booking->address_line_1 . ', ' . $booking->suburb . ' ' . $booking->state . ' ' . $booking->postcode;
        $startTime = Carbon::parse($booking->start_time);
        $timeString = $startTime->format('g:i A');
        if (!empty($booking->end_time) && $booking->end_time != '00:00:00') {
            $timeString .= ' - ' . Carbon::parse($booking->end_time)->format('g:i A');
        }

        $paymentMethod = $booking->payment_type ?: 'None';
        if ($paymentMethod === 'Card Holder' || $paymentMethod === 'credit_card') {
            $paymentMethod = 'Credit/Debit Card';
        }

        if ($type === 'invoice') {
            // Big Fun Invoice (Paperwork/Deposit)
            $this->email_subject = "Big Fun Invoice - " . $invNum;
            $this->email_body = "Hello,\n\nPlease find attached the paperwork for your booking on $eventDate. Kindly review the document to ensure all contact and delivery details are correct, then sign and return the form to us via email.\n\nBooking Details:\nDate: $eventDate\nTime: $timeString\nLocation: $fullAddress\n\nPayment Details:\nYour deposit is now due. The remaining balance is payable during the week of your event via direct deposit or Electronic Funds Transfer (EFT). Please note that our drivers do not accept payments.\n\nTotal Amount: $$totalAmount\nBalance Due: $" . number_format($balanceDue, 2) . "\n\nAll payments should be made to Big Fun. Please ensure your invoice number is quoted as the payment reference.\n\nIf you have any questions or require assistance, please feel free to contact us on 1800 244 386.\n\nThank you again for booking with us.\n\nKind regards,\nBIG FUN\n1800 244 386";
            $this->email_attachment = "BigFunInvoice-{$booking->id}.pdf";
        } elseif ($type === 'receipt') {
            // Payment Receipt
            $this->email_subject = "Payment Receipt - Booking #" . $booking->id;
            $this->email_body = "$fullName\n\nBIG FUN INVOICE No.: $invNum\n\nThank you for your payment for your booking on $eventDate. Do not hesitate to contact us if you have any questions.\n\nInvoice Amount: $$totalAmount\n\nAmount Paid:  $" . number_format($amountPaid, 2) . "\nPayment Method: $paymentMethod\n\nAmount Owing: $" . number_format($balanceDue, 2) . "\n\nREMEMBER: Your final payment is due PRIOR to your event.\n\nRegards\n\nBIG FUN\nwww.bigfun.com.au\n1800 244 386";
            $this->email_attachment = "BigFunReceipt-{$booking->id}.pdf";
        } elseif ($type === 'envelope') {
            $this->email_subject = "Envelope - " . $invNum;
            $this->email_body = "Hello,\n\nPlease find attached the envelope document for your booking on $eventDate.\n\nRegards,\nBIG FUN";
            $this->email_attachment = "BigFunEnvelope-{$booking->id}.pdf";
        } elseif ($type === 'debt') {
            $eventMidnight = Carbon::parse($booking->event_date)->startOfDay();
            $todayMidnight = now()->startOfDay();
            $daysPast = $eventMidnight->isPast() ? $todayMidnight->diffInDays($eventMidnight) : 0;
            $this->email_subject = "Outstanding Balance Reminder - Booking #{$booking->id}";
            $this->email_body = "Hello $fName,\n\nThis is a friendly reminder that your event on $eventDate is currently $daysPast days past due with an outstanding balance of $" . number_format($balanceDue, 2) . ".\n\nPlease find attached the debt reminder invoice which provides an overview of your booking and the outstanding amount.\n\nAll payments should be made to Big Fun quoting your invoice number as the payment reference.\n\nPlease contact us on 1800 244 386 if you wish to discuss this account.\n\nKind regards,\nBIG FUN\n1800 244 386";
            $this->email_attachment = "BigFunDebt-{$booking->id}.pdf";
        }

        $this->dispatch('open-modal', 'emailModal');
    }

    public function sendEmail(\App\Services\MailService $mailService)
    {
        $result = $mailService->sendEmail($this->email_booking_id, [
            'email_to' => $this->email_to,
            'email_cc' => $this->email_cc,
            'email_bcc' => $this->email_bcc,
            'email_subject' => $this->email_subject,
            'email_body' => $this->email_body,
            'email_type' => $this->email_type,
            'attachments' => [$this->email_attachment]
        ]);

        if ($result['success']) {
            $this->is_sent_successfully = true;
            $this->dispatch('close-modal', 'emailModal');
            $this->dispatch('open-modal', 'sentSuccessModal');

            if ($this->email_type === 'invoice') {
                Booking::where('id', $this->email_booking_id)->update(['invoice_emailed' => 1]);
            }
        } else {
            $this->dispatch('notify', title: 'Error', message: $result['message']);
        }
    }

    public function resetEmailState()
    {
        $this->is_sent_successfully = false;
    }

    public function render()
    {
        $enquiriesCount = Booking::where('status', 'Pending')->count();

        $paymentsQuery = Booking::with('payments')
            ->whereIn('status', ['Pending', 'Confirmed'])
            ->whereColumn('total_amount', '>', 'amount_paid');

        if ($this->search_pay) {
            $paymentsQuery->where(function ($q) {
                $q->where('id', 'like', "%{$this->search_pay}%")
                    ->orWhere('customer_first_name', 'like', "%{$this->search_pay}%")
                    ->orWhere('customer_last_name', 'like', "%{$this->search_pay}%")
                    ->orWhere('customer_organization', 'like', "%{$this->search_pay}%");
            });
        }
        $pendingPayments = $paymentsQuery->orderBy('event_date', 'asc')->paginate(10, ['*'], 'page_pay');

        $invoicesQuery = Booking::where('invoice_emailed', 0)->where('status', '!=', 'Cancelled');
        if ($this->search_inv) {
            $invoicesQuery->where(function ($q) {
                $q->where('id', 'like', "%{$this->search_inv}%")
                    ->orWhere('customer_first_name', 'like', "%{$this->search_inv}%")
                    ->orWhere('customer_last_name', 'like', "%{$this->search_inv}%");
            });
        }
        $invoices = $invoicesQuery->orderBy('event_date', 'asc')->paginate(10, ['*'], 'page_inv');

        $ordersQuery = Booking::where('event_date', '>=', now()->toDateString())->where('status', 'Confirmed');
        if ($this->search_ord) {
            $ordersQuery->where(function ($q) {
                $q->where('customer_first_name', 'like', "%{$this->search_ord}%")
                    ->orWhere('customer_last_name', 'like', "%{$this->search_ord}%");
            });
        }
        $orders = $ordersQuery->orderBy('event_date', 'asc')->paginate(10, ['*'], 'page_ord');

        $debtorsQuery = Booking::with('payments')->where('status', 'Completed')->whereColumn('total_amount', '>', 'amount_paid');
        if ($this->search_deb) {
            $debtorsQuery->where(function ($q) {
                $q->where('customer_first_name', 'like', "%{$this->search_deb}%")
                    ->orWhere('customer_last_name', 'like', "%{$this->search_deb}%");
            });
        }
        $debtors = $debtorsQuery->orderBy('event_date', 'desc')->paginate(10, ['*'], 'page_deb');

        $operatorsQuery = User::whereIn('role', ['Operator', 'Staff']);
        if ($this->search_op) {
            $operatorsQuery->where(function ($q) {
                $q->where('first_name', 'like', "%{$this->search_op}%")
                    ->orWhere('last_name', 'like', "%{$this->search_op}%")
                    ->orWhere('email', 'like', "%{$this->search_op}%");
            });
        }
        $operators = $operatorsQuery->paginate(10, ['*'], 'page_op');

        return view('livewire.supervisor.logistics-inbox', [
            'enquiriesCount' => $enquiriesCount,
            'pendingPayments' => $pendingPayments,
            'invoices' => $invoices,
            'orders' => $orders,
            'debtors' => $debtors,
            'operators' => $operators,
        ]);
    }
}
