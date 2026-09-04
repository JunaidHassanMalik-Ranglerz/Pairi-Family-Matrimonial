@php
    $header_user = Auth::guard('admin')->user();
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <link type="image/x-icon" href="{{ asset($setting->favicon ?? '') }}" rel="shortcut icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @yield('title')
    @include('admin.partials.styles')
    @stack('css')
    
    <style>
        /* Piyari Family Premium Theme */
        :root {
            --primary: #6E0016; /* Deep Red */
            --primary-hover: #5a0c0e;
            --secondary: #F5A623; /* Gold */
            --bg-color: #FFF5F5; /* Soft Pink/Cream */
        }
        body {
            background-color: var(--bg-color);
        }
        .navbar-bg {
            background-color: var(--primary) !important;
            height: 70px;
        }
        .main-sidebar {
            background-color: #fff;
            border-right: 2px solid var(--secondary);
            overflow-y: auto;
            height: 100vh;
        }
        .sidebar-menu li.active a {
            color: var(--primary) !important;
            font-weight: bold;
        }
        .sidebar-menu li a:hover {
            color: var(--secondary) !important;
        }
        .btn-primary {
            background-color: var(--primary) !important;
            border-color: var(--primary) !important;
            color: #fff !important;
        }
        .btn-primary:hover {
            background-color: var(--primary-hover) !important;
            border-color: var(--primary-hover) !important;
        }
        .btn-warning, .btn-secondary {
            background-color: var(--secondary) !important;
            border-color: var(--secondary) !important;
            color: #fff !important;
        }
        .card {
            border-top: 3px solid var(--primary);
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .brand-text {
            color: var(--primary);
            font-weight: bold;
        }
        .logo-img {
            max-height: 40px;
        }
        .panel-user-dropdown .dropdown-item.has-icon {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            font-size: 14px;
            color: #34395e;
        }
        .panel-user-dropdown .dropdown-item.has-icon i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
            color: #6E0016;
        }
        .panel-user-dropdown .dropdown-item.has-icon:hover {
            background-color: #f8f9fa;
            color: #6E0016;
        }
        .nav-link-user .d-sm-none.d-lg-inline-block,
        .nav-link-user span {
            color: #ffffff !important;
        }
    </style>
</head>

<body>
    <div id="app">
        <div class="main-wrapper">
            <div class="navbar-bg"></div>
            <nav class="navbar navbar-expand-lg main-navbar px-3 py-2">
                <div class="me-2 form-inline">
                    <ul class="navbar-nav d-flex align-items-center">
                        <li><a class="nav-link nav-link-lg" data-toggle="sidebar" href="javascript:void(0)"><i class="fas fa-bars"></i></a></li>

                        <p style="margin: 0; padding: 0; color: #ffffff; font-size: 20px; font-weight: bold;">
                            @if(Auth::guard('admin')->check())
                                Admin Panel
                            @elseif(Auth::guard('marriage_bureau')->check())
                                Marriage Bureau Panel
                            @else
                                Panel
                            @endif
                        </p>
                    </ul>
                </div>
                <div class="mr-auto me-md-auto search-box position-relative">
                </div>
                @include('partials.panel-user-dropdown', [
                    'profileRoute' => route('admin.edit-profile'),
                    'logoutRoute' => route('admin.logout'),
                    'userName' => $header_user?->name ?? 'Admin',
                    'userImage' => $header_user?->image,
                ])
            </nav>

            @include('admin.sidebar')

            <!-- Main Content -->
            @yield('admin-content')

            <footer class="main-footer">
                <div class="footer-left">
                    {{-- {{ $setting?->copyright }} --}}
                    Piyari Family &copy; {{ date('Y') }}
                </div>
                <div class="footer-right">
                    {{-- {{ $setting?->version }} --}}
                </div>
            </footer>
        </div>
    </div>

    @stack('modals')

    @include('admin.partials.javascripts')

    @stack('js')

    <script>
        (function () {
            document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function (el) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                    if (!bootstrap.Dropdown.getInstance(el)) {
                        new bootstrap.Dropdown(el);
                    }
                }
            });
        })();
    </script>

</body>

</html>
