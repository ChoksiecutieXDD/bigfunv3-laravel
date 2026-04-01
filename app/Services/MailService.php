<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class MailService
{
    /**
     * Send an email with optional PDF attachments.
     *
     * @param int $bookingId
     * @param array $params [email_to, email_subject, email_body, email_type, attachments, email_cc, email_bcc]
     * @return array [success, message]
     */
    public function sendEmail($bookingId, array $params)
    {
        $toRaw = $params['email_to'] ?? '';
        $ccRaw = $params['email_cc'] ?? '';
        $bccRaw = $params['email_bcc'] ?? '';
        $subject = $params['email_subject'] ?? "Booking Info #{$bookingId}";
        $bodyText = $params['email_body'] ?? '';
        $attachments = $params['attachments'] ?? [];
        $type = $params['email_type'] ?? 'generic';

        if (!is_array($attachments)) {
            $attachments = [$attachments];
        }

        $toList = $this->parseEmailList($toRaw);
        if (empty($toList)) {
            return ['success' => false, 'message' => 'Recipient email required (invalid email)'];
        }

        // Render the HTML body using the standard Blade template
        $fullHtml = View::make('emails.standard', [
            'bodyText' => $bodyText,
        ])->render();

        $mail = new PHPMailer(true);

        try {
            // SMTP Settings (from .env/config)
            $mail->isSMTP();
            $mail->Host       = config('mail.mailers.smtp.host', 'smtp.gmail.com');
            $mail->SMTPAuth   = true;
            $mail->Username   = config('mail.mailers.smtp.username');
            $mail->Password   = config('mail.mailers.smtp.password');
            $mail->SMTPSecure = config('mail.mailers.smtp.encryption', 'tls');
            $mail->Port       = config('mail.mailers.smtp.port', 587);
            $mail->CharSet    = 'UTF-8';

            // Sender
            $mail->setFrom(config('mail.from.address'), config('mail.from.name', 'Big Fun'));
            $mail->addReplyTo(config('mail.from.address'), 'Big Fun');

            // Recipients
            foreach ($toList as $addr) {
                $mail->addAddress($addr);
            }
            foreach ($this->parseEmailList($ccRaw) as $addr) {
                $mail->addCC($addr);
            }
            foreach ($this->parseEmailList($bccRaw) as $addr) {
                $mail->addBCC($addr);
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $fullHtml;
            $mail->AltBody = $bodyText . "\n\n--\nBig Fun\n1800 BIGFUN";

            // Attachment Logic
            if (!empty($attachments)) {
                $booking = \App\Models\Booking::find($bookingId);
                
                foreach ($attachments as $filename) {
                    $filename = trim((string)$filename);
                    if ($filename === '') continue;

                    // PDF Generation mapping
                    $pdfContent = null;
                    if (stripos($filename, 'BigFunInvoice') !== false) {
                        $pdfContent = Pdf::loadView('pdf.invoice', ['booking' => $booking])->output();
                    } elseif (stripos($filename, 'BigFunReceipt') !== false) {
                        $pdfContent = Pdf::loadView('pdf.receipt', ['booking' => $booking])->output();
                    } elseif (stripos($filename, 'BigFunEnvelope') !== false) {
                        $pdfContent = Pdf::loadView('pdf.envelope', ['booking' => $booking])->output();
                    } elseif (stripos($filename, 'BigFunPurchaseOrder') !== false) {
                        $pdfContent = Pdf::loadView('pdf.purchase_order', ['booking' => $booking])->output();
                    } elseif (stripos($filename, 'BigFunDebt') !== false) {
                        $pdfContent = Pdf::loadView('pdf.debt', ['booking' => $booking])->output();
                    }

                    if ($pdfContent) {
                        $mail->addStringAttachment($pdfContent, $filename);
                    } else {
                        // Check for static documents in public/uploads if necessary
                        $filePath = public_path('uploads/' . $filename);
                        if (file_exists($filePath)) {
                            $mail->addAttachment($filePath, $filename);
                        }
                    }
                }
            }

            // Embed Images for the Footer
            $this->embedFooterImages($mail);

            // Send
            $mail->send();

            // Log the email
            $this->logEmail($bookingId, $type, $toList[0] ?? $toRaw);

            // Update Database Flags
            $this->updateBookingFlags($bookingId, $type);

            return ['success' => true, 'message' => 'Email sent successfully'];
        } catch (Exception $e) {
            Log::error("Mailer Error for Booking #{$bookingId}: {$mail->ErrorInfo}");
            return ['success' => false, 'message' => "Mailer Error: {$mail->ErrorInfo}"];
        } catch (\Throwable $e) {
            Log::error("General Mail Error for Booking #{$bookingId}: " . $e->getMessage());
            return ['success' => false, 'message' => "Error: " . $e->getMessage()];
        }
    }

    /**
     * Embed standard footer images into the PHPMailer instance.
     */
    private function embedFooterImages(PHPMailer $mail)
    {
        $imgInflatable = public_path('assets/img/inflatable-email.png');
        $imgBigFun = public_path('assets/icon/bgfunlogo.png');
        $imgStarhire = public_path('assets/img/starhire-email.jpg');

        if (file_exists($imgInflatable)) $mail->addEmbeddedImage($imgInflatable, 'img_inflatable');
        if (file_exists($imgBigFun)) $mail->addEmbeddedImage($imgBigFun, 'img_bigfun');
        if (file_exists($imgStarhire)) $mail->addEmbeddedImage($imgStarhire, 'img_starhire');
    }

    /**
     * Parse a list of emails (comma/semicolon/space separated).
     */
    private function parseEmailList($str)
    {
        $emails = [];
        foreach (preg_split('/[\s,;]+/', (string)$str) as $e) {
            $e = trim($e);
            if ($e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $e;
            }
        }
        return array_values(array_unique($emails));
    }

    /**
     * Log the email dispatch.
     */
    private function logEmail($bookingId, $type, $recipient)
    {
        try {
            DB::table('email_logs')->insert([
                'booking_id' => $bookingId,
                'type' => $type,
                'sent_to' => $recipient,
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to log email for Booking #{$bookingId}: " . $e->getMessage());
        }
    }

    /**
     * Update booking flags based on email type.
     */
    private function updateBookingFlags($bookingId, $type)
    {
        if ($type === 'invoice') {
            DB::table('bookings')->where('id', $bookingId)->update([
                'invoice_emailed' => 1,
                // 'invoicing_done' => 1, // Optional: if field exists
            ]);
        }
    }
}
