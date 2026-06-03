<?php

namespace App\Livewire\Supervisor;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\User;
use App\Services\EmailQuotaService;
use App\Services\GoogleSheetService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

#[Layout('components.layouts.supervisor')]
class LogisticsInbox extends Component
{
    use WithPagination;

    // --- Search Filters ---
    public string $search_pay = '';
    public string $search_full = '';
    public string $search_inv = '';
    public string $search_ord = '';
    public string $search_deb = '';
    public string $search_op = '';

    // --- Sort Directions ---
    public string $sort_pay = 'asc';
    public string $sort_inv = 'asc';
    public string $sort_deb = 'desc';
    public string $sort_full = 'desc';

    // --- Modal Forms ---
    // Payment Processing
    public int|string|null $saving_type_id = null;
    public int|string|null $pay_booking_id = null;
    public float|int|string|null $pay_amount = null;
    public string $pay_type = 'Final Settlement';
    public string $pay_method = 'EFT';
    public string $pay_date = '';
    public string $pay_ref = '';
    public string $pay_notes = '';
    public string $eft_specific_method = 'Direct Deposit';
    public string $modal_card_category = 'Debit Card';
    public string $modal_card_network = 'Visa';
    public string $pay_card_holder = '';
    public string $pay_card_number = '';
    public string $pay_card_expiry = '';
    public string $pay_card_cvv = '';
    public array $pay_context = [];

    // Edit Card
    public int|string|null $card_booking_id = null;
    public ?string $edit_card_holder = null;
    public ?string $edit_card_category = null;
    public ?string $edit_card_type = null;
    public ?string $edit_card_number = null;
    public ?string $edit_card_expiry = null;
    public ?string $edit_card_cvv = null;

    // Edit EFT
    public int|string|null $eft_booking_id = null;
    public ?string $edit_eft_method = null;

    // Email
    public int|string|null $email_booking_id = null;
    public string $email_type = 'invoice';
    public string $email_to = '';
    public string $email_cc = '';
    public string $email_bcc = 'hire.enquiries@bigfunqld.com.au';
    public string $email_subject = '';
    public string $email_body = '';
    public string $email_attachment = '';
    public bool $is_sent_successfully = false;


    // View Details (from model directly)
    public ?\App\Models\BookingPayment $view_payment_details = null;
    public bool $is_editing_payment = false;
    public float|int|string|null $edit_payment_amount = null;
    public string $edit_payment_method = '';
    public string $edit_payment_date = '';
    public string $edit_payment_ref = '';
    public string $edit_payment_notes = '';

