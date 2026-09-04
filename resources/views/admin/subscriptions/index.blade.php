@extends('admin.master_layout')
@section('title')
    <title>Subscriptions - Piyari Family</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h1>Subscriptions Management</h1>
            {{-- Fixed plans only: Free, VIP, VVIP — create disabled
            <a href="{{ route('admin.subscriptions.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Add New Subscription</a>
            --}}
        </div>
        <div class="section-body">
            <p class="text-muted mb-3">Fixed user plans are available. You can edit <strong>Price</strong> and <strong>Duration</strong> only. Features are read-only. Profile-completion discounts are applied at purchase time and do not change these listed prices.</p>
            <div class="card">
                <div class="card-body">
                    <table class="table table-striped data-table" id="subscriptionsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Price</th>
                                <th>Duration</th>
                                <th>Features</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subscriptions as $sub)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $sub->name }}</strong></td>
                                <td>
                                    <span class="badge badge-{{ $sub->payment_status === 'free' ? 'secondary' : 'success' }}">
                                        {{ $sub->payment_status === 'free' ? 'Free (non paid)' : 'Paid' }}
                                    </span>
                                </td>
                                <td>PKR {{ number_format($sub->price, 2) }}</td>
                                <td>{{ $sub->durationLabel() }}</td>
                                <td>
                                    <ul class="mb-0 pl-3">
                                        @foreach($sub->displayFeatures() as $feature)
                                            <li>{{ $feature }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td>
                                    <a href="{{ route('admin.subscriptions.edit', $sub->id) }}" class="btn btn-info btn-sm" title="Edit"><i class="fa fa-edit"></i></a>
                                    {{-- Delete disabled for fixed plans --}}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td></td>
                                <td colspan="1">No plans found. Run SubscriptionPlansSeeder.</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
