<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index()
    {
        try {
            $notifications = AdminNotification::with('creator')->latest()->get();
        } catch (\Throwable $e) {
            $notifications = collect();
        }

        return view('admin.notifications.index', compact('notifications'));
    }

    public function create()
    {
        return view('admin.notifications.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'send_to' => 'required|in:all,selected',
            'user_ids' => 'required_if:send_to,selected|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $userIds = $data['send_to'] === 'all'
            ? User::query()->pluck('id')->all()
            : array_values(array_unique(array_map('intval', $data['user_ids'] ?? [])));

        if ($data['send_to'] === 'selected' && empty($userIds)) {
            return back()->withInput()->with([
                'message' => 'Please select at least one user.',
                'alert-type' => 'error',
            ]);
        }

        if ($data['send_to'] === 'all' && empty($userIds)) {
            return back()->withInput()->with([
                'message' => 'No users found to send notification.',
                'alert-type' => 'error',
            ]);
        }

        DB::transaction(function () use ($data, $userIds) {
            $adminNotification = AdminNotification::create([
                'title' => $data['title'],
                'message' => $data['message'],
                'send_to' => $data['send_to'],
                'recipient_count' => count($userIds),
                'recipient_user_ids' => $data['send_to'] === 'selected' ? $userIds : null,
                'created_by' => Auth::guard('admin')->id(),
            ]);

            $now = now();
            $rows = [];

            foreach ($userIds as $userId) {
                $rows[] = [
                    'admin_notification_id' => $adminNotification->id,
                    'user_id' => $userId,
                    'title' => $data['title'],
                    'message' => $data['message'],
                    'is_read' => false,
                    'read_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                UserNotification::insert($chunk);
            }
        });

        return redirect()->route('admin.notifications.index')->with([
            'message' => 'Notification sent successfully.',
            'alert-type' => 'success',
        ]);
    }

    public function show(AdminNotification $notification)
    {
        $notification->load(['creator', 'userNotifications.user:id,name,email']);

        return view('admin.notifications.show', compact('notification'));
    }

    public function destroy(AdminNotification $notification)
    {
        // Only remove from admin table. User notifications stay until user clears them.
        $notification->userNotifications()->update(['admin_notification_id' => null]);
        $notification->delete();

        return redirect()->route('admin.notifications.index')->with([
            'message' => 'Notification deleted from admin panel.',
            'alert-type' => 'success',
        ]);
    }

    public function searchUsers(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $users = User::query()
            ->where(function ($query) use ($q) {
                $query->where('email', 'like', '%' . $q . '%')
                    ->orWhere('name', 'like', '%' . $q . '%');
            })
            ->orderBy('email')
            ->limit(20)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'results' => $users->map(fn (User $user) => [
                'id' => $user->id,
                'text' => $user->email . ' (' . $user->name . ')',
            ])->values(),
        ]);
    }
}
