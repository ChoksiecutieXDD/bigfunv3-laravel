<?php

namespace App\Livewire\Staff;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Booking;
use App\Models\BookingItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

#[Layout('components.layouts.overview')]
class BookingOverview extends Component
{
    public Booking $booking;
    public $from_url;

    public function mount($id)
    {
        $this->from_url = request()->query('back');
        $this->booking = Booking::findOrFail($id);
    }

    public function render()
    {
        $items = BookingItem::where('booking_id', $this->booking->id)
            ->leftJoin('products', function($join) {
                $join->on('booking_items.item_name', '=', 'products.name')
                     ->where('booking_items.is_custom', '=', 0);
            })
            ->selectRaw('booking_items.item_name, booking_items.is_custom, SUM(booking_items.qty) as total_qty, products.specification, products.category')
            ->groupBy('booking_items.item_name', 'booking_items.is_custom', 'products.specification', 'products.category')
            ->get();

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
            'addons' => DB::table('category_addons')->orderBy('category_target')->get()->groupBy('category_target')->map(function($g) { return $g->map(fn($v) => (array)$v)->toArray(); })->toArray(),
            'questions' => DB::table('product_extras')->orderBy('category_target')->get()->groupBy('category_target')->map(function($g) { return $g->map(fn($v) => (array)$v)->toArray(); })->toArray(),
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
        if (!empty($this->booking->end_time) && $this->booking->end_time != '00:00:00') { $timeString .= ' - ' . Carbon::parse($this->booking->end_time)->format('g:i A'); }

        $galleryFiles = collect([$this->booking->delivery_attachment, $this->booking->delivery_attachment_2, $this->booking->delivery_attachment_3, $this->booking->delivery_attachment_4, $this->booking->delivery_attachment_5])->filter()->toArray();

        return view('livewire.staff.booking-overview', compact(
            'items', 'statusColor', 'timeString', 'galleryFiles', 'activeCategories', 'config', 'selectedExtras'
        ));
    }
}
