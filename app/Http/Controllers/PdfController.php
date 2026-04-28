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
        $logoPath = public_path('assets/icon/bgfile.png');
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            return 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
        return '';
    }

    public function generateInvoice(Request $request, int|string $id)
    {
        $booking = Booking::with(['items', 'payments'])->findOrFail($id);

        if ($request->has('prices')) {
            $booking->include_attraction_cost = $request->input('prices') == 1;
        }

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

    public function generatePurchaseOrder(Request $request, int|string $id)
    {
        $booking = Booking::with(['items'])->findOrFail($id);

        if ($request->has('prices')) {
            $booking->include_attraction_cost = $request->input('prices') == 1;
        }

        $pdf = Pdf::loadView('pdf.purchase_order', [
            'booking' => $booking,
            'items' => $booking->items,
            'logoData' => $this->getLogoData()
        ]);

        return $pdf->stream('PurchaseOrder-' . $booking->id . '.pdf');
    }

    public function generateReceipt(Request $request, int|string $id)
    {
        $booking = Booking::with(['items', 'payments'])->findOrFail($id);

        if ($request->has('prices')) {
            $booking->include_attraction_cost = $request->input('prices') == 1;
        }

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



    public function generateDebt(Request $request, int|string $id)
    {
        $booking = Booking::with(['items', 'payments'])->findOrFail($id);

        if ($request->has('prices')) {
            $booking->include_attraction_cost = $request->input('prices') == 1;
        }

        $amountPaid = $booking->payments->sum('amount');
        $totalAmount = $booking->total_amount;
        $balanceDue = max(0, $totalAmount - $amountPaid);

        $pdf = Pdf::loadView('pdf.debt', [
            'booking' => $booking,
            'items' => $booking->items,
            'amountPaid' => $amountPaid,
            'totalAmount' => $totalAmount,
            'balanceDue' => $balanceDue,
            'logoData' => $this->getLogoData()
        ]);

        return $pdf->stream('Debt-' . ($booking->invoice_number ?? $booking->id) . '.pdf');
    }

    public function generateDeliveryReceipt(int|string $id)
    {
        $booking = Booking::with(['items'])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.delivery_receipt', [
            'booking' => $booking,
            'items' => $booking->items,
            'logoData' => $this->getLogoData()
        ]);

        return $pdf->stream('DeliveryReceipt-' . ($booking->invoice_number ?? $booking->id) . '.pdf');
    }
}
