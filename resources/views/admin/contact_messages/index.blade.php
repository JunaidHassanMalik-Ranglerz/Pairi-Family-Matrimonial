@extends('admin.master_layout')
@section('title')
    <title>Contact Messages - Piyari Family</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Contact Messages</h1>
        </div>
        <div class="section-body">
            <div class="card mb-3">
                <div class="card-header">
                    <h4>Support Email</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.contact-messages.update-email') }}" method="POST">
                        @csrf
                        <div class="form-group row mb-0">
                            <label class="col-sm-3 col-form-label">Admin / Support Email</label>
                            <div class="col-sm-6">
                                <input type="email" class="form-control @error('support_email') is-invalid @enderror" name="support_email" value="{{ old('support_email', $supportEmail) }}" required>
                                <small class="text-muted">This address is shown in the app and receives contact messages from users.</small>
                                @error('support_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-sm-3">
                                <button type="submit" class="btn btn-primary">Save Email</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4>Messages</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped data-table" id="contactMessagesTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($messages as $message)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $message->name ?: ($message->user->name ?? '-') }}</td>
                                        <td>{{ $message->email ?: ($message->user->email ?? '-') }}</td>
                                        <td>{{ $message->phone ?: ($message->user->phone ?? '-') }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($message->message, 70) }}</td>
                                        <td>
                                            @if($message->is_read)
                                                <span class="badge badge-success">Read</span>
                                            @else
                                                <span class="badge badge-warning">Unread</span>
                                            @endif
                                        </td>
                                        <td>{{ $message->created_at?->timezone('Asia/Karachi')->format('d M Y, h:i A') }}</td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="{{ route('admin.contact-messages.show', $message) }}" class="btn btn-info btn-sm" title="View">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <x-admin.delete-button class="deleteForm" data-url="{{ route('admin.contact-messages.destroy', $message) }}" title="Delete" />
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<x-admin.delete-modal />
@endsection
