<?php

namespace App\Livewire\Supervisor;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use App\Services\EmailQuotaService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

#[Layout('components.layouts.plain')]
class BookingOverview extends Component
{
    public Booking $booking;

    // --- General Properties ---
    public ?string $newStatus = null;
    public ?string $newDate = null;

    // Payment Logic
    public int|float|string|null $payAmount = null;
    public string $payType = '';
    public string $payMethod = '';
    public string $eftMethod = 'Standard EFT';
    public string $payCardHolder = '';
    public string $cardNumber = '';
    public string $cardExpiry = '';
    public string $cardCvv = '';
    public string $cardCategory = 'Personal';
    public string $cardNetwork = 'Visa';
    public string $payDate = '';
    public string $payRef = '';
    public string $payNotes = '';

    // Email UI
    public string $emailType = '';
    public string $emailTo = '';
    public string $emailCc = '';
    public string $emailBcc = '';
    public string $emailSubject = '';
    public string $emailBody = '';
    public string $emailAttachment = '';
    public string $emailFrom = 'accounts@bigfun.com.au';
    public bool $isSentSuccessfully = false;

    // Email Confirmation Modals
    public string $confirmEmailMessage = '';
    public string $confirmEmailTitle = '';
    public string $pendingEmailType = '';
    public string $quotaWarningTitle = '';
    public string $quotaWarningMessage = '';
    public string $quotaLimitTitle = '';
    public string $quotaLimitMessage = '';

    // Calendar
    public int $calMonth;
    public int $calYear;
    public array $calDays = [];
    public ?string $tempSelectedDate = null;
    public array $bookedAttractions = [];
    public array $dailyAttractions = [];
    public array $categoryLimits = [];
    public array $dailyUsage = [];
    public array $bookingImpact = [];
    public array $modalNameConflicts = [];
    public ?string $lastToastDate = null;

    public ?array $previewExtras = null;
    public ?array $previewSelectedItems = null;
    public ?array $previewTotals = null;

    // Edit Payment
    public ?BookingPayment $selectedPayment = null;
    public bool $is_editing_payment = false;
    public int|float|string|null $edit_payment_amount = null;
    public string $edit_payment_method = '';
    public string $edit_payment_date = '';
    public string $edit_payment_ref = '';
    public string $edit_payment_notes = '';
    public int|string|null $selectedLogToDelete = null;

    public int|string|null $deleteConfirmId = null;
    public string $backUrl = '';

    public function mount(int|string $id)
    {
        $this->booking = Booking::findOrFail($id);
        $this->newStatus = $this->booking->status;
        $this->newDate = Carbon::parse($this->booking->event_date)->format('Y-m-d');
        $this->payDate = now()->format('Y-m-d');

        $this->calMonth = Carbon::parse($this->newDate)->month;
        $this->calYear = Carbon::parse($this->newDate)->year;

        // Dynamic Back Redirect Logic - Priority: 1. 'back' query param, 2. previous, 3. calendar
        $backParam = request()->query('back');
        $prev = url()->previous();

        if ($backParam) {
            $this->backUrl = $backParam;
        } elseif ($prev && !str_contains($prev, '/overview') && !str_contains($prev, '/edit')) {
            $this->backUrl = $prev;
        } else {
            $this->backUrl = route('supervisor.calendar');
        }
    }

    // --- Core Updates ---
    public function updateStatus()
    {
        // 1. COMPLETION VALIDATION (Strict) - Check this FIRST to avoid bypasses
        if ($this->newStatus === 'Completed') {
            $eventDate = Carbon::parse($this->booking->event_date)->startOfDay();
            $today = now()->startOfDay();

            // Date Check (Future events cannot be completed)
            if ($eventDate->isAfter($today)) {
                $this->dispatch('open-modal', 'futureCompleteModal');
                $this->newStatus = $this->booking->status;
                return;
            }

            // State Check (Hold/Cancelled -> Completed is forbidden, must go to Confirmed first)
            if (in_array($this->booking->status, ['Cancelled', 'Hold'])) {
                $this->dispatch('open-modal', 'restrictedStatusModal');
                $this->newStatus = $this->booking->status;
                return;
            }

            $this->dispatch('open-modal', 'completeStatusConfirmModal');
            return;
        }

        // 2. DRAFT EXIT CHECK
        if ($this->booking->status === 'Draft' && $this->newStatus !== 'Draft') {
            $this->dispatch('open-modal', 'draftModal');
            return;
        }

        // 3. CANCELLATION TOGGLE CHECK
        if (
            ($this->booking->status !== 'Cancelled' && $this->newStatus === 'Cancelled') ||
            ($this->booking->status === 'Cancelled' && $this->newStatus !== 'Cancelled')
        ) {
            $this->dispatch('open-modal', 'statusConfirmModal');
            return;
        }

        $this->executeStatusUpdate();
    }

    public function executeStatusUpdate()
    {
        $this->booking->update(['status' => $this->newStatus]);
        $this->booking->refresh();

        // Sync to Google Sheet
        app(\App\Services\GoogleSheetService::class)->sync($this->booking->id);

        $this->dispatch('close-modal', 'draftModal');
        $this->dispatch('close-modal', 'statusConfirmModal');
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

        // Sync to Google Sheet
        app(\App\Services\GoogleSheetService::class)->sync($this->booking->id);

        $this->dispatch('notify', title: 'Success', message: 'Terms agreement updated.');
    }

