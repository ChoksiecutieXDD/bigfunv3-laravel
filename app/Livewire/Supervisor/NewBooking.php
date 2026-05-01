<?php

namespace App\Livewire\Supervisor;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\Computed;

#[Layout('components.layouts.plain')]
class NewBooking extends Component
{
    use WithFileUploads;

    public string $invoice_number = '';
    public string $booking_id = '';
    public bool $is_edit_mode = false;
    public string $form_token = '';

    public array $existing_data = [];
    public array $saved_extras = [];
    public array $selected_products = [];
    public array $selected_manual_prices = [];
    public array $extraPrices = [];
    public array $activeOverrides = [];
    public string $default_event_date = '';
    public string $operational_hours = '';
    public array $availability = [];
    public array $categoryLimits = [];
    public array $calDays = [];
    public ?int $calMonth = null;
    public ?int $calYear = null;

    // File Upload Temporary Properties
    public mixed $temp_attachment_1 = null;
    public mixed $temp_attachment_2 = null;
    public mixed $temp_attachment_3 = null;
    public mixed $temp_attachment_4 = null;
    public mixed $temp_attachment_5 = null;

    // UI Data removed from public state

    public function mount()
    {
        $this->form_token = bin2hex(random_bytes(8));
        $lastInvoiceNum = DB::table('bookings')
            ->where('invoice_number', 'like', 'INV-%')
            ->orWhere('invoice_number', 'like', 'Inv no. %')
            ->selectRaw("MAX(CAST(REGEXP_REPLACE(invoice_number, '[^0-9]', '') AS UNSIGNED)) as max_num")
            ->value('max_num');

        if ($lastInvoiceNum > 999999) {
            $lastInvoiceNum = $lastInvoiceNum % 10000;
        }

        $nextNum = ($lastInvoiceNum ?? 0) + 1;
        $this->invoice_number = "INV-" . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

        $req_id = request()->query('edit_id');
        $req_invoice = request()->query('invoice');

        if ($req_id) {
            $this->loadExistingBooking(DB::table('bookings')->where('id', $req_id)->first());
        } elseif ($req_invoice) {
            $this->loadExistingBooking(DB::table('bookings')->where('invoice_number', $req_invoice)->first());
        }

        if (empty($this->default_event_date)) {
            $this->default_event_date = '';
        }
        
        $this->calMonth = Carbon::parse($this->default_event_date ?: now())->month;
        $this->calYear = Carbon::parse($this->default_event_date ?: now())->year;

        $this->checkAvailability();
    }

    #[Computed]
    public function delivery_options(): array
    {
        return DB::table('delivery_zones')->orderBy('price', 'asc')->get()->toArray();
    }

    #[Computed]
    public function duration_options(): array
    {
        return DB::table('duration_prices')->orderBy('hours', 'asc')->get()->toArray();
    }

    #[Computed]
    public function operators_list(): array
    {
        $staff = DB::table('users')
            ->whereIn('role', ['Staff', 'Operator', 'Supervisor'])
            ->where('is_active', 1)
            ->orderBy('first_name')
            ->get();
        $list = [];
        foreach ($staff as $row) {
            $list[] = trim($row->first_name . ' ' . $row->last_name);
        }
        return empty($list) ? ["No staff found"] : $list;
    }

    #[Computed]
    public function past_customers(): array
    {
        return DB::table('bookings')
            ->select(['customer_first_name', 'customer_last_name', 'customer_email', 'customer_phone', 'customer_organization', 'customer_abn', 'employer_name', 'customer_business_phone', 'address_line_1', 'business_address', 'suburb', 'state', 'postcode'])
            ->where('customer_first_name', '!=', '')
            ->orderBy('id', 'desc')
            ->limit(500)
            ->get()
            ->unique(fn($b) => strtolower(trim($b->customer_first_name . $b->customer_last_name . $b->customer_email)))
            ->take(100)
            ->values()
            ->toArray();
    }

