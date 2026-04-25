<?php

namespace App\Livewire\Supervisor;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use App\Models\Booking;
use App\Models\BookingItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

#[Layout('components.layouts.plain')]
class EditBooking extends Component
{
    use WithFileUploads;

    public Booking $booking;

    public $form = [];
    public $selectedItems = [];
    public $dynamicExtras = [];
    public $search = '';

    public $subtotal = 0;
    public $surchargeAmount = 0;
    public $totalAmount = 0;
    public $totalPaid = 0;
    public $balanceDue = 0;
    public $depositRequired = 0;

    public $availability = [];
    public $calMonth;
    public $calYear;
    public $calDays = [];
    public $tempSelectedDate;

    public $newAttachments = [];
    public $deletedAttachments = [];
    
    public $temp_attachment_1;
    public $temp_attachment_2;
    public $temp_attachment_3;
    public $temp_attachment_4;
    public $temp_attachment_5;

    // --- MOVE LOGISTICS PROPERTIES ---
    public $bookedAttractions = [];
    public $dailyAttractions = [];
    public $categoryLimits = [];
    public $dailyUsage = [];
    public $bookingImpact = [];
    public $modalConflicts = [];
    public $modalCapacityBreaches = [];
    public $modalNameConflicts = [];
    public $activeConflicts = [];
    public $activeCapacityBreaches = [];
    public $lastToastDate = null;
    public $backUrl;
    
    protected $rules = [
        'temp_attachment_1' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
        'temp_attachment_2' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
        'temp_attachment_3' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
        'temp_attachment_4' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
        'temp_attachment_5' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
    ];

    public $config = [];
    public $categories = [];
    public $saved_extras = [];
    public $isSupervisor = false;
    public $durationCost = 0;
    public $deliveryCost = 0;
    public $attractionsCost = 0;
    public $extrasCost = 0;
    public $staffList = [];

    // Computed-like property for JS bridge
    public $selectedItemsClean = [];

