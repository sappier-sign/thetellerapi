<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (!isset(User::where(['user_name' => $_SERVER['PHP_AUTH_USER'], 'api_key' => $_SERVER['PHP_AUTH_PW']])->first()->id)){
            return response(['status' => 'Unauthorized', 'code' => 999, 'description' => 'Merchant not found. Please make sure you have your credentials set using basic Authentication!'], 401);
        }

//        if ($this->auth->guard($guard)->guest()) {
//            return response(['status' => 'Unauthorized', 'code' => 999, 'description' => 'User not found. Please make sure you have your credentials set using basic Authentication!'], 401);
//        }

        return $next($request);
    }
}
