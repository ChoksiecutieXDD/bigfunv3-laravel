<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// We use a blank/app layout here instead of the admin layout because 
// this booking page is a full-screen "Focus Mode" page with its own nav bar.
#[Layout('components.layouts.admin-plain')]
class NewBooking extends Component
{
    public $invoice_number;
    public $booking_id = '';
    public $is_edit_mode = false;

    public $existing_data = [];
    public $saved_extras = [];
    public $selected_products = [];
    public $default_event_date = '';
    public $operational_hours = '';

    // UI Data Arrays
    public $operators_list = [];
    public $past_customers = [];
    public $delivery_options = [];
    public $duration_options = [];
    public $categories = [];
    public $config = ['questions' => [], 'addons' => [], 'dropdowns' => []];

    public function mount()
    {
        $this->invoice_number = "INV-" . date('Ymd') . "-" . rand(1000, 9999);

        $this->loadInitialData();

        $req_id = request()->query('edit_id');
        $req_invoice = request()->query('invoice');

        if ($req_id) {
            $this->loadExistingBooking(DB::table('bookings')->where('id', $req_id)->first());
        } elseif ($req_invoice) {
            $this->loadExistingBooking(DB::table('bookings')->where('invoice_number', $req_invoice)->first());
        }
    }

    private function loadInitialData()
    {
        // 1. Fetch Operators
        $staff = DB::table('users')
            ->whereIn('role', ['Staff', 'Operator', 'Supervisor'])
            ->where('is_active', 1)
            ->orderBy('first_name')
            ->get();

        foreach ($staff as $row) {
            $this->operators_list[] = $row->first_name . ' ' . (!empty($row->last_name) ? substr($row->last_name, 0, 1) . '.' : '');
        }
        if (empty($this->operators_list)) $this->operators_list = ["No staff found"];

        // 2. Fetch Past Customers
        $this->past_customers = DB::table('bookings')
            ->select('customer_first_name', 'customer_last_name', 'customer_email', 'customer_phone', 'customer_organization', 'customer_abn', 'employer_name', 'customer_business_phone', 'address_line_1', 'business_address', 'suburb', 'state', 'postcode')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('bookings')
                    ->whereNotNull('customer_email')
                    ->where('customer_email', '!=', '')
                    ->groupBy('customer_email');
            })
            ->orderBy('id', 'desc')
            ->limit(200)
            ->get()
            ->toArray();

        // 3. Fetch Delivery & Durations
        $this->delivery_options = DB::table('delivery_zones')->orderBy('price', 'asc')->get()->toArray();
        $this->duration_options = DB::table('duration_prices')->orderBy('hours', 'asc')->get()->toArray();

        // 4. Fetch Categories & Products
        $cats = DB::table('product_categories')->orderBy('sort_order', 'asc')->get();
        foreach ($cats as $c) {
            $this->categories[$c->category_name] = ['limit' => (int)$c->daily_limit, 'products' => []];
        }

        $prods = DB::table('products')->where('is_active', 1)->orderBy('category')->orderBy('name')->get();
        foreach ($prods as $p) {
            if (isset($this->categories[$p->category])) {
                $this->categories[$p->category]['products'][] = (array)$p;
            }
        }

        $this->loadProductConfigurations();
    }

    private function loadProductConfigurations()
    {
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

    private function loadExistingBooking($booking)
    {
        if ($booking) {
            $this->existing_data = (array)$booking;
            $this->invoice_number = $booking->invoice_number;
            $this->booking_id = $booking->id;
            $this->is_edit_mode = true;
            $this->default_event_date = $booking->event_date;
            $this->operational_hours = $booking->operational_hours ?? '';

            $this->selected_products = DB::table('booking_items')
                ->where('booking_id', $this->booking_id)
                ->pluck('item_name')
                ->toArray();

            if (!empty($booking->extras_json)) {
                $this->saved_extras = json_decode($booking->extras_json, true) ?: [];
            }
        }
    }

    // Helper method to safely get values for the blade view
    public function getVal($key, $default = '')
    {
        return isset($this->existing_data[$key]) && $this->existing_data[$key] !== ''
            ? htmlspecialchars($this->existing_data[$key], ENT_QUOTES)
            : $default;
    }

    public function toggleItem($name, $selected)
    {
        if ($selected) {
            if (!in_array($name, $this->selected_products)) {
                $this->selected_products[] = $name;
            }
        } else {
            $this->selected_products = array_diff($this->selected_products, [$name]);
        }
    }

    public function updateItemQty($name, $change)
    {
        // This is a placeholder for quantity-based logic if needed in the future
        // For now, it ensures the frontend call doesn't 500
    }

    public function render()
    {
        return view('livewire.admin.new-booking', [
            'title' => $this->is_edit_mode ? 'Edit Booking | BigFun Admin' : 'New Booking | BigFun Admin',
            'config' => $this->config,
            'categories' => $this->categories,
            'saved_extras' => $this->saved_extras,
            'past_customers' => $this->past_customers,
            'delivery_options' => $this->delivery_options,
            'operators_list' => $this->operators_list,
            'selected_products' => $this->selected_products
        ]);
    }
}
