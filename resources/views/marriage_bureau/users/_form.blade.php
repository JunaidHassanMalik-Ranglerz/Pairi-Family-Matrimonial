@php
    $u = $user ?? null;
    $old = fn($field, $default = null) => old($field, $u->{$field} ?? $default);
    $selected = fn($field, $value) => (string) $old($field) === (string) $value ? 'selected' : '';
    $checked = fn($field, $value) => (string) $old($field) === (string) $value ? 'checked' : '';
    $otherLanguages = old('other_languages', $u->other_languages ?? []);
    $selectedInterests = old('interests', $u->interests ?? []);
@endphp

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-basic">Basic Info</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-education">Education & Career</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-physical">Physical Attributes</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-faith">Faith & Language</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-about">About & Photo</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-basic">
        <div class="row">
            <div class="form-group col-md-6">
                <label>Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ $old('name') }}" required>
            </div>
            <div class="form-group col-md-6">
                <label>Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="{{ $old('email') }}" required>
            </div>
            <div class="form-group col-md-6">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ $old('phone') }}">
            </div>
            <div class="form-group col-md-4">
                <label>Gender <span class="text-danger">*</span></label>
                <select name="gender" class="form-control" required>
                    <option value="">Select</option>
                    <option value="male" {{ $selected('gender', 'male') }}>Male</option>
                    <option value="female" {{ $selected('gender', 'female') }}>Female</option>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label>Date of Birth</label>
                <input type="date" name="birthday" class="form-control" value="{{ $old('birthday') ? \Illuminate\Support\Carbon::parse($old('birthday'))->format('Y-m-d') : '' }}">
            </div>
            <div class="form-group col-md-4">
                <label>Marital Status</label>
                <select name="marital_status" class="form-control">
                    <option value="">Select</option>
                    @foreach($lookups['marital-statuses'] as $status)
                        <option value="{{ $status }}" {{ $selected('marital_status', $status) }}>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label>Country</label>
                <select name="country" class="form-control">
                    <option value="">Select</option>
                    @foreach($lookups['countries'] as $country)
                        <option value="{{ $country }}" {{ $selected('country', $country) }}>{{ $country }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label>City</label>
                <input type="text" name="city" class="form-control" value="{{ $old('city') }}">
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-education">
        <div class="row">
            <div class="form-group col-md-6">
                <label>Qualification</label>
                <select name="qualification" class="form-control">
                    <option value="">Select</option>
                    @foreach($lookups['qualifications'] as $q)
                        <option value="{{ $q }}" {{ $selected('qualification', $q) }}>{{ $q }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label>Field of Study</label>
                <select name="field_of_study" class="form-control">
                    <option value="">Select</option>
                    @foreach($lookups['fields-of-study'] as $field)
                        <option value="{{ $field }}" {{ $selected('field_of_study', $field) }}>{{ $field }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label>University</label>
                <input type="text" name="university" class="form-control" value="{{ $old('university') }}" placeholder="Enter university">
            </div>
            <div class="form-group col-md-6">
                <label>Graduation Year</label>
                <input type="text" name="graduation_year" class="form-control" value="{{ $old('graduation_year') }}" placeholder="e.g. 2026" maxlength="10">
            </div>
            <div class="form-group col-md-4">
                <label>Employment Type</label>
                <select name="employment_type" class="form-control">
                    <option value="">Select</option>
                    @foreach($lookups['employment-types'] as $type)
                        @php($employmentValue = \Illuminate\Support\Str::snake($type))
                        <option value="{{ $employmentValue }}" {{ $selected('employment_type', $employmentValue) }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-4">
                <label>Job Title</label>
                <input type="text" name="job_title" class="form-control" value="{{ $old('job_title') }}">
            </div>
            <div class="form-group col-md-4">
                <label>Company</label>
                <input type="text" name="company" class="form-control" value="{{ $old('company') }}">
            </div>
            <div class="form-group col-md-6">
                <label>Monthly Income</label>
                <select name="monthly_income" class="form-control">
                    <option value="">Select</option>
                    @foreach($lookups['incomes'] as $range)
                        <option value="{{ $range }}" {{ $selected('monthly_income', $range) }}>{{ $range }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label>Residential Status</label>
                <select name="residential_status" class="form-control">
                    <option value="">Select</option>
                    @foreach($lookups['residences'] as $status)
                        <option value="{{ $status }}" {{ $selected('residential_status', $status) }}>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-physical">
        <div class="row">
            <div class="form-group col-md-3">
                <label>Height</label>
                <input type="text" name="height" class="form-control" placeholder="e.g. 5'6&quot;" value="{{ $old('height') }}">
            </div>
            <div class="form-group col-md-3">
                <label>Weight</label>
                <input type="text" name="weight" class="form-control" placeholder="e.g. 60kg" value="{{ $old('weight') }}">
            </div>
            <div class="form-group col-md-3">
                <label>Body Type</label>
                <select name="body_type" class="form-control">
                    <option value="">Select</option>
                    @foreach($lookups['body-types'] as $type)
                        <option value="{{ $type }}" {{ $selected('body_type', $type) }}>{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-3">
                <label>Complexion</label>
                <select name="complexion" class="form-control">
                    <option value="">Select</option>
                    @foreach($lookups['complexions'] as $c)
                        <option value="{{ $c }}" {{ $selected('complexion', $c) }}>{{ ucfirst($c) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-12">
                <div class="custom-checkbox custom-control">
                    <input type="hidden" name="physical_disability" value="0">
                    <input type="checkbox" class="custom-control-input" id="physical_disability" name="physical_disability" value="1" {{ $old('physical_disability') ? 'checked' : '' }}>
                    <label class="custom-control-label" for="physical_disability">Has physical disability</label>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-faith">
        <div class="row">
            <div class="form-group col-md-6">
                <label>Religion</label>
                <select name="religion" class="form-control">
                    <option value="">Select</option>
                    @foreach($lookups['religions'] as $religion)
                        <option value="{{ $religion }}" {{ $selected('religion', $religion) }}>{{ $religion }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-6">
                <label>Community</label>
                <input type="text" name="community" class="form-control" value="{{ $old('community') }}" placeholder="Enter community">
            </div>
            <div class="form-group col-md-6">
                <label>Sub Community</label>
                <input type="text" name="sect" class="form-control" value="{{ $old('sect') }}" placeholder="Enter sub community">
            </div>
            <div class="form-group col-md-6">
                <label>Mother Tongue</label>
                <select name="mother_tongue" class="form-control">
                    <option value="">Select</option>
                    @foreach($lookups['mother-tongues'] as $lang)
                        <option value="{{ $lang }}" {{ $selected('mother_tongue', $lang) }}>{{ $lang }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-12">
                <label>Other Languages Spoken</label>
                <select name="other_languages[]" class="form-control select2-multi" multiple>
                    @foreach($lookups['languages'] as $lang)
                        <option value="{{ $lang }}" {{ in_array($lang, $otherLanguages ?? []) ? 'selected' : '' }}>{{ $lang }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-about">
        <div class="row">
            <div class="form-group col-md-12">
                <label>About / Bio</label>
                <textarea name="bio" class="form-control" rows="3" maxlength="200">{{ $old('bio') }}</textarea>
            </div>
            <div class="form-group col-md-12">
                <label>Interests</label>
                <select name="interests[]" class="form-control select2-multi" multiple>
                    @foreach($lookups['hobbies-interests'] as $interest)
                        <option value="{{ $interest }}" {{ in_array($interest, $selectedInterests ?? []) ? 'selected' : '' }}>{{ $interest }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Select one or more hobbies/interests configured by admin.</small>
            </div>
            <div class="form-group col-md-12">
                <label>Profile Photos</label>
                <p class="text-muted small mb-2">Select the radio button under an image to make it the main profile photo.</p>

                @php($existingPhotos = array_values($u?->photos ?? []))
                @if(count($existingPhotos))
                    <div class="d-flex flex-wrap mb-3" style="gap:12px;">
                        @foreach($existingPhotos as $photoIndex => $photo)
                            <label class="border rounded p-2 text-center mb-0" style="width:120px;cursor:pointer;">
                                <img src="{{ media_url($photo['path'] ?? null) }}" class="rounded mb-2" style="width:96px;height:96px;object-fit:cover;" alt="Profile photo">
                                <span class="d-block">
                                    <input type="radio" name="selected_photo" value="existing:{{ $photoIndex }}" {{ ($photo['is_main'] ?? false) ? 'checked' : '' }}>
                                    Set as profile
                                </span>
                                @if($photo['is_main'] ?? false)
                                    <span class="badge badge-success mt-1">Current Profile Photo</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                @endif

                <input id="mb-profile-photos" type="file" name="photos[]" class="form-control" accept="image/jpeg,image/png,image/jpg,image/webp" multiple>
                <small class="form-text text-muted">You can upload multiple photos (maximum 10, 5MB each).</small>
                <div id="mb-new-photo-previews" class="d-flex flex-wrap mt-3" style="gap:12px;"></div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" name="save_action" id="save_action" value="save">
<input type="hidden" name="active_tab" id="active_tab" value="tab-basic">

<div class="mt-3 d-flex flex-wrap gap-2" id="user-form-actions">
    <button type="submit" class="btn btn-primary" id="btn-save" data-action="save">
        <i class="fas fa-save"></i> Save
    </button>
    <button type="submit" class="btn btn-outline-primary" id="btn-save-next" data-action="save_next">
        <i class="fas fa-arrow-right"></i> Save and Next
    </button>
</div>
