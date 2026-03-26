<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    // Helper to get the base64 logo string
    private function getLogoData()
    {
        $logoPath = public_path('picture/bgfile.png');
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            return 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
        return '';
    }

    public function generateInvoice($id)
    {
        $booking = Booking::with(['items', 'payments'])->findOrFail($id);

        $amountPaid = $booking->payments->sum('amount');
        $totalAmount = $booking->total_amount;
        $balanceDue = max(0, $totalAmount - $amountPaid);

        $pdf = Pdf::loadView('pdf.invoice', [
            'booking' => $booking,
            'items' => $booking->items,
            'amountPaid' => $amountPaid,
            'totalAmount' => $totalAmount,
            'balanceDue' => $balanceDue,
            'logoData' => $this->getLogoData()
        ]);

        return $pdf->stream('Invoice-' . ($booking->invoice_number ?? $booking->id) . '.pdf');
    }

    public function generatePurchaseOrder($id)
    {
        $booking = Booking::with(['items'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.purchase_order', [
            'booking' => $booking,
            'items' => $booking->items,
            'logoData' => $this->getLogoData()
        ]);

        return $pdf->stream('PurchaseOrder-' . $booking->id . '.pdf');
    }

    public function generateReceipt($id)
    {
        $booking = Booking::with(['items', 'payments'])->findOrFail($id);

        $amountPaid = $booking->payments->sum('amount');
        $totalAmount = $booking->total_amount;
        $balanceDue = max(0, $totalAmount - $amountPaid);

        $pdf = Pdf::loadView('pdf.receipt', [
            'booking' => $booking,
            'items' => $booking->items,
            'amountPaid' => $amountPaid,
            'totalAmount' => $totalAmount,
            'balanceDue' => $balanceDue,
            'logoData' => $this->getLogoData()
        ]);

        return $pdf->stream('Receipt-' . ($booking->invoice_number ?? $booking->id) . '.pdf');
    }

    public function generateEnvelope($id)
    {
        $booking = Booking::findOrFail($id);

        // Envelopes require a custom page size (DL envelope size is standard)
        $pdf = Pdf::loadView('pdf.envelope', [
            'booking' => $booking,
            'logoData' => $this->getLogoData()
        ])->setPaper([0, 0, 623.62, 311.81], 'landscape'); // DL Size in points

        return $pdf->stream('Envelope-' . $booking->id . '.pdf');
    }
}