    // Define unique query string keys for multiple paginators
    protected array $queryString = [
        'search_pay' => ['except' => ''],
        'search_full' => ['except' => ''],
        'search_inv' => ['except' => ''],
        'search_ord' => ['except' => ''],
        'search_deb' => ['except' => ''],
        'search_op' => ['except' => ''],
        'sort_pay' => ['except' => 'asc'],
        'sort_inv' => ['except' => 'asc'],
        'sort_deb' => ['except' => 'desc'],
        'sort_full' => ['except' => 'desc'],
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
    public function updatingSearchFull()
    {
        $this->resetPage('page_full');
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

    public function toggleSort(string $section)
    {
        $prop = "sort_" . $section;
        $this->$prop = ($this->$prop === 'asc') ? 'desc' : 'asc';
        $this->resetPage("page_" . $section);
    }

    // --- Helpers ---
    public function getCardNetwork(string|null $number, string|null $storedType)
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

    public function getCardStyle(string|null $network)
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

    public function savePaymentType(int|string $bookingId, string $method)
    {
        $this->saving_type_id = $bookingId;
        
        $booking = Booking::find($bookingId);
        if ($booking) {
            $booking->updatePaymentMethod($method);
        }
        
        // Sync to Google Sheet
        app(GoogleSheetService::class)->sync($bookingId);

        $this->saving_type_id = null;
        $this->dispatch('notify', title: 'Success', message: 'Payment method saved!');
    }

    public function openPaymentModal(int|string $bookingId)
    {
        $booking = Booking::with('payments')->findOrFail($bookingId);
        $total = round((float)$booking->total_amount, 2);
        $paid = round((float)$booking->total_paid, 2);
        $owing = round(max(0, $total - $paid), 2);
        $deposit = round((float)$booking->deposit_required, 2);

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
            $this->pay_type = 'Deposit Capture';
            $this->pay_amount = min($deposit, $owing);
        } else {
            $this->pay_type = 'Final Settlement';
            $this->pay_amount = $owing;
        }

        $this->pay_method = match($booking->payment_type) {
            'Card Holder', 'credit_card' => 'Card Holder',
            'Cash' => 'Cash',
            default => 'EFT',
        };

        $this->reset(['pay_ref', 'pay_notes', 'pay_card_holder', 'pay_card_number', 'pay_card_expiry', 'pay_card_cvv']);
        
        // Default card holder to customer name
        $this->pay_card_holder = $this->pay_context['customer_name'];

        $this->dispatch('open-modal', 'paymentModal');
    }

    public function updatedPayType(string|float|int|null $value)
    {
        if (empty($this->pay_context)) return;

        $owing = $this->pay_context['owing'];
        $total = $this->pay_context['total'];
        $deposit = $this->pay_context['deposit'];

        if ($value === 'Final Settlement') $this->pay_amount = round($owing, 2);
        elseif ($value === 'Total Liquidation') $this->pay_amount = round($owing, 2);
        elseif ($value === 'Deposit Capture') $this->pay_amount = round(min($deposit, $owing), 2);
        // Partial Allocation keeps current amount
    }

    public function processPayment()
    {
        $owing = (float)($this->pay_context['owing'] ?? 0);
        
        // Smart Safeguard: Avoid exceeding pay
        if ($this->pay_amount > $owing && $this->pay_type !== 'Partial Allocation') {
            $this->pay_amount = $owing;
        }

        $this->validate([
            'pay_amount' => 'required|numeric|min:0.01',
            'pay_method' => 'required|string',
        ]);

        $combinedNotes = $this->pay_notes;

        BookingPayment::create([
            'booking_id' => $this->pay_booking_id,
            'amount' => $this->pay_amount,
            'payment_method' => $this->pay_method,
            'payment_date' => $this->pay_date,
            'reference' => $this->pay_ref,
            'notes' => $combinedNotes,
            'card_holder' => $this->pay_method === 'Card Holder' ? $this->pay_card_holder : null,
            'card_number' => $this->pay_method === 'Card Holder' ? $this->pay_card_number : null,
            'card_expiry' => $this->pay_method === 'Card Holder' ? $this->pay_card_expiry : null,
            'card_cvv' => $this->pay_method === 'Card Holder' ? $this->pay_card_cvv : null,
            'card_network' => $this->pay_method === 'Card Holder' ? $this->modal_card_network : null,
        ]);

        $booking = Booking::find($this->pay_booking_id);

        if ($this->pay_method === 'Card Holder') {
            $booking->card_holder = $this->pay_card_holder;
            $booking->card_category = $this->modal_card_category;
            $booking->card_type = $this->modal_card_network;
            $booking->card_number = $this->pay_card_number;
            $booking->card_expiry = $this->pay_card_expiry;
            $booking->card_cvv = $this->pay_card_cvv;
        } elseif ($this->pay_method === 'EFT') {
            $booking->eft_method = $this->eft_specific_method;
        }
        $booking->save();

        // RE-CALCULATE AND UPDATE CACHED COLUMNS (Including Payment Status)
        $booking->syncFinancials();

        // Sync to Google Sheet
        app(GoogleSheetService::class)->sync($booking->id);

        $this->dispatch('close-modal', 'paymentModal');
        // Dispatch the success modal instead of a notification
        $this->dispatch('open-modal', 'paymentSuccessModal');
    }

    public function openCardModal(int|string $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        $this->card_booking_id = $bookingId;
        $this->edit_card_holder = $booking->card_holder ?? ($booking->customer_first_name . ' ' . $booking->customer_last_name);
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
            'card_holder' => $this->edit_card_holder,
            'card_category' => $this->edit_card_category,
            'card_type' => $this->edit_card_type,
            'card_number' => $this->edit_card_number,
            'card_expiry' => $this->edit_card_expiry,
            'card_cvv' => $this->edit_card_cvv,
        ]);

