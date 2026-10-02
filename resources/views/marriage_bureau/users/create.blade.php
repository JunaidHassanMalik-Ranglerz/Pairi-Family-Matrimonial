@extends('marriage_bureau.master_layout')
@section('title')
<title>Create User - Piyari Family</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Create New User</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('marriage-bureau.users.index') }}">Users</a></div>
                <div class="breadcrumb-item">Create</div>
            </div>
        </div>

        <div class="section-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="card">
                <div class="card-body">
                    <form id="mb-user-form" action="{{ route('marriage-bureau.users.store') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
                        @csrf
                        @include('marriage_bureau.users._form', ['user' => null])
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@push('js')
@include('marriage_bureau.users._photo_selector_js')
<script>
    $(function () {
        $('.select2-multi').select2({
            placeholder: 'Select languages',
            width: '100%'
        });

        // Prevent browser autofill from filling password on create
        var $password = $('#mb-user-form input[name="password"]');
        $password.val('');
        setTimeout(function () { $password.val(''); }, 100);

        initMbUserFormTabs();
        initMbPhotoSelector();
    });
</script>
@include('marriage_bureau.users._form_tabs_js')
@endpush