    public function toggleAttractionCost()
    {
        $this->booking->include_attraction_cost = !$this->booking->include_attraction_cost;
        $this->booking->save();
        $this->dispatch('notify', title: 'Success', message: 'Attraction costing display updated.');
    }

    public function moveDate()
    {
        // 1. ATTRACTION CONFLICT CHECK
        $currentItems = BookingItem::where('booking_id', $this->booking->id)
            ->where('is_custom', 0)
            ->pluck('item_name')
            ->toArray();

        // Find conflicts on the new date
        $conflicts = DB::table('booking_items')
            ->join('bookings', 'booking_items.booking_id', '=', 'bookings.id')
            ->where('bookings.event_date', $this->newDate)
            ->where('bookings.id', '!=', $this->booking->id)
            ->whereNotIn('bookings.status', ['Cancelled'])
            ->whereIn('booking_items.item_name', $currentItems)
            ->pluck('booking_items.item_name')
            ->unique()
            ->toArray();

        if (!empty($conflicts)) {
            $this->dispatch(
                'notify',
                title: 'Move Blocked',
                message: 'Conflict detected: ' . implode(', ', $conflicts) . ' already booked for this day.',
                type: 'error'
            );
            return;
        }

        // 2. CATEGORY LIMIT CHECK
        $impact = DB::table('booking_items')
            ->join('products', 'booking_items.item_name', '=', 'products.name')
            ->where('booking_items.booking_id', $this->booking->id)
            ->where('booking_items.is_custom', 0)
            ->selectRaw('COALESCE(NULLIF(products.counts_against, ""), products.category) as cat, SUM(booking_items.qty) as total')
            ->groupBy('cat')
            ->pluck('total', 'cat')
            ->toArray();

        $usage = DB::table('booking_items')
            ->join('bookings', 'booking_items.booking_id', '=', 'bookings.id')
            ->join('products', 'booking_items.item_name', '=', 'products.name')
            ->where('bookings.event_date', $this->newDate)
            ->whereNotIn('bookings.status', ['Cancelled'])
            ->where('bookings.id', '!=', $this->booking->id)
            ->where('booking_items.is_custom', 0)
            ->selectRaw('COALESCE(NULLIF(products.counts_against, ""), products.category) as cat, SUM(booking_items.qty) as total')
            ->groupBy('cat')
            ->pluck('total', 'cat')
            ->toArray();

        $limits = DB::table('product_categories')
            ->where('daily_limit', '>', 0)
            ->pluck('daily_limit', 'category_name')
            ->toArray();

        foreach ($impact as $cat => $qty) {
            $limit = $limits[$cat] ?? 0;
            if ($limit > 0) {
                $current = $usage[$cat] ?? 0;
                if ($current + $qty > $limit) {
                    $this->dispatch(
                        'notify',
                        title: 'Move Blocked',
                        message: "The daily limit for '{$cat}' has been reached on this date.",
                        type: 'error'
                    );
                    return;
                }
            }
        }

        // Capture original date on FIRST move only
        if (empty($this->booking->original_event_date)) {
            $this->booking->original_event_date = $this->booking->getOriginal('event_date') ?? $this->booking->event_date;
        }

        $this->booking->event_date = $this->newDate;
        $this->booking->save();

        // Refresh financials (e.g., check if it's still 'Overdue' on the new date)
        $this->booking->syncFinancials();

        // Sync to Google Sheet (Moving event date)
        app(\App\Services\GoogleSheetService::class)->sync($this->booking->id);

        $this->dispatch('notify', title: 'Success', message: 'Booking moved to ' . $this->newDate);
    }

    public function deleteBooking()
    {
        // 1. Mark status as 'Deleted' in database for the sync payload
        // (This status matches the logic in GoogleSheetService to set is_deleted='YES')
        $this->booking->update(['status' => 'Deleted']);

        // 2. Sync to Google Sheets BEFORE database deletion
        app(\App\Services\GoogleSheetService::class)->sync($this->booking->id);

        // 3. Final deletion from database
        $this->booking->delete();

        return redirect()->to('/supervisor/calendar');
    }

    public function manualSync()
    {
        app(\App\Services\GoogleSheetService::class)->sync($this->booking->id);
        $this->dispatch('notify', title: 'Cloud Sync', message: 'Booking data synchronized to Google Sheets.');
    }

    // --- Payment Logic ---
    public function openPaymentModal()
    {
        $amountPaid = round(BookingPayment::where('booking_id', $this->booking->id)->sum('amount'), 2);
        $totalAmount = round((float) $this->booking->total_amount, 2);
        $balanceDue = round(max(0, $totalAmount - $amountPaid), 2);
        $depositReq = round($this->booking->deposit_required > 0 ? $this->booking->deposit_required : ($totalAmount / 2), 2);

        if ($balanceDue <= 0) {
            $this->payType = 'Final Settlement';
            $this->payAmount = 0;
        } elseif ($amountPaid == 0 && $balanceDue >= $depositReq) {
            $this->payType = 'Deposit Capture';
            $this->payAmount = $depositReq;
        } else {
            $this->payType = 'Final Settlement';
            $this->payAmount = $balanceDue;
        }

        $this->payMethod = match ($this->booking->payment_type) {
            'Card Holder', 'credit_card' => 'Card Holder',
            'Cash' => 'Cash',
            default => 'EFT',
        };

        $this->payCardHolder = $this->booking->card_holder ?? ($this->booking->customer_first_name . ' ' . $this->booking->customer_last_name);

        $this->reset(['payRef', 'payNotes']);
        $this->dispatch('open-modal', 'paymentModal');
    }

