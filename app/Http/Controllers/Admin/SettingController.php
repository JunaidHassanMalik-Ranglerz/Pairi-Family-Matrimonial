<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::pluck('value', 'key')->toArray();
        return view('admin.settings.index', compact('settings'));
    }

    public function store(Request $request)
    {
        $section = $request->input('section', 'all');

        if ($section === 'invite') {
            $data = $request->validate([
                'invite_reward_points' => 'required|numeric|min:0',
                'point_value_pkr' => 'sometimes|required|numeric|min:0',
            ]);
            $message = 'Reward points and PKR value updated successfully.';
        } elseif ($section === 'redeem') {
            $data = $request->validate([
                'redeem_vip_points' => 'required|numeric|min:0',
                'redeem_vvip_points' => 'required|numeric|min:0',
                'redeem_boost_points' => 'required|numeric|min:0',
                'redeem_boost_days' => 'required|integer|min:1',
            ]);
            $message = 'Redeem settings updated successfully.';
        } elseif ($section === 'billing') {
            $data = $request->validate([
                'annual_billing_discount_percent' => 'required|integer|min:0|max:100',
            ]);
            $message = 'Annual billing discount updated successfully.';
        } else {
            $data = $request->validate([
                'invite_reward_points' => 'required|numeric|min:0',
                'point_value_pkr' => 'sometimes|required|numeric|min:0',
                'redeem_vip_points' => 'required|numeric|min:0',
                'redeem_vvip_points' => 'required|numeric|min:0',
                'redeem_boost_points' => 'required|numeric|min:0',
                'redeem_boost_days' => 'required|integer|min:1',
                'annual_billing_discount_percent' => 'sometimes|required|integer|min:0|max:100',
            ]);
            $message = 'Settings updated successfully.';
        }

        foreach ($data as $key => $value) {
            SystemSetting::setVal($key, $value);
        }

        return redirect()->route('admin.settings.index')->with([
            'message' => $message,
            'alert-type' => 'success',
        ]);
    }
}
