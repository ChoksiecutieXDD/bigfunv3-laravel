<?php

namespace App\Livewire\Booking;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Illuminate\Validation\Rule;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\CategoryAddon;
use App\Models\ProductDropdown;
use App\Models\DropdownOption;


#[Layout('components.layouts.booking-layout')]
class Inventory extends Component
{
    #[Url]
    public string $activeTab = 'categories';
    public string $searchProduct = '';

    // --- Category State ---
    public ?int $cat_id = null;
    public ?string $category_name = null;
    public int $cat_daily_limit = 0;

    // --- Product State ---
    public ?int $prod_id = null;
    public ?string $prod_name = null;
    public ?string $prod_category = null;
    public ?string $counts_against = null;
    public ?string $prod_specification = null;
    public float $prod_price = 100;
    public int $prod_quantity = 999;
    public int $prod_limit = 0;
    public bool $is_active = true;

    // --- Add-on State ---
    public ?int $addon_id = null;
    public string $addon_category = 'General Logistics';
    public ?string $addon_counts_against = null;
    public array $addonRows = [['label' => '', 'price' => '']];

    // --- Dropdown State ---
    public ?int $dd_id = null;
    public string $dd_category = 'General Logistics';
    public ?string $dd_label = null;
    public ?string $dd_counts_against = null;
    public array $dropdownRows = [['label' => '', 'price' => '']];



    public function mount()
    {
        if (!session('system_unlocked')) {
            return redirect()->route('system.settings');
        }

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

    public function editCategory(int|string $id)
    {
        $cat = ProductCategory::findOrFail($id);
        $this->cat_id = $cat->id;
        $this->category_name = $cat->category_name;
        $this->cat_daily_limit = $cat->daily_limit;
        $this->dispatchToast("Editing '{$cat->category_name}' category.");
    }

    #[On('execute-delete-category')]
    public function deleteCategory(int|string|array $id)
    {
        $id = is_array($id) ? ($id['id'] ?? $id[0]) : $id;

        $category = ProductCategory::find($id);
        if ($category) {
            $catName = $category->category_name;

            // Delete related products
            Product::where('category', $catName)->delete();
            Product::where('counts_against', $catName)->update(['counts_against' => null]);

            // Delete related extras (CategoryAddon)
            CategoryAddon::where('category_target', $catName)->delete();

            // Delete related dropdowns and their options
            $dropdowns = ProductDropdown::where('category_target', $catName)->get();
            foreach ($dropdowns as $dd) {
                DropdownOption::where('dropdown_id', $dd->id)->delete();
                $dd->delete();
            }

            // Finally delete the category
            $category->delete();
        }

        $this->dispatchToast('Category and related items deleted.');
    }

    public function reorderCategory(int|string $id, string $direction)
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
        $this->dispatchToast('Category arrangement updated.');
    }

    public function resetCategoryForm()
    {
        $this->reset(['cat_id', 'category_name', 'cat_daily_limit']);
    }

    // ==========================================
    // 2. PRODUCTS
    // ==========================================
    #[On('execute-save-product')]
    public function saveProduct()
    {
        $this->validate([
            'prod_category' => 'required|string',
        ]);

        if (!$this->prod_id) {
            $maxOrder = Product::where('category', $this->prod_category)->max('sort_order') ?? 0;
            $sortOrder = $maxOrder + 1;
        } else {
            $sortOrder = Product::find($this->prod_id)->sort_order ?? 0;
        }

        Product::updateOrCreate(
            ['id' => $this->prod_id],
            [
                'name' => $this->prod_name,
                'category' => $this->prod_category,
                'counts_against' => $this->counts_against ?: $this->prod_category,
                'specification' => $this->prod_specification,
                'price' => 0,
                'total_quantity' => (int)($this->prod_quantity ?? 0),
                'daily_limit' => 0, // Product-level daily limit is now redundant; using stock as primary constraint.
                'sort_order' => $sortOrder,
                'is_active' => $this->is_active
            ]
        );

        $this->resetProductForm();
        $this->dispatchToast('Product saved successfully.');
    }

    public function editProduct(int|string $id)
    {
        $prod = Product::findOrFail($id);
        $this->prod_id = $prod->id;
        $this->prod_name = $prod->name;
        $this->prod_category = $prod->category;
        $this->counts_against = $prod->counts_against;
        $this->prod_specification = $prod->specification;
        $this->prod_quantity = $prod->total_quantity;
        $this->is_active = $prod->is_active;
        $this->dispatchToast("Editing '{$prod->name}' product.");
    }

    #[On('execute-delete-product')]
    public function deleteProduct(int|string|array $id)
    {
        $id = is_array($id) ? ($id['id'] ?? $id[0]) : $id;
        Product::destroy($id);
        $this->dispatchToast('Product deleted.');
    }

    public function reorderProduct(int|string $id, string $direction)
    {
        $current = Product::find($id);
        if (!$current) return;

        $compareOp = $direction === 'up' ? '<' : '>';
        $orderDir = $direction === 'up' ? 'desc' : 'asc';

        $adjacent = Product::where('category', $current->category)
            ->where('sort_order', $compareOp, $current->sort_order)
            ->orderBy('sort_order', $orderDir)
            ->first();

        if ($adjacent) {
            $temp = $current->sort_order;
            $current->update(['sort_order' => $adjacent->sort_order]);
            $adjacent->update(['sort_order' => $temp]);
        }
        $this->dispatchToast('Product arrangement updated.');
    }

    public function autoArrangeAlphabetical(string $categoryName)
    {
        $products = Product::where('category', $categoryName)
            ->orderBy('name', 'asc')
            ->get();

        $order = 1;
        foreach ($products as $prod) {
            $prod->update(['sort_order' => $order]);
            $order++;
        }

        $this->dispatchToast("Category '{$categoryName}' sorted alphabetically.");
    }

