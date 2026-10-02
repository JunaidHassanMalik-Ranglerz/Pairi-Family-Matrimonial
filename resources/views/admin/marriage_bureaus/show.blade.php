@extends('admin.master_layout')

@section('title')
<title>Marriage Bureau Details - Piyari Family</title>
@endsection

@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <div class="section-header-back">
                <a href="{{ route('admin.marriage-bureaus.index') }}" class="btn btn-icon"><i class="fas fa-arrow-left"></i></a>
            </div>
            <h1>Marriage Bureau Details</h1>
            <div class="section-header-button ml-auto d-flex" style="gap:6px;">
                <form action="{{ route('admin.marriage-bureaus.update', $bureau->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="{{ $bureau->status === 'active' ? 'inactive' : 'active' }}">
                    <button type="submit" class="btn btn-{{ $bureau->status === 'active' ? 'warning' : 'success' }}">
                        <i class="fas fa-{{ $bureau->status === 'active' ? 'ban' : 'check' }}"></i>
                        {{ $bureau->status === 'active' ? 'Deactivate' : 'Activate' }}
                    </button>
                </form>
                <x-admin.delete-button
                    class="deleteForm"
                    data-url="{{ route('admin.marriage-bureaus.destroy', $bureau->id) }}"
                    text="Delete"
                    title="Delete"
                />
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12 col-md-12 col-lg-5">
                    <div class="card profile-widget">
                        <div class="profile-widget-header">
                            <img alt="image" src="{{ $bureau->image ? asset($bureau->image) : asset('backend/img/avatar/avatar-1.png') }}" class="rounded-circle profile-widget-picture" style="object-fit:cover;">
                            <div class="profile-widget-items">
                                <div class="profile-widget-item">
                                    <div class="profile-widget-item-label">Status</div>
                                    <div class="profile-widget-item-value">
                                        @if($bureau->status == 'active')
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="profile-widget-description pb-0">
                            <div class="profile-widget-name">{{ $bureau->name }} <div class="text-muted d-inline font-weight-normal"><div class="slash"></div> Marriage Bureau</div></div>
                            <ul class="list-group list-group-flush mt-3">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Email:</strong> {{ $bureau->email }}
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Phone:</strong> {{ $bureau->phone ?? 'N/A' }}
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Joined:</strong> {{ $bureau->created_at->format('d M, Y') }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-12 col-lg-7">
                    <div class="card">
                        <div class="card-header">
                            <h4>Subscription Details</h4>
                        </div>
                        <div class="card-body">
                            @php
                                $subscription = \App\Models\MarriageBureauSubscription::with('plan')->where('marriage_bureau_id', $bureau->id)->latest()->first();
                            @endphp

                            @if($subscription)
                                <ul class="list-group mb-4">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>Plan Name:</strong> {{ $subscription->plan->name ?? 'N/A' }}
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>Plan Price:</strong> {{ $subscription->plan->price ?? '0' }}
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>Status:</strong> 
                                        @if($subscription->status == 'verified')
                                            <span class="badge badge-success">Verified</span>
                                        @elseif($subscription->status == 'paid')
                                            <span class="badge badge-warning">Pending Verification</span>
                                        @else
                                            <span class="badge badge-secondary">{{ ucfirst($subscription->status) }}</span>
                                        @endif
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <strong>Subscribed At:</strong> {{ $subscription->created_at->format('d M, Y h:i A') }}
                                    </li>
                                </ul>

                                @if($subscription->status == 'paid' && $subscription->payment_screenshot)
                                    <div class="mb-4">
                                        <h5>Payment Screenshot</h5>
                                        <a href="{{ asset($subscription->payment_screenshot) }}" target="_blank">
                                            <img src="{{ asset($subscription->payment_screenshot) }}" alt="Screenshot" class="img-fluid rounded border" style="max-height: 300px;">
                                        </a>
                                    </div>
                                    <form action="{{ route('admin.marriage-bureaus.verify-subscription', $subscription->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-check-circle"></i> Verify Payment</button>
                                    </form>
                                @elseif($subscription->status == 'paid' && !$subscription->payment_screenshot)
                                    <div class="alert alert-warning">
                                        Waiting for the Marriage Bureau to upload a payment screenshot.
                                    </div>
                                @endif
                            @else
                                <div class="alert alert-info">
                                    This Marriage Bureau has no active subscriptions.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
