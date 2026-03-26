@props(['href', 'icon', 'active' => false])

<a href="{{ $href }}"
    class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl transition-all group relative {{ $active ? 'bg-[#FDF2F4] text-[#9E6B73] font-bold border border-[#9E6B73]/10' : 'text-gray-500 hover:bg-gray-50 hover:text-[#9E6B73] font-medium' }}"
    :class="isCollapsed ? 'justify-center px-0' : ''">

    <span class="material-symbols-rounded text-xl shrink-0 transition-transform group-hover:scale-110">
        {{ $icon }}
    </span>

    <span class="nav-text whitespace-nowrap" x-show="!isCollapsed">
        {{ $slot }}
    </span>
</a>