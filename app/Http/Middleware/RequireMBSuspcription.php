<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\MarriageBureauSubscription;

class RequireMBSuspcription
{
    public function handle(Request $request, Closure $next): Response
    {
        $bureau = Auth::guard('marriage_bureau')->user();
        if (!$bureau) {
            return redirect()->route('marriage-bureau.login');
        }

        if ($bureau->status !== 'active') {
            Auth::guard('marriage_bureau')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('marriage-bureau.login')->withErrors([
                'email' => 'Your account has been deactivated. Please contact the admin.',
            ]);
        }

        $activeSub = MarriageBureauSubscription::where('marriage_bureau_id', $bureau->id)
            ->where('status', 'verified')
            ->first();

        if (!$activeSub) {
            return redirect()->route('marriage-bureau.subscription.index')->with(['message' => 'You must have an active premium subscription to access this feature.', 'alert-type' => 'error']);
        }

        return $next($request);
    }
}
