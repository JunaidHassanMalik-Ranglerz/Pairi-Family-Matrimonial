@extends('website.layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('global/css/password-toggle.css') }}">
@endpush

@section('content')
    @yield('user-breadcrumb')

    @php
        $user = auth()->user();
    @endphp

    <section class="wsus__dashboard mt_90 xs_mt_70 pb_120 xs_pb_100">
        <div class="container">
            <div class="row">
                <div class="col-xl-3 col-md-4">
                    <div class="wsus__dashboard_sidebar">
                        <div class="wsus__dashboard_sidebar_top">
                            <div class="dashboard_banner">
                                <img class="img-fluid" src="{{ asset('website/images/single_topic_sidebar_banner.webp') }}"
                                    alt="img">
                            </div>
                            <div class="img">
                                <img class="img-fluid w-100"
                                    src="{{ $user->profile_photo }}"
                                    alt="profile">
                            </div>
                            <h4>{{ auth()->user()->name ?? '' }}</h4>
                        </div>
                        <ul class="wsus__dashboard_sidebar_menu">
                            <li>
                                <a class="{{ isRoute(['website.user.dashboard', 'website.user.edit.profile'], 'active') }}"
                                    href="{{ route('website.user.dashboard') }}">
                                    <div class="img">
                                        <img class="img-fluid w-100" src="{{ asset('website/images/dash_icon_1.webp') }}"
                                            alt="icon">
                                    </div>
                                    {{ __('Profile') }}
                                </a>
                            </li>
                            <li>
                                <a class="{{ isRoute(['website.user.orders', 'website.user.invoice', 'website.invoice'], 'active') }}"
                                    href="{{ route('website.user.orders') }}">
                                    <div class="img">
                                        <img class="img-fluid w-100" src="{{ asset('website/images/dash_icon_5.webp') }}"
                                            alt="icon">
                                    </div>
                                    {{ __('Orders') }}
                                </a>
                            </li>
                            <li>
                                <a class="{{ isRoute('website.user.wishlist', 'active') }}"
                                    href="{{ route('website.user.wishlist') }}">
                                    <div class="img">
                                        <img class="img-fluid w-100" src="{{ asset('website/images/react_2.webp') }}"
                                            alt="icon">
                                    </div>
                                    {{ __('Wishlist') }}
                                </a>
                            </li>
                            <li>
                                <a class="{{ isRoute(['website.user.reviews*'], 'active') }}"
                                    href="{{ route('website.user.reviews') }}">
                                    <div class="img">
                                        <img class="img-fluid w-100" src="{{ asset('website/images/dash_icon_4.webp') }}"
                                            alt="icon">
                                    </div>
                                    {{ __('Reviews') }}
                                </a>
                            </li>

                            <li>
                                <a class="{{ isRoute(['website.user.address*'], 'active') }}"
                                    href="{{ route('website.user.address.index') }}">
                                    <div class="img">
                                        <img class="img-fluid w-100" src="{{ asset('website/images/location_2.webp') }}"
                                            alt="icon">
                                    </div>
                                    {{ __('Address') }}
                                </a>
                            </li>

                            <li>
                                <a class="{{ isRoute('website.user.change.password', 'active') }}"
                                    href="{{ route('website.user.change.password') }}">
                                    <div class="img">
                                        <img class="img-fluid w-100" src="{{ asset('website/images/dash_icon_10.webp') }}"
                                            alt="icon">
                                    </div>
                                    {{ __('Change Password') }}
                                </a>
                            </li>

                            @if (auth()->user()?->seller)
                                <li>
                                    <a href="{{ route('seller.dashboard') }}">
                                        <div class="img">
                                            <img class="img-fluid w-100" src="{{ asset('website/images/user_1.webp') }}"
                                                alt="icon">
                                        </div>
                                        {{ __('Seller Dashboard') }}
                                    </a>
                                </li>
                            @else
                                <li>
                                    <a href="{{ route('website.join-as-seller') }}">
                                        <div class="img">
                                            <img class="img-fluid w-100" src="{{ asset('website/images/user_1.webp') }}"
                                                alt="icon">
                                        </div>
                                        {{ __('Join as Seller') }}
                                    </a>
                                </li>
                            @endif

                            <li>
                                <a class="logout" href="{{ route('logout') }}">
                                    <div class="img">
                                        <img class="img-fluid w-100" src="{{ asset('website/images/dash_icon_16.webp') }}"
                                            alt="icon">
                                    </div>
                                    {{ __('Sign Out') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-xl-9 col-md-8">
                    @yield('user-content')
                </div>
            </div>
        </div>
    </section>
    <script src="{{ asset('global/js/password-toggle.js') }}"></script>
@endsection
