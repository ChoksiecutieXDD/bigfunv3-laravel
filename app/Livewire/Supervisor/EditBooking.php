<?php

namespace App\Livewire\Supervisor;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
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

    public array $form = [];
    public array $selectedItems = [];
    public array $saved_extras = [];
    public array $extraPrices = [];
    public array $activeOverrides = [];
    public array $manualPrices = [];
    public array $lockedOverrides = [];
    public float $previousSubtotal = 0;
    public string $search = '';

    public float $subtotal = 0;
    public float $surchargeAmount = 0;
    public float $totalAmount = 0;
    public float $totalPaid = 0;
    public float $balanceDue = 0;
    public float $depositRequired = 0;

    public array $availability = [];
    public mixed $calMonth = null;
    public mixed $calYear = null;
    public array $calDays = [];
    public ?string $tempSelectedDate = null;

    public array $newAttachments = [];
    public array $deletedAttachments = [];

    public mixed $temp_attachment_1 = null;
    public mixed $temp_attachment_2 = null;
    public mixed $temp_attachment_3 = null;
    public mixed $temp_attachment_4 = null;
    public mixed $temp_attachment_5 = null;

    public array $bookedAttractions = [];
    public array $dailyAttractions = [];
    public array $dailyUsage = [];
    public array $bookingImpact = [];
    public array $modalConflicts = [];
    public array $modalCapacityBreaches = [];
    public array $modalNameConflicts = [];
    public array $activeConflicts = [];
    public array $activeCapacityBreaches = [];
    public ?string $lastToastDate = null;
    public ?string $backUrl = null;

    protected array $rules = [
        'temp_attachment_1' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
        'temp_attachment_2' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
        'temp_attachment_3' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
        'temp_attachment_4' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
        'temp_attachment_5' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
        'extraPrices' => 'array',
        'activeOverrides' => 'array',
        'manualPrices' => 'array',
        'lockedOverrides' => 'array',
        'saved_extras' => 'array',
        'selectedItems' => 'array'
    ];

    public bool $isSupervisor = false;
    public float $durationCost = 0;
    public float $deliveryCost = 0;
    public float $attractionsCost = 0;
    public float $extrasCost = 0;

    public array $selectedItemsClean = [];

    public function mount(int|string $id)
    {
        $this->backUrl = request()->query('back');
        $this->isSupervisor = str_contains(request()->url(), '/supervisor/');
        $this->booking = Booking::findOrFail($id);
        $this->form = $this->booking->toArray();

        if (empty($this->form['payment_type'])) $this->form['payment_type'] = 'EFT';
        if (empty($this->form['eft_method']) && $this->form['payment_type'] === 'EFT') $this->form['eft_method'] = 'Direct Deposit';

        $items = BookingItem::where('booking_id', $id)->get();
        $productPrices = DB::table('products')->pluck('price', DB::raw('LOWER(TRIM(name))'))->toArray();

        foreach ($items as $item) {
            $key = strtolower(trim($item->item_name));
            $storedPrice = (float) $item->item_price;

            $this->selectedItems[$key] = [
                'qty' => (int) $item->qty,
                'price' => $storedPrice
            ];

            $defaultPrice = (float) ($productPrices[$key] ?? 0);
            if (round($storedPrice, 2) !== round($defaultPrice, 2)) {
                $mKey = 'ride_' . $key;
                $this->activeOverrides[$mKey] = true;
                $this->manualPrices[$mKey] = $storedPrice;
                $this->lockedOverrides[$mKey] = true;
            }
        }

        $this->saved_extras = json_decode($this->booking->extras_json ?? '[]', true) ?? [];
        $this->extraPrices = [];

        $genExt = json_decode($this->booking->general_extra ?? '[]', true) ?? [];
        $specExt = json_decode($this->booking->specific_extra ?? '[]', true) ?? [];
        $allExt = array_merge($genExt, $specExt);

        $itemNames = array_map(fn($it) => strtolower(trim($it)), array_keys($this->selectedItems));

        $addons = DB::table('category_addons')->get();
        foreach ($addons as $a) {
            $addonKey = 'add_' . $a->id;
            $aLabel = strtolower(trim($a->addon_label));
            $aTarget = strtolower(trim($a->category_target));

            $foundPrice = null;
            foreach ($allExt as $label => $price) {
                $cleanLabel = strtolower(trim($label));
                if ($cleanLabel === $aLabel || $cleanLabel === $aTarget . ': ' . $aLabel || str_ends_with($cleanLabel, ': ' . $aLabel)) {
                    $foundPrice = (float)$price;
                    break;
                }
            }

            $isSelected = ($this->saved_extras[$addonKey] ?? '0') === '1';
            if (!$isSelected) {
                foreach ($itemNames as $itName) {
                    if ($itName === $aLabel || str_contains($itName, $aLabel)) {
                        $this->saved_extras[$addonKey] = "1";
                        $isSelected = true;
                        break;
                    }
                }
            }

            if ($isSelected) {
                $price = $this->selectedItems[$aLabel]['price'] ?? ($foundPrice ?? (float)$a->addon_price);
                $this->extraPrices[$addonKey] = (float)$price;

                if (round((float)$price, 2) !== round((float)$a->addon_price, 2)) {
                    $this->activeOverrides[$addonKey] = true;
                    $this->manualPrices[$addonKey] = (float)$price;
                    $this->lockedOverrides[$addonKey] = true;
                }
            } else {
                $this->extraPrices[$addonKey] = (float)$a->addon_price;
            }
        }

        $questions = DB::table('product_extras')->get();
        foreach ($questions as $q) {
            $qKey = 'q_' . $q->id;
            $qText = strtolower(trim($q->question_text));
            $qTarget = strtolower(trim($q->category_target));

            $foundPrice = null;
            $answer = null;
            foreach ($allExt as $label => $price) {
                $cleanLabel = strtolower(trim($label));
                if (str_contains($cleanLabel, $qText)) {
                    $foundPrice = (float)$price;
                    if (str_contains($cleanLabel, '(yes)')) $answer = 'yes';
                    if (str_contains($cleanLabel, '(no)')) $answer = 'no';
                    break;
                }
            }

            $val = $this->saved_extras[$qKey] ?? null;
            $isSelected = ($val && $val !== "0" && !str_ends_with($val, "|no"));

            if (!$isSelected) {
                foreach ($itemNames as $itName) {
                    if (str_contains($itName, $qText)) {
                        $this->saved_extras[$qKey] = $q->yes_price . '|yes';
                        $isSelected = true;
                        $answer = 'yes';
                        break;
                    }
                }
            }

            if ($isSelected || ($val && str_ends_with($val, "|no"))) {
                $isYes = $answer === 'yes' || (isset($this->saved_extras[$qKey]) && str_ends_with($this->saved_extras[$qKey], '|yes'));
                $basePrice = $isYes ? (float)$q->yes_price : (float)$q->no_price;

                $price = ($isYes && isset($this->selectedItems[$qText])) ? $this->selectedItems[$qText]['price'] : ($foundPrice ?? $basePrice);
                $this->extraPrices[$qKey] = (float)$price;

                if (round((float)$price, 2) !== round($basePrice, 2)) {
                    $this->activeOverrides[$qKey] = true;
                    $this->manualPrices[$qKey] = (float)$price;
                    $this->lockedOverrides[$qKey] = true;
                }
            } else {
                $this->extraPrices[$qKey] = (float)$q->yes_price;
            }
        }

        $dropdowns = DB::table('product_dropdowns')->get();
        $dropdownOptions = DB::table('dropdown_options')->get();
        foreach ($dropdowns as $dd) {
            $ddKey = 'dd_' . $dd->id;
            $ddLabel = strtolower(trim($dd->label));
            $ddTarget = strtolower(trim($dd->category_target));

            $selectedOptId = $this->saved_extras[$ddKey] ?? null;
            $foundOpt = null;

            if ($selectedOptId) {
                $foundOpt = $dropdownOptions->where('id', $selectedOptId)->first();
            } else {
                foreach ($dropdownOptions->where('dropdown_id', $dd->id) as $opt) {
                    $optLabel = strtolower(trim($opt->option_label));
                    foreach ($itemNames as $itName) {
                        if (str_contains($itName, $optLabel) && (str_contains($itName, $ddLabel) || str_contains($itName, $ddTarget))) {
                            $this->saved_extras[$ddKey] = $opt->id;
                            $foundOpt = $opt;
                            break 2;
                        }
                    }
                }
            }

            if ($foundOpt) {
                $optLabel = strtolower(trim($foundOpt->option_label));
                $foundPrice = null;
                foreach ($allExt as $label => $price) {
                    $cleanLabel = strtolower(trim($label));
                    if (str_contains($cleanLabel, $optLabel) && (str_contains($cleanLabel, $ddLabel) || str_contains($cleanLabel, $ddTarget))) {
                        $foundPrice = (float)$price;
                        break;
                    }
                }

                $price = $this->selectedItems[$optLabel]['price'] ?? ($foundPrice ?? (float)$foundOpt->option_price);
                $this->extraPrices[$ddKey] = (float)$price;

                if (round((float)$price, 2) !== round((float)$foundOpt->option_price, 2)) {
                    $this->activeOverrides[$ddKey] = true;
                    $this->manualPrices[$ddKey] = (float)$price;
                    $this->lockedOverrides[$ddKey] = true;
                }
            } else {
                $firstOpt = $dropdownOptions->where('dropdown_id', $dd->id)->first();
                $this->extraPrices[$ddKey] = $firstOpt ? (float)$firstOpt->option_price : 0;
            }
        }

        if (empty($this->booking->extras_json) || $this->booking->extras_json === '[]') {
            $allExtClean = array_combine(
                array_map(fn($k) => strtolower(trim($k)), array_keys($allExt)),
                array_values($allExt)
            );

            foreach ($addons as $a) {
                $aLabel = strtolower(trim($a->addon_label));
                $aCatLabel = strtolower(trim($a->category_target . ': ' . $a->addon_label));
                if (isset($allExtClean[$aLabel]) || isset($allExtClean[$aCatLabel])) {
                    $this->saved_extras['add_' . $a->id] = "1";
                }
            }

            foreach ($dropdowns as $d) {
                foreach ($dropdownOptions->where('dropdown_id', $d->id) as $o) {
                    $search1 = strtolower(trim($d->label . ' - ' . $o->option_label));
                    $search2 = strtolower(trim($d->category_target . ': ' . $d->label . ' - ' . $o->option_label));
                    if (isset($allExtClean[$search1]) || isset($allExtClean[$search2])) {
                        $this->saved_extras['dd_' . $d->id] = (string)$o->id;
                    }
                }
            }

            foreach ($questions as $q) {
                $qText = strtolower(trim($q->question_text));
                foreach ($allExtClean as $extKey => $extVal) {
                    if (str_contains($extKey, $qText)) {
                        $isYes = str_contains($extKey, '(yes)');
                        $valToSet = $isYes ? $q->yes_price . '|yes' : $q->no_price . '|no';
                        $this->saved_extras['q_' . $q->id] = $valToSet;
                    }
                }
            }
        }
        $this->saved_extras = $this->saved_extras;

        $this->calMonth = Carbon::parse($this->form['event_date'])->month;
        $this->calYear = Carbon::parse($this->form['event_date'])->year;

        $this->tempSelectedDate = $this->form['event_date'];

        $durationLabels = DB::table('duration_prices')->pluck('label')->toArray();
        if (!empty($this->form['duration']) && !in_array($this->form['duration'], $durationLabels)) {
            $this->form['custom_duration_text'] = $this->form['duration'];
            $this->form['duration'] = 'custom';
            $this->form['is_custom_duration'] = true;
        } else {
            $this->form['is_custom_duration'] = false;
            $this->form['custom_duration_text'] = '';
        }

        if ((float)($this->form['delivery_cost'] ?? 0) === 0.0 && !empty($this->form['delivery_area']) && $this->form['delivery_area'] !== 'custom') {
            $zone = DB::table('delivery_zones')->where('zone_name', $this->form['delivery_area'])->first();
            if ($zone) {
                $this->form['delivery_cost'] = $zone->price;
            }
        }

        $this->refreshBookingImpact();
        if ((float)($this->form['duration_cost'] ?? 0) === 0.0 && !empty($this->form['duration']) && $this->form['duration'] !== 'custom') {
            $dur = DB::table('duration_prices')->where('label', $this->form['duration'])->first();
            if ($dur) {
                $this->form['duration_cost'] = $dur->price;
            }
        }

        $this->totalPaid = DB::table('booking_payments')->where('booking_id', $this->booking->id)->sum('amount') ?: 0;
        $this->loadCalendar();
        $this->checkAvailability();
        $this->calculateTotals();
        $this->syncSelectedItemsClean();
    }

    #[Computed]
    public function config(): array
    {
        $config = ['questions' => [], 'addons' => [], 'dropdowns' => []];
        $questions = DB::table('product_extras')->orderBy('category_target', 'asc')->get();
        foreach ($questions as $q) {
            $config['questions'][$q->category_target][] = (array)$q;
        }
        $addons = DB::table('category_addons')->orderBy('category_target', 'asc')->get();
        foreach ($addons as $a) {
            $config['addons'][$a->category_target][] = (array)$a;
        }
        $dropdowns = DB::table('product_dropdowns')->orderBy('sort_order', 'asc')->get();
        foreach ($dropdowns as $d) {
            $opts = DB::table('dropdown_options')->where('dropdown_id', $d->id)->get()->toArray();
            $dArray = (array)$d;
            $dArray['options'] = array_map(fn($o) => (array)$o, $opts);
            $config['dropdowns'][$d->category_target][] = $dArray;
        }
        return $config;
    }

    #[Computed]
    public function categories(): array
    {
        $categories = [];
        $cats = DB::table('product_categories')->orderBy('sort_order', 'asc')->get();
        foreach ($cats as $c) {
            $categories[$c->category_name] = ['limit' => (int)$c->daily_limit, 'products' => []];
        }
        return $categories;
    }

    #[Computed]
    public function categoryLimits(): array
    {
        return DB::table('product_categories')
            ->where('daily_limit', '>', 0)
            ->pluck('daily_limit', 'category_name')
            ->toArray();
    }

    #[Computed]
    public function staffList(): array
    {
        $list = \App\Models\User::whereIn('role', ['Staff', 'Operator', 'Supervisor'])
            ->where('is_active', 1)
            ->orderBy('first_name')
            ->get()
            ->map(fn($u) => trim($u->first_name . ' ' . $u->last_name))
            ->toArray();
        return empty($list) ? ["Team"] : $list;
    }

    private function syncSelectedItemsClean()
    {
        $this->selectedItemsClean = array_keys($this->selectedItems);
    }

    public function updatedForm(mixed $value, string $key)
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
                $this->form['start_time'] = '00:00:00';
                $this->form['end_time'] = '23:59:59';
            }
            $this->calculateTotals();
        }

        if ($key === 'duration_cost') {
            $this->calculateTotals();
        }
    }

    public function updatedSavedExtras()
    {
        $this->calculateTotals();
    }

    public function updatedSelectedItems()
    {
        $this->calculateTotals();
        $this->syncSelectedItemsClean();
        $this->checkAvailability();
        $this->refreshBookingImpact();
        $this->loadCalendar();
    }

    public function updatedExtraPrices()
    {
        $this->calculateTotals();
    }

    public function updatedManualPrices()
    {
        $this->calculateTotals();
    }

    private function syncSavedExtrasWithItems()
    {
        // Addons
        $addons = DB::table('category_addons')->get();
        foreach ($addons as $a) {
            $key = 'add_' . $a->id;
            $name = strtolower(trim($a->addon_label));

            $val = $this->saved_extras[$key] ?? null;
            $isSelected = ($val && $val !== "0" && is_string($val) && !str_ends_with($val, "|no"));

            if (!$isSelected) {
                if (isset($this->selectedItems[$name])) {
                    unset($this->selectedItems[$name]);
                    Log::info("syncSavedExtrasWithItems: REMOVED item '$name' because its corresponding extra '$key' is unselected (val: " . var_export($val, true) . ")");
                }
            } else {
                if (!isset($this->selectedItems[$name])) {
                    Log::info("syncSavedExtrasWithItems: ADDING item '$name' because its corresponding extra '$key' is selected.");
                    $product = DB::table('products')->whereRaw('LOWER(TRIM(name)) = ?', [$name])->first();

                    // Priority: 1. Manual Override Registry, 2. Current ExtraPrice, 3. Product Default
                    $price = $this->manualPrices[$key] ?? ($this->extraPrices[$key] ?? ($product ? (float)$product->price : 0));

                    $this->selectedItems[$name] = [
                        'qty' => 1,
                        'price' => (float)$price
                    ];
                    $this->extraPrices[$key] = (float)$price;

                    // If we used a manual price, make sure it stays in extraPrices
                    if (isset($this->manualPrices[$key])) {
                        $this->extraPrices[$key] = (float)$this->manualPrices[$key];
                    }
                } else {
                    // Item exists, but we MUST enforce the manual price if it's in our registry
                    $price = $this->manualPrices[$key] ?? ($this->extraPrices[$key] ?? null);
                    if ($price !== null) {
                        $this->selectedItems[$name]['price'] = (float)$price;
                        $this->extraPrices[$key] = (float)$price;
                    }
                }
            }
        }

        // Questions
        $questions = DB::table('product_extras')->get();
        foreach ($questions as $q) {
            $key = 'q_' . $q->id;
            $name = strtolower(trim($q->question_text));

            $val = $this->saved_extras[$key] ?? null;
            $isSelected = ($val && is_string($val) && $val !== "0" && !str_ends_with($val, '|no'));

            if (!$isSelected) {
                if (isset($this->selectedItems[$name])) {
                    unset($this->selectedItems[$name]);
                }
            } else {
                if (!isset($this->selectedItems[$name])) {
                    $product = DB::table('products')->whereRaw('LOWER(TRIM(name)) = ?', [$name])->first();
                    $price = $this->manualPrices[$key] ?? ($this->extraPrices[$key] ?? ($product ? (float)$product->price : 0));
                    $this->selectedItems[$name] = [
                        'qty' => 1,
                        'price' => (float)$price
                    ];
                    $this->extraPrices[$key] = (float)$price;
                    if (isset($this->manualPrices[$key])) {
                        $this->extraPrices[$key] = (float)$this->manualPrices[$key];
                    }
                } else {
                    // Item exists, but we MUST enforce the manual price if it's in our registry
                    $price = $this->manualPrices[$key] ?? ($this->extraPrices[$key] ?? null);
                    if ($price !== null) {
                        $this->selectedItems[$name]['price'] = (float)$price;
                        $this->extraPrices[$key] = (float)$price;
                    }
                }
            }
        }

        // Dropdowns
        $dropdownOptions = DB::table('dropdown_options')->get();
        foreach ($dropdownOptions as $opt) {
            $key = 'dd_' . $opt->dropdown_id;
            $name = strtolower(trim($opt->option_label));

            $isSelected = isset($this->saved_extras[$key]) && (string)$this->saved_extras[$key] === (string)$opt->id;

            if (!$isSelected) {
                // If it was selected before but now it's not THIS specific option, remove it
                // (Another option in same dropdown might be selected and added in its own iteration)
                if (isset($this->selectedItems[$name]) && isset($this->saved_extras[$key])) {
                    // Check if ANY other option of THIS dropdown is selected
                    if ((string)$this->saved_extras[$key] !== (string)$opt->id) {
                        unset($this->selectedItems[$name]);
                    }
                }
            } else {
                if (!isset($this->selectedItems[$name])) {
                    $product = DB::table('products')->whereRaw('LOWER(TRIM(name)) = ?', [$name])->first();
                    $price = $this->manualPrices[$key] ?? ($this->extraPrices[$key] ?? (float)$opt->option_price);
                    $this->selectedItems[$name] = [
                        'qty' => 1,
                        'price' => (float)$price
                    ];
                    $this->extraPrices[$key] = (float)$price;
                    if (isset($this->manualPrices[$key])) {
                        $this->extraPrices[$key] = (float)$this->manualPrices[$key];
                    }
                } else {
                    // Item exists, but we MUST enforce the manual price if it's in our registry
                    $price = $this->manualPrices[$key] ?? ($this->extraPrices[$key] ?? null);
                    if ($price !== null) {
                        $this->selectedItems[$name]['price'] = (float)$price;
                        $this->extraPrices[$key] = (float)$price;
                    }
                }
            }
        }

        $this->syncSelectedItemsClean();
    }

    public function syncExtras(array $extras)
    {
        // Use array_merge to preserve state for items not currently in the DOM
        $this->saved_extras = array_merge($this->saved_extras, $extras);

        // Ensure extraPrices has keys for all extras
        foreach ($extras as $key => $val) {
            $isSelected = ($val && $val !== "0" && is_string($val) && !str_ends_with($val, "|no"));

            if ($isSelected) {
                // ONLY initialize if NOT set at all. If it's 0, it might be a valid manual override.
                if (isset($this->manualPrices[$key])) {
                    $this->extraPrices[$key] = (float)$this->manualPrices[$key];
                } elseif (!isset($this->extraPrices[$key])) {
                    if (str_starts_with($key, 'add_')) {
                        $id = str_replace('add_', '', $key);
                        $addon = DB::table('category_addons')->where('id', $id)->first();
                        $this->extraPrices[$key] = $addon ? (float)$addon->addon_price : 0;
                    } elseif (str_starts_with($key, 'dd_')) {
                        if ($val && $val !== "0") {
                            $opt = DB::table('dropdown_options')->where('id', $val)->first();
                            $this->extraPrices[$key] = $opt ? (float)$opt->option_price : 0;
                        } else {
                            $this->extraPrices[$key] = 0;
                        }
                    } elseif (str_starts_with($key, 'q_')) {
                        $parts = explode('|', $val);
                        $this->extraPrices[$key] = (float)($parts[0] ?? 0);
                    }
                }
            }
            // CRITICAL: Do NOT reset to 0 if !isSelected. 
            // We want to preserve the manual override even if the user toggles it off and back on.
        }

        $this->booking->extras_json = json_encode($extras);
        $this->calculateTotals();
    }

    public function updateExtraPrice(string $key, mixed $price)
    {
        $this->extraPrices[$key] = (float)$price;
        $this->manualPrices[$key] = (float)$price;
        $this->activeOverrides[$key] = true;
        $this->lockedOverrides[$key] = true;

        // Forcefully save to database registry
        $specExt = json_decode($this->booking->specific_extra ?? '[]', true) ?? [];
        $label = $this->getExtraLabel($key);
        if ($label) {
            $specExt[$label] = (float)$price;
            $this->booking->specific_extra = json_encode($specExt);
            $this->booking->save();
        }

        $this->calculateTotals();
    }

    private function getExtraLabel(string $key): ?string
    {
        if (str_starts_with($key, 'add_')) {
            return DB::table('category_addons')->where('id', str_replace('add_', '', $key))->value('addon_label');
        }
        if (str_starts_with($key, 'q_')) {
            $q = DB::table('product_extras')->where('id', str_replace('q_', '', $key))->first();
            return $q ? $q->question_text . ' (yes)' : null;
        }
        if (str_starts_with($key, 'dd_')) {
            // Need to find which dropdown it is
            $ddId = str_replace('dd_', '', $key);
            $dd = DB::table('product_dropdowns')->where('id', $ddId)->first();
            $optId = $this->saved_extras[$key] ?? null;
            if ($dd && $optId) {
                $opt = DB::table('dropdown_options')->where('id', $optId)->first();
                return $opt ? $dd->label . ': ' . $opt->option_label : null;
            }
        }
        if (str_starts_with($key, 'ride_')) {
            return str_replace('ride_', '', $key);
        }
        return null;
    }

    public function updateOverrideState(string $key, bool $isActive)
    {
        $this->activeOverrides[$key] = $isActive;
        if (!$isActive) {
            unset($this->manualPrices[$key]);
            unset($this->lockedOverrides[$key]);
        }
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

    public function toggleItem(string $itemName, ?bool $isChecked = null)
    {
        $key = strtolower(trim($itemName));
        Log::info("toggleItem called for: $itemName (key: $key), isChecked: " . var_export($isChecked, true));
        // Determine if there's an extra override for this item
        $overridePrice = null;
        $addons = DB::table('category_addons')->pluck('id', DB::raw('LOWER(TRIM(addon_label))'))->toArray();
        $opts = DB::table('dropdown_options')->pluck('dropdown_id', DB::raw('LOWER(TRIM(option_label))'))->toArray();
        $questions = DB::table('product_extras')->pluck('id', DB::raw('LOWER(TRIM(question_text))'))->toArray();

        if (isset($addons[$key]) && isset($this->extraPrices['add_' . $addons[$key]])) $overridePrice = (float)$this->extraPrices['add_' . $addons[$key]];
        elseif (isset($opts[$key]) && isset($this->extraPrices['dd_' . $opts[$key]])) $overridePrice = (float)$this->extraPrices['dd_' . $opts[$key]];
        elseif (isset($questions[$key]) && isset($this->extraPrices['q_' . $questions[$key]])) $overridePrice = (float)$this->extraPrices['q_' . $questions[$key]];

        try {
            if ($isChecked === true) {
                if (!isset($this->selectedItems[$key])) {
                    $product = DB::table('products')->whereRaw('LOWER(TRIM(name)) = ?', [$key])->first();
                    $this->selectedItems[$key] = [
                        'qty' => 1,
                        'price' => $overridePrice ?? ($product ? (float)$product->price : 0)
                    ];
                }
                // SYNC EXTRAS: If this ride is also an extra, mark it selected in saved_extras
                $this->syncExtraState($key, true);
            } elseif ($isChecked === false) {
                Log::info("toggleItem: Processing UNCHECK for $key");
                if (isset($this->selectedItems[$key])) {
                    $productName = DB::table('products')->whereRaw('LOWER(TRIM(name)) = ?', [$key])->value('name') ?? $key;
                    unset($this->selectedItems[$key]);
                    Log::info("toggleItem: UNSET selectedItems[$key]");
                }
                // SYNC EXTRAS: If this ride is also an extra, mark it UNSELECTED in saved_extras
                $this->syncExtraState($key, false);
            } else {
                // Traditional toggle if no state provided
                if (isset($this->selectedItems[$key])) {
                    $productName = DB::table('products')->whereRaw('LOWER(TRIM(name)) = ?', [$key])->value('name') ?? $key;
                    unset($this->selectedItems[$key]);
                    $isChecked = false;
                    $this->syncExtraState($key, false);
                } else {
                    $product = DB::table('products')->whereRaw('LOWER(TRIM(name)) = ?', [$key])->first();
                    $productName = $product ? $product->name : $key;
                    $isChecked = true;

                    // CRITICAL FIX: If this ride is also an extra, check if we have a price override for it
                    $effectivePrice = null;
                    if ($product) {
                        $cleanName = strtolower(trim($product->name));
                        // Check addons
                        $addon = DB::table('category_addons')->whereRaw('LOWER(TRIM(addon_label)) = ?', [$cleanName])->first();
                        if ($addon && isset($this->extraPrices['add_' . $addon->id])) {
                            $effectivePrice = (float)$this->extraPrices['add_' . $addon->id];
                        }
                        // Check questions
                        if (!$effectivePrice) {
                            $q = DB::table('product_extras')->whereRaw('LOWER(TRIM(question_text)) = ?', [$cleanName])->first();
                            if ($q && isset($this->extraPrices['q_' . $q->id])) {
                                $effectivePrice = (float)$this->extraPrices['q_' . $q->id];
                            }
                        }
                        // Check dropdowns
                        if (!$effectivePrice) {
                            $opt = DB::table('dropdown_options')->whereRaw('LOWER(TRIM(option_label)) = ?', [$cleanName])->first();
                            if ($opt && isset($this->extraPrices['dd_' . $opt->dropdown_id])) {
                                $effectivePrice = (float)$this->extraPrices['dd_' . $opt->dropdown_id];
                            }
                        }
                    }

                    $this->selectedItems[$key] = [
                        'qty' => 1,
                        'price' => $effectivePrice ?? ($overridePrice ?? ($product ? (float)$product->price : 0))
                    ];
                    $this->syncExtraState($key, true);
                }
            }
            $this->calculateTotals($productName ?? ($product->name ?? $key), $isChecked === true ? 'added' : 'removed');
        } catch (\Exception $e) {
            Log::error("toggleItem Error for $itemName: " . $e->getMessage());
            $this->dispatch('show-toast', title: 'Error', message: 'Could not update selection: ' . $e->getMessage(), type: 'error');
        }
    }

    private function syncExtraState(string $name, bool $isSelected)
    {
        $cleanName = strtolower(trim($name));

        // 1. Addons
        $addon = DB::table('category_addons')->whereRaw('LOWER(TRIM(addon_label)) = ?', [$cleanName])->first();
        if ($addon) {
            $this->saved_extras['add_' . $addon->id] = $isSelected ? "1" : "0";
        }

        // 2. Questions
        $q = DB::table('product_extras')->whereRaw('LOWER(TRIM(question_text)) = ?', [$cleanName])->first();
        if ($q) {
            $this->saved_extras['q_' . $q->id] = $isSelected ? $q->yes_price . '|yes' : $q->no_price . '|no';
        }

        // 3. Dropdowns
        $opt = DB::table('dropdown_options')->whereRaw('LOWER(TRIM(option_label)) = ?', [$cleanName])->first();
        if ($opt) {
            $this->saved_extras['dd_' . $opt->dropdown_id] = $isSelected ? (string)$opt->id : "0";
        }
    }

    public function updateItemQty(string $itemName, int $change)
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

    public function updateManualPrice(string $name, mixed $price)
    {
        $key = strtolower(trim($name));
        if (isset($this->selectedItems[$key])) {
            $this->selectedItems[$key]['price'] = (float)$price;
            $this->calculateTotals();
        }
    }



    public function calculateTotals(?string $itemName = null, ?string $action = null)
    {
        // Clean up garbage keys from Alpine/Livewire serialization
        if (is_array($this->saved_extras)) {
            $this->saved_extras = array_filter($this->saved_extras, function ($key) {
                return is_string($key) && (str_starts_with($key, 'add_') || str_starts_with($key, 'q_') || str_starts_with($key, 'dd_'));
            }, ARRAY_FILTER_USE_KEY);
        }

        $this->syncSavedExtrasWithItems();

        // Store current subtotal before calculation to detect changes
        $oldSubtotal = (float)($this->form['subtotal'] ?? 0);

        Log::info("calculateTotals started. Current selectedItems: " . json_encode($this->selectedItems));
        $ridesTotal = 0;
        $products = DB::table('products')->pluck('price', DB::raw('LOWER(TRIM(name))'))->toArray();

        foreach ($this->selectedItems as $name => $data) {
            if (!is_array($data)) {
                Log::warning("Malformed item in selectedItems for '$name': " . json_encode($data));
                continue;
            }

            // Critical check: If this selected item has a manual override, use it!
            $price = (float) ($data['price'] ?? 0);

            // Check if there is an override for this ride/item name in manualPrices
            foreach ($this->manualPrices as $mKey => $mPrice) {
                $mLabel = strtolower(trim($this->getExtraLabel($mKey) ?? ''));
                if ($mLabel === strtolower(trim($name))) {
                    $price = (float)$mPrice;
                    Log::info("calculateTotals: Applied manual override for ride '$name' from manualPrices[$mKey]: $price");
                    break;
                }
            }

            $qty = (int) ($data['qty'] ?? 1);

            $ridesTotal += ($price * $qty);
        }

        Log::info("calculateTotals: saved_extras: " . json_encode($this->saved_extras));
        $extrasTotal = 0;
        $addons = DB::table('category_addons')->pluck('addon_price', 'id');
        $opts = DB::table('dropdown_options')->pluck('option_price', 'id');

        // Pre-map addon/dropdown labels for double-counting check
        $extraLabels = [];
        foreach ($addons as $id => $p) $extraLabels['add_' . $id] = strtolower(trim(DB::table('category_addons')->where('id', $id)->value('addon_label') ?: ''));
        foreach ($opts as $id => $p) $extraLabels['dd_' . DB::table('dropdown_options')->where('id', $id)->value('dropdown_id')] = strtolower(trim(DB::table('dropdown_options')->where('id', $id)->value('option_label') ?: ''));
        foreach (DB::table('product_extras')->get() as $q) $extraLabels['q_' . $q->id] = strtolower(trim($q->question_text));

        foreach ($this->saved_extras as $key => $val) {
            if (!$val || $val === "0" || (is_string($val) && str_ends_with($val, '|no'))) continue;

            // Skip price contribution if this extra is already counted in selectedItems (rides)
            if (isset($extraLabels[$key]) && isset($this->selectedItems[$extraLabels[$key]])) {
                Log::info("calculateTotals: Skipping price for extra '$key' because it is already in selectedItems.");
                continue;
            }

            // If val is an array, skip it for now to avoid crashes (unexpected state)
            if (is_array($val)) {
                Log::warning("saved_extras contains an array for key '$key': " . json_encode($val));
                continue;
            }

            if (isset($this->manualPrices[$key])) {
                $extrasTotal += (float)$this->manualPrices[$key];
            } elseif (isset($this->extraPrices[$key])) {
                $extrasTotal += (float)$this->extraPrices[$key];
            } else {
                // Fallback
                if (str_starts_with($key, 'add_')) {
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
        }

        $this->durationCost = (float) ($this->form['duration_cost'] ?? 0);
        $this->deliveryCost = (float) ($this->form['delivery_cost'] ?? 0);
        $this->attractionsCost = $ridesTotal;
        $this->extrasCost = $extrasTotal;

        $this->subtotal = round($this->attractionsCost + $this->extrasCost + $this->durationCost + $this->deliveryCost, 2);

        if (in_array(($this->form['payment_type'] ?? ''), ['Card Holder', 'credit_card'])) {
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

        Log::info("calculateTotals FINAL BREAKDOWN:", [
            'rides' => $this->attractionsCost,
            'extras' => $this->extrasCost,
            'duration' => $this->durationCost,
            'delivery' => $this->deliveryCost,
            'subtotal' => $this->subtotal,
            'surcharge' => $this->surchargeAmount,
            'total' => $this->totalAmount,
            'paid' => $this->totalPaid,
            'balance' => $this->balanceDue,
            'saved_extras' => $this->saved_extras
        ]);

        // --- DISPATCH SYNC EVENTS ---
        $this->dispatch(
            'booking-preview-updated',
            extras: $this->saved_extras,
            selectedItems: $this->selectedItems,
            totals: [
                'subtotal' => $this->subtotal,
                'total' => $this->totalAmount,
                'balance' => $this->balanceDue,
                'surcharge' => $this->surchargeAmount,
                'duration' => $this->durationCost,
                'delivery' => $this->deliveryCost,
                'attractions' => $this->attractionsCost,
                'extras' => $this->extrasCost
            ]
        );

        // --- SMART NOTIFICATION CONSOLIDATION ---
        if (abs($this->totalAmount - $oldTotal) > 0.01) {
            $delta = abs($this->totalAmount - $oldTotal);
            $deltaStr = '$' . number_format($delta, 2);
            $totalStr = '$' . number_format($this->totalAmount, 2);
            $balanceStr = '$' . number_format($this->balanceDue, 2);
            $direction = ($this->totalAmount > $oldTotal) ? 'increased' : 'decreased';

            if ($itemName && $action) {
                $title = ($action === 'added') ? 'Attraction Added' : 'Attraction Removed';
                $type = ($action === 'added') ? 'success' : 'warning';
                $costMsg = "Cost $direction by $deltaStr.";
                $this->dispatch('show-toast', title: $title, message: "$itemName has been $action. $costMsg New Balance: $balanceStr", type: $type);
            } else {
                $title = ($this->totalAmount > $oldTotal) ? 'Cost Increased' : 'Cost Decreased';
                $type = ($this->totalAmount > $oldTotal) ? 'info' : 'success';
                $this->dispatch('show-toast', title: $title, message: "Total $direction by $deltaStr. Your balance is now $balanceStr", type: $type);
            }
        } elseif ($itemName && $action) {
            $balanceStr = '$' . number_format($this->balanceDue, 2);
            $this->dispatch('show-toast', title: ($action === 'added' ? 'Attraction Added' : 'Attraction Removed'), message: "$itemName has been $action. Your balance is now $balanceStr", type: ($action === 'added' ? 'success' : 'warning'));
        }

        if ($this->balanceDue < -0.01) {
            $this->dispatch('show-toast', title: 'Refund Required', message: "The total is now less than the amount already paid.", type: 'warning');
        }

        $this->dispatch('recalculate-requested');
    }

    public function checkAvailability()
    {
        $date = $this->form['event_date'] ?? now()->format('Y-m-d');

        // 1. Fetch Product Usage
        $usage = DB::table('booking_items')
            ->join('bookings', 'booking_items.booking_id', '=', 'bookings.id')
            ->where('bookings.event_date', $date)
            ->where('bookings.id', '!=', $this->booking->id)
            ->whereNotIn('bookings.status', ['Cancelled'])
            ->selectRaw('LOWER(TRIM(booking_items.item_name)) as name, SUM(booking_items.qty) as total')
            ->groupBy('name')
            ->pluck('total', 'name')
            ->toArray();

        // 2. Fetch Category Limits & Calculate Category Usage
        $catLimits = DB::table('product_categories')->pluck('daily_limit', 'category_name')->toArray();
        $catUsage = [];

        $products = DB::table('products')->where('is_active', 1)->get();

        foreach ($products as $p) {
            $cleanName = strtolower(trim($p->name));
            $used = $usage[$cleanName] ?? 0;
            if ($used > 0) {
                $targetCat = $p->counts_against ?: $p->category;
                if ($targetCat) {
                    $catUsage[$targetCat] = ($catUsage[$targetCat] ?? 0) + $used;
                }
            }
        }

        $this->availability = [];

        foreach ($products as $p) {
            $cleanName = strtolower(trim($p->name));
            $limit = (int) $p->daily_limit;
            $stock = (int) $p->total_quantity;
            $used = $usage[$cleanName] ?? 0;
            $targetCat = $p->counts_against ?: $p->category;

            // Base availability is physical stock
            $left = max(0, $stock - $used);

            // Cap by Product Daily Limit
            if ($limit > 0) {
                $limit_left = max(0, $limit - $used);
                if ($limit_left < $left) $left = $limit_left;
            }

            // Cap by Category Daily Limit
            if ($targetCat && isset($catLimits[$targetCat]) && $catLimits[$targetCat] > 0) {
                $cLimit = (int)$catLimits[$targetCat];
                $cUsed = $catUsage[$targetCat] ?? 0;
                $cLeft = max(0, $cLimit - $cUsed);
                if ($cLeft < $left) $left = $cLeft;
            }

            $this->availability[$cleanName] = [
                'used' => $used,
                'limit' => $limit,
                'left' => $left,
                'sold_out' => ($left <= 0)
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

        foreach ($this->saved_extras as $key => $val) {
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

    public function selectDate(string $dateStr)
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
            $this->dispatch(
                'notify',
                title: 'Duplicate Contact Detected',
                message: "This customer already has a booking on " . \Carbon\Carbon::parse($dateStr)->format('d M Y'),
                type: 'warning'
            );
            $this->lastToastDate = $dateStr;
        }
    }

    protected function performAnalysis(string $dateStr)
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
            ->map(function ($b) {
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

    public function markAttachmentDeleted(string $field)
    {
        $this->deletedAttachments[] = $field;
    }

    public function saveBooking(?array $extras = null)
    {
        $this->dispatch('notify', title: 'Processing...', message: 'Initiating save sequence...', type: 'info');
        
        try {
            if ($extras && is_array($extras)) {
                $this->saved_extras = array_merge($this->saved_extras, $extras);
            }

            $this->syncSavedExtrasWithItems();

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
                'id',
                'created_at',
                'updated_at',
                'invoice_number',
                'amount_paid',
                'owing_amount',
                'is_custom_duration',
                'custom_duration_text',
                'surcharge_amount',
                'total_amount',
                'payment_status',
                'is_debtor',
                'delivery_fee',
                'extras_json',
                'specific_extra',
                'general_extra'
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

            foreach ($this->saved_extras as $key => $val) {
                if (str_starts_with($key, 'add_') && $val) {
                    $id = str_replace('add_', '', $key);
                    if ($addon = $allAddons->get($id)) {
                        $price = (float)($this->manualPrices[$key] ?? ($this->extraPrices[$key] ?? $addon->addon_price));
                        if ($addon->category_target === 'General Logistics') {
                            $generalExtras[$addon->addon_label] = $price;
                        } else {
                            $specificExtras[$addon->category_target . ': ' . $addon->addon_label] = $price;
                        }
                    }
                }
                if (str_starts_with($key, 'dd_') && $val) {
                    $ddId = str_replace('dd_', '', $key);
                    if (($dd = $allDropdowns->get($ddId)) && ($opt = $allOptions->get($val))) {
                        $label = $dd->label . ' - ' . $opt->option_label;
                        $price = (float)($this->manualPrices[$key] ?? ($this->extraPrices[$key] ?? $opt->option_price));
                        if ($dd->category_target === 'General Logistics') {
                            $generalExtras[$label] = $price;
                        } else {
                            $specificExtras[$dd->category_target . ': ' . $label] = $price;
                        }
                    }
                }
                if (str_starts_with($key, 'q_') && $val) {
                    $qId = str_replace('q_', '', $key);
                    if ($q = $allQuestions->get($qId)) {
                        $parts = explode('|', $val);
                        $price = (float)($this->manualPrices[$key] ?? ($this->extraPrices[$key] ?? ($parts[0] ?? 0)));
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

            // --- Just-in-Time Sync between Ride Items and Extras ---
            $itemNames = array_map(fn($it) => strtolower(trim($it)), array_keys($this->selectedItems));

            // Match Addons - Only sync if not explicitly unselected (set to '0')
            foreach ($allAddons as $id => $addon) {
                $key = 'add_' . $id;
                if (in_array(strtolower(trim($addon->addon_label)), $itemNames)) {
                    if (!isset($this->saved_extras[$key]) || $this->saved_extras[$key] !== "0") {
                        $this->saved_extras[$key] = "1";
                    }
                }
            }

            // Match Questions - Only sync if not explicitly unselected (ends with '|no' or set to '0')
            foreach ($allQuestions as $q) {
                $key = 'q_' . $q->id;
                if (in_array(strtolower(trim($q->question_text)), $itemNames)) {
                    if (!isset($this->saved_extras[$key]) || (!str_ends_with($this->saved_extras[$key], '|no') && $this->saved_extras[$key] !== "0")) {
                        $this->saved_extras[$key] = $q->yes_price . '|yes';
                    }
                }
            }

            // Match Dropdowns - Only sync if not explicitly unselected (set to '0' or empty)
            $rawDropdowns = DB::table('product_dropdowns')->get();
            $rawOpts = DB::table('dropdown_options')->get()->groupBy('dropdown_id');
            $allDropdownOpts = [];
            foreach ($rawDropdowns as $dd) {
                $opts = $rawOpts->get($dd->id) ?? collect([]);
                foreach ($opts as $o) {
                    $allDropdownOpts[] = $o;
                }
            }

            foreach ($allDropdownOpts as $opt) {
                $key = 'dd_' . $opt->dropdown_id;
                if (in_array(strtolower(trim($opt->option_label)), $itemNames)) {
                    if (!isset($this->saved_extras[$key]) || ($this->saved_extras[$key] !== "0" && $this->saved_extras[$key] !== "")) {
                        $this->saved_extras[$key] = $opt->id;
                    }
                }
            }

            $saveData['general_extra'] = json_encode($generalExtras);
            $saveData['specific_extra'] = json_encode($specificExtras);
            $saveData['extras_json'] = json_encode($this->saved_extras);

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
            
            // Wrap in independent try-catch to ensure spreadsheet issues NEVER block the redirect
            try {
                \App\Services\GoogleSheetService::sync($this->booking->id);
            } catch (\Throwable $sheetError) {
                Log::error("Google Sheet Sync Failed (Non-Blocking): " . $sheetError->getMessage());
            }

            $this->dispatch('booking-updated');
            $this->dispatch('notify', title: 'Booking Updated', message: 'All changes have been successfully saved.', type: 'success');

            $redirectUrl = $this->isSupervisor 
                ? route('supervisor.bookings.overview', $this->booking->id)
                : route('admin.bookings.overview', $this->booking->id);

            Log::info("Save successful. Forcefully redirecting to: " . $redirectUrl);
            
            // EMERGENCY REDIRECT: If standard redirect fails, use JS
            $this->js("window.location.href = '$redirectUrl'");
            return redirect()->to($redirectUrl);

        } catch (\Throwable $e) {
            if ($e instanceof \Illuminate\Http\Exceptions\HttpResponseException) throw $e;

            Log::error("FORCEFUL SAVE ERROR: " . $e->getMessage(), [
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            $this->dispatch('notify', title: 'Critical Save Error', message: $e->getMessage(), type: 'error');
            return;
        }
    }

    public function downloadAttachment(string $column, string $fileName)
    {
        $path1 = public_path('uploads/' . $fileName);
        $path2 = storage_path('app/public/uploads/' . $fileName);

        if (file_exists($path1)) {
            return response()->download($path1);
        } elseif (file_exists($path2)) {
            return response()->download($path2);
        }

        $this->dispatch('notify', title: 'Error', message: 'File not found.', type: 'error');
    }

    public function removeAttachment(string $column)
    {
        // Clear any temporary un-saved uploads instantly
        switch ($column) {
            case 'delivery_attachment':
                $this->temp_attachment_1 = null;
                break;
            case 'delivery_attachment_2':
                $this->temp_attachment_2 = null;
                break;
            case 'delivery_attachment_3':
                $this->temp_attachment_3 = null;
                break;
            case 'delivery_attachment_4':
                $this->temp_attachment_4 = null;
                break;
            case 'delivery_attachment_5':
                $this->temp_attachment_5 = null;
                break;
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

    public function attachmentUploaded(string $column, string $fileName)
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
            ->when($this->search, function ($q) {
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

        // Fetch configs for saved_extras matching new-booking logic 
        $this->config = [
            'addons' => DB::table('category_addons')->orderBy('category_target')->get()->groupBy('category_target')->map(function ($g) {
                return $g->toArray();
            })->toArray(),
            'questions' => DB::table('product_extras')->orderBy('category_target')->get()->groupBy('category_target')->map(function ($g) {
                return $g->toArray();
            })->toArray(),
            'dropdowns' => []
        ];


        $rawDropdowns = DB::table('product_dropdowns')->orderBy('sort_order')->get();
        $rawOpts = DB::table('dropdown_options')->get()->groupBy('dropdown_id');
        foreach ($rawDropdowns as $dd) {
            $ddArray = (array)$dd;
            $opts = $rawOpts->get($dd->id) ?? collect([]);
            $ddArray['options'] = $opts->map(function ($o) {
                return (array)$o;
            })->toArray();
            $this->config['dropdowns'][$dd->category_target][] = $ddArray;
        }
        // Ensure saved_extras is an array for the JS bridge
        $this->saved_extras = is_array($this->saved_extras) ? $this->saved_extras : (array)$this->saved_extras;
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
