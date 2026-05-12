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

        $extrasList = array_merge(json_decode($this->booking->general_extra ?? '[]', true) ?? [], json_decode($this->booking->specific_extra ?? '[]', true) ?? []);
        
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

        if ($isFullDay && !empty($this->booking->duration) && !in_array($this->booking->duration, ['4 Hours', '7 Hours'])) {
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

        return view('livewire.staff.booking-overview', compact(
            'items', 'statusColor', 'timeString', 'galleryFiles', 'activeCategories', 'config', 'selectedExtras'
        ));
    }
}