    public function mount($id)
    {
        $this->backUrl = request()->query('back');
        $this->isSupervisor = str_contains(request()->url(), '/supervisor/');
        $this->booking = Booking::findOrFail($id);
        $this->form = $this->booking->toArray();

        if (empty($this->form['payment_type'])) $this->form['payment_type'] = 'EFT';
        if (empty($this->form['eft_method']) && $this->form['payment_type'] === 'EFT') $this->form['eft_method'] = 'Direct Deposit';

        $items = BookingItem::where('booking_id', $id)->get();
        // Pre-fetch product prices for fallback if needed
        $productPrices = DB::table('products')->pluck('price', DB::raw('LOWER(TRIM(name))'))->toArray();

        foreach ($items as $item) {
            $key = strtolower(trim($item->item_name));
            $storedPrice = (float) $item->item_price;
            
            // Fallback: If stored price is 0, use the current product default
            if ($storedPrice < 0.01 && isset($productPrices[$key])) {
                $storedPrice = (float) $productPrices[$key];
            }

            $this->selectedItems[$key] = [
                'qty' => (int) $item->qty,
                'price' => $storedPrice
            ];
        }

        $this->loadProductConfigurations();

        // 4. Fetch Categories & Product Limits (Match NewBooking logic)
        $cats = DB::table('product_categories')->orderBy('sort_order', 'asc')->get();
        foreach ($cats as $c) {
            $this->categories[$c->category_name] = ['limit' => (int)$c->daily_limit, 'products' => []];
        }

        // --- LOADING EXTRAS ---
        $this->saved_extras = json_decode($this->booking->extras_json ?? '[]', true) ?? [];
        
        // --- DATA RECOVERY & PARITY CHECK ---
        // Even if extras_json is not empty, it might be incomplete if items were added via ride list
        // that are also defined as addons (e.g. 
        $itemNames = array_map(fn($it) => strtolower(trim($it)), array_keys($this->selectedItems));
        
        // 1. Recover Addons
        $addons = DB::table('category_addons')->get();
        foreach ($addons as $a) {
            $addonKey = 'add_' . $a->id;
            $aLabel = strtolower(trim($a->addon_label));
            $aTarget = strtolower(trim($a->category_target));
            
            // Aggressive backend matching
            foreach ($itemNames as $itName) {
                if ($itName === $aLabel || str_contains($itName, $aLabel) || str_contains($itName, $aTarget)) {
                    $this->saved_extras[$addonKey] = "1";
                    break;
                }
            }
        }

        // 2. Recover Questions
        $questions = DB::table('product_extras')->get();
        foreach ($questions as $q) {
            $qKey = 'q_' . $q->id;
            $qText = strtolower(trim($q->question_text));
            
            // Check if any item name contains the question text
            foreach ($itemNames as $itName) {
                if (str_contains($itName, $qText)) {
                    $this->saved_extras[$qKey] = $q->yes_price . '|yes';
                    break;
                }
            }
        }

        // 3. Recover Dropdowns
        $dropdowns = DB::table('product_dropdowns')->get();
        $dropdownOptions = DB::table('dropdown_options')->get();
        foreach ($dropdownOptions as $opt) {
            $dd = $dropdowns->where('id', $opt->dropdown_id)->first();
            if (!$dd) continue;
            
            $ddKey = 'dd_' . $opt->dropdown_id;
            $optLabel = strtolower(trim($opt->option_label));
            
            $optFound = false;
            foreach ($itemNames as $itName) {
                if (str_contains($itName, $optLabel)) {
                    $this->saved_extras[$ddKey] = $opt->id;
                    $optFound = true;
                    break;
                }
            }
            if ($optFound) continue;
        }

        if (empty($this->booking->extras_json) || $this->booking->extras_json === '[]') {
            // --- FALLBACK: REVERSE MAP EXTRAS ---
            $genExt = json_decode($this->booking->general_extra ?? '[]', true) ?? [];
            $specExt = json_decode($this->booking->specific_extra ?? '[]', true) ?? [];
            $allExt = array_merge($genExt, $specExt);

            foreach ($addons as $a) {
                if (isset($allExt[$a->addon_label]) || isset($allExt[$a->category_target . ': ' . $a->addon_label])) {
                    $this->saved_extras['add_' . $a->id] = "1";
                }
            }

            $dropdowns = DB::table('product_dropdowns')->get();
            $options = DB::table('dropdown_options')->get();
            foreach ($dropdowns as $d) {
                foreach ($options->where('dropdown_id', $d->id) as $o) {
                    $search1 = $d->label . ' - ' . $o->option_label;
                    $search2 = $d->category_target . ': ' . $search1;
                    if (isset($allExt[$search1]) || isset($allExt[$search2])) {
                        $this->saved_extras['dd_' . $d->id] = (string)$o->id;
                    }
                }
            }

            $questions = DB::table('product_extras')->get();
            foreach ($questions as $q) {
                foreach ($allExt as $extKey => $extVal) {
                    if (str_contains(strtolower($extKey), strtolower($q->question_text))) {
                        $isYes = str_contains(strtolower($extKey), '(yes)');
                        $valToSet = $isYes ? $q->yes_price . '|yes' : $q->no_price . '|no';
                        $this->saved_extras['q_' . $q->id] = $valToSet;
                    }
                }
            }
        }
        $this->dynamicExtras = $this->saved_extras;

        $this->calMonth = Carbon::parse($this->form['event_date'])->month;
        $this->calYear = Carbon::parse($this->form['event_date'])->year;

        $this->tempSelectedDate = $this->form['event_date'];

        $durationLabels = DB::table('duration_prices')->pluck('label')->toArray();
        // Duration Custom Check
        $durationLabels = DB::table('duration_prices')->pluck('label')->toArray();
        if (!empty($this->form['duration']) && !in_array($this->form['duration'], $durationLabels)) {
            $this->form['custom_duration_text'] = $this->form['duration'];
            $this->form['duration'] = 'custom';
            $this->form['is_custom_duration'] = true;
        } else {
            $this->form['is_custom_duration'] = false;
            $this->form['custom_duration_text'] = '';
        }

        // Initialize costs if they are zero but an area/duration is selected
        if ((float)($this->form['delivery_cost'] ?? 0) === 0.0 && !empty($this->form['delivery_area']) && $this->form['delivery_area'] !== 'custom') {
            $zone = DB::table('delivery_zones')->where('zone_name', $this->form['delivery_area'])->first();
            if ($zone) {
                $this->form['delivery_cost'] = $zone->price;
            }
        }

        // --- CONNECT INVENTORY LIMITS ---
        $this->categoryLimits = DB::table('product_categories')
            ->where('daily_limit', '>', 0)
            ->pluck('daily_limit', 'category_name')
            ->toArray();

        foreach ($this->categoryLimits as $catName => $limit) {
            if (isset($this->categories[$catName])) {
                $this->categories[$catName]['limit'] = (int)$limit;
            }
        }

        $this->refreshBookingImpact();
        $this->loadCalendar();

        if ((float)($this->form['duration_cost'] ?? 0) === 0.0 && !empty($this->form['duration']) && $this->form['duration'] !== 'custom') {
            $dur = DB::table('duration_prices')->where('label', $this->form['duration'])->first();
            if ($dur) {
                $this->form['duration_cost'] = $dur->price;
            }
        }

        $this->totalPaid = (float) ($this->booking->total_paid ?? 0);

        // Fetch Staff/Operators (Match formatting in NewBooking.php)
        $this->staffList = \App\Models\User::whereIn('role', ['Staff', 'Operator', 'Supervisor'])
            ->where('is_active', 1)
            ->orderBy('first_name')
            ->get()
            ->map(fn($u) => trim($u->first_name . ' ' . $u->last_name))
            ->toArray();
        
        if (empty($this->staffList)) $this->staffList = ["Team"];

        $this->loadCalendar(); // Initialize calendar grid
        $this->checkAvailability();
        $this->calculateTotals();
        $this->syncSelectedItemsClean();
    }

