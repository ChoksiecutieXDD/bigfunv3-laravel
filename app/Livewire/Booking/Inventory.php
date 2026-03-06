<?php

namespace App\Livewire\Booking;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Validation\Rule;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\CategoryAddon;
use App\Models\ProductDropdown;
use App\Models\DropdownOption;
use App\Models\DeliveryZone;
use App\Models\DurationPrice;

#[Layout('components.booking.booking-layout')]
class Inventory extends Component
{
    public $activeTab = 'categories';
    public $searchProduct = '';

    // --- Category State ---
    public $cat_id, $category_name, $cat_daily_limit = 0;

    // --- Product State ---
    public $prod_id, $prod_name, $prod_category, $counts_against, $prod_price, $prod_limit = 0, $is_active = true;

    // --- Add-on State ---
    public $addon_id;
    public $addon_category = 'General Logistics';
    // Using an array to handle dynamic rows natively in Livewire
    public $addonRows = [['label' => '', 'price' => '']];

    // --- Dropdown State ---
    public $dd_id, $dd_category = 'General Logistics', $dd_label;
    public $dropdownRows = [['label' => '', 'price' => '']];

    // --- Delivery State ---
    public $del_id, $zone_name, $del_price;

    // --- Duration State ---
    public $dur_id, $dur_label, $dur_hours, $dur_price;

    public function mount()
    {
        $this->activeTab = session('active_tab', 'categories');
    }

    // ==========================================
    // 1. CATEGORIES
    // ==========================================
    public function saveCategory()
    {
        $this->validate([
            'category_name' => 'required|string|max:255',
            'cat_daily_limit' => 'nullable|integer'
        ]);

        if ($this->cat_id) {
            ProductCategory::where('id', $this->cat_id)->update([
                'category_name' => $this->category_name,
                'daily_limit' => $this->cat_daily_limit ?: 0,
            ]);
            $msg = 'Category updated successfully.';
        } else {
            $maxOrder = ProductCategory::max('sort_order') ?? 0;
            ProductCategory::create([
                'category_name' => $this->category_name,
                'daily_limit' => $this->cat_daily_limit ?: 0,
                'sort_order' => $maxOrder + 1,
            ]);
            $msg = 'Category added successfully.';
        }

        $this->resetCategoryForm();
        $this->dispatchToast($msg);
    }

    public function editCategory($id)
    {
        $cat = ProductCategory::findOrFail($id);
        $this->cat_id = $cat->id;
        $this->category_name = $cat->category_name;
        $this->cat_daily_limit = $cat->daily_limit;
    }

    public function deleteCategory($id)
    {
        ProductCategory::destroy($id);
        $this->dispatchToast('Category deleted.');
    }

    public function reorderCategory($id, $direction)
    {
        $current = ProductCategory::find($id);
        if (!$current) return;

        $compareOp = $direction === 'up' ? '<' : '>';
        $orderDir = $direction === 'up' ? 'desc' : 'asc';

        $adjacent = ProductCategory::where('sort_order', $compareOp, $current->sort_order)
            ->orderBy('sort_order', $orderDir)
            ->first();

        if ($adjacent) {
            $temp = $current->sort_order;
            $current->update(['sort_order' => $adjacent->sort_order]);
            $adjacent->update(['sort_order' => $temp]);
        }
    }

    public function resetCategoryForm()
    {
        $this->reset(['cat_id', 'category_name', 'cat_daily_limit']);
    }

