<?php

namespace App\Livewire\Supervisor;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use App\Models\Booking;
use App\Models\BookingItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

#[Layout('components.layouts.plain')]
class EditBooking extends Component
{
    use WithFileUploads;

    public Booking $booking;

    public $form = [];
    public $selectedItems = [];
    public $dynamicExtras = [];

    public $subtotal = 0;
    public $surchargeAmount = 0;
    public $totalAmount = 0;
    public $depositRequired = 0;

    public $availability = [];
    public $calMonth;
    public $calYear;
    public $calDays = [];
    public $tempSelectedDate;

    public $newAttachments = [];
    public $deletedAttachments = [];

    public function mount($id)
    {
        $this->booking = Booking::findOrFail($id);
        $this->form = $this->booking->toArray();

        if (empty($this->form['payment_type'])) $this->form['payment_type'] = 'EFT';
        if (empty($this->form['eft_method']) && $this->form['payment_type'] === 'EFT') $this->form['eft_method'] = 'Direct Deposit';

        $items = BookingItem::where('booking_id', $id)->get();
        foreach ($items as $item) {
            $this->selectedItems[strtolower(trim($item->item_name))] = (int) $item->qty;
        }

        // --- REVERSE MAP EXTRAS ---
        $genExt = json_decode($this->booking->general_extra ?? '[]', true) ?? [];
        $specExt = json_decode($this->booking->specific_extra ?? '[]', true) ?? [];
        $allExt = array_merge($genExt, $specExt);

        $addons = DB::table('category_addons')->get();
        foreach ($addons as $a) {
            if (isset($allExt[$a->addon_label]) || isset($allExt[$a->category_target . ': ' . $a->addon_label])) {
                $this->dynamicExtras['add_' . $a->id] = true;
            }
        }

        $dropdowns = DB::table('product_dropdowns')->get();
        $options = DB::table('dropdown_options')->get();
        foreach ($dropdowns as $d) {
            foreach ($options->where('dropdown_id', $d->id) as $o) {
                $search1 = $d->label . ' - ' . $o->option_label;
                $search2 = $d->category_target . ': ' . $search1;
                if (isset($allExt[$search1]) || isset($allExt[$search2])) {
                    $this->dynamicExtras['dd_' . $d->id] = $o->id;
                }
            }
        }

        $questions = DB::table('product_extras')->get();
        foreach ($questions as $q) {
            foreach ($allExt as $extKey => $extVal) {
                if (str_contains($extKey, $q->question_text)) {
                    $isYes = str_contains(strtolower($extKey), '(yes)') || str_contains(strtolower($extKey), '(Yes)');
                    $valToSet = $isYes ? $q->yes_price . '|yes' : $q->no_price . '|no';
                    $this->dynamicExtras['q_' . $q->id] = $valToSet;
                }
            }
        }

        $this->calMonth = Carbon::parse($this->form['event_date'])->month;
        $this->calYear = Carbon::parse($this->form['event_date'])->year;

        $durationLabels = DB::table('duration_prices')->pluck('label')->toArray();
        if (!empty($this->form['duration']) && !in_array($this->form['duration'], $durationLabels)) {
            $this->form['is_custom_duration'] = true;
            $this->form['custom_duration_text'] = $this->form['duration'];
        } else {
            $this->form['is_custom_duration'] = false;
            $this->form['custom_duration_text'] = '';
        }

        $this->checkAvailability();
        $this->calculateTotals();
    }

    public function updatedFormEventDate()
    {
        $this->checkAvailability();
    }
    public function updatedFormPaymentType()
    {
        $this->calculateTotals();
    }
    public function updatedDynamicExtras()
    {
        $this->calculateTotals();
    }

    public function updatedSelectedItems()
    {
        $this->calculateTotals();
        $this->checkAvailability();
    }

