<aside
    id="sidebar"
    class="fixed inset-y-0 left-0 z-[60] bg-white shadow-2xl lg:shadow-xl flex flex-col h-full border-r border-gray-100 overflow-visible transition-all duration-300 ease-in-out"
    :class="{ 
        'collapsed w-20': isCollapsed, 
        'w-72': !isCollapsed, 
        '-translate-x-full lg:translate-x-0': !isMobileOpen, 
        'translate-x-0': isMobileOpen 
    }">

    <div class="h-20 flex items-center justify-center relative shrink-0 border-b border-gray-50 px-4">
        <img src="{{ asset('assets/icon/bgfunlogo.png') }}" alt="BigFun" class="h-8 w-auto transition-opacity duration-300" :class="isCollapsed ? 'hidden' : 'block'">
        <img src="{{ asset('assets/icon/bfun.png') }}" alt="B" class="h-8 w-auto transition-opacity duration-300" :class="isCollapsed ? 'block' : 'hidden'">

        <button id="pc-toggle-btn" type="button" @click="isCollapsed = !isCollapsed" class="hidden lg:flex absolute -right-3 top-1/2 -translate-y-1/2 bg-white border border-gray-200 text-gray-400 p-1 rounded-full shadow-sm z-20 hover:text-[#9E6B73] transition-colors" aria-label="Toggle sidebar size">
            <span class="material-symbols-rounded text-lg transition-transform duration-300" :class="isCollapsed ? 'rotate-180' : ''">chevron_left</span>
        </button>

        <button type="button" @click="isMobileOpen = false" class="lg:hidden absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#9E6B73]" aria-label="Close sidebar">
            <span class="material-symbols-rounded text-2xl">close</span>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto no-scrollbar py-4 px-3 space-y-1 overflow-x-hidden">
        <div class="nav-section-title text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2 px-3 whitespace-nowrap overflow-hidden transition-all duration-300">
            Main Menu
        </div>

        <a href="/calendar" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all group relative {{ request()->is('calendar*') ? 'active-nav bg-[#FDF2F4] text-[#9E6B73] font-semibold border border-[#9E6B73]/10' : 'text-gray-500 hover:bg-gray-50 hover:text-[#9E6B73]' }}">
            <span class="material-symbols-rounded text-xl shrink-0">calendar_month</span>
            <span class="nav-text whitespace-nowrap transition-opacity duration-300">Calendar View</span>
        </a>

        <a href="/history" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all group relative {{ request()->is('history*') ? 'active-nav bg-[#FDF2F4] text-[#9E6B73] font-semibold border border-[#9E6B73]/10' : 'text-gray-500 hover:bg-gray-50 hover:text-[#9E6B73]' }}">
            <span class="material-symbols-rounded text-xl shrink-0">history</span>
            <span class="nav-text whitespace-nowrap transition-opacity duration-300">Booking History</span>
        </a>

        <div class="nav-section-title text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2 mt-6 px-3 whitespace-nowrap overflow-hidden transition-all duration-300">
            Management
        </div>

        <a href="/logistics" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all group relative {{ request()->is('logistics*') ? 'active-nav bg-[#FDF2F4] text-[#9E6B73] font-semibold border border-[#9E6B73]/10' : 'text-gray-500 hover:bg-gray-50 hover:text-[#9E6B73]' }}">
            <span class="material-symbols-rounded text-xl shrink-0">inbox</span>
            <span class="nav-text whitespace-nowrap transition-opacity duration-300">Logistics Inbox</span>
        </a>

        <a href="/enquiries" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all group relative {{ request()->is('enquiries*') ? 'active-nav bg-[#FDF2F4] text-[#9E6B73] font-semibold border border-[#9E6B73]/10' : 'text-gray-500 hover:bg-gray-50 hover:text-[#9E6B73]' }}">
            <span class="material-symbols-rounded text-xl shrink-0">contact_mail</span>
            <span class="nav-text whitespace-nowrap transition-opacity duration-300">Manage Enquiries</span>
        </a>

        <a href="/staff" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all group relative {{ request()->is('staff*') ? 'active-nav bg-[#FDF2F4] text-[#9E6B73] font-semibold border border-[#9E6B73]/10' : 'text-gray-500 hover:bg-gray-50 hover:text-[#9E6B73]' }}">
            <span class="material-symbols-rounded text-xl shrink-0">group</span>
            <span class="nav-text whitespace-nowrap transition-opacity duration-300">Staff & Deliverers</span>
        </a>

        <a href="/reports" class="nav-item flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all group relative {{ request()->is('reports*') ? 'active-nav bg-[#FDF2F4] text-[#9E6B73] font-semibold border border-[#9E6B73]/10' : 'text-gray-500 hover:bg-gray-50 hover:text-[#9E6B73]' }}">
            <span class="material-symbols-rounded text-xl shrink-0">bar_chart</span>
            <span class="nav-text whitespace-nowrap transition-opacity duration-300">Financial Reports</span>
        </a>
    </nav>

    <div class="p-4 border-t border-gray-100 whitespace-nowrap overflow-hidden">
        <a href="/profile" class="nav-item flex items-center gap-3 p-2 rounded-xl transition-all group {{ request()->is('profile*') ? 'active-nav bg-[#FDF2F4] ring-1 ring-[#9E6B73]/20' : 'hover:bg-gray-50' }}">
            <div class="w-9 h-9 rounded-full bg-[#9E6B73] text-white flex items-center justify-center shrink-0 font-bold shadow-sm">
                <span class="material-symbols-rounded text-lg">person</span>
            </div>
            <div class="profile-details overflow-hidden transition-all duration-300">
                <p class="font-bold text-xs text-gray-700 truncate profile-name">Supervisor</p>
                <p class="text-[10px] text-gray-400 truncate">Edit Profile</p>
            </div>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="mt-1">
            @csrf
            <button type="submit" class="w-full nav-item flex items-center gap-3 p-2 rounded-xl text-red-400 hover:text-red-500 hover:bg-red-50 transition-all">
                <span class="material-symbols-rounded text-xl shrink-0">logout</span>
                <span class="nav-text text-xs font-bold uppercase transition-opacity duration-300">Log Out</span>
            </button>
        </form>
    </div>
</aside>