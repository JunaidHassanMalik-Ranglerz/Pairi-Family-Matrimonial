<?php

namespace App\Http\Controllers\MarriageBureau;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LookupOption;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        $bureauId = Auth::guard('marriage_bureau')->id();
        $users = User::where('marriage_bureau_id', $bureauId)->latest()->get();
        return view('marriage_bureau.users.index', compact('users'));
    }

    public function create()
    {
        return view('marriage_bureau.users.create', ['lookups' => $this->formLookups()]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);

        $validated['password'] = Hash::make(Str::random(40));
        $validated['marriage_bureau_id'] = Auth::guard('marriage_bureau')->id();
        $validated['can_login'] = false;

        // Profiles created by a marriage bureau are display-only: they must never
        // be able to log in through the app, but should behave like a fully
        // completed & verified profile so they surface in matches/search.
        $validated['status'] = 'active';
        $validated['is_verified'] = true;
        $validated['email_verified_at'] = now();
        $validated['profile_completed'] = true;
        $validated['profile_step'] = 8;
        $validated['terms_accepted_at'] = now();

        $user = User::create($validated);

        if ($request->hasFile('photos')) {
            $this->storePhotos($request, $user);
        }

        return $this->redirectAfterSave($request, $user, 'User created successfully.');
    }

    public function show(User $user)
    {
        $this->authorizeBureauUser($user);

        return view('marriage_bureau.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->authorizeBureauUser($user);

        return view('marriage_bureau.users.edit', [
            'user' => $user,
            'lookups' => $this->formLookups(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeBureauUser($user);

        $validated = $this->validatedData($request, $user);

        // Keep the profile flagged as active/complete so it remains visible on the app.
        $validated['profile_completed'] = true;

        $user->update($validated);

        if ($request->hasFile('photos') || $request->filled('selected_photo')) {
            $this->storePhotos($request, $user);
        }

        return $this->redirectAfterSave($request, $user, 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorizeBureauUser($user);

        $user->delete();
        return redirect()->route('marriage-bureau.users.index')->with(['message' => 'Deleted successfully.', 'alert-type' => 'success']);
    }

    private function authorizeBureauUser(User $user): void
    {
        abort_unless($user->marriage_bureau_id === Auth::guard('marriage_bureau')->id(), 403);
    }

    private function redirectAfterSave(Request $request, User $user, string $message)
    {
        $flash = ['message' => $message, 'alert-type' => 'success'];

        if ($request->input('save_action') === 'save_next') {
            $nextTab = $this->nextTabId($request->input('active_tab', 'tab-basic'));

            return redirect()
                ->route('marriage-bureau.users.edit', $user->id)
                ->with($flash)
                ->with('active_tab', $nextTab);
        }

        return redirect()->route('marriage-bureau.users.index')->with($flash);
    }

    private function nextTabId(?string $currentTab): string
    {
        $tabs = ['tab-basic', 'tab-education', 'tab-physical', 'tab-faith', 'tab-about'];
        $index = array_search($currentTab, $tabs, true);

        if ($index === false || $index >= count($tabs) - 1) {
            return 'tab-about';
        }

        return $tabs[$index + 1];
    }

    private function validatedData(Request $request, ?User $user = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email' . ($user ? ',' . $user->id : ''),
            'phone' => 'nullable|string|max:20',
            'gender' => 'required|in:male,female',
            'birthday' => 'nullable|date|before:today',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:200',
            'marital_status' => 'nullable|string|max:50',
            'interests' => 'nullable|array',
            'interests.*' => 'string|max:255',

            'qualification' => 'nullable|string|max:100',
            'field_of_study' => 'nullable|string|max:255',
            'university' => 'nullable|string|max:255',
            'graduation_year' => 'nullable|string|max:10',

            'employment_type' => 'nullable|string|max:100',
            'job_title' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'monthly_income' => 'nullable|string|max:100',
            'residential_status' => 'nullable|string|max:100',

            'height' => 'nullable|string|max:50',
            'weight' => 'nullable|string|max:50',
            'body_type' => 'nullable|string|max:100',
            'complexion' => 'nullable|string|max:100',
            'physical_disability' => 'nullable|boolean',

            'religion' => 'nullable|string|max:100',
            'community' => 'nullable|string|max:100',
            'sect' => 'nullable|string|max:100',
            'mother_tongue' => 'nullable|string|max:100',
            'other_languages' => 'nullable|array',

            'photos' => 'nullable|array|max:10',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'selected_photo' => ['nullable', 'string', 'regex:/^(existing|new):\d+$/'],
        ];

        $validated = $request->validate($rules);

        $existingPhotoCount = count($user?->photos ?? []);
        $newPhotoCount = count($request->file('photos', []));
        if ($existingPhotoCount + $newPhotoCount > 10) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'photos' => 'A user can have a maximum of 10 profile photos.',
            ]);
        }

        unset($validated['photos'], $validated['selected_photo']);

        $validated['physical_disability'] = $request->boolean('physical_disability');

        $validated['interests'] = array_values(array_filter($request->input('interests', [])));

        $validated['other_languages'] = $request->other_languages ?? [];

        return $validated;
    }

    private function formLookups(): array
    {
        return Cache::remember('marriage_bureau_form_lookups', 600, function () {
            $types = [
                'countries',
                'marital-statuses',
                'qualifications',
                'fields-of-study',
                'employment-types',
                'incomes',
                'residences',
                'body-types',
                'complexions',
                'religions',
                'mother-tongues',
                'languages',
                'hobbies-interests',
            ];
            $lookups = [];

            foreach ($types as $type) {
                $definition = config("profile_lookups.{$type}");
                $lookups[$type] = is_array($definition)
                    ? LookupOption::fromTable($definition['table'])
                        ->newQuery()
                        ->where('status', 'active')
                        ->orderBy('name')
                        ->pluck('name')
                        ->all()
                    : [];
            }

            return $lookups;
        });
    }

    private function storePhotos(Request $request, User $user): void
    {
        $existingPhotos = array_values($user->photos ?? []);
        $newPhotos = [];

        foreach ($request->file('photos', []) as $photo) {
            $path = $photo->store('profiles/' . $user->id, 'public');
            $newPhotos[] = [
                'path' => $path,
                'is_main' => false,
            ];
        }

        $photos = array_merge($existingPhotos, $newPhotos);
        if (empty($photos)) {
            return;
        }

        $selected = $request->input('selected_photo');
        $selectedIndex = null;

        if (is_string($selected) && preg_match('/^(existing|new):(\d+)$/', $selected, $matches)) {
            $index = (int) $matches[2];
            $selectedIndex = $matches[1] === 'new'
                ? count($existingPhotos) + $index
                : $index;
        }

        if ($selectedIndex === null || !array_key_exists($selectedIndex, $photos)) {
            $currentMain = collect($photos)->search(fn ($photo) => (bool) ($photo['is_main'] ?? false));
            $selectedIndex = $currentMain === false ? 0 : (int) $currentMain;
        }

        foreach ($photos as $index => &$photo) {
            $photo['is_main'] = $index === $selectedIndex;
        }
        unset($photo);

        $user->update(['photos' => $photos]);
    }
}
