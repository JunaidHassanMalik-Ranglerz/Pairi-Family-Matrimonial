<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhotoAccessRequest;
use App\Models\ProfileInterest;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PhotoAccessController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $type = $request->get('type', 'incoming');
        $user = $request->user();

        $query = PhotoAccessRequest::query()
            ->when(
                $type === 'outgoing',
                fn ($q) => $q->where('requester_id', $user->id)
                    ->with('owner:id,name,gender,photos,image,profile_photo_visible'),
                fn ($q) => $q->where('owner_id', $user->id)
                    ->with('requester:id,name,gender,photos,image,profile_photo_visible')
            )
            ->latest();

        $requests = $query->get()->map(function (PhotoAccessRequest $accessRequest) use ($type, $user) {
            $profile = $type === 'outgoing' ? $accessRequest->owner : $accessRequest->requester;
            $profilePhotoVisible = $profile
                && ((bool) ($profile->profile_photo_visible ?? true)
                    || PhotoAccessRequest::hasApprovedAccess((int) $user->id, (int) $profile->id));

            return [
                'id' => $accessRequest->id,
                'status' => $accessRequest->status,
                'profile' => $profile ? [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'profile_photo' => $profilePhotoVisible ? $profile->profile_photo : null,
                ] : null,
                'responded_at' => $accessRequest->responded_at?->toIso8601String(),
                'created_at' => $accessRequest->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => 200,
            'type' => $type === 'outgoing' ? 'outgoing' : 'incoming',
            'requests' => $requests,
        ]);
    }

    public function requestAccess(Request $request, User $user): JsonResponse
    {
        $requester = $request->user();

        if ($requester->id === $user->id) {
            return response()->json(['success' => false, 'message' => 'You cannot request access from yourself.'], 422);
        }

        if (!$this->isMutualMatch($requester->id, $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Photo access can only be requested after both users like each other.',
            ], 403);
        }

        if (
            (bool) ($user->profile_photo_visible ?? true)
            && (bool) ($user->additional_photos_visible ?? true)
        ) {
            return response()->json([
                'success' => 200,
                'message' => 'This user’s photos are already visible.',
                'status' => 'not_required',
            ]);
        }

        $accessRequest = PhotoAccessRequest::query()->firstOrNew([
            'requester_id' => $requester->id,
            'owner_id' => $user->id,
        ]);

        if ($accessRequest->exists && $accessRequest->status === 'approved') {
            return response()->json([
                'success' => 200,
                'message' => 'Photo access is already approved.',
                'request_id' => $accessRequest->id,
                'status' => 'approved',
            ]);
        }

        if ($accessRequest->exists && $accessRequest->status === 'pending') {
            return response()->json([
                'success' => 200,
                'message' => 'Photo access request is already pending.',
                'request_id' => $accessRequest->id,
                'status' => 'pending',
            ]);
        }

        $accessRequest->fill([
            'status' => 'pending',
            'responded_at' => null,
        ])->save();

        UserNotification::create([
            'user_id' => $user->id,
            'title' => 'Photo Access Request',
            'message' => "{$requester->name} requested permission to view your hidden photos.",
        ]);

        return response()->json([
            'success' => 200,
            'message' => 'Photo access request sent.',
            'request_id' => $accessRequest->id,
            'status' => 'pending',
        ]);
    }

    public function respond(Request $request, PhotoAccessRequest $photoAccessRequest): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject'])],
        ]);

        if ($photoAccessRequest->owner_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'You cannot respond to this request.'], 403);
        }

        $status = $data['action'] === 'approve' ? 'approved' : 'rejected';
        $photoAccessRequest->update([
            'status' => $status,
            'responded_at' => now(),
        ]);

        PhotoAccessRequest::clearApprovedAccessCache($photoAccessRequest->requester_id);

        UserNotification::create([
            'user_id' => $photoAccessRequest->requester_id,
            'title' => 'Photo Access ' . ucfirst($status),
            'message' => $status === 'approved'
                ? "{$request->user()->name} allowed you to view their hidden photos."
                : "{$request->user()->name} declined your photo access request.",
        ]);

        return response()->json([
            'success' => 200,
            'message' => "Photo access request {$status}.",
            'request_id' => $photoAccessRequest->id,
            'status' => $status,
        ]);
    }

    private function isMutualMatch(int $firstUserId, int $secondUserId): bool
    {
        $directions = ProfileInterest::query()
            ->where('action', 'interest')
            ->where(function ($query) use ($firstUserId, $secondUserId) {
                $query->where(function ($q) use ($firstUserId, $secondUserId) {
                    $q->where('from_user_id', $firstUserId)->where('to_user_id', $secondUserId);
                })->orWhere(function ($q) use ($firstUserId, $secondUserId) {
                    $q->where('from_user_id', $secondUserId)->where('to_user_id', $firstUserId);
                });
            })
            ->count();

        return $directions === 2;
    }
}