    public function updatedPayType(string|float|int|null $value)
    {
        $amountPaid = round(BookingPayment::where('booking_id', $this->booking->id)->sum('amount'), 2);
        $totalAmount = round((float) $this->booking->total_amount, 2);
        $balanceDue = round(max(0, $totalAmount - $amountPaid), 2);
        $depositReq = round($this->booking->deposit_required > 0 ? $this->booking->deposit_required : ($totalAmount / 2), 2);

        if ($value === 'Deposit Capture') {
            $this->payAmount = round(min($depositReq, $balanceDue), 2);
        } elseif ($value === 'Final Settlement') {
            $this->payAmount = round($balanceDue, 2);
        } elseif ($value === 'Total Liquidation') {
            $this->payAmount = round($balanceDue, 2);
        }
        // Partial Allocation keeps current amount
    }

    public function savePayment()
    {
        $amountPaid = BookingPayment::where('booking_id', $this->booking->id)->sum('amount');
        $totalAmount = (float) $this->booking->total_amount;
        $balanceDue = max(0, $totalAmount - $amountPaid);

        // Smart Safeguard: Avoid exceeding pay
        if ($this->payAmount > $balanceDue && $this->payType !== 'Partial Allocation') {
            $this->payAmount = $balanceDue;
        }

        $this->validate([
            'payAmount' => 'required|numeric|min:0.01',
            'payMethod' => 'required|string',
        ]);

        $combinedNotes = $this->payNotes;
        if ($this->payMethod === 'EFT') {
            $combinedNotes = "Method: {$this->eftMethod} | " . $combinedNotes;
        }

        BookingPayment::create([
            'booking_id' => $this->booking->id,
            'amount' => $this->payAmount,
            'payment_method' => $this->payMethod,
            'payment_type' => $this->payType,
            'payment_date' => $this->payDate,
            'reference' => $this->payRef,
            'notes' => $combinedNotes,
            'card_holder' => $this->payMethod === 'Card Holder' ? $this->payCardHolder : null,
            'card_number' => $this->payMethod === 'Card Holder' ? $this->cardNumber : null,
            'card_expiry' => $this->payMethod === 'Card Holder' ? $this->cardExpiry : null,
            'card_cvv' => $this->payMethod === 'Card Holder' ? $this->cardCvv : null,
            'card_network' => $this->payMethod === 'Card Holder' ? $this->cardNetwork : null,
        ]);

        if ($this->payMethod === 'Card Holder') {
            $this->booking->update([
                'card_holder' => $this->payCardHolder,
                'card_category' => $this->cardCategory,
                'card_type' => $this->cardNetwork,
                'card_number' => $this->cardNumber,
                'card_expiry' => $this->cardExpiry,
                'card_cvv' => $this->cardCvv,
            ]);
        }

        // RE-CALCULATE AND UPDATE CACHED COLUMNS
        $this->booking->syncFinancials();

        // Sync to Google Sheet (Update debt info)
        app(\App\Services\GoogleSheetService::class)->sync($this->booking->id);

        $this->reset(['payAmount', 'payRef', 'payNotes']);
        $this->dispatch('close-modal', 'paymentModal');
        $this->dispatch('notify', title: 'Success', message: 'Payment recorded.');
    }

    public function selectPayment(int|string $id)
    {
        $this->selectedPayment = BookingPayment::find($id);
        $this->is_editing_payment = false;
        $this->dispatch('open-modal', 'paymentDetailsModal');
    }

    public function editPaymentDetails()
    {
        if (!$this->selectedPayment) return;

        $this->edit_payment_amount = $this->selectedPayment->amount;
        $this->edit_payment_method = $this->selectedPayment->payment_method;
        $this->edit_payment_date = $this->selectedPayment->payment_date ? Carbon::parse($this->selectedPayment->payment_date)->format('Y-m-d') : date('Y-m-d');
        $this->edit_payment_ref = $this->selectedPayment->reference;
        $this->edit_payment_notes = $this->selectedPayment->notes;

        $this->is_editing_payment = true;
    }

    public function cancelPaymentEdit()
    {
        $this->is_editing_payment = false;
    }

    public function updatePaymentDetails()
    {
        if (!$this->selectedPayment) return;

        $this->validate([
            'edit_payment_amount' => 'required|numeric|min:0.01',
            'edit_payment_method' => 'required|string',
            'edit_payment_date' => 'required|date',
        ]);

        $this->selectedPayment->update([
            'amount' => $this->edit_payment_amount,
            'payment_method' => $this->edit_payment_method,
            'payment_date' => $this->edit_payment_date,
            'reference' => $this->edit_payment_ref,
            'notes' => $this->edit_payment_notes,
        ]);

        // Sync financials for the associated booking
        $this->booking->syncFinancials();
        app(\App\Services\GoogleSheetService::class)->sync($this->booking->id);

        $this->is_editing_payment = false;
        $this->selectedPayment->refresh();
        $this->dispatch('notify', title: 'Updated', message: 'Financial record has been corrected.');
    }

