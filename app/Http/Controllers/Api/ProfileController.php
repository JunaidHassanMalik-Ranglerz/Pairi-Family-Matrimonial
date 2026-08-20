<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
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

        $photos = [];
        $mainIndex = (int) $request->input('main_index', 0);

        foreach ($uploadedFiles as $index => $photo) {
            $path = $photo->store('profiles/' . $user->id, 'public');
            $photos[] = [
                'path' => $path,
                'is_main' => $index === $mainIndex,
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
             $request->validate([
            'photos' => 'required',
            ],[
                'photos.required' => 'Photo is required.',
            ]);

            $user = $request->user();
            $data = $request->only([
                'name', 'birthday', 'gender', 'bio', 'email', 'phone',
                'city', 'country', 'height', 'mother_tongue', 'marital_status',
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

            return $this->success($user->fresh(), 'Profile updated.');
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
        return response()->json([
            'success' => 200,
            'message' => $message,
            'user' => UserResource::toPayload($user->fresh()),
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
}
