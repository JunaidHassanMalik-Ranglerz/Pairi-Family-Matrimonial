@extends('admin.master_layout')
@section('title')
    <title>Users - Piyari Family</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Users Management</h1>
        </div>
        <div class="section-body">
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-2 align-items-center">
                        <div class="col-xl-3 col-md-6">
                            <input type="text" name="search" class="form-control" placeholder="Search name, email, phone, city..." value="{{ request('search') }}">
                        </div>
                        <div class="col-xl col-md-4">
                            <select name="verified" class="form-control">
                                <option value="">Email Verified</option>
                                <option value="yes" @selected(request('verified') === 'yes')>Verified</option>
                                <option value="no" @selected(request('verified') === 'no')>Pending</option>
                            </select>
                        </div>
                        <div class="col-xl col-md-4">
                            <select name="phone_verified" class="form-control">
                                <option value="">Phone Verified</option>
                                <option value="yes" @selected(request('phone_verified') === 'yes')>Verified</option>
                                <option value="no" @selected(request('phone_verified') === 'no')>Pending</option>
                            </select>
                        </div>
                        <div class="col-xl col-md-4">
                            <select name="profile" class="form-control">
                                <option value="">Profile Status</option>
                                <option value="complete" @selected(request('profile') === 'complete')>Complete</option>
                                <option value="incomplete" @selected(request('profile') === 'incomplete')>Incomplete</option>
                            </select>
                        </div>
                        <div class="col-xl col-md-4">
                            <select name="status" class="form-control">
                                <option value="">Account Status</option>
                                <option value="active" @selected(request('status') === 'active')>Active</option>
                                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                                <option value="reactivation_requested" @selected(request('status') === 'reactivation_requested')>Reactivation Requested</option>
                            </select>
                        </div>
                        <div class="col-xl col-md-4">
                            <select name="creation_type" class="form-control">
                                <option value="">Creation Type</option>
                                <option value="app" @selected(request('creation_type') === 'app')>App</option>
                                <option value="marriage_bureau" @selected(request('creation_type') === 'marriage_bureau')>Marriage Bureau</option>
                            </select>
                        </div>
                        <div class="col-xl col-md-4">
                            <select name="subscription" class="form-control">
                                <option value="">Subscription</option>
                                <option value="needs_verification" @selected(request('subscription') === 'needs_verification')>Needs verification</option>
                                <option value="verified" @selected(request('subscription') === 'verified')>Verified (paid)</option>
                                <option value="free" @selected(request('subscription') === 'free')>Free</option>
                            </select>
                        </div>
                        <div class="col-xl-auto col-md-4">
                            <button class="btn btn-primary btn-block" type="submit"><i class="fa fa-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <table class="table table-striped data-table" id="usersTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Creation Type</th>
                                <th>Age</th>
                                <th>Location</th>
                                <th>Profession</th>
                                <th>Email / Phone</th>
                                <th>Verified</th>
                                <th>Profile</th>
                                <th>Subscription</th>
                                <th>Points</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <strong>{{ $user->name }}</strong>
                                    @if($user->gender)
                                        <br><small class="text-muted">{{ ucfirst($user->gender) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($user->marriage_bureau_id)
                                        <span class="badge badge-info">{{ $user->marriageBureau?->name ?? 'Marriage Bureau' }}</span>
                                    @else
                                        <span class="badge badge-secondary">App</span>
                                    @endif
                                </td>
                                <td>{{ $user->age ?? '-' }}</td>
                                <td>{{ trim(implode(', ', array_filter([$user->city, $user->country]))) ?: '-' }}</td>
                                <td>{{ $user->job_title ?? '-' }}</td>
                                <td>
                                    <small>{{ $user->email }}</small><br>
                                    <small>{{ $user->phone ?? '-' }}</small>
                                </td>
                                <td>
                                    @if($user->is_verified)
                                        <span class="badge badge-success">Email</span>
                                    @else
                                        <span class="badge badge-warning">Email</span>
                                    @endif
                                    @if($user->phone_verified)
                                        <span class="badge badge-info">Phone</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ $user->profile_completed ? 'success' : 'secondary' }}">
                                        {{ $user->profile_completed ? 'Complete' : 'Step ' . $user->profile_step . '/8' }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $pendingSub = $user->pendingVerificationSubscription();
                                        $activeSub = $user->activeSubscription();
                                        $activePlanType = $activeSub?->plan?->type;
                                    @endphp
                                    @if($pendingSub)
                                        <span class="badge badge-warning">Needs verification</span>
                                        <br><small>{{ $pendingSub->plan?->name ?? $pendingSub->plan?->type ?? 'Plan' }}</small>
                                    @elseif($activePlanType && $activePlanType !== 'Free' && (float) ($activeSub->plan?->price ?? 0) > 0)
                                        <span class="badge badge-success">{{ $activePlanType }}</span>
                                    @else
                                        <span class="badge badge-secondary">Free</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $user->reward_points }}</strong>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $user->status === 'active' ? 'success' : 'danger' }}">
                                        {{ ucfirst($user->status) }}
                                    </span>
                                    @if($user->hasPendingReactivationRequest())
                                        <br><span class="badge badge-warning mt-1">Reactivation requested</span>
                                        <br><small class="text-muted">{{ $user->reactivation_requested_at?->format('d M Y, h:i A') }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-info btn-sm" title="View"><i class="fa fa-eye"></i></a>
                                        @if($user->pendingVerificationSubscription())
                                            <a href="{{ route('admin.users.show', $user->id) }}#subscriptions" class="btn btn-warning btn-sm" title="Verify subscription">Verify</a>
                                        @endif
                                        @if($user->hasPendingReactivationRequest())
                                            <button type="button" class="btn btn-success btn-sm toggle-status-btn" data-url="{{ route('admin.users.toggle-status', $user->id) }}" title="Approve reactivation">
                                                Activate
                                            </button>
                                        @endif
                                        <x-admin.delete-button class="deleteForm" data-url="{{ route('admin.users.destroy', $user->id) }}" title="Delete" />
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
<x-admin.delete-modal />
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).on('click', '.toggle-status-btn', function() {
    var $btn = $(this);
    var original = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

    $.post($btn.data('url'), { _token: '{{ csrf_token() }}' }, function(res) {
        if (res.success) {
            Swal.fire('Success', res.message, 'success').then(() => location.reload());
        } else {
            Swal.fire('Error', res.message, 'error');
            $btn.prop('disabled', false).html(original);
        }
    }).fail(function() {
        Swal.fire('Error', 'Something went wrong.', 'error');
        $btn.prop('disabled', false).html(original);
    });
});
</script>
@endpush
