@extends('admin.master_layout')
@section('title')
    <title>{{ $user->name }} - Piyari Family</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>User Profile</h1>
            <div class="section-header-breadcrumb">
                <a href="{{ route('admin.users.index') }}" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <div class="section-body">
                    <div class="card">
                        <div class="card-body">
                    <div class="row mb-4 align-items-center">
                        <div class="col-md-2 text-center">
                            @if($user->profile_photo)
                                <img src="{{ $user->profile_photo }}" class="rounded-circle border" style="width:110px;height:110px;object-fit:cover;" alt="Profile">
                            @else
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width:110px;height:110px;">
                                    <i class="fas fa-user fa-3x text-muted"></i>
                                </div>
                                        @endif
                        </div>
                        <div class="col-md-7">
                            <h3 class="mb-1">{{ $user->name }} @if($user->age), {{ $user->age }}@endif</h3>
                            <p class="text-muted mb-2">
                                {{ trim(implode(', ', array_filter([$user->city, $user->country]))) ?: 'Location not set' }}
                                @if($user->job_title) &bull; {{ $user->job_title }} @endif
                            </p>
                            <div class="mb-2">
                                        @if($user->is_verified)
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Email Verified</span>
                                        @else
                                    <span class="badge badge-warning">Email Pending</span>
                                    <button type="button" class="btn btn-sm btn-success ml-1 verify-email-btn" data-url="{{ route('admin.users.verify-email', $user->id) }}">
                                        Verify Email
                                                    </button>
                                                @endif

                                @if($user->phone_verified)
                                    <span class="badge badge-info"><i class="fas fa-phone"></i> Phone Verified</span>
                                            @else
                                    <span class="badge badge-secondary">Phone Pending</span>
                                    @if($user->phone)
                                    <button type="button" class="btn btn-sm btn-info ml-1 verify-phone-btn" data-url="{{ route('admin.users.verify-phone', $user->id) }}">
                                        Verify Phone
                                    </button>
                                            @endif
                                            @endif

                                @if($user->profile_completed)
                                    <span class="badge badge-primary">Profile Complete</span>
                                                @endif
                                            </div>
                            <small class="text-muted">Registered: {{ $user->created_at?->format('d M Y, h:i A') }}</small>
                                        </div>
                        <div class="col-md-3 text-right">
                            <button type="button" class="btn btn-{{ $user->status === 'active' ? 'warning' : 'success' }} toggle-status-btn"
                                data-url="{{ route('admin.users.toggle-status', $user->id) }}">
                                {{ $user->status === 'active' ? 'Deactivate Account' : 'Activate Account' }}
                            </button>
                                        </div>
                                    </div>

                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#personal">Personal</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#education">Education & Career</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#faith">Faith & Physical</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#photos">Photos</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#subscriptions">Subscriptions</a></li>
                    </ul>

                    <div class="tab-content pt-3">
                        <div class="tab-pane fade show active" id="personal">
                            <table class="table table-bordered">
                                <tr><th width="20%">Full Name</th><td>{{ $user->name }}</td><th width="20%">Gender</th><td>{{ ucfirst($user->gender ?? '-') }}</td></tr>
                                <tr><th>Date of Birth</th><td>{{ $user->birthday?->format('d M Y') ?? '-' }}</td><th>Age</th><td>{{ $user->age ?? '-' }}</td></tr>
                                <tr><th>Email</th><td>{{ $user->email }}</td><th>Phone</th><td>{{ $user->phone ?? '-' }}</td></tr>
                                <tr><th>Country</th><td>{{ $user->country ?? '-' }}</td><th>City</th><td>{{ $user->city ?? '-' }}</td></tr>
                                <tr><th>Marital Status</th><td>{{ $user->marital_status ?? '-' }}</td><th>Height</th><td>{{ $user->height ?? '-' }}</td></tr>
                                <tr><th>Residential Status</th><td>{{ $user->residential_status ?? '-' }}</td><th>Social Login</th><td>{{ ucfirst($user->social_provider ?? 'Email') }}</td></tr>
                                <tr><th>About Me</th><td colspan="3">{{ $user->bio ?? '-' }}</td></tr>
                                @if(!empty($user->interests))
                                <tr><th>Interests</th><td colspan="3">
                                    @foreach($user->interests as $interest)
                                        <span class="badge badge-primary">{{ $interest }}</span>
                                                                @endforeach
                                </td></tr>
                                @endif
                            </table>
                                                            </div>

                        <div class="tab-pane fade" id="education">
                            <table class="table table-bordered">
                                <tr><th width="20%">Qualification</th><td>{{ $user->qualification ?? '-' }}</td><th width="20%">Field of Study</th><td>{{ $user->field_of_study ?? '-' }}</td></tr>
                                <tr><th>University</th><td>{{ $user->university ?? '-' }}</td><th>Graduation Year</th><td>{{ $user->graduation_year ?? '-' }}</td></tr>
                                <tr><th>Employment Type</th><td>{{ ucfirst(str_replace('_', ' ', $user->employment_type ?? '-')) }}</td><th>Job Title</th><td>{{ $user->job_title ?? '-' }}</td></tr>
                                <tr><th>Company</th><td>{{ $user->company ?? '-' }}</td><th>Monthly Income</th><td>{{ $user->monthly_income ?? '-' }}</td></tr>
                            </table>
                                                        </div>

                        <div class="tab-pane fade" id="faith">
                            <table class="table table-bordered">
                                <tr><th width="20%">Religion</th><td>{{ $user->religion ?? '-' }}</td><th width="20%">Community</th><td>{{ $user->community ?? '-' }}</td></tr>
                                <tr><th>Sect</th><td>{{ $user->sect ?? '-' }}</td><th>Mother Tongue</th><td>{{ $user->mother_tongue ?? '-' }}</td></tr>
                                <tr><th>Other Languages</th><td colspan="3">
                                    @forelse($user->other_languages ?? [] as $lang)
                                        <span class="badge badge-secondary">{{ $lang }}</span>
                                    @empty - @endforelse
                                </td></tr>
                                <tr><th>Weight</th><td>{{ $user->weight ?? '-' }}</td><th>Body Type</th><td>{{ ucfirst($user->body_type ?? '-') }}</td></tr>
                                <tr><th>Complexion</th><td>{{ ucfirst($user->complexion ?? '-') }}</td><th>Physical Disability</th><td>{{ $user->physical_disability ? 'Yes' : 'No' }}</td></tr>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="photos">f
                            @if(!empty($user->photos))
                            <div class="row">
                                @foreach($user->photos as $photo)
                                <div class="col-md-3 mb-3 text-center">
                                    <img src="{{ asset('uploads/store/' . ($photo['path'] ?? '')) }}" class="img-fluid rounded border mb-1" alt="Photo">
                                    @if($photo['is_main'] ?? false)
                                        <span class="badge badge-success">Main Photo</span>
                                                    @endif
                                </div>
                                @endforeach
                            </div>
                            @else
                                <p class="text-muted">No photos uploaded yet.</p>
                            @endif
                        </div>

                        <div class="tab-pane fade" id="subscriptions">
                            @php
                                $userSubscriptions = \App\Models\UserSubscription::where('user_id', $user->id)
                                    ->join('subscriptions', 'subscriptions.id', '=', 'user_subscriptions.subscription_id')
                                    ->select('user_subscriptions.*', 'subscriptions.name as plan_name', 'subscriptions.price as plan_price')
                                    ->get();
                            @endphp
                            
                            @if($userSubscriptions->count() > 0)
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Plan Name</th>
                                            <th>Price</th>
                                            <th>Payable</th>
                                            <th>Status</th>
                                            <th>Purchased On</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($userSubscriptions as $sub)
                                        <tr>
                                            <td>{{ $sub->plan_name }}</td>
                                            <td>PKR {{ number_format($sub->plan_price, 2) }}</td>
                                            <td>
                                                @if(!is_null($sub->amount_payable))
                                                    PKR {{ number_format($sub->amount_payable, 2) }}
                                                    @if((int) ($sub->discount_percent ?? 0) > 0)
                                                        <br><small class="text-success">{{ (int) $sub->discount_percent }}% profile discount</small>
                                                    @endif
                                                @else
                                                    PKR {{ number_format($sub->plan_price, 2) }}
                                                @endif
                                            </td>
                                            <td>
                                                @if($sub->status == 'verified' || $sub->status == 'free')
                                                    <span class="badge badge-success">{{ ucfirst($sub->status) }}</span>
                                                @elseif($sub->status == 'paid')
                                                    <span class="badge badge-warning">Paid (Pending Verification)</span>
                                                @else
                                                    <span class="badge badge-secondary">{{ ucfirst($sub->status) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $sub->created_at->format('d M Y') }}</td>
                                            <td>
                                                @if($sub->status == 'paid')
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#verifySubscriptionModal" data-sub-id="{{ $sub->id }}">
                                                        Verify Payment
                                                    </button>
                                                @endif
                                                @if($sub->payment_screenshot)
                                                    <a href="{{ asset('uploads/store/' . $sub->payment_screenshot) }}" target="_blank" class="btn btn-sm btn-info">View Screenshot</a>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="text-muted">User has no subscriptions.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
