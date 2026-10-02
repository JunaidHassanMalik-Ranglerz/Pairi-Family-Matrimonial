@extends('admin.master_layout')

@section('title')
<title>Marriage Bureaus - Piyari Family</title>
@endsection

@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Marriage Bureaus</h1>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped data-table" id="bureausTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Joined At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bureaus as $bureau)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $bureau->name }}</td>
                                    <td>{{ $bureau->email }}</td>
                                    <td>
                                        @if($bureau->status == 'active')
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $bureau->created_at->format('d M, Y') }}</td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="{{ route('admin.marriage-bureaus.show', $bureau->id) }}" class="btn btn-primary btn-sm" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form action="{{ route('admin.marriage-bureaus.update', $bureau->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="{{ $bureau->status === 'active' ? 'inactive' : 'active' }}">
                                                <button type="submit" class="btn btn-{{ $bureau->status === 'active' ? 'warning' : 'success' }} btn-sm" title="{{ $bureau->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                                    <i class="fas fa-{{ $bureau->status === 'active' ? 'ban' : 'check' }}"></i>
                                                </button>
                                            </form>
                                            <x-admin.delete-button
                                                class="deleteForm"
                                                data-url="{{ route('admin.marriage-bureaus.destroy', $bureau->id) }}"
                                                title="Delete"
                                            />
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
@endsection
