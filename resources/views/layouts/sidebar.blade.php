<!-- Mobile Sidebar - hidden by default, shown only via Alpine -->
<aside x-cloak
       x-show="sidebarMobileOpen"
       x-transition:enter="transition ease-in-out duration-300 transform"
       x-transition:enter-start="-translate-x-full"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in-out duration-300 transform"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="-translate-x-full"
       class="mobile-sidebar fixed inset-y-0 left-0 z-50 w-64 bg-sidebar lg:hidden flex flex-col"
       style="display: none !important;"
       x-bind:style="sidebarMobileOpen ? 'display: flex !important;' : 'display: none !important;'">
    @include('layouts.sidebar-content')
</aside>

<!-- Desktop Sidebar -->
<aside class="sidebar-critical hidden lg:flex lg:flex-col lg:fixed lg:inset-y-0 lg:z-50 bg-sidebar transition-all duration-300"
       :class="{ 'lg:w-64': sidebarOpen, 'lg:w-20': !sidebarOpen }">
    @include('layouts.sidebar-content')
</aside>