</div>
@endsection

@push('modals')
<!-- Verify Subscription Modal -->
<div class="modal fade" id="verifySubscriptionModal" tabindex="-1" role="dialog" aria-labelledby="verifySubscriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="verifySubscriptionForm" action="{{ route('admin.users.verify-subscription', $user->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="user_subscription_id" id="verify_sub_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verifySubscriptionModalLabel">Verify Subscription Payment</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="payment_screenshot">Upload Payment Screenshot <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="payment_screenshot" id="payment_screenshot" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Verify Payment</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endpush

@push('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function postAction(url, $btn, successMsg) {
    const original = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

    $.post(url, { _token: '{{ csrf_token() }}' }, function(res) {
        if (res.success) {
            Swal.fire('Success', successMsg || res.message, 'success').then(() => location.reload());
        } else {
            Swal.fire('Error', res.message, 'error');
            $btn.prop('disabled', false).html(original);
        }
    }).fail(function() {
        Swal.fire('Error', 'Something went wrong.', 'error');
        $btn.prop('disabled', false).html(original);
    });
}

$(document).on('click', '.verify-email-btn', function() {
    postAction($(this).data('url'), $(this));
});

$(document).on('click', '.verify-phone-btn', function() {
    postAction($(this).data('url'), $(this));
});

$(document).on('click', '.toggle-status-btn', function() {
    postAction($(this).data('url'), $(this));
});

$(document).on('click', '[data-bs-target="#verifySubscriptionModal"]', function (e) {
    e.preventDefault();
    var subId = $(this).data('sub-id');
    $('#verify_sub_id').val(subId);

    var modalEl = document.getElementById('verifySubscriptionModal');
    if (!modalEl) {
        return;
    }

    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    } else if (typeof $(modalEl).modal === 'function') {
        $(modalEl).modal('show');
    }
});

$('#verifySubscriptionForm').on('submit', function(e) {
    e.preventDefault();
    var form = $(this);
    var btn = form.find('button[type="submit"]');
    var original = btn.html();
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

    var formData = new FormData(this);

    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: formData,
        success: function (res) {
            if (res.success) {
                Swal.fire('Success', res.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', res.message, 'error');
                btn.prop('disabled', false).html(original);
            }
        },
        error: function() {
            Swal.fire('Error', 'Something went wrong.', 'error');
            btn.prop('disabled', false).html(original);
        },
        cache: false,
        contentType: false,
        processData: false
    });
});
</script>
@endpush
