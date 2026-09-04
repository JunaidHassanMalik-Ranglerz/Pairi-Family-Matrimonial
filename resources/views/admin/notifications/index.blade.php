@extends('admin.master_layout')
@section('title')
    <title>Notifications - Piyari Family</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header d-flex justify-content-between align-items-center">
            <h1>Notifications</h1>
            <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">
                <i class="fa fa-plus"></i> Send Notification
            </a>
        </div>
        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <table class="table table-striped data-table" id="notificationsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Send To</th>
                                <th>Recipients</th>
                                <th>Created By</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($notifications as $notification)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $notification->title }}</strong></td>
                                    <td>{{ \Illuminate\Support\Str::limit($notification->message, 60) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $notification->send_to === 'all' ? 'info' : 'primary' }}">
                                            {{ $notification->send_to === 'all' ? 'All Users' : 'Selected Users' }}
                                        </span>
                                    </td>
                                    <td>{{ $notification->recipient_count }}</td>
                                    <td>{{ $notification->creator->name ?? 'Admin' }}</td>
                                   <td>{{ $notification->created_at?->timezone('Asia/Karachi')->format('d M Y, h:i A') }}</td>
                                    <td>
                                        <a href="{{ route('admin.notifications.show', $notification->id) }}" class="btn btn-info btn-sm" title="View">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <x-admin.delete-button class="deleteForm" data-url="{{ route('admin.notifications.destroy', $notification->id) }}" title="Delete" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center"></td>
                                    <td class="text-center"></td>
                                    <td class="text-center">No notifications sent yet.</td>
                                    <td class="text-center"></td>
                                    <td class="text-center"></td>
                                    <td class="text-center"></td>
                                    <td class="text-center"></td>
                                    <td class="text-center"></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
<x-admin.delete-modal />
@endsection