    // --- Email Logic ---
    public function openEmailModal(string $type)
    {
        if ($this->handleQuotaGuardForEmail($type)) {
            return;
        }

        $payments = BookingPayment::where('booking_id', $this->booking->id)->get();
        $amountPaid = $payments->sum('amount');
        $totalAmount = (float) $this->booking->total_amount;
        $balanceDue = max(0, $totalAmount - $amountPaid);
        $paymentsCount = $payments->count();

        $lastSentAt = DB::table('email_logs')
            ->where('booking_id', $this->booking->id)
            ->where('type', $type)
            ->max('sent_at'); // String timestamp

        $lastPaymentAt = BookingPayment::where('booking_id', $this->booking->id)->max('payment_date');

        $hasHistory = !empty($lastSentAt);
        $newPaymentMade = $hasHistory && (!empty($lastPaymentAt) && Carbon::parse($lastPaymentAt)->isAfter(Carbon::parse($lastSentAt)));

        $warnings = [];

        // 1. Smart Debt Warning (Even for first send)
        $isOverdue = Carbon::parse($this->booking->event_date)->startOfDay()->isBefore(now()->startOfDay());
        $isCompleted = $this->booking->status === 'Completed';
        $hasDebt = $balanceDue > 0 && ($isOverdue || $isCompleted);

        if ($hasDebt && in_array($type, ['receipt', 'invoice', 'po'])) {
            $warnings[] = "Outstanding debt detected: <strong>$" . number_format($balanceDue, 2) . "</strong>.<br>Are you sure this has debt and you wish to proceed?";
        }

        // 2. Resend Warning (Only if no new payments since last send)
        if ($hasHistory && !$newPaymentMade) {
            if ($type === 'receipt') {
                $warnings[] = "A receipt has already been sent for the existing payments.";
            } else {
                $prefix = ($type === 'invoice') ? 'An' : 'A';
                $typeName = match ($type) {
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
            $this->dispatch('open-modal', 'confirmEmailModal');
            return;
        }

        $this->executeOpenEmailModal($type);
    }

    private function handleQuotaGuardForEmail(string $type): bool
    {
        $quota = app(EmailQuotaService::class)->statusForDefaultMailer();

        if ($quota['is_limit_reached']) {
            $this->quotaLimitTitle = 'Daily Email Quota Reached';
            $this->quotaLimitMessage = "{$quota['label']} has reached {$quota['used']}/{$quota['limit']} for today. Please switch mailer or wait for reset.";
            $this->dispatch('open-modal', 'quotaLimitModal');

            return true;
        }

        if ($quota['is_low']) {
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
        $type = $this->pendingEmailType ?: 'invoice';
        $this->dispatch('close-modal', 'quotaWarningModal');
        $this->executeOpenEmailModal($type);
    }

    public function deleteEmailLog(int|string|null $logId)
    {
        if ($logId) {
            \Illuminate\Support\Facades\DB::table('email_logs')->where('id', $logId)->delete();
            $this->dispatch('notify', title: 'Deleted', message: 'Email history log removed.');
        }
        $this->dispatch('close-modal', 'deleteSingleLogModal');
    }

    public function clearHistory()
    {
        \Illuminate\Support\Facades\DB::table('email_logs')->where('booking_id', $this->booking->id)->delete();
        $this->booking->update(['invoice_emailed' => false]);
        $this->dispatch('notify', title: 'Cleared', message: 'All email history has been deleted.');
        $this->dispatch('close-modal', 'historyClearModal');
    }

    public function deleteLegacyLog()
    {
        $this->booking->update(['invoice_emailed' => false]);
        $this->booking->refresh();
        $this->dispatch('notify', title: 'Deleted', message: 'Legacy invoice record removed.');
        $this->dispatch('close-modal', 'deleteLegacyModal');
    }

    public function sendInvoiceEmail()
    {
        $this->proceedWithEmail();
    }

    public function proceedWithEmail()
    {
        $this->dispatch('close-modal', 'confirmEmailModal');
        $this->executeOpenEmailModal($this->pendingEmailType);
    }

    private function executeOpenEmailModal(string $type)
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
        } elseif ($type === 'debt') {
            // Debt Reminder
            $eventMidnight = Carbon::parse($this->booking->event_date)->startOfDay();
            $todayMidnight = now()->startOfDay();
            $daysPast = $eventMidnight->isPast() ? max(0, (int)$todayMidnight->diffInDays($eventMidnight)) : 0;
            $this->emailSubject = "Outstanding Balance Reminder - Booking #{$this->booking->id}";
            $this->emailBody = "Hello $fName,\n\nThis is a friendly reminder that your event on $eventDate is currently $daysPast days past due with an outstanding balance of $" . number_format($balanceDue, 2) . ".\n\nPlease find attached the debt reminder invoice which provides an overview of your booking and the outstanding amount.\n\nAll payments should be made to Big Fun quoting your invoice number as the payment reference.\n\nPlease contact us on 1800 244 386 if you wish to discuss this account.\n\nKind regards,\nBIG FUN\n1800 244 386";
            $this->emailAttachment = "BigFunDebt-{$this->booking->id}.pdf";
        } else {
            // Big Fun Invoice (Paperwork/Deposit)
            $this->emailSubject = "Big Fun Invoice - $invNum";
            $this->emailBody = "Hello,\n\nPlease find attached the paperwork for your booking on $eventDate. Kindly review the document to ensure all contact and delivery details are correct, then sign and return the form to us via email.\n\nBooking Details:\nDate: $eventDate\nTime: $timeString\nLocation: $fullAddress\n\nPayment Details:\nYour deposit is now due. The remaining balance is payable during the week of your event via direct deposit or Electronic Funds Transfer (EFT). Please note that our drivers do not accept payments.\n\nTotal Amount: $" . number_format($totalAmount, 2) . "\nBalance Due: $" . number_format($balanceDue, 2) . "\n\nAll payments should be made to Big Fun. Please ensure your invoice number is quoted as the payment reference.\n\nIf you have any questions or require assistance, please feel free to contact us on 1800 244 386.\n\nThank you again for booking with us.\n\nKind regards,\nBIG FUN\n1800 244 386";
            $this->emailAttachment = "BigFunInvoice-{$this->booking->id}.pdf";
        }

        $this->dispatch('open-modal', 'emailModal');
    }

    public function sendEmail(\App\Services\MailService $mailService)
    {
        $result = $mailService->sendEmail($this->booking->id, [
            'email_from' => $this->emailFrom,
            'email_to' => $this->emailTo,
            'email_cc' => $this->emailCc,
            'email_bcc' => $this->emailBcc,
            'email_subject' => $this->emailSubject,
            'email_body' => $this->emailBody,
            'email_type' => $this->emailType,
            'attachments' => [$this->emailAttachment]
        ]);

        if ($result['success']) {
            $this->isSentSuccessfully = true;
            $this->dispatch('close-modal', 'emailModal');
            $this->dispatch('open-modal', 'sentSuccessModal');

            // Re-fetch email logs to show the new one
            $this->booking->refresh();
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
        $this->isSentSuccessfully = false;
    }

    // --- Live Calendar Logic ---
    public function openCalendarModal()
    {
        $this->tempSelectedDate = $this->booking->event_date;

        // 1. Current Booking Attractions
        $this->bookedAttractions = BookingItem::where('booking_id', $this->booking->id)
            ->where('is_custom', 0)
            ->pluck('item_name')
            ->unique()
            ->toArray();

        // 2. Category Limits
        $this->categoryLimits = DB::table('product_categories')
            ->where('daily_limit', '>', 0)
            ->pluck('daily_limit', 'category_name')
            ->toArray();

        // 3. Current Booking Impact per Category
        $impactSub = DB::table('booking_items')
            ->join('products', 'booking_items.item_name', '=', 'products.name')
            ->where('booking_items.booking_id', $this->booking->id)
            ->where('booking_items.is_custom', 0)
            ->selectRaw('COALESCE(NULLIF(products.counts_against, ""), products.category) as cat, booking_items.qty');

        $this->bookingImpact = DB::table($impactSub, 't')
            ->selectRaw('cat, SUM(qty) as total')
            ->groupBy('cat')
            ->pluck('total', 'cat')
            ->toArray();

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

        // Get daily counts
        $counts = Booking::whereBetween('event_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->whereNotIn('status', ['Cancelled'])
            ->selectRaw('event_date, COUNT(*) as cnt')
            ->groupBy('event_date')
            ->pluck('cnt', 'event_date')
            ->toArray();

        // Get daily attractions names for conflict dots/warnings
        $attractions = DB::table('booking_items')
            ->join('bookings', 'booking_items.booking_id', '=', 'bookings.id')
            ->whereBetween('bookings.event_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->whereNotIn('bookings.status', ['Cancelled'])
            ->where('bookings.id', '!=', $this->booking->id) // Exclude current booking
            ->where('booking_items.is_custom', 0)
            ->select(['bookings.event_date', 'booking_items.item_name'])
            ->get()
            ->groupBy('event_date')
            ->map(fn($group) => $group->pluck('item_name')->unique()->values()->toArray())
            ->toArray();

        $this->dailyAttractions = $attractions;

        // Get daily category usage using subquery for SQL compatibility
        $usageSub = DB::table('booking_items')
            ->join('bookings', 'booking_items.booking_id', '=', 'bookings.id')
            ->join('products', 'booking_items.item_name', '=', 'products.name')
            ->whereBetween('bookings.event_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->whereNotIn('bookings.status', ['Cancelled'])
            ->where('bookings.id', '!=', $this->booking->id)
            ->where('booking_items.is_custom', 0)
            ->selectRaw('bookings.event_date, COALESCE(NULLIF(products.counts_against, ""), products.category) as cat, booking_items.qty');

        $usage = DB::table($usageSub, 't')
            ->selectRaw('event_date, cat, SUM(qty) as total')
            ->groupBy('event_date', 'cat')
            ->get()
            ->groupBy('event_date')
            ->map(fn($group) => $group->pluck('total', 'cat')->toArray())
            ->toArray();

        $this->dailyUsage = $usage;

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
            $dayItems = $attractions[$dateStr] ?? [];
            $dayUsage = $usage[$dateStr] ?? [];

            // Check if ANY category in this day is over capacity for the current booking
            $hasCapBreach = false;
            foreach ($this->bookingImpact as $cat => $qty) {
                $limit = $this->categoryLimits[$cat] ?? 0;
                if ($limit > 0) {
                    $current = $dayUsage[$cat] ?? 0;
                    if ($current + $qty > $limit) {
                        $hasCapBreach = true;
                        break;
                    }
                }
            }

            $this->calDays[] = [
                'date' => $dateStr,
                'day' => $day,
                'left' => max(0, $dailyLimit - $used),
                'items' => $dayItems,
                'usage' => $dayUsage,
                'breach' => $hasCapBreach
            ];
        }
    }

    public function applySelectedDate()
    {
        if ($this->tempSelectedDate) {
            // 1. Conflict Check
            $dayItems = $this->dailyAttractions[$this->tempSelectedDate] ?? [];
            $conflicts = array_intersect($this->bookedAttractions, $dayItems);

            if (!empty($conflicts)) {
                $this->dispatch('notify', title: 'Invalid Selection', message: 'You cannot move to this date due to attraction conflicts.', type: 'error');
                return;
            }

            // 2. Category Limit Check
            $dayUsage = $this->dailyUsage[$this->tempSelectedDate] ?? [];
            foreach ($this->bookingImpact as $cat => $qty) {
                $limit = $this->categoryLimits[$cat] ?? 0;
                if ($limit > 0) {
                    $current = $dayUsage[$cat] ?? 0;
                    if ($current + $qty > $limit) {
                        $this->dispatch('notify', title: 'Category Limit', message: "Moving to this date exceeds the daily limit for {$cat}.", type: 'error');
                        return;
                    }
                }
            }

            $this->newDate = \Carbon\Carbon::parse($this->tempSelectedDate)->format('Y-m-d');
            $this->dispatch('close-modal', 'calendarModal');
        }
    }

    #[On('booking-preview-updated')]
    public function updatePreview(?array $extras, ?array $selectedItems, ?array $totals)
    {
        $this->previewExtras = $extras;
        $this->previewSelectedItems = $selectedItems;
        $this->previewTotals = $totals;
    }

    #[On('booking-updated')]
    public function render()
    {
        if ($this->previewSelectedItems !== null) {
            // Build virtual items collection from preview data
            $items = collect($this->previewSelectedItems)->map(function ($data, $name) {
                $product = DB::table('products')->whereRaw('LOWER(TRIM(name)) = ?', [$name])->first();
                return (object)[
                    'item_name' => $product ? $product->name : ucwords($name),
                    'total_qty' => $data['qty'],
                    'unit_price' => $data['price'] ?? ($product ? $product->price : 0),
                    'specification' => $product ? $product->specification : '',
                    'category' => $product ? $product->category : 'Custom',
                    'is_custom' => $product ? 0 : 1
                ];
            });
        } else {
            $items = BookingItem::where('booking_id', $this->booking->id)
                ->leftJoin('products', function ($join) {
                    $join->on('booking_items.item_name', '=', 'products.name')
                        ->where('booking_items.is_custom', '=', 0);
                })
                ->selectRaw('booking_items.item_name, booking_items.is_custom, SUM(booking_items.qty) as total_qty, products.specification, COALESCE(NULLIF(booking_items.item_price, 0), products.price, 0) as unit_price, products.category')
                ->groupBy('booking_items.item_name', 'booking_items.is_custom', 'products.specification', 'booking_items.item_price', 'products.price', 'products.category')
                ->get();
        }

        $payments = BookingPayment::where('booking_id', $this->booking->id)->orderBy('payment_date', 'asc')->get();
        $emailLogs = DB::table('email_logs')->where('booking_id', $this->booking->id)->orderBy('sent_at', 'desc')->get();

        $modalConflicts = [];
        $modalCapacityBreaches = [];
        $this->modalNameConflicts = [];

        if ($this->tempSelectedDate) {
            // Attraction Conflict
            $dayItems = $this->dailyAttractions[$this->tempSelectedDate] ?? [];
            $modalConflicts = array_intersect($this->bookedAttractions, $dayItems);

            // Capacity Breach
            $dayUsage = $this->dailyUsage[$this->tempSelectedDate] ?? [];
            foreach ($this->bookingImpact as $cat => $qty) {
                $limit = $this->categoryLimits[$cat] ?? 0;
                if ($limit > 0) {
                    $current = $dayUsage[$cat] ?? 0;
                    if ($current + $qty > $limit) {
                        $modalCapacityBreaches[$cat] = [
                            'current' => $current,
                            'added' => $qty,
                            'limit' => $limit
                        ];
                    }
                }
            }

            // Name Duplicate Check
            $this->modalNameConflicts = Booking::where('customer_first_name', $this->booking->customer_first_name)
                ->where('customer_last_name', $this->booking->customer_last_name)
                ->where('event_date', $this->tempSelectedDate)
                ->where('id', '!=', $this->booking->id)
                ->whereNotIn('status', ['Cancelled'])
                ->get()
                ->toArray();

            // Notify if newly discovered
            if (!empty($this->modalNameConflicts) && $this->lastToastDate !== $this->tempSelectedDate) {
                $this->dispatch(
                    'notify',
                    title: 'Duplicate Contact Detected',
                    message: "This customer already has a booking on " . \Carbon\Carbon::parse($this->tempSelectedDate)->format('d M Y'),
                    type: 'warning'
                );
                $this->lastToastDate = $this->tempSelectedDate;
            }
        }

        $amountPaid = $payments->sum('amount');
        $totalAmount = $this->previewTotals['total'] ?? (float) $this->booking->total_amount;
        $balanceDue = max(0, $totalAmount - $amountPaid);
        $depositReq = ($this->previewTotals['total'] ?? 0) > 0 ? ($this->previewTotals['total'] / 2) : ($this->booking->deposit_required > 0 ? $this->booking->deposit_required : ($totalAmount / 2));

        // Check if Debt Indicators should be active (Must have balance AND be in the past)
        $isPastDate = Carbon::parse($this->booking->event_date)->startOfDay()->isBefore(now()->startOfDay());
        $isDebt = $balanceDue > 0 && $isPastDate;

        $extrasList = array_merge(
            json_decode($this->booking->general_extra ?? '[]', true) ?? [],
            json_decode($this->booking->specific_extra ?? '[]', true) ?? []
        );
        $calculatedExtrasTotal = $this->previewTotals['extras'] ?? (empty($extrasList) ? ($this->booking->extra_logistics_cost ?? 0) : array_sum($extrasList));

        $isCard = in_array($this->booking->payment_type, ['credit_card', 'Card Holder']);
        $baseAmount = $this->previewTotals['subtotal'] ?? $totalAmount;
        $surcharge = $this->previewTotals['surcharge'] ?? 0;

        if ($this->previewTotals === null && $isCard && $totalAmount > 0) {
            $baseAmount = $totalAmount / 1.029;
            $surcharge = $totalAmount - $baseAmount;
        }

        $deliveryCost = $this->previewTotals['delivery'] ?? (($this->booking->delivery_cost ?: $this->booking->delivery_fee) ?: 0);
        $ridesCost = $this->previewTotals['attractions'] ?? max(0, $baseAmount - $calculatedExtrasTotal - $deliveryCost);

        $statusColor = match ($this->booking->status) {
            'Completed' => 'bg-green-100 text-green-700 border-green-200',
            'Cancelled' => 'bg-red-100 text-red-700 border-red-200',
            'Hold'      => 'bg-yellow-100 text-yellow-700 border-yellow-200',
            'Draft'     => 'bg-orange-100 text-orange-700 border-orange-200',
            default     => 'bg-plum/10 text-[#9D686E] border-[#9D686E]/20',
        };

        $activeCategories = ['General Logistics'];
        foreach ($items as $item) {
            if ($item->category) {
                $activeCategories[] = $item->category;
            }
        }
        $activeCategories = array_unique($activeCategories);

        // Create a lookup for prices from actual booking items to ensure consistency
        $itemPriceLookup = $items->pluck('unit_price', 'item_name')->mapWithKeys(fn($p, $n) => [strtolower(trim($n)) => $p])->toArray();

        $duration_options = DB::table('duration_prices')->orderBy('hours', 'asc')->get();
        $delivery_options = DB::table('delivery_zones')->orderBy('price', 'asc')->get();

        $config = [
            'addons' => DB::table('category_addons')->orderBy('category_target')->get()->groupBy('category_target')->map(function ($g) use ($extrasList, $itemPriceLookup) {
                // Lowercase keys for case-insensitive lookup
                $extrasListLower = array_combine(
                    array_map(fn($k) => strtolower(trim($k)), array_keys($extrasList)),
                    array_values($extrasList)
                );

                return $g->map(function ($v) use ($extrasListLower, $itemPriceLookup) {
                    $arr = (array)$v;
                    $label = strtolower(trim($arr['addon_label'] ?? ''));
                    $catLabel = strtolower(trim(($arr['category_target'] ?? '') . ': ' . ($arr['addon_label'] ?? '')));

                    // Priority: 1. Actual Item Price (from BookingItem table), 2. Overridden Price (from JSON), 3. Default Price
                    $finalPrice = (float)($arr['addon_price'] ?? 0);
                    if (isset($itemPriceLookup[$label])) {
                        $finalPrice = (float)$itemPriceLookup[$label];
                    } elseif (isset($itemPriceLookup[$catLabel])) {
                        $finalPrice = (float)$itemPriceLookup[$catLabel];
                    } elseif (isset($extrasListLower[$label])) {
                        $finalPrice = (float)$extrasListLower[$label];
                    } elseif (isset($extrasListLower[$catLabel])) {
                        $finalPrice = (float)$extrasListLower[$catLabel];
                    }
                    $arr['addon_price'] = $finalPrice;

                    return $arr;
                })->toArray();
            })->toArray(),
            'questions' => DB::table('product_extras')->orderBy('category_target')->get()->groupBy('category_target')->map(function ($g) use ($extrasList, $itemPriceLookup) {
                return $g->map(function ($v) use ($extrasList, $itemPriceLookup) {
                    $arr = (array)$v;
                    $qText = strtolower(trim($arr['question_text']));

                    // Priority: 1. Actual Item Price, 2. Overridden Price (from JSON), 3. Default Price
                    if (isset($itemPriceLookup[$qText])) {
                        $arr['yes_price'] = (float)$itemPriceLookup[$qText];
                    } else {
                        // Search in extrasList for any key containing the question text
                        foreach ($extrasList as $label => $price) {
                            if (str_contains(strtolower(trim($label)), $qText)) {
                                $arr['yes_price'] = (float)$price;
                                break;
                            }
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
            $ddArray['options'] = $opts->map(function ($o) use ($extrasList, $dd, $itemPriceLookup) {
                $arr = (array)$o;
                $optLabel = strtolower(trim($arr['option_label']));
                $ddLabel = strtolower(trim($dd->label));
                $targetLabel = strtolower(trim($dd->category_target));

                // Priority: 1. Actual Item Price, 2. Overridden Price (from JSON), 3. Default Price
                if (isset($itemPriceLookup[$optLabel])) {
                    $arr['option_price'] = (float)$itemPriceLookup[$optLabel];
                } else {
                    foreach ($extrasList as $label => $price) {
                        $lowLabel = strtolower(trim($label));
                        if (str_contains($lowLabel, $optLabel) && (str_contains($lowLabel, $ddLabel) || str_contains($lowLabel, $targetLabel))) {
                            $arr['option_price'] = (float)$price;
                            break;
                        }
                    }
                }
                return $arr;
            })->toArray();
            $config['dropdowns'][$dd->category_target][] = $ddArray;
        }

        $selectedExtras = $this->previewExtras ?? json_decode($this->booking->extras_json ?? '[]', true) ?? [];

        // --- DATA RECOVERY & PARITY CHECK FOR OVERVIEW ---
        // ONLY run recovery if we are NOT in preview mode (to allow explicit unselection)
        if ($this->previewExtras === null) {
            $lowercaseItems = array_map(fn($it) => strtolower(trim($it->item_name)), $items->all());

            // 1. Sync Addons - Only if not explicitly '0'
            $addonsLookup = DB::table('category_addons')->get();
            foreach ($addonsLookup as $a) {
                $addonKey = 'add_' . $a->id;
                if (!isset($selectedExtras[$addonKey]) && in_array(strtolower(trim($a->addon_label)), $lowercaseItems)) {
                    $selectedExtras[$addonKey] = "1";
                } elseif (isset($selectedExtras[$addonKey]) && $selectedExtras[$addonKey] === "0") {
                    // Keep it removed if explicitly 0
                }
            }

            // 2. Sync Questions - Only if not explicitly 'no' or '0'
            $questionsLookup = DB::table('product_extras')->get();
            foreach ($questionsLookup as $q) {
                $qKey = 'q_' . $q->id;
                if (!isset($selectedExtras[$qKey]) && in_array(strtolower(trim($q->question_text)), $lowercaseItems)) {
                    $selectedExtras[$qKey] = $q->yes_price . '|yes';
                } elseif (isset($selectedExtras[$qKey]) && (str_ends_with($selectedExtras[$qKey], '|no') || $selectedExtras[$qKey] === "0")) {
                    // Keep it removed
                }
            }

            // 3. Sync Dropdowns - Only if not explicitly '0'
            $dropdownOptions = DB::table('dropdown_options')->get();
            foreach ($dropdownOptions as $opt) {
                $ddKey = 'dd_' . $opt->dropdown_id;
                if (!isset($selectedExtras[$ddKey]) && in_array(strtolower(trim($opt->option_label)), $lowercaseItems)) {
                    $selectedExtras[$ddKey] = $opt->id;
                } elseif (isset($selectedExtras[$ddKey]) && ($selectedExtras[$ddKey] === "0" || $selectedExtras[$ddKey] === "")) {
                    // Keep it removed
                }
            }
        }

        $rawStartTime = $this->booking->start_time;
        $rawEndTime = $this->booking->end_time;
        $isFullDay = ($rawStartTime === '00:00:00' && ($rawEndTime === '23:59:59' || $rawEndTime === '23:59:00' || $rawEndTime === '23:30:00'));

        if ($isFullDay && !empty($this->booking->duration) && !in_array($this->booking->duration, ['4 Hours', '7 Hours'])) {
            $timeString = 'Full Day (Duration Selected)';
        } else {
            $startTime = Carbon::parse($rawStartTime);
            $timeString = $startTime->format('g:i A');
            if (!empty($rawEndTime) && $rawEndTime != '00:00:00') {
                $timeString .= ' - ' . Carbon::parse($rawEndTime)->format('g:i A');
            }
        }


        $galleryFiles = collect([
            $this->booking->delivery_attachment,
            $this->booking->delivery_attachment_2,
            $this->booking->delivery_attachment_3,
            $this->booking->delivery_attachment_4,
            $this->booking->delivery_attachment_5
        ])->filter()->toArray();

        // --- FILTER DUPLICATES ---
        $allAddonLabels = DB::table('category_addons')->select(['addon_label', 'category_target'])->get()->flatMap(function ($a) {
            return [strtolower(trim($a->addon_label)), strtolower(trim($a->category_target . ': ' . $a->addon_label))];
        })->toArray();
        $allQuestionLabels = DB::table('product_extras')->pluck('question_text')->map(fn($l) => strtolower(trim($l)))->toArray();
        $allDropdownOptions = DB::table('dropdown_options as do')
            ->join('product_dropdowns as pd', 'do.dropdown_id', '=', 'pd.id')
            ->select(['do.option_label', 'pd.label as dd_label'])
            ->get()
            ->flatMap(function ($o) {
                return [strtolower(trim($o->option_label)), strtolower(trim($o->dd_label . ' - ' . $o->option_label))];
            })->toArray();

        $allExtraLabels = array_unique(array_merge($allAddonLabels, $allQuestionLabels, $allDropdownOptions));

        $items = $items->reject(function ($item) use ($allExtraLabels) {
            return in_array(strtolower(trim($item->item_name)), $allExtraLabels);
        });

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
            'galleryFiles',
            'activeCategories',
            'config',
            'selectedExtras',
            'modalConflicts',
            'modalCapacityBreaches',
            'isDebt',
            'itemPriceLookup',
            'duration_options',
            'delivery_options'
        ));
    }
}
