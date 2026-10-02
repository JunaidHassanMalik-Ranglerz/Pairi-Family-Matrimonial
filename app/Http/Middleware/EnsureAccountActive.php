<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->status === 'active') {
            return $next($request);
        }

        $isReactivateRequest = $request->isMethod('post')
            && str_contains($request->path(), 'account/deactivate')
            && $request->input('action') === 'activate';

        if ($isReactivateRequest) {
            return $next($request);
        }

        $this->revokeTokens($user);

        return response()->json([
            'success' => false,
            'message' => 'Your account is inactive.',
            'account_inactive' => true,
            'reactivation_requested' => (bool) $user->reactivation_requested_at,
        ], 401);
    }

    private function revokeTokens($user): void
    {
        PersonalAccessToken::query()
            ->where('tokenable_id', $user->id)
            ->where('tokenable_type', $user->getMorphClass())
            ->delete();
    }
}
