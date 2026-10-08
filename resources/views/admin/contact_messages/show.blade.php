@extends('admin.master_layout')
@section('title')
    <title>Contact Message - Piyari Family</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <div class="section-header-back">
                <a href="{{ route('admin.contact-messages.index') }}" class="btn btn-icon"><i class="fas fa-arrow-left"></i></a>
            </div>
            <h1>Contact Message</h1>
        </div>
        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> {{ $contactMessage->name ?: ($contactMessage->user->name ?? '—') }}</p>
                            <p><strong>Email:</strong> {{ $contactMessage->email ?: ($contactMessage->user->email ?? '—') }}</p>
                            <p><strong>Phone:</strong> {{ $contactMessage->phone ?: ($contactMessage->user->phone ?? '—') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong>
                                <span class="badge badge-success">Read</span>
                            </p>
                            <p><strong>Date:</strong> {{ $contactMessage->created_at?->timezone('Asia/Karachi')->format('d M Y, h:i A') }}</p>
                            @if($contactMessage->user_id)
                                <p><strong>User ID:</strong>
                                    <a href="{{ route('admin.users.show', $contactMessage->user_id) }}">{{ $contactMessage->user_id }}</a>
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="form-group">
                        <label><strong>Message</strong></label>
                        <div class="p-3 border rounded bg-light" style="white-space:pre-wrap;">{{ $contactMessage->message }}</div>
                    </div>
                    <div class="mt-3">
                        <x-admin.delete-button class="deleteForm btn btn-danger" data-url="{{ route('admin.contact-messages.destroy', $contactMessage) }}" title="Delete" />
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<x-admin.delete-modal />
@endsection
