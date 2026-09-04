<style>
.main-sidebar {
    height: 100vh;
    overflow-y: auto;
    background: #ffffff;
}

#sidebar-wrapper {
    height: 100%;
    overflow-y: auto;
}

.sidebar-brand {
    padding: 20px 15px;
    text-align: center;
    border-bottom: 1px solid #dee2e6;
}

.sidebar-brand a {
    display: inline-block;
    text-decoration: none;
}

.sidebar-brand .brand-text {
    font-size: 22px;
    font-weight: 700;
    color: #7B1E3A;
}

.sidebar-brand.panel-logo-brand {
    height: auto !important;
    min-height: 82px;
    padding: 7px 10px;
}

.panel-brand-logo {
    width: 145px;
    height: 68px;
    object-fit: contain;
}

.sidebar-brand-sm .panel-brand-logo {
    width: 42px;
    height: 42px;
}

.sidebar-menu {
    list-style: none;
    padding: 0 0 20px 0;
    margin: 0;
}

.sidebar-menu .menu-header {
    margin: 15px 0 5px;
    padding: 5px 20px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #6c757d;
    font-weight: 600;
}

.sidebar-menu li a {
    display: flex;
    align-items: center;
    padding: 10px 20px;
    color: #333333;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.sidebar-menu li a:hover {
    background: #e9ecef;
    color: #000;
}

.sidebar-menu li a.active {
    background: #7B1E3A;
    color: white;
}

.sidebar-menu li a i {
    width: 24px;
    margin-right: 10px;
    text-align: center;
    color: inherit;
}

.sidebar-menu > li {
    border-bottom: 1px solid #eeeeee;
}

.sidebar-group-toggle {
    font-weight: 600;
    cursor: pointer;
    margin: 4px 8px;
    border-radius: 8px;
    padding: 11px 12px !important;
}

.sidebar-group-toggle .menu-caret {
    width: 24px;
    height: 24px;
    margin-right: 0;
    margin-left: auto;
    border-radius: 50%;
    background: #f2e7eb;
    color: #7B1E3A;
    align-items: center;
    justify-content: center;
    transition: transform 0.25s ease, background-color 0.25s ease;
}

.sidebar-group-toggle .minus-icon,
.sidebar-group-toggle[aria-expanded="true"] .plus-icon {
    display: none;
}

.sidebar-group-toggle[aria-expanded="true"] .minus-icon {
    display: inline-flex;
}

.sidebar-group-toggle[aria-expanded="false"] .plus-icon {
    display: inline-flex;
}

.sidebar-group-toggle[aria-expanded="true"] {
    background: #f8eef1;
    color: #7B1E3A;
}

body.sidebar-mini .main-sidebar .sidebar-group-toggle .menu-caret {
    display: none !important;
}

body.sidebar-mini .main-sidebar .sidebar-submenu,
body.sidebar-gone .main-sidebar .sidebar-submenu {
    display: none !important;
    height: 0 !important;
}

.sidebar-submenu {
    list-style: none;
    margin: 0 10px 6px 22px;
    padding: 4px 0;
    background: #fafafa;
    border-left: 2px solid #ead4dc;
    border-radius: 0 8px 8px 0;
}

.sidebar-submenu li a {
    padding: 9px 15px 9px 45px;
    font-size: 13px;
    border-left: 3px solid transparent;
}

.sidebar-submenu li a::before {
    content: "\203A";
    margin-right: 10px;
    color: #9a9a9a;
}

.sidebar-submenu li a.active {
    color: #7B1E3A;
    background: #f2e7eb;
    border-left-color: #7B1E3A;
    font-weight: 600;
}
</style>