    private function syncSelectedItemsClean()
    {
        $this->selectedItemsClean = array_keys($this->selectedItems);
    }

    public function updatedForm($value, $key)
    {
        if ($key === 'event_date') {
            $this->checkAvailability();
        }
        
        if ($key === 'payment_type') {
            $this->calculateTotals();
        }

        if ($key === 'delivery_area') {
            if ($this->form['delivery_area'] !== 'custom') {
                $zone = DB::table('delivery_zones')->where('zone_name', $this->form['delivery_area'])->first();
                $this->form['delivery_cost'] = $zone ? (float)$zone->price : 0;
            }
            $this->calculateTotals();
        }

        if ($key === 'delivery_cost') {
            $this->calculateTotals();
        }

        if ($key === 'duration') {
            if ($this->form['duration'] !== 'custom') {
                $this->form['is_custom_duration'] = false;
                $dur = DB::table('duration_prices')->where('label', $this->form['duration'])->first();
                $this->form['duration_cost'] = $dur ? (float)$dur->price : 0;
            } else {
                $this->form['is_custom_duration'] = true;
            }
            $this->calculateTotals();
        }

        if ($key === 'duration_cost') {
            $this->calculateTotals();
        }
    }

    public function updatedDynamicExtras()
    {
        $this->calculateTotals();
        $this->refreshBookingImpact();
        $this->loadCalendar();
    }

    public function updatedSelectedItems()
    {
        $this->calculateTotals();
        $this->syncSelectedItemsClean();
        $this->checkAvailability();
        $this->refreshBookingImpact();
        $this->loadCalendar();
    }


    public function syncExtras($extras)
    {
        $this->saved_extras = $extras;
        $this->dynamicExtras = $extras;
        $this->booking->extras_json = json_encode($extras);
        $this->calculateTotals();
    }

    private function loadProductConfigurations()
    {
        $this->config = ['questions' => [], 'addons' => [], 'dropdowns' => []];

        $questions = DB::table('product_extras')->orderBy('category_target', 'asc')->get();
        foreach ($questions as $q) {
            $this->config['questions'][$q->category_target][] = (array)$q;
        }

        $addons = DB::table('category_addons')->orderBy('category_target', 'asc')->get();
        foreach ($addons as $a) {
            $this->config['addons'][$a->category_target][] = (array)$a;
        }

        $dropdowns = DB::table('product_dropdowns')->orderBy('sort_order', 'asc')->get();
        foreach ($dropdowns as $d) {
            $opts = DB::table('dropdown_options')->where('dropdown_id', $d->id)->get()->toArray();
            $dArray = (array)$d;
            $dArray['options'] = array_map(function ($o) {
                return (array)$o;
            }, $opts);
            $this->config['dropdowns'][$d->category_target][] = $dArray;
        }
    }

    public function toggleItem($itemName, $isChecked = null)
    {
        $key = strtolower(trim($itemName));
        if ($isChecked === true) {
            if (!isset($this->selectedItems[$key])) {
                $product = DB::table('products')->whereRaw('LOWER(TRIM(name)) = ?', [$key])->first();
                $this->selectedItems[$key] = [
                    'qty' => 1,
                    'price' => $product ? (float)$product->price : 0
                ];
            }
        } elseif ($isChecked === false) {
            unset($this->selectedItems[$key]);
        } else {
            // Traditional toggle if no state provided
            if (isset($this->selectedItems[$key])) {
                unset($this->selectedItems[$key]);
            } else {
                $product = DB::table('products')->whereRaw('LOWER(TRIM(name)) = ?', [$key])->first();
                $this->selectedItems[$key] = [
                    'qty' => 1,
                    'price' => $product ? (float)$product->price : 0
                ];
            }
        }
        $this->updatedSelectedItems();
    }

    public function updateItemQty($itemName, $change)
    {
        $key = strtolower(trim($itemName));
        if (isset($this->selectedItems[$key])) {
            $newQty = $this->selectedItems[$key]['qty'] + $change;
            if ($newQty > 0) {
                $this->selectedItems[$key]['qty'] = $newQty;
            }
        }
        $this->updatedSelectedItems();
    }