    public function resetProductForm()
    {
        $this->reset(['prod_id', 'prod_name', 'prod_category', 'counts_against', 'prod_specification', 'prod_quantity', 'prod_limit']);
        $this->prod_quantity = 999;
        $this->is_active = true;
    }

    // ==========================================
    // 3. SPECIFIC ADD-ONS
    // ==========================================
    public function addAddonRow()
    {
        $this->addonRows[] = ['label' => ''];
    }

    public function removeAddonRow(int|string $index)
    {
        unset($this->addonRows[$index]);
        $this->addonRows = array_values($this->addonRows);
    }

    #[On('execute-save-addon')]
    public function saveAddons()
    {
        if ($this->addon_id) {
            $row = $this->addonRows[0] ?? null;
            if ($row && !empty($row['label'])) {
                CategoryAddon::where('id', $this->addon_id)->update([
                    'category_target' => $this->addon_category,
                    'addon_label' => $row['label'],
                    'addon_price' => 0,
                    'counts_against' => $this->addon_counts_against ?: $this->addon_category
                ]);
            }
            $msg = 'Add-on updated.';
        } else {
            foreach ($this->addonRows as $row) {
                if (!empty($row['label'])) {
                    CategoryAddon::create([
                        'category_target' => $this->addon_category,
                        'addon_label' => $row['label'],
                        'addon_price' => 0,
                        'counts_against' => $this->addon_counts_against ?: $this->addon_category
                    ]);
                }
            }
            $msg = 'Add-ons saved successfully.';
        }

        $this->resetAddonForm();
        $this->dispatchToast($msg);
    }

    public function editAddon(int|string $id)
    {
        $addon = CategoryAddon::findOrFail($id);
        $this->addon_id = $addon->id;
        $this->addon_category = $addon->category_target;
        $this->addon_counts_against = $addon->counts_against;
        $this->addonRows = [['label' => $addon->addon_label]];
        $this->dispatchToast("Editing '{$addon->addon_label}' add-on.");
    }

    #[On('execute-delete-addon')]
    public function deleteAddon(int|string|array $id)
    {
        $id = is_array($id) ? ($id['id'] ?? $id[0]) : $id;
        CategoryAddon::destroy($id);
        $this->dispatchToast('Add-on deleted.');
    }

    public function resetAddonForm()
    {
        $this->reset(['addon_id', 'addon_counts_against']);
        $this->addonRows = [['label' => '']];
    }

    // ==========================================
    // 4. DROPDOWNS
    // ==========================================
    public function addDropdownRow()
    {
        $this->dropdownRows[] = ['label' => ''];
    }

    public function removeDropdownRow(int|string $index)
    {
        unset($this->dropdownRows[$index]);
        $this->dropdownRows = array_values($this->dropdownRows);
    }

    #[On('execute-save-dropdown')]
    public function saveDropdown()
    {
        $this->validate([
            'dd_category' => 'required|string',
            'dd_label' => 'required|string'
        ]);

        $dropdown = ProductDropdown::updateOrCreate(
            ['id' => $this->dd_id],
            [
                'category_target' => $this->dd_category,
                'label' => $this->dd_label,
                'counts_against' => $this->dd_counts_against ?: $this->dd_category
            ]
        );

        if ($this->dd_id) {
            DropdownOption::where('dropdown_id', $this->dd_id)->delete();
        }

        foreach ($this->dropdownRows as $row) {
            if (!empty($row['label'])) {
                DropdownOption::create([
                    'dropdown_id' => $dropdown->id,
                    'option_label' => $row['label'],
                    'option_price' => 0
                ]);
            }
        }

        $this->resetDropdownForm();
        $this->dispatchToast('Dropdown saved successfully.');
    }

    public function editDropdown(int|string $id)
    {
        $dd = ProductDropdown::with('options')->findOrFail($id);
        $this->dd_id = $dd->id;
        $this->dd_category = $dd->category_target;
        $this->dd_label = $dd->label;
        $this->dd_counts_against = $dd->counts_against;

        $this->dropdownRows = [];
        foreach ($dd->options as $opt) {
            $this->dropdownRows[] = ['label' => $opt->option_label];
        }

        if (empty($this->dropdownRows)) {
            $this->addDropdownRow();
        }
        $this->dispatchToast("Editing '{$dd->label}' dropdown.");
    }

    #[On('execute-delete-dropdown')]
    public function deleteDropdown(int|string|array $id)
    {
        $id = is_array($id) ? ($id['id'] ?? $id[0]) : $id;
        ProductDropdown::destroy($id);
        $this->dispatchToast('Dropdown deleted.');
    }

    public function resetDropdownForm()
    {
        $this->reset(['dd_id', 'dd_label', 'dd_counts_against']);
        $this->dropdownRows = [['label' => '']];
    }



    // ==========================================
    // HELPER & RENDER
    // ==========================================
    private function dispatchToast(string $message, string $type = 'success')
    {
        $this->dispatch('show-toast', ['message' => $message, 'type' => $type]);
    }

    public function render()
    {
        return view('livewire.booking.inventory', [
            'categories' => ProductCategory::orderBy('sort_order')->get(),
            'products' => Product::where('name', 'like', '%' . $this->searchProduct . '%')
                ->orderBy('category')->orderBy('sort_order')->orderBy('name')->get(),
            'extras_addons' => CategoryAddon::orderBy('category_target')->orderBy('addon_label')->get(),
            'dropdowns' => ProductDropdown::with('options')->orderBy('category_target')->get(),
        ]);
    }
}
