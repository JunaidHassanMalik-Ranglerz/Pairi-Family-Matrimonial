@extends('admin.master_layout')
@section('title')
    <title>System Settings - Piyari Family</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>System Settings</h1>
        </div>
        <div class="section-body">
            <div class="card mb-3">
                <div class="card-header">
                    <h4>Invite & Earn Settings</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="section" value="invite">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Reward Points per Registration</label>
                            <div class="col-sm-6">
                                <input type="number" class="form-control" name="invite_reward_points" value="{{ $settings['invite_reward_points'] ?? 50 }}" required>
                                <small class="text-muted">Points awarded when a referred user completes email verification.</small>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">1 Point Value (PKR)</label>
                            <div class="col-sm-6">
                                <div class="input-group">
                                    <span class="input-group-text">PKR</span>
                                    <input type="number" step="0.01" min="0" class="form-control" name="point_value_pkr" value="{{ old('point_value_pkr', $settings['point_value_pkr'] ?? 1) }}" required>
                                </div>
                                <small class="text-muted">Example: value 2 means 1 reward point = PKR 2.</small>
                            </div>
                        </div>
                        <div class="form-group row mb-0">
                            <div class="col-sm-3"></div>
                            <div class="col-sm-6">
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h4>Redeem Rewards (Points Cost)</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="section" value="redeem">

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">VIP Plan (1 Month)</label>
                            <div class="col-sm-6">
                                <input type="number" class="form-control" name="redeem_vip_points" value="{{ $settings['redeem_vip_points'] ?? 500 }}" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">VVIP Plan (1 Month)</label>
                            <div class="col-sm-6">
                                <input type="number" class="form-control" name="redeem_vvip_points" value="{{ $settings['redeem_vvip_points'] ?? 1000 }}" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Profile Boost</label>
                            <div class="col-sm-3">
                                <input type="number" class="form-control" name="redeem_boost_points" value="{{ $settings['redeem_boost_points'] ?? 50 }}" required>
                                <small class="text-muted">Points cost</small>
                            </div>
                            <div class="col-sm-3">
                                <input type="number" class="form-control" name="redeem_boost_days" value="{{ $settings['redeem_boost_days'] ?? 7 }}" required>
                                <small class="text-muted">Boost duration (days)</small>
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-sm-3"></div>
                            <div class="col-sm-6">
                                <button type="submit" class="btn btn-primary">Save Settings</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h4>Billing Cycle</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="section" value="billing">
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Annual Discount (%)</label>
                            <div class="col-sm-6">
                                <input type="number" class="form-control" name="annual_billing_discount_percent" min="0" max="100" value="{{ old('annual_billing_discount_percent', $settings['annual_billing_discount_percent'] ?? 40) }}" required>
                                <small class="text-muted">Applied when a VIP or VVIP member switches their current plan from monthly to annual. Example: 40 means 12 months at 40% off the monthly price.</small>
                            </div>
                        </div>
                        <div class="form-group row mb-0">
                            <div class="col-sm-3"></div>
                            <div class="col-sm-6">
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