        // Sync to Google Sheet
        app(GoogleSheetService::class)->sync($this->card_booking_id);

        $this->dispatch('close-modal', 'cardModal');
        $this->dispatch('notify', title: 'Success', message: 'Card details updated!');
    }

    public function openEftModal(int|string $bookingId)
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

        // Sync to Google Sheet
        app(GoogleSheetService::class)->sync($this->eft_booking_id);

        $this->dispatch('close-modal', 'eftModal');
        $this->dispatch('notify', title: 'Success', message: 'EFT details updated!');
    }

    public function viewPaymentDetails(int|string $paymentId)
    {
        $this->view_payment_details = BookingPayment::with('booking')->findOrFail($paymentId);
        $this->is_editing_payment = false;
        $this->dispatch('open-modal', 'paymentDetailsModal');
    }

    public function editPaymentDetails()
    {
        if (!$this->view_payment_details) return;
        
        $this->edit_payment_amount = $this->view_payment_details->amount;
        $this->edit_payment_method = $this->view_payment_details->payment_method;
        $this->edit_payment_date = $this->view_payment_details->payment_date ? Carbon::parse($this->view_payment_details->payment_date)->format('Y-m-d') : date('Y-m-d');
        $this->edit_payment_ref = $this->view_payment_details->reference;
        $this->edit_payment_notes = $this->view_payment_details->notes;
        
        $this->is_editing_payment = true;
    }

    public function cancelPaymentEdit()
    {
        $this->is_editing_payment = false;
    }

    public function updatePaymentDetails()
    {
        if (!$this->view_payment_details) return;

        $this->validate([
            'edit_payment_amount' => 'required|numeric|min:0.01',
            'edit_payment_method' => 'required|string',
            'edit_payment_date' => 'required|date',
        ]);

        $this->view_payment_details->update([
            'amount' => $this->edit_payment_amount,
            'payment_method' => $this->edit_payment_method,
            'payment_date' => $this->edit_payment_date,
            'reference' => $this->edit_payment_ref,
            'notes' => $this->edit_payment_notes,
        ]);

        // Sync financials for the associated booking
        $booking = Booking::find($this->view_payment_details->booking_id);
        if ($booking) {
            $booking->syncFinancials();
            app(GoogleSheetService::class)->sync($booking->id);
        }

        $this->is_editing_payment = false;
        $this->view_payment_details->refresh();
        $this->dispatch('notify', title: 'Updated', message: 'Financial record has been corrected.');
    }

    // Email Confirmation
    public string $confirmEmailTitle = '';
    public string $confirmEmailMessage = '';
    public string $pendingEmailType = '';
    public int|string|null $pendingBookingId = null;
    public string $quotaWarningTitle = '';
    public string $quotaWarningMessage = '';
    public string $quotaLimitTitle = '';
    public string $quotaLimitMessage = '';

    // Global PDF Price Toggles (per section)
    public bool $invoice_pdf_prices = true;
    public bool $debtor_pdf_prices = true;

    public function toggleAttractionCost(int|string $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        $booking->update(['include_attraction_cost' => !$booking->include_attraction_cost]);
        $this->dispatch('notify', title: 'Updated', message: 'PDF attraction prices ' . ($booking->include_attraction_cost ? 'included.' : 'excluded.'));
    }

    public function prepareEmail(int|string $bookingId, string $type)
    {
        if ($this->handleQuotaGuardForEmail((int) $bookingId, (string) $type)) {
            return;
        }

        $booking = Booking::with('payments')->findOrFail($bookingId);
        
        // Apply global section settings
        if ($type === 'invoice' || $type === 'receipt' || $type === 'envelope') {
            $booking->update(['include_attraction_cost' => $this->invoice_pdf_prices]);
        } elseif ($type === 'debt') {
            $booking->update(['include_attraction_cost' => $this->debtor_pdf_prices]);
        }
        $booking->refresh();

        $amountPaid = (float)$booking->amount_paid;
        $totalAmount = (float)$booking->total_amount;
        $balanceDue = max(0, $totalAmount - $amountPaid);

        $lastSentAt = DB::table('email_logs')
            ->where('booking_id', $bookingId)
            ->where('type', $type)
            ->max('sent_at'); // String timestamp
            
        $lastPaymentAt = BookingPayment::where('booking_id', $bookingId)->max('payment_date');
        
        $hasHistory = !empty($lastSentAt);
        $newPaymentMade = $hasHistory && (!empty($lastPaymentAt) && Carbon::parse($lastPaymentAt)->isAfter(Carbon::parse($lastSentAt)));

        $warnings = [];

        // 1. Smart Debt Warning (Even for first send)
        $isOverdue = Carbon::parse($booking->event_date)->startOfDay()->isBefore(now()->startOfDay());
        $hasDebt = $balanceDue > 0 && $isOverdue;

        if ($hasDebt && in_array($type, ['receipt', 'invoice', 'po'])) {
            $warnings[] = "This booking has an outstanding debt of $" . number_format($balanceDue, 2) . ".";
        }

        // 2. Resend Warning (Only if no new payments since last send)
        if ($hasHistory && !$newPaymentMade) {
            if ($type === 'receipt') {
                $warnings[] = "A receipt has already been sent for the existing payments.";
            } else {
                $prefix = ($type === 'invoice') ? 'An' : 'A';
                $typeName = match($type) {
                    'invoice' => 'Invoice',
                    'po' => 'Purchase Order',
                    default => 'Email'
                };
                $warnings[] = "$prefix $typeName has already been sent to this customer.";
            }
        }

        if (!empty($warnings)) {
            $this->confirmEmailTitle = $hasDebt ? "Debt Reminder Warning" : "Send Another Email?";
            $this->confirmEmailMessage = implode("<br>", $warnings) . "<br><br>Do you want to proceed with sending the email?";
            $this->pendingEmailType = $type;
            $this->pendingBookingId = $bookingId;
            $this->dispatch('open-modal', 'confirmEmailModal');
            return;
        }

        $this->executePrepareEmail($bookingId, $type);
    }

    public function proceedWithEmail()
    {
        $this->executePrepareEmail($this->pendingBookingId, $this->pendingEmailType);
    }

    private function handleQuotaGuardForEmail(int $bookingId, string $type): bool
    {
        $quota = app(EmailQuotaService::class)->statusForDefaultMailer();

        if ($quota['is_limit_reached']) {
            $this->quotaLimitTitle = 'Daily Email Quota Reached';
            $this->quotaLimitMessage = "{$quota['label']} has reached {$quota['used']}/{$quota['limit']} for today. Please switch mailer or wait for reset.";
            $this->dispatch('open-modal', 'quotaLimitModal');

            return true;
        }

        if ($quota['is_low']) {
            $this->pendingBookingId = $bookingId;
            $this->pendingEmailType = $type;
            $this->quotaWarningTitle = 'Email Credits Running Low';
            $this->quotaWarningMessage = "{$quota['label']} has {$quota['remaining']} credits left today ({$quota['used']}/{$quota['limit']}). Continue sending?";
            $this->dispatch('open-modal', 'quotaWarningModal');

            return true;
        }

        return false;
    }

    public function continueEmailAfterQuotaWarning(): void
    {
        $bookingId = (int) $this->pendingBookingId;
        $type = (string) $this->pendingEmailType;
        $this->dispatch('close-modal', 'quotaWarningModal');

        if ($bookingId > 0 && $type !== '') {
            $this->executePrepareEmail($bookingId, $type);
        }
    }

    public function executePrepareEmail(int|string $bookingId, string $type)
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
            $this->email_subject = "Big Fun Invoice - " . $invNum;
            $this->email_body = "Hello,\n\nPlease find attached the paperwork for your booking on $eventDate. Kindly review the document to ensure all contact and delivery details are correct, then sign and return the form to us via email.\n\nBooking Details:\nDate: $eventDate\nTime: $timeString\nLocation: $fullAddress\n\nPayment Details:\nYour deposit is now due. The remaining balance is payable during the week of your event via direct deposit or Electronic Funds Transfer (EFT). Please note that our drivers do not accept payments.\n\nTotal Amount: $$totalAmount\nBalance Due: $" . number_format($balanceDue, 2) . "\n\nAll payments should be made to Big Fun. Please ensure your invoice number is quoted as the payment reference.\n\nIf you have any questions or require assistance, please feel free to contact us on 1800 244 386.\n\nThank you again for booking with us.\n\nKind regards,\nBIG FUN\n1800 244 386";
            $this->email_attachment = "BigFunInvoice-{$booking->id}.pdf";
        } elseif ($type === 'receipt') {
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
            $daysPast = $eventMidnight->isPast() ? max(0, (int)$todayMidnight->diffInDays($eventMidnight)) : 0;
            $this->email_subject = "Outstanding Balance Reminder - Booking #{$booking->id}";
            $this->email_body = "Hello $fName,\n\nThis is a friendly reminder that your event on $eventDate is currently $daysPast days past due with an outstanding balance of $" . number_format($balanceDue, 2) . ".\n\nPlease find attached the debt reminder invoice which provides an overview of your booking and the outstanding amount.\n\nAll payments should be made to Big Fun quoting your invoice number as the payment reference.\n\nPlease contact us on 1800 244 386 if you wish to discuss this account.\n\nKind regards,\nBIG FUN\n1800 244 386";
            $this->email_attachment = "BigFunDebt-{$booking->id}.pdf";
        }

        $this->dispatch('close-modal', 'confirmEmailModal');
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
            if (($result['error_code'] ?? null) === 'quota_reached' && isset($result['quota'])) {
                $quota = $result['quota'];
                $this->quotaLimitTitle = 'Daily Email Quota Reached';
                $this->quotaLimitMessage = "{$quota['label']} has reached {$quota['used']}/{$quota['limit']} for today. Please switch mailer or wait for reset.";
                $this->dispatch('open-modal', 'quotaLimitModal');
            } else {
                $this->dispatch('notify', title: 'Error', message: $result['message']);
            }
        }
    }

    public function resetEmailState()
    {
        $this->is_sent_successfully = false;
    }

    public function render()
    {
        $enquiriesCount = Booking::where('status', 'Pending')->count();

        // 1. Pending Payments (Still owe money)
        $paymentsQuery = Booking::with('payments')
            ->whereIn('status', ['Pending', 'Confirmed'])
            ->where(function ($q) {
                $q->whereColumn('amount_paid', '<', 'total_amount')
                    ->orWhere('owing_amount', '>', 0.01);
            });

        if ($this->search_pay) {
            $paymentsQuery->where(function ($q) {
                $q->where('id', 'like', "%{$this->search_pay}%")
                    ->orWhere('invoice_number', 'like', "%{$this->search_pay}%")
                    ->orWhere('customer_first_name', 'like', "%{$this->search_pay}%")
                    ->orWhere('customer_last_name', 'like', "%{$this->search_pay}%")
                    ->orWhere('customer_organization', 'like', "%{$this->search_pay}%");
            });
        }
        $pendingPayments = $paymentsQuery->orderBy('id', $this->sort_pay)->paginate(5, ['*'], 'page_pay');

        // 2. Fully Paid Bookings (All settled bookings, including completed)
        $fullyPaidQuery = Booking::with('payments')
            ->whereIn('status', ['Pending', 'Confirmed', 'Completed'])
            ->where('total_amount', '>', 0)
            ->whereColumn('amount_paid', '>=', 'total_amount');

        if ($this->search_full) {
            $fullyPaidQuery->where(function ($q) {
                $q->where('id', 'like', "%{$this->search_full}%")
                    ->orWhere('invoice_number', 'like', "%{$this->search_full}%")
                    ->orWhere('customer_first_name', 'like', "%{$this->search_full}%")
                    ->orWhere('customer_last_name', 'like', "%{$this->search_full}%")
                    ->orWhere('customer_organization', 'like', "%{$this->search_full}%");
            });
        }
        $fullyPaidBookings = $fullyPaidQuery->orderBy('event_date', $this->sort_full)->paginate(5, ['*'], 'page_full');

        $invoicesQuery = Booking::where('invoice_emailed', 0)->whereNotIn('status', ['Cancelled', 'Deleted']);
        if ($this->search_inv) {
            $invoicesQuery->where(function ($q) {
                $q->where('id', 'like', "%{$this->search_inv}%")
                    ->orWhere('invoice_number', 'like', "%{$this->search_inv}%")
                    ->orWhere('customer_first_name', 'like', "%{$this->search_inv}%")
                    ->orWhere('customer_last_name', 'like', "%{$this->search_inv}%");
            });
        }
        $invoices = $invoicesQuery->orderBy('event_date', $this->sort_inv)->paginate(5, ['*'], 'page_inv');

        $ordersQuery = Booking::where('event_date', '>=', now()->toDateString())->where('status', 'Confirmed');
        if ($this->search_ord) {
            $ordersQuery->where(function ($q) {
                $q->where('id', 'like', "%{$this->search_ord}%")
                    ->orWhere('invoice_number', 'like', "%{$this->search_ord}%")
                    ->orWhere('customer_first_name', 'like', "%{$this->search_ord}%")
                    ->orWhere('customer_last_name', 'like', "%{$this->search_ord}%");
            });
        }
        $orders = $ordersQuery->orderBy('event_date', 'asc')->paginate(5, ['*'], 'page_ord');

        $debtorsQuery = Booking::with('payments')
            ->where('status', 'Completed')
            ->whereColumn('total_amount', '>', 'amount_paid')
            ->where('event_date', '<', now()->toDateString());
        if ($this->search_deb) {
            $debtorsQuery->where(function ($q) {
                $q->where('id', 'like', "%{$this->search_deb}%")
                    ->orWhere('invoice_number', 'like', "%{$this->search_deb}%")
                    ->orWhere('customer_first_name', 'like', "%{$this->search_deb}%")
                    ->orWhere('customer_last_name', 'like', "%{$this->search_deb}%");
            });
        }
        $debtors = $debtorsQuery->orderBy('event_date', $this->sort_deb)->paginate(5, ['*'], 'page_deb');

        $operatorsQuery = User::whereIn('role', ['Operator', 'Staff']);
        if ($this->search_op) {
            $operatorsQuery->where(function ($q) {
                $q->where('first_name', 'like', "%{$this->search_op}%")
                    ->orWhere('last_name', 'like', "%{$this->search_op}%")
                    ->orWhere('email', 'like', "%{$this->search_op}%");
            });
        }
        $operators = $operatorsQuery->paginate(5, ['*'], 'page_op');

        return view('livewire.supervisor.logistics-inbox', [
            'enquiriesCount' => $enquiriesCount,
            'pendingPayments' => $pendingPayments,
            'fullyPaidBookings' => $fullyPaidBookings,
            'invoices' => $invoices,
            'orders' => $orders,
            'debtors' => $debtors,
            'operators' => $operators,
        ]);
    }
}
