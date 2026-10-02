<?php

namespace App\Http\Controllers\MarriageBureau;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\MarriageBureau;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('marriage_bureau.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $bureau = MarriageBureau::where('email', $credentials['email'])->first();
        if (
            $bureau
            && $bureau->status !== 'active'
            && Hash::check($credentials['password'], $bureau->password)
        ) {
            return back()->withErrors([
                'email' => 'Your account has been deactivated. Please contact the admin.',
            ])->onlyInput('email');
        }

        $credentials['status'] = 'active';

        if (Auth::guard('marriage_bureau')->attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended($this->postLoginRedirect())->with([
                'message' => 'Logged in successfully.',
                'alert-type' => 'success',
            ]);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegisterForm()
    {
        return view('marriage_bureau.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:marriage_bureaus',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ];

        if ($request->hasFile('image')) {
            $data['image'] = file_upload(file: $request->file('image'), path: 'uploads/marriage-bureau/');
        }

        MarriageBureau::create($data);

        // #region agent log
        file_put_contents(base_path('debug-64ce68.log'), json_encode(['sessionId'=>'64ce68','runId'=>'post-fix','hypothesisId'=>'MB1','location'=>'AuthController.php:register','message'=>'Register flash set before redirect','data'=>['flashKey'=>'message','alertType'=>'success','text'=>'Registration successful. Please login to continue.'],'timestamp'=>round(microtime(true)*1000)])."\n", FILE_APPEND);
        // #endregion

        return redirect()->route('marriage-bureau.login')->with([
            'message' => 'Registration successful. Please login to continue.',
            'alert-type' => 'success',
        ]);
    }

    private function postLoginRedirect(): string
    {
        $bureauId = Auth::guard('marriage_bureau')->id();
        $hasAccess = \App\Models\MarriageBureauSubscription::where('marriage_bureau_id', $bureauId)
            ->whereIn('status', ['verified', 'free'])
            ->exists();

        return $hasAccess
            ? route('marriage-bureau.dashboard')
            : route('marriage-bureau.subscription.index');
    }

    public function logout(Request $request)
    {
        Auth::guard('marriage_bureau')->logout();

        // Invalidate then flash on the fresh session so the toast survives redirect
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // #region agent log
        file_put_contents(base_path('debug-64ce68.log'), json_encode(['sessionId'=>'64ce68','runId'=>'post-fix','hypothesisId'=>'MB2','location'=>'AuthController.php:logout','message'=>'Logout flash set after session regenerate','data'=>['flashKey'=>'message','alertType'=>'success','text'=>'Logged out successfully.'],'timestamp'=>round(microtime(true)*1000)])."\n", FILE_APPEND);
        // #endregion

        return redirect()->route('marriage-bureau.login')->with([
            'message' => 'Logged out successfully.',
            'alert-type' => 'success',
        ]);
    }
}
