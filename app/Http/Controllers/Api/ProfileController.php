<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\PhotoAccessRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function updateCountry(Request $request): JsonResponse
    {
        try {
            $request->validate(['country' => 'required|string|max:100']);

            $user = $request->user();
            $user->update(['country' => $request->country, 'profile_step' => max($user->profile_step, 1)]);

            return $this->success($user, 'Country saved.');
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update country.', $e);
        }
    }

    public function updateLocation(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'city' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
            ]);

            $user = $request->user();
            $user->update(array_filter([
                'city' => $request->city,
                'country' => $request->country,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ], fn ($value) => $value !== null && $value !== ''));

            return $this->success($user, 'Location saved.');
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update location.', $e);
        }
    }

    public function updateBasicInfo(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'gender' => 'required|in:male,female',
                'birthday' => 'required|date|before:today',
            ]);

            $user = $request->user();
            $user->update([
                'name' => $request->name,
                'gender' => $request->gender,
                'birthday' => $request->birthday,
                'profile_step' => max($user->profile_step, 2),
            ]);

            return $this->success($user, 'Basic info saved.');
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update basic info.', $e);
        }
    }

    public function updateEducation(Request $request): JsonResponse
    {
        try {
           

            $user = $request->user();
            $user->update([
                'qualification' => $request->qualification,
                'field_of_study' => $request->field_of_study,
                'university' => $request->university,
                'graduation_year' => $request->graduation_year,
                'profile_step' => max($user->profile_step, 3),
            ]);

            return $this->success($user, 'Education saved.');
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update education.', $e);
        }
    }

    public function updateCareer(Request $request): JsonResponse
    {
        try {
            

            $user = $request->user();
            $user->update([
                'employment_type' => $request->employment_type,
                'job_title' => $request->job_title,
                'company' => $request->company,
                'monthly_income' => $request->monthly_income,
                'residential_status' => $request->residential_status,
                'profile_step' => max($user->profile_step, 4),
            ]);

            return $this->success($user, 'Career info saved.');
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update career.', $e);
        }
    }

    public function updatePhysical(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'height' => 'nullable|string|max:50',
                'weight' => 'nullable|string|max:50',
                'body_type' => 'nullable|in:slim,athletic,average,heavy',
                'complexion' => 'nullable|in:fair,wheatish,dusky,dark',
                'physical_disability' => 'nullable|boolean',
            ]);

            $user = $request->user();
            $user->update([
                'height' => $request->height,
                'weight' => $request->weight,
                'body_type' => $request->body_type,
                'complexion' => $request->complexion,
                'physical_disability' => $request->boolean('physical_disability'),
                'profile_step' => max($user->profile_step, 5),
            ]);

            return $this->success($user, 'Physical details saved.');
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update physical details.', $e);
        }
    }

    public function updateFaith(Request $request): JsonResponse
    {
        try {
        

            $user = $request->user();
            $user->update([
                'religion' => $request->religion,
                'community' => $request->community,
                'sect' => $request->sect,
                'mother_tongue' => $request->mother_tongue,
                'other_languages' => $request->other_languages,
                'profile_step' => max($user->profile_step, 6),
            ]);

            return $this->success($user, 'Faith & community info saved.');
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update faith info.', $e);
        }
    }

  public function uploadPhotos(Request $request): JsonResponse
{
    try {
        $request->validate([
            'photos' => 'required',
            'photos.*' => 'required|file|max:5120',
            'main_index' => 'nullable|integer|min:0',
        ]);

        $user = $request->user();
        
        // Directly get files
        $uploadedFiles = $request->file('photos');
        
        // Ensure it's an array
        if (!is_array($uploadedFiles)) {
            $uploadedFiles = [$uploadedFiles];
        }

        if (empty($uploadedFiles)) {
            return response()->json([
                'success' => false,
                'message' => 'No photos received. Send files as photos[] in form-data.',
            ], 422);
        }

        $photos = array_values($user->photos ?? []);
        if (count($photos) + count($uploadedFiles) > 10) {
            return response()->json([
                'success' => false,
                'message' => 'A user can have a maximum of 10 profile photos.',
            ], 422);
        }

        $mainIndex = $request->filled('main_index')
            ? (int) $request->input('main_index')
            : null;

        if ($mainIndex !== null && $mainIndex >= count($uploadedFiles)) {
            return response()->json([
                'success' => false,
                'message' => 'The selected main photo does not exist.',
            ], 422);
        }

        $hasMainPhoto = collect($photos)->contains(fn ($photo) => (bool) ($photo['is_main'] ?? false));
        if ($mainIndex !== null) {
            foreach ($photos as &$existingPhoto) {
                $existingPhoto['is_main'] = false;
            }
            unset($existingPhoto);
        }

        foreach ($uploadedFiles as $index => $photo) {
            $path = $photo->store('profiles/' . $user->id, 'public');
            $photos[] = [
                'path' => $path,
                'is_main' => $mainIndex !== null
                    ? $index === $mainIndex
                    : (!$hasMainPhoto && $index === 0),
            ];
        }

        $user->update([
            'photos' => $photos,
            'profile_step' => max($user->profile_step, 7),
        ]);

        return $this->success($user->fresh(), 'Photos uploaded.');
    } catch (ValidationException $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        return $this->errorResponse('Failed to upload photos.', $e);
    }
}

    public function setMainPhoto(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'photo_index' => 'required|integer|min:0',
            ]);

            $user = $request->user();
            $photos = array_values($user->photos ?? []);
            $selectedIndex = (int) $data['photo_index'];

            if (!array_key_exists($selectedIndex, $photos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected photo does not exist.',
                ], 422);
            }

            foreach ($photos as $index => &$photo) {
                $photo['is_main'] = $index === $selectedIndex;
            }
            unset($photo);

            $user->update(['photos' => $photos]);

            return $this->success($user, 'Profile photo updated.');
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to set profile photo.', $e);
        }
    }

    private function collectUploadedPhotos(Request $request): array
    {
        $files = $request->file('photos');

        if (!$files) {
            return [];
        }

        return is_array($files) ? array_values(array_filter($files)) : [$files];
    }

    public function updateProfile(Request $request): JsonResponse
    {
        try {
            //  $request->validate([
            // 'photos' => 'required',
            // ],[
            //     'photos.required' => 'Photo is required.',
            // ]);

            $user = $request->user();
            $data = $request->only([
                'name', 'birthday', 'gender', 'bio', 'email', 'phone',
                'city', 'country', 'latitude', 'longitude', 'height', 'mother_tongue', 'marital_status',
                'community', 'residential_status',
            ]);

            if ($request->has('other_languages')) {
                $data['other_languages'] = $request->other_languages;
            }
            if ($request->has('interests')) {
                $data['interests'] = $request->interests;
            }

            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('profiles/' . $user->id, 'public');
                $photos = $user->photos ?? [];
                array_unshift($photos, ['path' => $path, 'is_main' => true]);
                $data['photos'] = $photos;
            }

            $user->update(array_filter($data, fn ($v) => $v !== null));

            return $this->success($user->fresh(), 'Profile updated successfully.');
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update profile.', $e);
        }
    }

    public function completeProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->update(['profile_completed' => true, 'profile_step' => 8]);

            return $this->success($user, 'Congratulations! Your profile is ready.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to complete profile.', $e);
        }
    }

    private function success($user, string $message): JsonResponse
    {
        $user = $user->fresh();

        return response()->json([
            'success' => 200,
            'message' => $message,
            'profile_step' => (int) ($user->profile_step ?? 0),
            'profile_completed' => $user->profile_completed ? 1 : 0,
            'user' => UserResource::toPayload($user),
        ], 200);
    }

    private function errorResponse(string $message, \Exception $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }

    public function updatePhotoVisibility(Request $request): JsonResponse
{
    try {

        $request->validate([
            'profile_photo_visible' => 'sometimes|in:0,1,true,false',
            'additional_photos_visible' => 'sometimes|in:0,1,true,false',
        ]);

        $user = $request->user();

        $data = [];

        if ($request->has('profile_photo_visible')) {
            $data['profile_photo_visible'] = filter_var(
                $request->input('profile_photo_visible'),
                FILTER_VALIDATE_BOOLEAN
            );
        }

        if ($request->has('additional_photos_visible')) {
            $data['additional_photos_visible'] = filter_var(
                $request->input('additional_photos_visible'),
                FILTER_VALIDATE_BOOLEAN
            );
        }

        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide at least one visibility setting.',
            ], 422);
        }

        $user->update($data);

        $user->refresh();

        return response()->json([
            'success' => 200,
            'message' => 'Photo visibility updated successfully.',
            'data' => [
                'profile_photo_visible' => (bool) $user->profile_photo_visible,
                'additional_photos_visible' => (bool) $user->additional_photos_visible,
            ],
        ], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {

        return response()->json([
            'success' => false,
            'message' => $e->validator->errors()->first(),
            'errors' => $e->errors(),
        ], 422);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Failed to update photo visibility settings.',
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}

public function photoGallery(Request $request, User $user): JsonResponse
{
    try {

        $viewer = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Check profile status
        |--------------------------------------------------------------------------
        */

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Profile not available.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Check photo access
        |--------------------------------------------------------------------------
        */

        $accessGranted = false;

        if ($viewer) {

            // Owner can always view own photos
            if ((int) $viewer->id === (int) $user->id) {
                $accessGranted = true;
            }

            // Check approved photo access request
            if (!$accessGranted) {
                $accessGranted = PhotoAccessRequest::hasApprovedAccess(
                    (int) $viewer->id,
                    (int) $user->id
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Profile photo visibility
        |--------------------------------------------------------------------------
        */

        $profilePhotoVisible =
            (bool) ($user->profile_photo_visible ?? true)
            || $accessGranted;

        /*
        |--------------------------------------------------------------------------
        | Additional photos visibility
        |--------------------------------------------------------------------------
        */

        $additionalPhotosVisible =
            (bool) ($user->additional_photos_visible ?? true)
            || $accessGranted;

        /*
        |--------------------------------------------------------------------------
        | Get photos
        |--------------------------------------------------------------------------
        */

        $photos = collect($user->photos ?? []);

        /*
        |--------------------------------------------------------------------------
        | If profile photo is hidden
        |--------------------------------------------------------------------------
        */

        if (!$profilePhotoVisible) {
            $photos = $photos->reject(function ($photo) {
                return (bool) ($photo['is_main'] ?? false);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | If additional photos are hidden
        |--------------------------------------------------------------------------
        */

        if (!$additionalPhotosVisible) {
            $photos = $photos->filter(function ($photo) {
                return (bool) ($photo['is_main'] ?? false);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Format photos
        |--------------------------------------------------------------------------
        */

        $photos = $photos->values()->map(function ($photo, $index) {

            return [
                'index' => $index,
                'url' => media_url($photo['path'] ?? null),
                'path' => $photo['path'] ?? null,
                'is_main' => (bool) ($photo['is_main'] ?? false),
            ];

        })->values()->all();

        return response()->json([
            'success' => 200,
            'message' => 'Photo gallery retrieved successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'visibility' => [
                'profile_photo_visible' => $profilePhotoVisible,
                'additional_photos_visible' => $additionalPhotosVisible,
                'access_granted' => $accessGranted,
            ],
            'photos' => $photos,
            'total_photos' => count($photos),
        ], 200);

    } catch (\Exception $e) {

        \Log::error('Failed to load photo gallery', [
            'viewer_id' => $request->user()?->id,
            'profile_id' => $user->id ?? null,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to load photo gallery.',
            'error' => config('app.debug')
                ? $e->getMessage()
                : null,
        ], 500);
    }
}
}
