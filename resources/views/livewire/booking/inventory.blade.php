<div
    x-data="{ tab: @entangle('activeTab') }"
    class="w-full py-10 px-6 lg:px-8 max-w-[1440px] mx-auto relative z-10 font-['Poppins']">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-text-main tracking-tight">Booking Administration</h1>
        <p class="text-slate-500 text-[15px] font-medium mt-1">Manage categories, products, and extras.</p>
    </div>

    <div class="flex overflow-x-auto hide-scrollbar bg-white/50 backdrop-blur-sm rounded-t-2xl pt-2 px-2 mb-8 border-b border-slate-200">
        <button @click="tab = 'categories'"
            :class="tab === 'categories' ? 'bg-white text-plum border-t-4 border-plum font-semibold shadow-sm' : 'text-slate-500 hover:text-slate-700 font-medium border-t-4 border-transparent'"
            class="px-6 py-3.5 text-[15px] rounded-t-lg transition-all whitespace-nowrap">
            Categories & Limits
        </button>
        <button @click="tab = 'products'"
            :class="tab === 'products' ? 'bg-white text-plum border-t-4 border-plum font-semibold shadow-sm' : 'text-slate-500 hover:text-slate-700 font-medium border-t-4 border-transparent'"
            class="px-6 py-3.5 text-[15px] rounded-t-lg transition-all whitespace-nowrap">
            Products (Rides)
        </button>
        <button @click="tab = 'extras'"
            :class="tab === 'extras' ? 'bg-white text-plum border-t-4 border-plum font-semibold shadow-sm' : 'text-slate-500 hover:text-slate-700 font-medium border-t-4 border-transparent'"
            class="px-6 py-3.5 text-[15px] rounded-t-lg transition-all whitespace-nowrap">
            Extras (General & Cat)
        </button>
        <button @click="tab = 'delivery'"
            :class="tab === 'delivery' ? 'bg-white text-plum border-t-4 border-plum font-semibold shadow-sm' : 'text-slate-500 hover:text-slate-700 font-medium border-t-4 border-transparent'"
            class="px-6 py-3.5 text-[15px] rounded-t-lg transition-all whitespace-nowrap">
            Delivery Zones
        </button>
        <button @click="tab = 'duration'"
            :class="tab === 'duration' ? 'bg-white text-plum border-t-4 border-plum font-semibold shadow-sm' : 'text-slate-500 hover:text-slate-700 font-medium border-t-4 border-transparent'"
            class="px-6 py-3.5 text-[15px] rounded-t-lg transition-all whitespace-nowrap">
            Duration Pricing
        </button>
    </div>

    <div x-show="tab === 'categories'" wire:key="tab-container-categories" class="flex flex-col gap-6">
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-text-main text-xs uppercase tracking-widest">
                    {{ $cat_id ? 'Edit Category' : 'Add Category' }}
                </h3>
                @if($cat_id)
                <button type="button" wire:click="resetCategoryForm" class="text-xs font-semibold text-slate-400 hover:text-red-500 transition-colors">
                    Cancel Edit
                </button>
                @endif
            </div>

            <form wire:submit="saveCategory">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                    <div class="md:col-span-12 lg:col-span-6">
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-widest mb-2">Category Name</label>
                        <input type="text" wire:model="category_name" class="w-full rounded-xl border border-slate-200 text-text-main font-medium focus:ring-plum focus:border-plum px-4 py-3 transition-colors" placeholder="e.g. Packages" required>
                    </div>
                    <div class="md:col-span-12 lg:col-span-4">
                        <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-widest mb-2">Daily Limit <span class="text-slate-400 normal-case tracking-normal font-normal">(0 = Unlimited)</span></label>
                        <input type="number" wire:model="cat_daily_limit" class="w-full rounded-xl border border-slate-200 text-text-main font-medium focus:ring-plum focus:border-plum px-4 py-3 transition-colors" placeholder="0">
                    </div>
                    <div class="md:col-span-12 lg:col-span-2">
                        <button type="submit" class="w-full btn-plum py-3 px-6 rounded-xl font-semibold text-[15px]">
                            Save
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[600px]">
                    <thead class="border-b border-slate-100 bg-slate-50/50">
                        <tr>
                            <th class="py-4 pl-8 text-[11px] font-bold text-slate-500 uppercase tracking-widest w-1/3">Category</th>
                            <th class="py-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest w-1/4">Limit</th>
                            <th class="py-4 text-center text-[11px] font-bold text-slate-500 uppercase tracking-widest w-1/4">Arrangement</th>
                            <th class="py-4 pr-8 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest w-1/6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($categories as $cat)
                        <tr wire:key="category-{{ $cat->id }}" class="hover:bg-slate-50/50 transition-colors group">
                            <td class="py-4 pl-8 font-semibold text-text-main text-[15px]">{{ $cat->category_name }}</td>
                            <td class="py-4 font-semibold text-text-main">
                                @if($cat->daily_limit > 0)
                                <span class="text-amber-600 text-[11px] font-bold bg-amber-50 px-2.5 py-1 rounded uppercase tracking-wider">Max {{ $cat->daily_limit }}</span>
                                @else
                                <span class="text-slate-400 text-sm italic font-medium">Unlimited</span>
                                @endif
                            </td>
                            <td class="py-4 text-center">
                                <div class="flex justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button wire:click="reorderCategory({{ $cat->id }}, 'up')" class="text-slate-400 hover:text-plum bg-slate-50 hover:bg-plum-light p-1 rounded transition-colors"><span class="material-symbols-rounded text-[20px]">arrow_upward</span></button>
                                    <button wire:click="reorderCategory({{ $cat->id }}, 'down')" class="text-slate-400 hover:text-plum bg-slate-50 hover:bg-plum-light p-1 rounded transition-colors"><span class="material-symbols-rounded text-[20px]">arrow_downward</span></button>
                                </div>
                            </td>
                            <td class="py-4 pr-8 text-right">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="editCategory({{ $cat->id }})" class="w-8 h-8 rounded bg-slate-100 text-slate-600 hover:bg-slate-200 flex items-center justify-center transition-colors">
                                        <span class="material-symbols-rounded text-[16px]">edit</span>
                                    </button>
                                    <button type="button"
                                        @click="$dispatch('open-modal', { 
                                            title: 'Delete Category?', 
                                            message: 'Are you sure you want to remove the \'{{ addslashes($cat->category_name) }}\' category? This cannot be undone.', 
                                            type: 'danger', 
                                            event: 'execute-delete-category', 
                                            params: {{ $cat->id }} 
                                        })"
                                        class="w-8 h-8 rounded bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors shadow-sm">
                                        <span class="material-symbols-rounded text-[16px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-show="tab === 'products'" wire:key="tab-container-products" class="flex flex-col gap-6">
        <div class="relative w-full mb-2">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <span class="material-symbols-rounded text-slate-400">search</span>
            </div>
            <input type="text" wire:model.live.debounce.300ms="searchProduct" placeholder="Search..." class="w-full rounded-xl border border-slate-200 text-text-main font-medium pl-12 py-3 focus:ring-plum focus:border-plum shadow-sm bg-white transition-colors">
        </div>

        <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-text-main text-xs uppercase tracking-widest">
                    {{ $prod_id ? 'Edit Product' : 'Add New Product' }}
                </h3>
                @if($prod_id)
                <button type="button" wire:click="resetProductForm" class="text-xs font-semibold text-slate-400 hover:text-red-500 transition-colors">
                    Cancel Edit
                </button>
                @endif
            </div>

            <form wire:submit="saveProduct">
                <div class="grid grid-cols-1 gap-6 mb-6">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-2">Product Name</label>
                        <input type="text" wire:model="prod_name" class="w-full rounded-xl border border-slate-200 text-text-main font-medium focus:ring-plum focus:border-plum px-4 py-3 transition-colors" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-2">Category</label>
                        <select wire:model="prod_category" class="w-full rounded-xl border border-slate-200 text-text-main font-medium focus:ring-plum focus:border-plum px-4 py-3 transition-colors" required>
                            <option value="">Select...</option>
                            @foreach ($categories as $c)
                            <option value="{{ $c->category_name }}">{{ $c->category_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-2">Counts Against Target</label>
                        <select wire:model="counts_against" class="w-full rounded-xl border border-slate-200 text-text-main font-medium focus:ring-plum focus:border-plum px-4 py-3 transition-colors">
                            <option value="">-- Select Target --</option>
                            @foreach ($categories as $c)
                            <option value="{{ $c->category_name }}">{{ $c->category_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-2">Price ($)</label>
                        <input type="number" step="0.01" wire:model="prod_price" class="w-full rounded-xl border border-slate-200 text-text-main font-medium focus:ring-plum focus:border-plum px-4 py-3 transition-colors" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-2">Item Limit <span class="text-slate-400 normal-case tracking-normal font-normal">(0=UNL)</span></label>
                        <input type="number" wire:model="prod_limit" class="w-full rounded-xl border border-slate-200 text-text-main font-medium focus:ring-plum focus:border-plum px-4 py-3 transition-colors" placeholder="0">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-2">Specification <span class="text-slate-400 normal-case tracking-normal font-normal">(Each line will be bulleted)</span></label>
                    <textarea wire:model="prod_specification" rows="3" class="w-full rounded-xl border border-slate-200 text-text-main font-medium focus:ring-plum focus:border-plum px-4 py-3 transition-colors" placeholder="e.g. 5m x 5m area needed&#10;Power required within 20m"></textarea>
                </div>

                <div class="flex justify-between items-center pt-6 border-t border-slate-100">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" wire:model="is_active" id="prod_active" class="w-5 h-5 rounded text-red-500 border-slate-300 focus:ring-red-500 shadow-sm cursor-pointer transition-colors">
                        <label class="text-[15px] font-bold text-text-main cursor-pointer select-none" for="prod_active">Active</label>
                    </div>
                    <button type="submit" class="btn-plum py-3 px-8 rounded-xl font-bold text-[15px] shadow-sm">
                        Save Product
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <table wire:key="products-table" class="w-full text-left border-collapse min-w-[800px]" x-data="{ arranging: null }">
                <tbody class="divide-y divide-slate-100">
                    @php $currentCat = ''; @endphp
                    @forelse ($products as $prod)

                    @if($currentCat !== $prod->category)
                    @php $currentCat = $prod->category; @endphp
                    <tr wire:key="cat-header-{{ $currentCat }}" class="bg-slate-50 border-b border-slate-200">
                        <td colspan="5" class="py-3 px-8 text-[11px] font-bold text-slate-500 uppercase tracking-widest bg-slate-100">
                            <div class="flex justify-between items-center w-full">
                                <span>{{ $currentCat }}</span>
                                <div class="flex gap-4">
                                    <button type="button" wire:click="autoArrangeAlphabetical('{{ addslashes($currentCat) }}')" class="hover:text-plum transition-colors flex items-center gap-1">
                                        <span class="material-symbols-rounded text-[14px]">sort_by_alpha</span> Auto Arrange (A-Z)
                                    </button>
                                    <button type="button" @click="arranging === '{{ addslashes($currentCat) }}' ? arranging = null : arranging = '{{ addslashes($currentCat) }}'" class="hover:text-plum transition-colors flex items-center gap-1" :class="arranging === '{{ addslashes($currentCat) }}' ? 'text-plum' : ''">
                                        <span class="material-symbols-rounded text-[14px]">swap_vert</span> Edit Arrange
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endif

                    <tr wire:key="product-{{ $prod->id }}" class="hover:bg-slate-50/50 transition-colors">
                        <td class="py-5 pl-8 w-[40%]">
                            <div class="flex items-center flex-wrap gap-x-4 gap-y-2">
                                <div class="flex flex-col -space-y-2 mr-1 h-full justify-center" x-show="arranging === '{{ addslashes($prod->category) }}'" x-cloak>
                                    <button type="button" wire:click.prevent="reorderProduct({{ $prod->id }}, 'up')" class="hover:text-plum text-slate-300 leading-none"><span class="material-symbols-rounded text-[20px]">arrow_drop_up</span></button>
                                    <button type="button" wire:click.prevent="reorderProduct({{ $prod->id }}, 'down')" class="hover:text-plum text-slate-300 leading-none"><span class="material-symbols-rounded text-[20px]">arrow_drop_down</span></button>
                                </div>
                                <span class="font-bold text-text-main text-[15px]">{{ $prod->name }}</span>
                                @if($prod->counts_against && $prod->counts_against !== $prod->category)
                                <span class="inline-flex items-center gap-1 bg-[#FDF2F4] text-[#B07079] border border-[#F9E1E5] px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-widest">
                                    <span class="material-symbols-rounded text-[14px]">link</span> Target: {{ $prod->counts_against }}
                                </span>
                                @endif

                                @if($prod->specification)
                                <div class="w-full mt-2 space-y-1">
                                    @foreach(explode("\n", str_replace("\r", "", $prod->specification)) as $line)
                                        @if(trim($line))
                                        <div class="flex items-start gap-2 text-[11px] text-slate-500 font-medium">
                                            <span class="text-plum mt-0.5">&bull;</span>
                                            <span>{{ trim($line) }}</span>
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </td>
                        <td class="py-5 font-bold text-slate-800 text-[15px] w-[15%]">
                            ${{ number_format($prod->price, 2) }}
                        </td>
                        <td class="py-5 w-[20%] text-text-main font-medium">
                            @php
                            $target_cat = $prod->counts_against ?: $prod->category;
                            $cat_obj = $categories->firstWhere('category_name', $target_cat);
                            $c_limit = $cat_obj ? $cat_obj->daily_limit : 0;
                            @endphp
                            <div class="flex flex-col border border-slate-200 rounded-lg text-[11px] w-[110px] overflow-hidden shadow-sm">
                                <div class="flex justify-between items-center px-2 py-1 border-b border-slate-200 bg-white">
                                    <span class="text-slate-600 font-bold tracking-wider">ITEM</span>
                                    <span class="{{ $prod->daily_limit > 0 ? 'text-[#D97757] font-bold' : 'text-slate-400 italic' }}">{{ $prod->daily_limit > 0 ? $prod->daily_limit.'/day' : 'Unl' }}</span>
                                </div>
                                <div class="flex justify-between items-center px-2 py-1 bg-white">
                                    <span class="text-slate-600 font-bold tracking-wider">CAT</span>
                                    <span class="{{ $c_limit > 0 ? 'text-[#D97757] font-bold' : 'text-slate-400 italic' }}">{{ $c_limit > 0 ? $c_limit.'/day' : 'Unl' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="py-5 text-left w-[15%]">
                            @if($prod->is_active)
                            <span class="text-emerald-500 border border-emerald-300 bg-[#F0FDF4] px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">Active</span>
                            @else
                            <span class="text-slate-500 border border-slate-300 bg-slate-50 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">Inactive</span>
                            @endif
                        </td>
                        <td class="py-5 pr-8 text-right w-[10%]">
                            <div class="flex justify-end gap-2">
                                <button type="button" wire:click="editProduct({{ $prod->id }})" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 flex items-center justify-center transition-colors">
                                    <span class="material-symbols-rounded text-[18px]">edit</span>
                                </button>
                                <button type="button"
                                    @click="$dispatch('open-modal', { 
                                        title: 'Delete Product?', 
                                        message: 'Are you sure you want to delete \'{{ addslashes($prod->name) }}\'?', 
                                        type: 'danger', 
                                        event: 'execute-delete-product', 
                                        params: {{ $prod->id }} 
                                    })"
                                    class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors shadow-sm">
                                    <span class="material-symbols-rounded text-[18px]">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-500 italic">No products match your search.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div x-show="tab === 'extras'" wire:key="tab-container-extras" class="flex flex-col gap-10">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            <div class="xl:col-span-4 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden h-fit">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-bold text-text-main text-[15px]">1. Add Specific Items</h3>
                    @if($addon_id)
                    <button type="button" wire:click="resetAddonForm" class="text-xs font-semibold text-slate-400 hover:text-red-500 transition-colors">Cancel Edit</button>
                    @endif
                </div>
                <div class="p-6">
                    <form wire:submit.prevent="saveAddons">
                        <div class="space-y-4 mb-2">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Target Category</label>
                                <select wire:model="addon_category" class="w-full rounded-xl border border-slate-200 text-text-main font-medium focus:ring-plum focus:border-plum px-4 py-3 transition-colors">
                                    <option value="General Logistics">General Logistics</option>
                                    @foreach ($categories as $c)
                                    <option value="{{ $c->category_name }}">{{ $c->category_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Counts Against Target</label>
                                <select wire:model="addon_counts_against" class="w-full rounded-xl border border-slate-200 text-text-main font-medium focus:ring-plum focus:border-plum px-4 py-3 transition-colors">
                                    <option value="">-- Same as Target Category --</option>
                                    @foreach ($categories as $c)
                                    <option value="{{ $c->category_name }}">{{ $c->category_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @foreach($addonRows as $index => $row)
                            <div class="relative space-y-4 pt-4 {{ $index > 0 ? 'border-t border-dashed border-slate-200' : '' }}">
                                @if($index > 0)
                                <button type="button" wire:click="removeAddonRow({{ $index }})" class="absolute top-2 right-0 text-red-400 hover:text-red-600 transition-colors">
                                    <span class="material-symbols-rounded text-sm">close</span>
                                </button>
                                @endif
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Item Name</label>
                                    <input type="text" wire:model="addonRows.{{ $index }}.label" class="w-full rounded-xl border border-slate-200 text-text-main font-medium focus:ring-plum px-4 py-3 transition-colors" placeholder="e.g. Generator" required>
                                </div>
                                <div class="mt-4">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Price <span class="text-slate-400 normal-case tracking-normal font-normal">(Leave empty for free)</span></label>
                                    <input type="number" step="0.01" wire:model="addonRows.{{ $index }}.price" class="w-full rounded-xl border border-slate-200 text-text-main font-medium focus:ring-plum px-4 py-3 transition-colors" placeholder="Free">
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @if(!$addon_id)
                        <button type="button" wire:click="addAddonRow" class="text-plum text-[12px] font-bold flex items-center gap-1 mb-6 mt-4 hover:text-plum-dark transition-colors tracking-wide">+ Add Row</button>
                        @else
                        <div class="mt-6"></div>
                        @endif
                        <button type="submit" class="w-full btn-plum py-3 rounded-xl font-bold text-[15px]">{{ $addon_id ? 'Update Item' : 'Save Items' }}</button>
                    </form>
                </div>
            </div>

            <div class="xl:col-span-8 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden h-fit">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-bold text-text-main text-[15px]">Existing Specific Add-ons</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="border-b border-slate-100 bg-white">
                            <tr>
                                <th class="py-4 pl-6 text-[11px] font-bold text-slate-500 uppercase tracking-widest w-[20%]">Category</th>
                                <th class="py-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest w-[50%]">Item Name</th>
                                <th class="py-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest w-[15%]">Price</th>
                                <th class="py-4 pr-6 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest w-[15%]">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($extras_addons as $addon)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-5 pl-6 text-[12px] font-bold text-slate-500 uppercase tracking-wider">
                                    {{ $addon->category_target }}
                                    @if($addon->counts_against && $addon->counts_against !== $addon->category_target)
                                    <div class="mt-1">
                                        <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 border border-amber-100 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-widest">
                                            Limit: {{ $addon->counts_against }}
                                        </span>
                                    </div>
                                    @endif
                                </td>
                                <td class="py-5 text-[15px] font-medium text-text-main">{{ $addon->addon_label }}</td>
                                <td class="py-5 font-bold text-text-main">
                                    @if($addon->addon_price > 0) ${{ number_format($addon->addon_price, 2) }} @else <span class="text-emerald-600 uppercase text-[11px]">Free</span> @endif
                                </td>
                                <td class="py-5 pr-6 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="editAddon({{ $addon->id }})" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 flex items-center justify-center transition-colors"><span class="material-symbols-rounded text-[18px]">edit</span></button>
                                        <button type="button"
                                            @click="$dispatch('open-modal', { 
                                                title: 'Delete Add-on?', 
                                                message: 'Remove \'{{ addslashes($addon->addon_label) }}\'?', 
                                                type: 'danger', 
                                                event: 'execute-delete-addon', 
                                                params: {{ $addon->id }} 
                                            })"
                                            class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors"><span class="material-symbols-rounded text-[18px]">delete</span></button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <hr class="border-t-[1.5px] border-dashed border-slate-300">

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            <div class="xl:col-span-4 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden h-fit">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-bold text-text-main text-[15px]">2. Add Dropdown Selection</h3>
                    @if($dd_id)
                    <button type="button" wire:click="resetDropdownForm" class="text-xs font-semibold text-slate-400 hover:text-red-500 transition-colors">Cancel Edit</button>
                    @endif
                </div>
                <div class="p-6">
                    <form wire:submit.prevent="saveDropdown">
                        <div class="space-y-4 mb-2">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Target Category</label>
                                <select wire:model="dd_category" class="w-full rounded-xl border border-slate-200 text-text-main font-medium focus:ring-plum px-4 py-3 transition-colors">
                                    <option value="General Logistics">General Logistics</option>
                                    @foreach ($categories as $c)
                                    <option value="{{ $c->category_name }}">{{ $c->category_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Counts Against Target</label>
                                <select wire:model="dd_counts_against" class="w-full rounded-xl border border-slate-200 text-text-main font-medium focus:ring-plum focus:border-plum px-4 py-3 transition-colors">
                                    <option value="">-- Same as Target Category --</option>
                                    @foreach ($categories as $c)
                                    <option value="{{ $c->category_name }}">{{ $c->category_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Dropdown Label</label>
                                <input type="text" wire:model="dd_label" class="w-full rounded-xl border border-slate-200 text-text-main font-medium focus:ring-plum px-4 py-3 transition-colors" placeholder="e.g. Hire Type" required>
                            </div>
                            @foreach($dropdownRows as $index => $row)
                            <div class="relative space-y-4 pt-4 {{ $index > 0 ? 'border-t border-dashed border-slate-200' : '' }}">
                                @if($index > 0)
                                <button type="button" wire:click="removeDropdownRow({{ $index }})" class="absolute top-2 right-0 text-red-400 hover:text-red-600 transition-colors">
                                    <span class="material-symbols-rounded text-sm">close</span>
                                </button>
                                @endif
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Option Name</label>
                                    <input type="text" wire:model="dropdownRows.{{ $index }}.label" class="w-full rounded-xl border border-slate-200 text-text-main font-medium focus:ring-plum px-4 py-3 transition-colors" placeholder="e.g. Standard Hire" required>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Price <span class="text-slate-400 normal-case tracking-normal font-normal">(Leave empty for free)</span></label>
                                    <input type="number" step="0.01" wire:model="dropdownRows.{{ $index }}.price" class="w-full rounded-xl border border-slate-200 text-text-main font-medium focus:ring-plum px-4 py-3 transition-colors" placeholder="Free">
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" wire:click="addDropdownRow" class="text-plum text-[12px] font-bold flex items-center gap-1 mb-6 mt-4 hover:text-plum-dark transition-colors tracking-wide">+ Add Option</button>
                        <button type="submit" class="w-full btn-plum py-3 rounded-xl font-bold text-[15px]">{{ $dd_id ? 'Update Dropdown' : 'Save Dropdown' }}</button>
                    </form>
                </div>
            </div>

            <div class="xl:col-span-8 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden h-fit">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-bold text-text-main text-[15px]">Existing Dropdowns</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="border-b border-slate-100 bg-white">
                            <tr>
                                <th class="py-4 pl-6 text-[11px] font-bold text-slate-500 uppercase tracking-widest w-1/4">Category</th>
                                <th class="py-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest w-2/4">Label / Options</th>
                                <th class="py-4 pr-6 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($dropdowns as $dd)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-5 pl-6 text-[12px] font-bold text-slate-500 uppercase tracking-wider align-top">
                                    {{ $dd->category_target }}
                                    @if($dd->counts_against && $dd->counts_against !== $dd->category_target)
                                    <div class="mt-1">
                                        <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 border border-amber-100 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-widest">
                                            Limit: {{ $dd->counts_against }}
                                        </span>
                                    </div>
                                    @endif
                                </td>
                                <td class="py-5 align-top">
                                    <div class="font-bold text-text-main text-[15px] mb-2">{{ $dd->label }}</div>
                                    @foreach($dd->options as $opt)
                                    <div class="text-[13px] text-slate-500 flex items-center gap-2 mb-1 pl-2 font-medium">
                                        {{ $opt->option_label }}
                                        <span class="text-text-main font-bold ml-1">(@if($opt->option_price > 0) ${{ number_format($opt->option_price, 2) }} @else <span class="text-emerald-600 uppercase text-[10px]">Free</span> @endif)</span>
                                    </div>
                                    @endforeach
                                </td>
                                <td class="py-5 pr-6 text-right align-top">
                                    <div class="flex justify-end gap-2 mt-1">
                                        <button wire:click="editDropdown({{ $dd->id }})" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 flex items-center justify-center transition-colors"><span class="material-symbols-rounded text-[18px]">edit</span></button>
                                        <button type="button"
                                            @click="$dispatch('open-modal', { 
                                                title: 'Delete Dropdown?', 
                                                message: 'Remove the \'{{ addslashes($dd->label) }}\' dropdown?', 
                                                type: 'danger', 
                                                event: 'execute-delete-dropdown', 
                                                params: {{ $dd->id }} 
                                            })"
                                            class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors"><span class="material-symbols-rounded text-[18px]">delete</span></button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div x-show="tab === 'delivery'" wire:key="tab-container-delivery" class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-6">
        <div class="lg:col-span-1">
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-bold text-text-main text-[15px]">{{ $del_id ? 'Edit Zone' : 'Add Zone' }}</h3>
                    @if($del_id)
                    <button type="button" wire:click="$set('del_id', null); $set('zone_name', ''); $set('del_price', '');" class="text-xs font-semibold text-slate-400 hover:text-red-500 transition-colors">Cancel</button>
                    @endif
                </div>
                <form wire:submit="saveDelivery" class="space-y-4">
                    <input type="text" wire:model="zone_name" class="w-full rounded-xl border border-slate-200 text-text-main font-medium px-4 py-3 focus:ring-plum transition-colors" placeholder="Zone Name" required>
                    <input type="number" step="0.01" wire:model="del_price" class="w-full rounded-xl border border-slate-200 text-text-main font-medium px-4 py-3 focus:ring-plum transition-colors" placeholder="Price" required>
                    <button type="submit" class="w-full btn-plum py-3 mt-4 rounded-xl font-bold text-[15px]">Save</button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="border-b border-slate-100 bg-slate-50/50">
                        <tr>
                            <th class="py-4 pl-8 text-[11px] font-bold text-slate-500 uppercase tracking-widest w-1/2">Zone Name</th>
                            <th class="py-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest w-1/4">Price</th>
                            <th class="py-4 pr-8 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest w-1/4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($deliveries as $del)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 pl-8 font-semibold text-text-main text-[15px] flex items-center gap-3">
                                <span class="material-symbols-rounded text-slate-300 text-xl">location_on</span> {{ $del->zone_name }}
                            </td>
                            <td class="py-4 font-bold text-slate-700">${{ number_format($del->price, 2) }}</td>
                            <td class="py-4 pr-8 text-right">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="editDelivery({{ $del->id }})" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 flex items-center justify-center transition-colors"><span class="material-symbols-rounded text-[18px]">edit</span></button>
                                    <button type="button"
                                        @click="$dispatch('open-modal', { 
                                            title: 'Delete Delivery Zone?', 
                                            message: 'Remove zone \'{{ addslashes($del->zone_name) }}\'?', 
                                            type: 'danger', 
                                            event: 'execute-delete-delivery', 
                                            params: {{ $del->id }} 
                                        })"
                                        class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors"><span class="material-symbols-rounded text-[18px]">delete</span></button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-show="tab === 'duration'" wire:key="tab-container-duration" class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-6">
        <div class="lg:col-span-1">
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-bold text-text-main text-[15px]">{{ $dur_id ? 'Edit Duration' : 'Add Duration' }}</h3>
                    @if($dur_id)
                    <button type="button" wire:click="$set('dur_id', null); $set('dur_label', ''); $set('dur_hours', ''); $set('dur_price', '');" class="text-xs font-semibold text-slate-400 hover:text-red-500 transition-colors">Cancel</button>
                    @endif
                </div>
                <form wire:submit="saveDuration" class="space-y-4">
                    <input type="text" wire:model="dur_label" class="w-full rounded-xl border border-slate-200 text-text-main font-medium px-4 py-3 focus:ring-plum transition-colors" placeholder="Label (e.g. 4 Hours)" required>
                    <input type="number" step="0.5" wire:model="dur_hours" class="w-full rounded-xl border border-slate-200 text-text-main font-medium px-4 py-3 focus:ring-plum transition-colors" placeholder="Hours (4.0)" required>
                    <input type="number" step="0.01" wire:model="dur_price" class="w-full rounded-xl border border-slate-200 text-text-main font-medium px-4 py-3 focus:ring-plum transition-colors" placeholder="Price" required>
                    <button type="submit" class="w-full btn-plum py-3 mt-4 rounded-xl font-bold text-[15px]">Save</button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="border-b border-slate-100 bg-slate-50/50">
                        <tr>
                            <th class="py-4 pl-8 text-[11px] font-bold text-slate-500 uppercase tracking-widest w-1/3">Label</th>
                            <th class="py-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest w-1/4">Hours</th>
                            <th class="py-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest w-1/4">Price</th>
                            <th class="py-4 pr-8 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest w-1/6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($durations as $dur)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 pl-8 font-semibold text-text-main text-[15px]">{{ $dur->label }}</td>
                            <td class="py-4 text-[14px] font-medium text-slate-600">{{ $dur->hours }} hrs</td>
                            <td class="py-4 font-bold text-slate-700">${{ number_format($dur->price, 2) }}</td>
                            <td class="py-4 pr-8 text-right">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="editDuration({{ $dur->id }})" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 flex items-center justify-center transition-colors"><span class="material-symbols-rounded text-[18px]">edit</span></button>
                                    <button type="button"
                                        @click="$dispatch('open-modal', { 
                                            title: 'Delete Duration?', 
                                            message: 'Remove duration \'{{ addslashes($dur->label) }}\'?', 
                                            type: 'danger', 
                                            event: 'execute-delete-duration', 
                                            params: {{ $dur->id }} 
                                        })"
                                        class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors"><span class="material-symbols-rounded text-[18px]">delete</span></button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>