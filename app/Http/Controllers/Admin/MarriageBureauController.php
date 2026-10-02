<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarriageBureau;
use App\Models\MarriageBureauSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MarriageBureauController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bureaus = MarriageBureau::latest()->get();
        return view('admin.marriage_bureaus.index', compact('bureaus'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $bureau = MarriageBureau::findOrFail($id);
        return view('admin.marriage_bureaus.show', compact('bureau'));
    }

    public function verifySubscription(string $id)
    {
        $subscription = \App\Models\MarriageBureauSubscription::findOrFail($id);
        $subscription->status = 'verified';
        $subscription->save();

        $notification = ['message' => 'Subscription verified successfully.', 'alert-type' => 'success'];
        return redirect()->back()->with($notification);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $bureau = MarriageBureau::findOrFail($id);
        $bureau->update($data);

        return redirect()->back()->with([
            'message' => 'Marriage Bureau status updated successfully.',
            'alert-type' => 'success',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $bureau = MarriageBureau::findOrFail($id);

        DB::transaction(function () use ($bureau) {
            User::where('marriage_bureau_id', $bureau->id)
                ->update(['marriage_bureau_id' => null]);
            MarriageBureauSubscription::where('marriage_bureau_id', $bureau->id)->delete();
            $bureau->delete();
        });

        return redirect()->route('admin.marriage-bureaus.index')->with([
            'message' => 'Marriage Bureau deleted successfully.',
            'alert-type' => 'success',
        ]);
    }
}