    public function calculateTotals()
    {
        $ridesTotal = 0;
        $products = DB::table('products')->pluck('price', DB::raw('LOWER(TRIM(name))'))->toArray();

        foreach ($this->selectedItems as $name => $data) {
            $ridesTotal += ((float)$data['price'] * $data['qty']);
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

        $this->durationCost = (float) ($this->form['duration_cost'] ?? 0);
        $this->deliveryCost = (float) ($this->form['delivery_cost'] ?? 0);
        $this->attractionsCost = $ridesTotal;
        $this->extrasCost = $extrasTotal;

        $this->subtotal = round($this->attractionsCost + $this->extrasCost + $this->durationCost + $this->deliveryCost, 2);

        if (in_array($this->form['payment_type'], ['Card Holder', 'credit_card'])) {
            $this->surchargeAmount = round($this->subtotal * 0.029, 2);
        } else {
            $this->surchargeAmount = 0;
        }

        $oldTotal = (float) $this->totalAmount;
        $this->totalAmount = round($this->subtotal + $this->surchargeAmount, 2);
        $this->depositRequired = round($this->totalAmount * 0.5, 2);

        $this->form['extra_logistics_cost'] = $this->extrasCost;
        $this->form['total_amount'] = $this->totalAmount;
        $this->form['deposit_required'] = $this->depositRequired;

        $this->balanceDue = round($this->totalAmount - $this->totalPaid, 2);

        // --- DISPATCH COST CHANGE EVENTS ---
        if ($oldTotal > 0 && abs($this->totalAmount - $oldTotal) > 0.01) {
            if ($this->totalAmount > $oldTotal) {
                $this->dispatch('cost-increased', newTotal: $this->totalAmount, delta: $this->totalAmount - $oldTotal);
            } else {
                $this->dispatch('cost-decreased', newTotal: $this->totalAmount, delta: $oldTotal - $this->totalAmount);
            }

            if ($this->totalAmount < $this->totalPaid) {
                $this->dispatch('negative-balance-alert', newTotal: $this->totalAmount, totalPaid: $this->totalPaid);
            }
        }
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

    public function refreshBookingImpact()
    {
        // 1. Get Category Limits
        $this->categoryLimits = DB::table('product_categories')
            ->where('daily_limit', '>', 0)
            ->pluck('daily_limit', 'category_name')
            ->toArray();

        // 2. Calculate impact of CURRENT selected items
        $this->bookingImpact = [];
        $names = array_keys(array_filter($this->selectedItems));
        if (!empty($names)) {
            $impactRes = DB::table('products')
                ->whereIn(DB::raw('LOWER(TRIM(name))'), $names)
                ->selectRaw('LOWER(TRIM(name)) as name, COALESCE(NULLIF(counts_against, ""), category) as cat')
                ->get();

            foreach ($impactRes as $row) {
                $qty = $this->selectedItems[$row->name]['qty'] ?? 1;
                $this->bookingImpact[$row->cat] = ($this->bookingImpact[$row->cat] ?? 0) + $qty;
            }
        }

        // 3. Add Impact from Extras
        $addonMap = DB::table('category_addons')->get()->keyBy('id');
        $dropdownMap = DB::table('product_dropdowns')->get()->keyBy('id');
        $questionMap = DB::table('product_extras')->get()->keyBy('id');

        foreach ($this->dynamicExtras as $key => $val) {
            $targetCat = null;
            if (str_starts_with($key, 'add_') && $val) {
                $id = str_replace('add_', '', $key);
                $addon = $addonMap->get($id);
                if ($addon) $targetCat = $addon->counts_against ?: $addon->category_target;
            } elseif (str_starts_with($key, 'dd_') && $val && $val !== '0') {
                $id = str_replace('dd_', '', $key);
                $dd = $dropdownMap->get($id);
                if ($dd) $targetCat = $dd->counts_against ?: $dd->category_target;
            } elseif (str_starts_with($key, 'q_') && $val && $val !== '0' && !str_ends_with($val, '|no')) {
                $id = str_replace('q_', '', $key);
                $q = $questionMap->get($id);
                if ($q) $targetCat = $q->counts_against ?: $q->category_target;
            }

            if ($targetCat) {
                $catKey = strtolower(trim($targetCat));
                $this->bookingImpact[$catKey] = ($this->bookingImpact[$catKey] ?? 0) + 1;
            }
        }

        $this->bookedAttractions = $names;

        // --- UPDATE ACTIVE ANALYSIS FOR GLOBAL SAVE ---
        $res = $this->performAnalysis($this->form['event_date']);
        $this->activeConflicts = $res['conflicts'];
        $this->activeCapacityBreaches = $res['capacityBreaches'];
    }

    public function openCalendarModal()
    {
        $this->refreshBookingImpact();
        $this->loadCalendar();
        $this->dispatch('open-modal', 'calendarModal');
    }

    public function loadCalendar()
    {
        $this->tempSelectedDate = $this->form['event_date'];
        $start = Carbon::create($this->calYear, $this->calMonth, 1);
        $end = $start->copy()->endOfMonth();

        // 1. Get Daily Booking Counts (Exclude current to see baseline)
        $counts = Booking::whereBetween('event_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->whereNotIn('status', ['Cancelled'])
            ->selectRaw('event_date, COUNT(*) as cnt')
            ->groupBy('event_date')
            ->pluck('cnt', 'event_date')
            ->toArray();

        // 2. Get Daily Attraction Names (Baseline excluding current)
        $this->dailyAttractions = DB::table('booking_items')
            ->join('bookings', 'booking_items.booking_id', '=', 'bookings.id')
            ->whereBetween('bookings.event_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->whereNotIn('bookings.status', ['Cancelled'])
            ->where('bookings.id', '!=', $this->booking->id)
            ->selectRaw('bookings.event_date, LOWER(TRIM(booking_items.item_name)) as name')
            ->get()
            ->groupBy('event_date')
            ->map(fn($items) => $items->pluck('name')->unique()->toArray())
            ->toArray();

        // 3. Get Daily Category Usage (Baseline excluding current)
        $sub = DB::table('booking_items')
            ->join('bookings', 'booking_items.booking_id', '=', 'bookings.id')
            ->join('products', DB::raw('LOWER(TRIM(booking_items.item_name))'), '=', DB::raw('LOWER(TRIM(products.name))'))
            ->whereBetween('bookings.event_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->whereNotIn('bookings.status', ['Cancelled'])
            ->where('bookings.id', '!=', $this->booking->id)
            ->selectRaw('bookings.event_date, COALESCE(NULLIF(products.counts_against, ""), products.category) as cat, booking_items.qty');
        $this->dailyUsage = DB::table($sub, 't')
            ->selectRaw('event_date, cat, SUM(qty) as total')
            ->groupBy('event_date', 'cat')
            ->get()
            ->groupBy('event_date')
            ->map(fn($day) => $day->pluck('total', 'cat')->toArray())
            ->toArray();

        // 3b. Add Extras Usage to Daily Usage (Factor in Extras from existing bookings)
        $bookingsOnDates = Booking::whereBetween('event_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->whereNotIn('status', ['Cancelled'])
            ->where('id', '!=', $this->booking->id)
            ->select('event_date', 'extras_json')
            ->get();

        $addonCatMap = DB::table('category_addons')->get()->pluck('counts_against', 'id')->toArray();
        $dropdownCatMap = DB::table('product_dropdowns')->get()->pluck('counts_against', 'id')->toArray();
        $questionCatMap = DB::table('product_extras')->get()->pluck('counts_against', 'id')->toArray();

        foreach ($bookingsOnDates as $b) {
            $extras = json_decode($b->extras_json, true) ?: [];
            foreach ($extras as $key => $val) {
                $targetCat = null;
                if (str_starts_with($key, 'add_') && $val) {
                    $id = str_replace('add_', '', $key);
                    $targetCat = $addonCatMap[$id] ?? null;
                } elseif (str_starts_with($key, 'dd_') && $val && $val !== '0') {
                    $id = str_replace('dd_', '', $key);
                    $targetCat = $dropdownCatMap[$id] ?? null;
                } elseif (str_starts_with($key, 'q_') && $val && $val !== '0' && !str_ends_with($val, '|no')) {
                    $id = str_replace('q_', '', $key);
                    $targetCat = $questionCatMap[$id] ?? null;
                }

                if ($targetCat) {
                    $catKey = strtolower(trim($targetCat));
                    $this->dailyUsage[$b->event_date][$catKey] = ($this->dailyUsage[$b->event_date][$catKey] ?? 0) + 1;
                }
            }
        }

        // 4. Get Daily Name Duplicates (Same Customer)
        $duplicateDates = Booking::where('customer_first_name', $this->booking->customer_first_name)
            ->where('customer_last_name', $this->booking->customer_last_name)
            ->whereBetween('event_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->where('id', '!=', $this->booking->id)
            ->whereNotIn('status', ['Cancelled'])
            ->pluck('event_date')
            ->toArray();

        $this->calDays = [];
        for ($i = 0; $i < $start->dayOfWeek; $i++) $this->calDays[] = null;

        $globalDailyLimit = 7;
        for ($day = 1; $day <= $start->daysInMonth; $day++) {
            $dateStr = $start->copy()->day($day)->format('Y-m-d');
            $used = $counts[$dateStr] ?? 0;

            // Check for category breaches on this day
            $breachedCategories = [];
            $usageOnDay = $this->dailyUsage[$dateStr] ?? [];
            foreach ($this->bookingImpact as $cat => $demand) {
                $limit = $this->categoryLimits[$cat] ?? 0;
                if ($limit > 0) {
                    $currentUsage = $usageOnDay[$cat] ?? 0;
                    if (($currentUsage + $demand) > $limit) {
                        $breachedCategories[] = $cat;
                    }
                }
            }

            // Check for attraction conflicts on this day
            $dayAttractions = $this->dailyAttractions[$dateStr] ?? [];
            $hasConflict = !empty(array_intersect($this->bookedAttractions, $dayAttractions));

            // Check for name duplicates
            $hasDuplicate = in_array($dateStr, $duplicateDates);

            $this->calDays[] = [
                'date' => $dateStr,
                'day' => $day,
                'left' => max(0, $globalDailyLimit - $used),
                'breach' => !empty($breachedCategories),
                'conflict' => $hasConflict,
                'duplicate' => $hasDuplicate
            ];
        }
    }

    public function selectDate($dateStr)
    {
        $this->form['event_date'] = $dateStr;
        $this->tempSelectedDate = $dateStr;
        
        $this->refreshBookingImpact();
        
        $analysis = $this->performAnalysis($dateStr);
        $this->modalConflicts = $analysis['conflicts'];
        $this->modalCapacityBreaches = $analysis['capacityBreaches'];
        $this->modalNameConflicts = $analysis['nameConflicts'];

        $this->dispatch('date-selected', date: $dateStr);
        $this->dispatch('notify', title: 'Date Updated', message: "Booking has been moved to " . \Carbon\Carbon::parse($dateStr)->format('d M Y'), type: 'primary');

        if (!empty($this->modalNameConflicts) && $this->lastToastDate !== $dateStr) {
            $this->dispatch('notify', 
                title: 'Duplicate Contact Detected', 
                message: "This customer already has a booking on " . \Carbon\Carbon::parse($dateStr)->format('d M Y'),
                type: 'warning'
            );
            $this->lastToastDate = $dateStr;
        }
    }

    protected function performAnalysis($dateStr)
    {
        $conflicts = [];
        $capacityBreaches = [];

        // 1. Attraction Conflicts
        $dayAttractions = $this->dailyAttractions[$dateStr] ?? [];
        foreach ($this->bookedAttractions as $att) {
            $attClean = strtolower(trim($att));
            if (in_array($attClean, $dayAttractions)) {
                $conflicts[] = $att;
            }
        }

        // 2. Category Capacity Breaches
        $usageOnDay = $this->dailyUsage[$dateStr] ?? [];
        foreach ($this->bookingImpact as $cat => $demand) {
            $limit = $this->categoryLimits[$cat] ?? 0;
            if ($limit > 0) {
                $current = $usageOnDay[$cat] ?? 0;
                if (($current + $demand) > $limit) {
                    $capacityBreaches[$cat] = [
                        'current' => $current,
                        'added' => $demand,
                        'limit' => $limit
                    ];
                }
            }
        }

        // 3. Name Duplicates
        $nameConflicts = Booking::where('customer_first_name', $this->booking->customer_first_name)
            ->where('customer_last_name', $this->booking->customer_last_name)
            ->where('event_date', $dateStr)
            ->where('id', '!=', $this->booking->id)
            ->whereNotIn('status', ['Cancelled'])
            ->get()
            ->map(function($b) {
                $arr = $b->toArray();
                $items = DB::table('booking_items')->where('booking_id', $b->id)->pluck('item_name')->toArray();
                $arr['item_names_summary'] = count($items) > 0 ? implode(', ', array_slice($items, 0, 2)) . (count($items) > 2 ? '...' : '') : 'No Items';
                return $arr;
            })
            ->toArray();

        return [
            'conflicts' => $conflicts,
            'capacityBreaches' => $capacityBreaches,
            'nameConflicts' => $nameConflicts
        ];
    }

    public function markAttachmentDeleted($field)
    {
        $this->deletedAttachments[] = $field;
    }

    public function saveBooking($extras = null)
    {
        if ($extras) {
            $this->dynamicExtras = $extras;
        }
        
        // --- FINAL CONFLICT VALIDATION ---
        $this->refreshBookingImpact();
        if (!empty($this->activeConflicts) || !empty($this->activeCapacityBreaches)) {
            $msg = !empty($this->activeConflicts) ? "Attraction Conflict: " . implode(', ', $this->activeConflicts) : "Category Capacity Breach";
            $this->dispatch('notify', title: 'Save Blocked!', message: $msg . ". Please fix before saving.", type: 'error');
            return;
        }

        $saveData = $this->form;
        unset($saveData['is_custom_duration']);
        unset($saveData['custom_duration_text']);

        if (!empty($this->form['is_custom_duration'])) {
            $saveData['duration'] = $this->form['custom_duration_text'];
        }

        // --- SANITIZE TIME FIELDS ---
        // Ensure empty strings are treated as DEFAULTS to avoid SQL syntax errors on TIME columns
        $saveData['start_time'] = !empty($this->form['start_time']) ? $this->form['start_time'] : '00:00:00';
        $saveData['end_time'] = !empty($this->form['end_time']) ? $this->form['end_time'] : '23:59:59';

        // --- MANAGE ORIGINAL DATE TIMELINE ---
        // If this is the first time the date is being moved, preserve the original date
        if (isset($saveData['event_date']) && $saveData['event_date'] !== $this->booking->event_date) {
            if (empty($this->booking->original_event_date)) {
                $saveData['original_event_date'] = $this->booking->event_date;
            }
        }

        // --- PREVENT DATA CORRUPTION ---
        // Do not update internal identifiers, timestamps, or financials that are handled separately.
        $protectedFields = [
            'id', 'created_at', 'updated_at', 'invoice_number',
            'amount_paid', 'owing_amount', 'is_custom_duration', 'custom_duration_text'
        ];
        foreach ($protectedFields as $f) {
            unset($saveData[$f]);
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

        // --- BRIDGE ITEMS TO EXTRAS (Just-in-Time Sync) ---
        // --- Just-in-Time Sync between Ride Items and Extras ---
        $allAddons = DB::table('category_addons')->get()->keyBy('id');
        $allQuestions = DB::table('product_extras')->get();
        $allDropdownOpts = DB::table('dropdown_options')->get();
        $itemNames = array_map(fn($it) => strtolower(trim($it)), array_keys($this->selectedItems));
        
        // Match Addons
        foreach ($allAddons as $id => $addon) {
            if (in_array(strtolower(trim($addon->addon_label)), $itemNames)) {
                $this->dynamicExtras['add_' . $id] = "1";
            }
        }

        // Match Questions
        foreach ($allQuestions as $q) {
            if (in_array(strtolower(trim($q->question_text)), $itemNames)) {
                $this->dynamicExtras['q_' . $q->id] = $q->yes_price . '|yes';
            }
        }

        // Match Dropdowns
        foreach ($allDropdownOpts as $opt) {
            if (in_array(strtolower(trim($opt->option_label)), $itemNames)) {
                $this->dynamicExtras['dd_' . $opt->dropdown_id] = $opt->id;
            }
        }

        $saveData['general_extra'] = json_encode($generalExtras);
        $saveData['specific_extra'] = json_encode($specificExtras);
        $saveData['extras_json'] = json_encode($this->dynamicExtras);

        // --- MAP RECALCULATED TOTALS ---
        $saveData['surcharge_amount'] = $this->surchargeAmount;
        $saveData['total_amount'] = $this->totalAmount;

        // --- HANDLE ATTACHMENTS ---
        $totalSize = 0;
        $filesToSave = [];
        
        for ($i = 1; $i <= 5; $i++) {
            $field = ($i === 1) ? 'delivery_attachment' : 'delivery_attachment_' . $i;
            $tempProp = 'temp_attachment_' . $i;
            
            // Check if there is a new attachment for this slot
            if ($this->$tempProp) {
                $totalSize += $this->$tempProp->getSize();
                $filesToSave[$field] = $this->$tempProp;
            } 
            // Otherwise check if there is an existing attachment that wasn't deleted
            elseif (!empty($this->booking->$field) && !in_array($field, $this->deletedAttachments)) {
                $fileName = $this->booking->$field;
                $path1 = public_path('uploads/' . $fileName);
                $path2 = storage_path('app/public/uploads/' . $fileName);
                
                if (file_exists($path1)) {
                    $totalSize += filesize($path1);
                } elseif (file_exists($path2)) {
                    $totalSize += filesize($path2);
                }
            }
        }

        if ($totalSize > 5 * 1024 * 1024) {
            $existingMB = 0;
            $newMB = 0;
            foreach ($filesToSave as $field => $file) {
                $newMB += $file->getSize();
            }
            $existingMB = $totalSize - $newMB;

            $totalMB = number_format($totalSize / (1024 * 1024), 2);
            $newMBStr = number_format($newMB / (1024 * 1024), 2);
            $existingMBStr = number_format($existingMB / (1024 * 1024), 2);

            $detailedMsg = "Total size ({$totalMB}MB) exceeds 5MB limit. New: {$newMBStr}MB. Existing: {$existingMBStr}MB.";
            $this->dispatch('notify', title: 'Limit Exceeded!', message: $detailedMsg, type: 'error');
            return;
        }

        foreach ($filesToSave as $field => $file) {
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('uploads', $filename, 'public');
            $saveData[$field] = $filename;
        }

        foreach ($this->deletedAttachments as $field) {
            $saveData[$field] = null;
        }

        // Sync delivery cost to fee for compatibility
        $saveData['delivery_fee'] = $saveData['delivery_cost'] ?? 0;
        
        \Illuminate\Support\Facades\Log::info("Booking Update Payload:", $saveData);

        // --- SAVE TO DB ---
        try {
            Log::info("Attempting update for booking: " . $this->booking->id);
            $this->booking->update($saveData);

            BookingItem::where('booking_id', $this->booking->id)->delete();
            foreach ($this->selectedItems as $name => $data) {
                $product = DB::table('products')->whereRaw('LOWER(TRIM(name)) = ?', [$name])->first();
                BookingItem::create([
                    'booking_id' => $this->booking->id,
                    'item_name' => $product ? $product->name : ucwords($name),
                    'qty' => $data['qty'],
                    'item_price' => $data['price'] ?? ($product ? $product->price : 0),
                    'is_custom' => $product ? 0 : 1
                ]);
            }

            // RE-CALCULATE AND UPDATE CACHED COLUMNS
            $this->calculateTotals();
            $this->booking->save();
            $this->booking->syncFinancials();

            // Re-fetch for GS sync
            $this->booking = $this->booking->fresh();
            Log::info("Triggering Google Sheet Sync for: " . $this->booking->invoice_number);
            \App\Services\GoogleSheetService::sync($this->booking->id);
            
            $this->dispatch('booking-saved');
            $this->dispatch('notify', title: 'Success', message: 'Booking saved successfully.', type: 'success');

        } catch (\Exception $e) {
            Log::error("Save Booking Error: " . $e->getMessage());
            $this->dispatch('notify', title: 'Save Failed', message: $e->getMessage(), type: 'error');
            return;
        }
        if ($this->isSupervisor) {
            return redirect()->route('supervisor.bookings.overview', $this->booking->id);
        } else {
            return redirect()->route('admin.bookings.overview', $this->booking->id);
        }
    }

    public function removeAttachment($column)
    {
        // Clear any temporary un-saved uploads instantly
        switch($column) {
            case 'delivery_attachment': $this->temp_attachment_1 = null; break;
            case 'delivery_attachment_2': $this->temp_attachment_2 = null; break;
            case 'delivery_attachment_3': $this->temp_attachment_3 = null; break;
            case 'delivery_attachment_4': $this->temp_attachment_4 = null; break;
            case 'delivery_attachment_5': $this->temp_attachment_5 = null; break;
        }

        if (!$this->booking->id) return;

        if (!empty($this->booking->$column)) {
            $fileName = $this->booking->$column;
            
            // Delete physical files
            @unlink(public_path('uploads/' . $fileName));
            @unlink(storage_path('app/public/uploads/' . $fileName));

            // Clear from DB
            DB::table('bookings')->where('id', $this->booking->id)->update([$column => null]);
            
            // Update local state
            $this->booking->$column = null;
            $this->form[$column] = null;
        }
        
        $this->dispatch('notify', title: 'Success', message: 'Attachment removed.');
    }

    public function attachmentUploaded($column, $fileName)
    {
        $this->form[$column] = $fileName;
    }

    public function render()
    {
        // Bridge Data
        $this->categories = [];
        $catRes = DB::table('product_categories')->orderBy('sort_order')->get();
        $catMap = [];
        foreach ($catRes as $c) {
            $catKey = strtolower(trim($c->category_name));
            $this->categories[$c->category_name] = ['limit' => (int)$c->daily_limit, 'products' => []];
            $catMap[$catKey] = $c->category_name;
        }

        $products = DB::table('products')
            ->where('is_active', 1)
            ->when($this->search, function($q) {
                return $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $productCategoryMap = [];
        foreach ($products as $p) {
            $productCategoryMap[strtolower(trim($p->name))] = $p->counts_against ?: $p->category;
            $pCatKey = strtolower(trim($p->category));
            
            if (isset($catMap[$pCatKey])) {
                $this->categories[$catMap[$pCatKey]]['products'][] = (array)$p;
            } else {
                if (!isset($this->categories['Other'])) $this->categories['Other'] = ['limit' => 0, 'products' => []];
                $this->categories['Other']['products'][] = (array)$p;
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

        // Fetch configs for dynamicExtras matching new-booking logic 
        $this->config = [
            'addons' => DB::table('category_addons')->orderBy('category_target')->get()->groupBy('category_target')->map(function($g) { return $g->toArray(); })->toArray(),
            'questions' => DB::table('product_extras')->orderBy('category_target')->get()->groupBy('category_target')->map(function($g) { return $g->toArray(); })->toArray(),
            'dropdowns' => []
        ];

        
        $rawDropdowns = DB::table('product_dropdowns')->orderBy('sort_order')->get();
        $rawOpts = DB::table('dropdown_options')->get()->groupBy('dropdown_id');
        foreach ($rawDropdowns as $dd) {
            $ddArray = (array)$dd;
            $opts = $rawOpts->get($dd->id) ?? collect([]);
            $ddArray['options'] = $opts->map(function($o) { return (array)$o; })->toArray();
            $this->config['dropdowns'][$dd->category_target][] = $ddArray;
        }
        // Ensure dynamicExtras is an associative array (JSON object) for the JS bridge
        $this->saved_extras = (object)($this->dynamicExtras ?? []);
        $selectedItemsClean = $this->selectedItems;

        if ($this->tempSelectedDate) {
            $analysis = $this->performAnalysis($this->tempSelectedDate);
            $this->modalConflicts = $analysis['conflicts'];
            $this->modalCapacityBreaches = $analysis['capacityBreaches'];
            $this->modalNameConflicts = $analysis['nameConflicts'];
        }

        // Note: passing just other standard variables.
        return view('livewire.supervisor.edit-booking', compact('deliveryOptions', 'durationOptions', 'activeCategories', 'selectedItemsClean'));
    }
}