    public function updatedFormDeliveryArea()
    {
        if ($this->form['delivery_area'] !== 'custom') {
            $zone = DB::table('delivery_zones')->where('zone_name', $this->form['delivery_area'])->first();
            $this->form['delivery_cost'] = $zone ? $zone->price : 0;
        }
        $this->calculateTotals();
    }

    public function updatedFormDuration()
    {
        if ($this->form['duration'] !== 'custom') {
            $this->form['is_custom_duration'] = false;
            $dur = DB::table('duration_prices')->where('label', $this->form['duration'])->first();
            $this->form['duration_cost'] = $dur ? $dur->price : 0;
        } else {
            $this->form['is_custom_duration'] = true;
        }
        $this->calculateTotals();
    }

    public function toggleItem($itemName)
    {
        $key = strtolower(trim($itemName));
        if (isset($this->selectedItems[$key])) {
            unset($this->selectedItems[$key]);
        } else {
            $this->selectedItems[$key] = 1;
        }
        $this->updatedSelectedItems();
    }

    public function updateItemQty($itemName, $change)
    {
        $key = strtolower(trim($itemName));
        if (isset($this->selectedItems[$key])) {
            $newQty = $this->selectedItems[$key] + $change;
            if ($newQty > 0) {
                $this->selectedItems[$key] = $newQty;
            }
        }
        $this->updatedSelectedItems();
    }

    public function calculateTotals()
    {
        $ridesTotal = 0;
        $products = DB::table('products')->pluck('price', DB::raw('LOWER(TRIM(name))'))->toArray();

        foreach ($this->selectedItems as $name => $qty) {
            if (isset($products[$name])) {
                $ridesTotal += ((float)$products[$name] * $qty);
            }
        }

        $extrasTotal = 0;
        $addons = DB::table('category_addons')->pluck('addon_price', 'id');
        $opts = DB::table('dropdown_options')->pluck('option_price', 'id');

        foreach ($this->dynamicExtras as $key => $val) {
            if (str_starts_with($key, 'add_') && $val) {
                $id = str_replace('add_', '', $key);
                $extrasTotal += (float) ($addons[$id] ?? 0);
            }
            if (str_starts_with($key, 'dd_') && $val) {
                $extrasTotal += (float) ($opts[$val] ?? 0);
            }
            if (str_starts_with($key, 'q_') && $val) {
                $parts = explode('|', $val);
                $extrasTotal += (float) ($parts[0] ?? 0);
            }
        }

        $deliveryCost = (float) ($this->form['delivery_cost'] ?? 0);
        $durationCost = (float) ($this->form['duration_cost'] ?? 0);

        $this->subtotal = $ridesTotal + $extrasTotal + $deliveryCost + $durationCost;

        if (in_array($this->form['payment_type'], ['Card Holder', 'credit_card'])) {
            $this->surchargeAmount = $this->subtotal * 0.029;
        } else {
            $this->surchargeAmount = 0;
        }

        $this->totalAmount = $this->subtotal + $this->surchargeAmount;
        $this->depositRequired = $this->totalAmount * 0.5;

        $this->form['extra_logistics_cost'] = $extrasTotal;
        $this->form['total_amount'] = $this->totalAmount;
        $this->form['deposit_required'] = $this->depositRequired;
    }

    public function checkAvailability()
    {
        $date = $this->form['event_date'] ?? now()->format('Y-m-d');

        $usage = DB::table('booking_items')
            ->join('bookings', 'booking_items.booking_id', '=', 'bookings.id')
            ->where('bookings.event_date', $date)
            ->where('bookings.id', '!=', $this->booking->id)
            ->whereNotIn('bookings.status', ['Cancelled'])
            ->selectRaw('LOWER(TRIM(booking_items.item_name)) as name, SUM(booking_items.qty) as total')
            ->groupBy('name')
            ->pluck('total', 'name')
            ->toArray();

        $products = DB::table('products')->where('is_active', 1)->get();
        $this->availability = [];

        foreach ($products as $p) {
            $cleanName = strtolower(trim($p->name));
            $limit = (int) $p->daily_limit;
            $used = $usage[$cleanName] ?? 0;
            $left = ($limit > 0) ? max(0, $limit - $used) : 999;

            $this->availability[$cleanName] = [
                'used' => $used,
                'limit' => $limit,
                'left' => $left,
                'sold_out' => ($limit > 0 && $left <= 0)
            ];
        }
    }

