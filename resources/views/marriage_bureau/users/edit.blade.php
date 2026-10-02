@extends('marriage_bureau.master_layout')
@section('title')
<title>Edit User - Piyari Family</title>
@endsection
@section('admin-content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Edit User: {{ $user->name }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ route('marriage-bureau.users.index') }}">Users</a></div>
                <div class="breadcrumb-item">Edit</div>
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
                    <form id="mb-user-form" action="{{ route('marriage-bureau.users.update', $user->id) }}" method="POST" enctype="multipart/form-data" autocomplete="off">
                        @csrf
                        @method('PUT')
                        @include('marriage_bureau.users._form', ['user' => $user])
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

        initMbUserFormTabs(@json(session('active_tab', request('tab', 'tab-basic'))));
        initMbPhotoSelector();
    });
</script>
@include('marriage_bureau.users._form_tabs_js')
@endpush
