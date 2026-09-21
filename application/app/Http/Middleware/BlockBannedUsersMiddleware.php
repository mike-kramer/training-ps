<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class BlockBannedUsersMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()->status === User::STATUS_BANNED) {
            return response(status: 403);
        }
        return $next($request);
    }
}