    public function loadCalendar()
    {
        $start = Carbon::create($this->calYear, $this->calMonth, 1);
        $end = $start->copy()->endOfMonth();

        $counts = Booking::whereBetween('event_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->where('id', '!=', $this->booking->id)
            ->whereNotIn('status', ['Cancelled'])
            ->selectRaw('event_date, COUNT(*) as cnt')
            ->groupBy('event_date')
            ->pluck('cnt', 'event_date')
            ->toArray();

        $this->calDays = [];
        for ($i = 0; $i < $start->dayOfWeek; $i++) $this->calDays[] = null;

        $dailyLimit = 7;
        for ($day = 1; $day <= $start->daysInMonth; $day++) {
            $dateStr = $start->copy()->day($day)->format('Y-m-d');
            $used = $counts[$dateStr] ?? 0;
            $this->calDays[] = [
                'date' => $dateStr,
                'day' => $day,
                'left' => max(0, $dailyLimit - $used)
            ];
        }
    }

    public function calPrev()
    {
        $d = Carbon::create($this->calYear, $this->calMonth, 1)->subMonth();
        $this->calMonth = $d->month;
        $this->calYear = $d->year;
        $this->loadCalendar();
    }

    public function calNext()
    {
        $d = Carbon::create($this->calYear, $this->calMonth, 1)->addMonth();
        $this->calMonth = $d->month;
        $this->calYear = $d->year;
        $this->loadCalendar();
    }

    public function applySelectedDate()
    {
        if ($this->tempSelectedDate) {
            $this->form['event_date'] = $this->tempSelectedDate;
            $this->checkAvailability();
            $this->dispatch('close-modal', 'calendarModal');
        }
    }

    public function markAttachmentDeleted($field)
    {
        $this->deletedAttachments[] = $field;
    }

    public function saveBooking()
    {
        $saveData = $this->form;
        unset($saveData['is_custom_duration']);
        unset($saveData['custom_duration_text']);

        if (!empty($this->form['is_custom_duration'])) {
            $saveData['duration'] = $this->form['custom_duration_text'];
        }

        // --- ENCODE ALL EXTRAS ---
        $generalExtras = [];
        $specificExtras = [];

        $allAddons = DB::table('category_addons')->get()->keyBy('id');
        $allDropdowns = DB::table('product_dropdowns')->get()->keyBy('id');
        $allOptions = DB::table('dropdown_options')->get()->keyBy('id');
        $allQuestions = DB::table('product_extras')->get()->keyBy('id');

        foreach ($this->dynamicExtras as $key => $val) {
            if (str_starts_with($key, 'add_') && $val) {
                $id = str_replace('add_', '', $key);
                if ($addon = $allAddons->get($id)) {
                    if ($addon->category_target === 'General Logistics') {
                        $generalExtras[$addon->addon_label] = (float)$addon->addon_price;
                    } else {
                        $specificExtras[$addon->category_target . ': ' . $addon->addon_label] = (float)$addon->addon_price;
                    }
                }
            }
            if (str_starts_with($key, 'dd_') && $val) {
                $ddId = str_replace('dd_', '', $key);
                if (($dd = $allDropdowns->get($ddId)) && ($opt = $allOptions->get($val))) {
                    $label = $dd->label . ' - ' . $opt->option_label;
                    if ($dd->category_target === 'General Logistics') {
                        $generalExtras[$label] = (float)$opt->option_price;
                    } else {
                        $specificExtras[$dd->category_target . ': ' . $label] = (float)$opt->option_price;
                    }
                }
            }
            if (str_starts_with($key, 'q_') && $val) {
                $qId = str_replace('q_', '', $key);
                if ($q = $allQuestions->get($qId)) {
                    $parts = explode('|', $val);
                    $price = (float)($parts[0] ?? 0);
                    $answer = $parts[1] ?? 'yes';
                    $label = $q->question_text . ' (' . ucfirst($answer) . ')';
                    if ($q->category_target === 'General Logistics') {
                        $generalExtras[$label] = $price;
                    } else {
                        $specificExtras[$q->category_target . ': ' . $label] = $price;
                    }
                }
            }
        }

        $saveData['general_extra'] = json_encode($generalExtras);
        $saveData['specific_extra'] = json_encode($specificExtras);

        // --- SAVE TO DB ---
        $this->booking->update($saveData);

        BookingItem::where('booking_id', $this->booking->id)->delete();
        foreach ($this->selectedItems as $name => $qty) {
            $product = DB::table('products')->whereRaw('LOWER(TRIM(name)) = ?', [$name])->first();
            BookingItem::create([
                'booking_id' => $this->booking->id,
                'item_name' => $product ? $product->name : ucwords($name),
                'qty' => $qty,
                'is_custom' => $product ? 0 : 1
            ]);
        }

        foreach ($this->deletedAttachments as $field) {
            $this->booking->update([$field => null]);
        }

        $this->dispatch('notify', title: 'Saved!', message: 'Booking successfully updated.', type: 'success');
        
        // Determine the redirect based on the route name/prefix
        if (request()->routeIs('supervisor.*')) {
            return redirect()->route('supervisor.bookings.overview', $this->booking->id);
        } else {
            return redirect()->route('booking.overview', $this->booking->id);
        }
    }

    public function render()
    {
        $categories = [];
        $catRes = DB::table('product_categories')->orderBy('sort_order')->get();
        foreach ($catRes as $c) {
            $categories[$c->category_name] = ['limit' => $c->daily_limit, 'products' => []];
        }

        $products = DB::table('products')->where('is_active', 1)->orderBy('category')->orderBy('name')->get();
        $productCategoryMap = [];

        foreach ($products as $p) {
            $productCategoryMap[strtolower(trim($p->name))] = $p->counts_against ?: $p->category;
            if (isset($categories[$p->category])) {
                $categories[$p->category]['products'][] = $p;
            } else {
                if (!isset($categories['Other'])) $categories['Other'] = ['limit' => 0, 'products' => []];
                $categories['Other']['products'][] = $p;
            }
        }

        $deliveryOptions = DB::table('delivery_zones')->orderBy('price')->get();
        $durationOptions = DB::table('duration_prices')->orderBy('hours')->get();

        $activeCategories = ['General Logistics'];
        foreach ($this->selectedItems as $name => $qty) {
            if (isset($productCategoryMap[$name])) {
                $activeCategories[] = $productCategoryMap[$name];
            }
        }
        $activeCategories = array_unique($activeCategories);

        $addons = DB::table('category_addons')->whereIn('category_target', $activeCategories)->get()->groupBy('category_target');
        $questions = DB::table('product_extras')->whereIn('category_target', $activeCategories)->get()->groupBy('category_target');

        $rawDropdowns = DB::table('product_dropdowns')->whereIn('category_target', $activeCategories)->orderBy('sort_order')->get();
        $rawOptions = DB::table('dropdown_options')->get()->groupBy('dropdown_id');
        $dropdowns = [];
        foreach ($rawDropdowns as $dd) {
            $dd->options = $rawOptions->get($dd->id) ?? collect([]);
            $dropdowns[$dd->category_target][] = $dd;
        }

        return view('livewire.supervisor.edit-booking', compact('categories', 'deliveryOptions', 'durationOptions', 'addons', 'dropdowns', 'questions', 'activeCategories'));
    }
}