    #[Computed]
    public function categories(): array
    {
        $categories = [];
        $cats = DB::table('product_categories')->orderBy('sort_order', 'asc')->get();
        foreach ($cats as $c) {
            $categories[$c->category_name] = ['limit' => (int)$c->daily_limit, 'products' => []];
        }
        $prods = DB::table('products')->where('is_active', 1)->orderBy('category')->orderBy('name')->get();
        foreach ($prods as $p) {
            if (isset($categories[$p->category])) {
                $categories[$p->category]['products'][] = (array)$p;
            }
        }
        return $categories;
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

    private function loadExistingBooking(?object $booking)
    {
        if ($booking) {
            $this->existing_data = (array)$booking;
            $this->invoice_number = $booking->invoice_number;
            $this->booking_id = $booking->id;
            $this->is_edit_mode = true;
            $this->default_event_date = $booking->event_date;
            $this->operational_hours = $booking->operational_hours ?? '';
            $this->existing_data = (array)$booking;

            $this->selected_products = DB::table('booking_items')
                ->where('booking_id', $this->booking_id)
                ->pluck('item_name')
                ->toArray();

            $this->selected_manual_prices = DB::table('booking_items')
                ->where('booking_id', $this->booking_id)
                ->pluck('item_price', 'item_name')
                ->toArray();

            if (!empty($booking->extras_json)) {
                $this->saved_extras = json_decode($booking->extras_json, true) ?: [];
            }
        }
    }

    public function getVal(string $key, mixed $default = '')
    {
        return isset($this->existing_data[$key]) && $this->existing_data[$key] !== ''
            ? htmlspecialchars($this->existing_data[$key], ENT_QUOTES)
            : $default;
    }

    public function checkAvailability()
    {
        $date = $this->default_event_date ?: date('Y-m-d');

        $usage = DB::table('booking_items')
            ->join('bookings', 'booking_items.booking_id', '=', 'bookings.id')
            ->where('bookings.event_date', $date)
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
            $cleanName = strtolower(preg_replace('/\s+/', ' ', trim($p->name)));
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
            $cleanName = strtolower(preg_replace('/\s+/', ' ', trim($p->name)));
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

    public function toggleItem(string $name, bool|string|int|null $selected)
    {
        if ($selected) {
            if (!in_array($name, $this->selected_products)) {
                $this->selected_products[] = $name;
            }
        } else {
            $this->selected_products = array_diff($this->selected_products, [$name]);
            if (isset($this->selected_manual_prices[$name])) {
                unset($this->selected_manual_prices[$name]);
            }
        }
        $this->checkAvailability();
    }

    public function updateManualPrice(string $name, float|int|string|null $price)
    {
        if ($price === '' || $price === null) {
            unset($this->selected_manual_prices[$name]);
        } else {
            $this->selected_manual_prices[$name] = (float)$price;
        }
    }

    public function updateItemQty($name, $change)
    {
        // This is a placeholder for quantity-based logic if needed in the future
        // For now, it ensures the frontend call doesn't 500
    }

    public function syncExtras(array $extras)
    {
        $this->saved_extras = $extras;
        
        // Ensure extraPrices has keys for all extras
        foreach ($extras as $key => $val) {
            $isSelected = ($val && $val !== "0" && !str_ends_with($val, "|no"));
            
            if ($isSelected && (!isset($this->extraPrices[$key]) || $this->extraPrices[$key] == 0)) {
                // Initialize default price if missing or zero
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
            } elseif (!$isSelected) {
                $this->extraPrices[$key] = 0;
            }
        }
    }

    public function updateExtraPrice(string|int $key, float|int|string|null $price)
    {
        $this->extraPrices[$key] = (float)$price;
    }

    public function updateOverrideState(string|int $key, bool|int|string|null $isActive)
    {
        $this->activeOverrides[$key] = $isActive;
    }

    public function removeAttachment(string $column)
    {
        if (!$this->booking_id) return;

        $booking = DB::table('bookings')->where('id', $this->booking_id)->first();
        if ($booking && !empty($booking->$column)) {
            $fileName = $booking->$column;
            
            // Delete physical files
            @unlink(public_path('uploads/' . $fileName));
            @unlink(storage_path('app/public/uploads/' . $fileName));

            // Clear from DB
            DB::table('bookings')->where('id', $this->booking_id)->update([$column => null]);
            
            // Update local state
            $this->existing_data[$column] = null;
            
            $this->dispatch('attachment-removed');
        }
    }

    public function attachmentUploaded(string $column, string $fileName)
    {
        $this->existing_data[$column] = $fileName;
    }

    public function render()
    {
        return view('livewire.supervisor.new-booking', [
            'title' => $this->is_edit_mode ? 'Edit Booking | BigFun Supervisor' : 'New Booking | BigFun Supervisor',
            'config' => $this->config,
            'categories' => $this->categories,
            'saved_extras' => $this->saved_extras,
            'past_customers' => $this->past_customers,
            'duration_options' => $this->duration_options,
            'delivery_options' => $this->delivery_options,
            'operators_list' => $this->operators_list,
            'selected_products' => $this->selected_products,
            'availability' => $this->availability
        ]);
    }
}
