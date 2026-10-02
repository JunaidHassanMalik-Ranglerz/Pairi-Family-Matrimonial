@extends('user.layout.app')

@section('title')
    {{ __('Edit Profile') }} || {{ $setting->app_name }}
@endsection

@section('user-breadcrumb')
    @include('components::breadcrumb', ['title' => __('Edit Profile'), 'image' => 'edit_profile'])
@endsection

@section('user-content')
    <div class="wsus__dashboard_contant">
        <div class="wsus__dashboard_contant_top d-flex flex-wrap justify-content-between">
            <div class="wsus__dashboard_heading">
                <h5>{{ __('Update Your Information') }}</h5>
            </div>
            <div class="wsus__dashboard_profile_delete">
                <a class="common_btn" id="delete_profile" href="javascript:void(0)">{{ __('Delete Profile') }}</a>
            </div>
        </div>

        <div class="wsus__dashboard_profile wsus__dashboard_profile_avatar">
            <div class="img">
                <img class="img-fluid w-100" id="profile_photo_preview"
                    src="{{ $user->profile_photo }}" alt="profile">
                <label for="profile_photo">
                    <img class="img-fluid w-100" src="{{ asset('website/images/dash_camera.webp') }}" alt="camera">
                </label>
                <input id="profile_photo" name="image" form="profileForm" type="file" hidden>
            </div>
            <div class="text">
                <h6>{{ __('Your avatar') }}</h6>
                <p>{{ __('PNG or JPG/JPEG format') }}</p>
            </div>
        </div>

        <form class="wsus__dashboard_profile_update" id="profileForm" action="{{ route('website.user.store.profile') }}"
            enctype="multipart/form-data" method="POST">
            @method('PUT')
            @csrf
            <div class="row">
                <div class="col-xl-6">
                    <div class="wsus__dashboard_profile_update_info">
                        <label for="name">{{ __('Name') }}<span class="text-danger">*</span></label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}"
                            placeholder="{{ __('Enter your name') }}">
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="wsus__dashboard_profile_update_info">
                        <label for="phone">{{ __('Phone') }}<span class="text-danger">*</span></label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}"
                            placeholder="{{ __('Enter your number') }}">
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="wsus__dashboard_profile_update_info">
                        <label for="email">{{ __('Email') }}<span class="text-danger">*</span></label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}"
                            placeholder="Enter your mail">
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="wsus__dashboard_profile_update_info">
                        <label for="birthday">{{ __('Birthday') }}</label>
                        <input id="birthday" name="birthday" type="date" value="{{ old('birthday', $user->birthday) }}"
                            placeholder="{{ __('Enter birthday date') }}">
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="wsus__dashboard_profile_update_info">
                        <label for="gender">{{ __('Gender') }}<span class="text-danger">*</span></label>
                        <select class="select_2" id="gender" name="gender">
                            <option value="">{{ __('Select gender') }}</option>
                            <option value="male" @selected($user->gender == 'male')>{{ __('Male') }}</option>
                            <option value="female" @selected($user->gender == 'female')>{{ __('Female') }}</option>
                            <option value="other" @selected($user->gender == 'other')>{{ __('Other') }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="wsus__dashboard_profile_update_info">
                        <label for="country_id">{{ __('Country') }}<span class="text-danger">*</span></label>
                        <select class="select_2" id="country_id" name="country_id">
                            <option value="">{{ __('Select Country') }}</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}" @selected($user->country_id == $country->id)>{{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="wsus__dashboard_profile_update_info">
                        <label for="city_id">{{ __('City') }}<span class="text-danger">*</span></label>
                        <select class="select_2" id="city_id" name="city_id">
                            <option value="">{{ __('Select City') }}</option>
                            @if (isset($cities))
                                @foreach ($cities as $city)
                                    <option value="{{ $city->id }}" @selected($user->city_id == $city->id)>{{ $city->name }}
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="wsus__dashboard_profile_update_info">
                        <label for="state_id">{{ __('State') }}<span class="text-danger">*</span></label>
                        <select class="select_2" id="state_id" name="state_id">
                            <option value="">{{ __('Select State') }}</option>
                            @if (isset($states))
                                @foreach ($states as $state)
                                    <option value="{{ $state->id }}" @selected($user->state_id == $state->id)>{{ $state->name }}
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div class="col-xl-12">
                    <div class="wsus__dashboard_profile_update_info">
                        <label for="zip_code">{{ __('Zip Code') }}<span class="text-danger">*</span></label>
                        <input id="zip_code" name="zip_code" type="text"
                            value="{{ old('zip_code', $user->zip_code) }}" placeholder="Enter zip code ">
                    </div>
                </div>
                <div class="col-xl-12">
                    <div class="wsus__dashboard_profile_update_info">
                        <label for="address">{{ __('Address') }}<span class="text-danger">*</span></label>
                        <input id="address" name="address" type="text"
                            value="{{ old('address', $user->address) }}" placeholder="Enter your address">
                    </div>
                </div>
                <div class="col-xl-12">
                    <div class="wsus__dashboard_profile_update_info">
                        <label for="about">{{ __('About Me') }}</label>
                        <textarea id="about" name="bio" rows="7" placeholder="{{ __('Write Something') }}">{{ old('bio', $user->bio) }}</textarea>
                    </div>
                </div>
                <div class="col-xl-12">
                    <div class="wsus__dashboard_profile_update_btn">
                        <button class="common_btn" type="submit">{{ __('Update Profile') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="wsus__product_modal">
        <div class="modal fade" id="deleteModal" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true"
            tabindex="-1">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="wsus__delete_modal">
                            <div class="wsus__delete_modal_text">
                                <h4>{{ __('Are you sure?') }}</h4>
                                <p>{{ __('Do you really want to delete your profile?') }} <br>
                                    {{ __('This process cannot be undone and all the data related to this profile will be lost') }}
                                </p>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="wsus__delete_modal_btn">
                            <form action="{{ route('website.user.delete.profile') }}" method="post">
                                @csrf
                                @method('DELETE')
                                <button class="btn" data-bs-dismiss="modal"
                                    type="button">{{ __('Cancel') }}</button>
                                <button class="btn btn-danger" type="submit">{{ __('Delete') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        "use strict";

        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('profile_photo');
            const previewImg = document.getElementById('profile_photo_preview');

            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        previewImg.src = event.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
        });

        $(document).ready(function() {
            // get states
            $(document).on('change', '[name="country_id"]', function() {
                var country_id = $(this).val();
                var form = $(this).closest('form').attr('id');

                $.ajax({
                    url: `{{ route('website.get.all.states.by.country', ':country_id') }}`.replace(
                        ':country_id', country_id),
                    beforeSend: function() {
                        $('.preloader_area').removeClass('d-none');
                    },
                    success: function(response) {
                        $(`#${form} [name="state_id"]`).html('');
                        let options =
                            '<option value="" selected disabled>{{ __('Select State') }}</option>';

                        response.data.forEach(function(state) {
                            options += '<option value="' +
                                state.id +
                                '">' + state.name + '</option>';
                        })
                        $(`#${form} [name="state_id"]`).html(options).niceSelect('destroy')
                            .niceSelect();
                    },
                    error: function(error) {
                        handleError(error);
                    },
                    complete: function() {
                        $('.preloader_area').addClass('d-none');
                    }
                });
            });

            $(document).on('change', '[name="state_id"]', function() {
                var state_id = $(this).val();
                var form = $(this).closest('form');
                $.ajax({
                    url: `{{ route('website.get.all.cities.by.state', ':state_id') }}`.replace(
                        ':state_id', state_id),
                    ,
                    beforeSend: function() {
                        $('.preloader_area').removeClass('d-none');
                    },
                    success: function(response) {
                        form.find('[name="city_id"]').html('');

                        let options =
                            '<option value="" selected disabled>{{ __('Select City') }}</option>';

                        response.data.forEach(function(city) {
                            options += '<option value="' +
                                city.id +
                                '">' + city.name + '</option>';
                        })
                        form.find('[name="city_id"]').html(options).niceSelect('destroy')
                            .niceSelect();
                    },
                    error: function(error) {
                        handleError(error);
                    },
                    complete: function() {
                        $('.preloader_area').addClass('d-none');
                    }
                });
            });

            $(document).on('click', '#delete_profile', function() {
                $('#deleteModal').modal('show');
            });
        });
    </script>
@endpush