    // ==========================================
    // 2. PRODUCTS
    // ==========================================
    public function saveProduct()
    {
        $this->validate([
            'prod_name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')->ignore($this->prod_id)],
            'prod_category' => 'required|string',
            'prod_price' => 'nullable|numeric'
        ]);

        Product::updateOrCreate(
            ['id' => $this->prod_id],
            [
                'name' => $this->prod_name,
                'category' => $this->prod_category,
                'counts_against' => $this->counts_against ?: $this->prod_category,
                'price' => $this->prod_price ?: 0,
                'daily_limit' => $this->prod_limit ?: 0,
                'is_active' => $this->is_active
            ]
        );

        $this->resetProductForm();
        $this->dispatchToast('Product saved successfully.');
    }

    public function editProduct($id)
    {
        $prod = Product::findOrFail($id);
        $this->prod_id = $prod->id;
        $this->prod_name = $prod->name;
        $this->prod_category = $prod->category;
        $this->counts_against = $prod->counts_against;
        $this->prod_price = $prod->price;
        $this->prod_limit = $prod->daily_limit;
        $this->is_active = $prod->is_active;
    }

    public function deleteProduct($id)
    {
        Product::destroy($id);
        $this->dispatchToast('Product deleted.');
    }

    public function resetProductForm()
    {
        $this->reset(['prod_id', 'prod_name', 'prod_category', 'counts_against', 'prod_price', 'prod_limit']);
        $this->is_active = true;
    }

    // ==========================================
    // 3. SPECIFIC ADD-ONS
    // ==========================================
    public function addAddonRow()
    {
        $this->addonRows[] = ['label' => '', 'price' => ''];
    }

    public function removeAddonRow($index)
    {
        unset($this->addonRows[$index]);
        $this->addonRows = array_values($this->addonRows); // Re-index array
    }

    public function saveAddons()
    {
        if ($this->addon_id) {
            // Editing a single record
            $row = $this->addonRows[0] ?? null;
            if ($row && !empty($row['label'])) {
                CategoryAddon::where('id', $this->addon_id)->update([
                    'category_target' => $this->addon_category,
                    'addon_label' => $row['label'],
                    'addon_price' => floatval($row['price']) ?: 0
                ]);
            }
            $msg = 'Add-on updated.';
        } else {
            // Bulk inserting new rows
            foreach ($this->addonRows as $row) {
                if (!empty($row['label'])) {
                    CategoryAddon::create([
                        'category_target' => $this->addon_category,
                        'addon_label' => $row['label'],
                        'addon_price' => floatval($row['price']) ?: 0
                    ]);
                }
            }
            $msg = 'Add-ons saved successfully.';
        }

        $this->resetAddonForm();
        $this->dispatchToast($msg);
    }

    public function editAddon($id)
    {
        $addon = CategoryAddon::findOrFail($id);
        $this->addon_id = $addon->id;
        $this->addon_category = $addon->category_target;
        $this->addonRows = [['label' => $addon->addon_label, 'price' => $addon->addon_price]];
    }

    public function deleteAddon($id)
    {
        CategoryAddon::destroy($id);
        $this->dispatchToast('Add-on deleted.');
    }

    public function resetAddonForm()
    {
        $this->reset(['addon_id']);
        $this->addonRows = [['label' => '', 'price' => '']];
    }

    // ==========================================
    // 4. DROPDOWNS
    // ==========================================
    public function addDropdownRow()
    {
        $this->dropdownRows[] = ['label' => '', 'price' => ''];
    }

    public function removeDropdownRow($index)
    {
        unset($this->dropdownRows[$index]);
        $this->dropdownRows = array_values($this->dropdownRows);
    }

    public function saveDropdown()
    {
        $this->validate([
            'dd_category' => 'required|string',
            'dd_label' => 'required|string'
        ]);

        // Save or Update Parent Dropdown
        $dropdown = ProductDropdown::updateOrCreate(
            ['id' => $this->dd_id],
            ['category_target' => $this->dd_category, 'label' => $this->dd_label]
        );

        // Wipe old options if editing, then insert fresh
        if ($this->dd_id) {
            DropdownOption::where('dropdown_id', $this->dd_id)->delete();
        }

        // Insert new options
        foreach ($this->dropdownRows as $row) {
            if (!empty($row['label'])) {
                DropdownOption::create([
                    'dropdown_id' => $dropdown->id,
                    'option_label' => $row['label'],
                    'option_price' => floatval($row['price']) ?: 0
                ]);
            }
        }

        $this->resetDropdownForm();
        $this->dispatchToast('Dropdown saved successfully.');
    }

    public function editDropdown($id)
    {
        $dd = ProductDropdown::with('options')->findOrFail($id);
        $this->dd_id = $dd->id;
        $this->dd_category = $dd->category_target;
        $this->dd_label = $dd->label;

        $this->dropdownRows = [];
        foreach ($dd->options as $opt) {
            $this->dropdownRows[] = ['label' => $opt->option_label, 'price' => $opt->option_price];
        }

        if (empty($this->dropdownRows)) {
            $this->addDropdownRow();
        }
    }

    public function deleteDropdown($id)
    {
        ProductDropdown::destroy($id);
        $this->dispatchToast('Dropdown deleted.');
    }

    public function resetDropdownForm()
    {
        $this->reset(['dd_id', 'dd_label']);
        $this->dropdownRows = [['label' => '', 'price' => '']];
    }

    // ==========================================
    // 5. DELIVERY ZONES
    // ==========================================
    public function saveDelivery()
    {
        $this->validate(['zone_name' => 'required|string', 'del_price' => 'required|numeric']);

        DeliveryZone::updateOrCreate(
            ['id' => $this->del_id],
            ['zone_name' => $this->zone_name, 'price' => $this->del_price]
        );

        $this->reset(['del_id', 'zone_name', 'del_price']);
        $this->dispatchToast('Delivery zone saved.');
    }

    public function editDelivery($id)
    {
        $zone = DeliveryZone::findOrFail($id);
        $this->del_id = $zone->id;
        $this->zone_name = $zone->zone_name;
        $this->del_price = $zone->price;
    }

    public function deleteDelivery($id)
    {
        DeliveryZone::destroy($id);
        $this->dispatchToast('Delivery zone deleted.');
    }

    // ==========================================
    // 6. DURATION PRICING
    // ==========================================
    public function saveDuration()
    {
        $this->validate([
            'dur_label' => 'required|string',
            'dur_hours' => 'required|numeric',
            'dur_price' => 'required|numeric'
        ]);

        DurationPrice::updateOrCreate(
            ['id' => $this->dur_id],
            ['label' => $this->dur_label, 'hours' => $this->dur_hours, 'price' => $this->dur_price]
        );

        $this->reset(['dur_id', 'dur_label', 'dur_hours', 'dur_price']);
        $this->dispatchToast('Duration saved successfully.');
    }

    public function editDuration($id)
    {
        $dur = DurationPrice::findOrFail($id);
        $this->dur_id = $dur->id;
        $this->dur_label = $dur->label;
        $this->dur_hours = $dur->hours;
        $this->dur_price = $dur->price;
    }

    public function deleteDuration($id)
    {
        DurationPrice::destroy($id);
        $this->dispatchToast('Duration deleted.');
    }

    // ==========================================
    // HELPER & RENDER
    // ==========================================
    private function dispatchToast($message, $type = 'success')
    {
        $this->dispatch('show-toast', message: $message, type: $type);
    }

    public function render()
    {
        return view('livewire.booking.inventory', [
            'categories' => ProductCategory::orderBy('sort_order')->get(),
            'products' => Product::where('name', 'like', '%' . $this->searchProduct . '%')
                ->orderBy('category')->orderBy('name')->get(),
            'extras_addons' => CategoryAddon::orderBy('category_target')->orderBy('addon_label')->get(),
            'dropdowns' => ProductDropdown::with('options')->orderBy('category_target')->get(),
            'deliveries' => DeliveryZone::orderBy('price')->get(),
            'durations' => DurationPrice::orderBy('hours')->get(),
        ]);
    }
}
