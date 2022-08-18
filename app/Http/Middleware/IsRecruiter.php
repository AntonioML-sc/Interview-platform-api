<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsRecruiter
{

    // go ahead only if user is type recruiter
    public function handle(Request $request, Closure $next)
    {
        $userId = auth()->user()->id;

        $user = User::find($userId);

        $userRole = $user->role->name;

        if ($userRole != 'recruiter') {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'route not found'
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        return $next($request);
    }
}
