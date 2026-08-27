{{-- Mobile drawer --}}
<aside x-show="mobileSidebarOpen" x-cloak
    class="fixed inset-0 z-50 md:hidden flex"
    x-transition:enter="transition-opacity ease-linear duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0">

    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" @click="mobileSidebarOpen = false"></div>

    <div class="relative flex w-full max-w-xs flex-col bg-white shadow-2xl h-full"
        x-show="mobileSidebarOpen"
        x-transition:enter="transition ease-in-out duration-300 transform"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in-out duration-300 transform"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full">
        
        <div class="flex items-center justify-between px-4 pt-5 pb-4">
            <div class="flex items-center gap-3">
                <div class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white shadow-sm">
                    <span class="text-sm font-bold tracking-wide">GCI</span>
                </div>
                <div>
                    <div class="text-sm font-semibold text-slate-900 leading-5">Geum Cheon Indo</div>
                    <div class="text-[10px] uppercase tracking-wider text-slate-500 font-medium">Smart App System</div>
                </div>
            </div>
            <button type="button" class="rounded-xl p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600" @click="mobileSidebarOpen = false">
                <span class="sr-only">Close sidebar</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <nav id="mobile-sidebar-nav" class="flex-1 overflow-y-auto px-4 pb-4 space-y-6">
            @include('layouts.sidebar-links')
        </nav>

        <div class="border-t border-slate-200 px-4 py-4">
            <a href="{{ route('profile.edit') }}" class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200 text-slate-600 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-violet-50 hover:text-slate-900" @click="mobileSidebarOpen = false">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 0 0-16 0 M12 13a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" />
                </svg>
                <span class="ml-3">Profile</span>
            </a>
            
            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                @csrf
                <button type="submit" class="w-full group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200 text-rose-600 hover:bg-rose-50" @click="mobileSidebarOpen = false">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                    </svg>
                    <span class="ml-3">Log Out</span>
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- Desktop sidebar --}}
<aside class="hidden md:flex sticky top-0 z-30 h-screen shrink-0 flex-col border-r border-slate-200 bg-white transition-all duration-200 overflow-hidden" :class="sidebarCollapsed ? 'w-20' : 'w-72'">
    <div class="px-4 pt-6">
        <div class="flex items-center rounded-2xl border border-slate-200 bg-white shadow-sm px-4 py-4" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
            <div class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white shadow-sm">
                <span class="text-sm font-bold tracking-wide">GCI</span>
            </div>
            <div x-show="!sidebarCollapsed" x-cloak class="min-w-0">
                <div class="text-sm font-semibold text-slate-900 leading-5 truncate">Geum Cheon Indo</div>
                <div class="text-[10px] uppercase tracking-wider text-slate-500 font-medium truncate">Smart App System</div>
            </div>
        </div>
    </div>

    <nav id="desktop-sidebar-nav" class="flex-1 min-h-0 overflow-y-auto px-4 pb-6 pt-6 space-y-6">
        @include('layouts.sidebar-links')
    </nav>
</aside>