<div class="main-sidebar">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand panel-logo-brand">
            <a href="{{ route('admin.dashboard') }}">
                <img src="{{ asset('assets/img/piyari_logo.png') }}" alt="Piyari Family" class="panel-brand-logo">
            </a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="{{ route('admin.dashboard') }}">
                <img src="{{ asset('assets/img/piyari_logo.png') }}" alt="PF" class="panel-brand-logo">
            </a>
        </div>

        @php
            $usersOpen = request()->routeIs('admin.users.*');
            $featuresOpen = request()->routeIs('admin.subscriptions.*', 'admin.notifications.*');
            $marriageBureauOpen = request()->routeIs('admin.marriage-bureaus.*', 'admin.marriage-bureau-subscriptions.*');
            $profileSettingsOpen = request()->routeIs('admin.lookups.*');
            $configurationOpen = request()->routeIs('admin.settings.*', 'admin.faqs.*', 'admin.content.*');
        @endphp

        <ul class="sidebar-menu" id="adminSidebarMenu">
            <li>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>{{ __('Dashboard') }}</span>
                </a>
            </li>

            <li>
                <a class="sidebar-group-toggle" data-bs-toggle="collapse" href="#usersMenu" role="button" aria-expanded="{{ $usersOpen ? 'true' : 'false' }}" aria-controls="usersMenu">
                    <i class="fas fa-user-friends"></i>
                    <span>{{ __('Users') }}</span>
                    <i class="fas fa-plus menu-caret plus-icon"></i>
                    <i class="fas fa-minus menu-caret minus-icon"></i>
                </a>
                <ul id="usersMenu" class="sidebar-submenu collapse {{ $usersOpen ? 'show' : '' }}" data-bs-parent="#adminSidebarMenu">
                    <li><a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">{{ __('All Users') }}</a></li>
                </ul>
            </li>

            <li>
                <a class="sidebar-group-toggle" data-bs-toggle="collapse" href="#featuresMenu" role="button" aria-expanded="{{ $featuresOpen ? 'true' : 'false' }}" aria-controls="featuresMenu">
                    <i class="fas fa-layer-group"></i>
                    <span>{{ __('Features') }}</span>
                    <i class="fas fa-plus menu-caret plus-icon"></i>
                    <i class="fas fa-minus menu-caret minus-icon"></i>
                </a>
                <ul id="featuresMenu" class="sidebar-submenu collapse {{ $featuresOpen ? 'show' : '' }}" data-bs-parent="#adminSidebarMenu">
                    <li><a href="{{ route('admin.subscriptions.index') }}" class="{{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}">{{ __('Subscriptions') }}</a></li>
                    <li><a href="{{ route('admin.notifications.index') }}" class="{{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">{{ __('Notifications') }}</a></li>
                </ul>
            </li>

            <li>
                <a class="sidebar-group-toggle" data-bs-toggle="collapse" href="#marriageBureauMenu" role="button" aria-expanded="{{ $marriageBureauOpen ? 'true' : 'false' }}" aria-controls="marriageBureauMenu">
                    <i class="fas fa-building"></i>
                    <span>{{ __('Marriage Bureau') }}</span>
                    <i class="fas fa-plus menu-caret plus-icon"></i>
                    <i class="fas fa-minus menu-caret minus-icon"></i>
                </a>
                <ul id="marriageBureauMenu" class="sidebar-submenu collapse {{ $marriageBureauOpen ? 'show' : '' }}" data-bs-parent="#adminSidebarMenu">
                    <li><a href="{{ route('admin.marriage-bureaus.index') }}" class="{{ request()->routeIs('admin.marriage-bureaus.*') ? 'active' : '' }}">{{ __('Marriage Bureaus') }}</a></li>
                    <li><a href="{{ route('admin.marriage-bureau-subscriptions.index') }}" class="{{ request()->routeIs('admin.marriage-bureau-subscriptions.*') ? 'active' : '' }}">{{ __('MB Subscriptions') }}</a></li>
                </ul>
            </li>

            <li>
                <a class="sidebar-group-toggle" data-bs-toggle="collapse" href="#profileSettingsMenu" role="button" aria-expanded="{{ $profileSettingsOpen ? 'true' : 'false' }}" aria-controls="profileSettingsMenu">
                    <i class="fas fa-user-cog"></i>
                    <span>{{ __('User Profile Settings') }}</span>
                    <i class="fas fa-plus menu-caret plus-icon"></i>
                    <i class="fas fa-minus menu-caret minus-icon"></i>
                </a>
                <ul id="profileSettingsMenu" class="sidebar-submenu collapse {{ $profileSettingsOpen ? 'show' : '' }}" data-bs-parent="#adminSidebarMenu">
                    @foreach(config('profile_lookups', []) as $lookupType => $lookup)
                        @continue(in_array($lookupType, ['physical-body-types', 'physical-disabilities', 'communities', 'sub-communities', 'graduation-years', 'universities'], true))
                        <li>
                            <a href="{{ route('admin.lookups.index', ['type' => $lookupType]) }}" class="{{ request()->routeIs('admin.lookups.*') && request()->route('type') === $lookupType ? 'active' : '' }}">
                                {{ __($lookup['label']) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </li>

            <li>
                <a class="sidebar-group-toggle" data-bs-toggle="collapse" href="#configurationMenu" role="button" aria-expanded="{{ $configurationOpen ? 'true' : 'false' }}" aria-controls="configurationMenu">
                    <i class="fas fa-cogs"></i>
                    <span>{{ __('Configuration') }}</span>
                    <i class="fas fa-plus menu-caret plus-icon"></i>
                    <i class="fas fa-minus menu-caret minus-icon"></i>
                </a>
                <ul id="configurationMenu" class="sidebar-submenu collapse {{ $configurationOpen ? 'show' : '' }}" data-bs-parent="#adminSidebarMenu">
                    <li><a href="{{ route('admin.settings.index') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">{{ __('System Settings') }}</a></li>
                    <li><a href="{{ route('admin.faqs.index') }}" class="{{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}">{{ __('FAQs') }}</a></li>
                    <li><a href="{{ route('admin.content.edit', ['type' => 'terms-conditions']) }}" class="{{ request()->routeIs('admin.content.*') && request()->route('type') === 'terms-conditions' ? 'active' : '' }}">{{ __('Terms & Conditions') }}</a></li>
                    <li><a href="{{ route('admin.content.edit', ['type' => 'privacy-policy']) }}" class="{{ request()->routeIs('admin.content.*') && request()->route('type') === 'privacy-policy' ? 'active' : '' }}">{{ __('Privacy Policy') }}</a></li>
                </ul>
            </li>
        </ul>
    </aside>
</div>
