<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 4/24/2018
 * Time: 1:48 PM
 */

namespace App\Http\Middleware;
use App\User;
use Closure;
use Illuminate\Http\Request;

class AuthSettler
{
    public function handle(Request $request, Closure $next)
    {
        $user = User::where('user_name', 'testuser')->first();
        if ($request->getUser() === $user->user_name && $request->getPassword() === $user->api_key) {
            return $next($request);
        }
        return response([
            'status' => 'Unauthorized',
            'code' => 401,
            'reason' => 'user not authorized'
        ], 401);
    }
}