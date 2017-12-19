<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 12/09/2017
 * Time: 2:49 PM
 */

namespace App\Http\Middleware;


use App\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AuthApiUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])){

            return response(['status' => 'Bad request', 'code' => 999, 'description' => 'Authorization parameters are not set.'], 400);

        } elseif ($request->has('merchant_id') <> true) {

            return response(['status' => 'Bad request', 'code' => 999, 'description' => 'Merchant ID is not set!'], 400);

        } elseif ($request->input('merchant_id') === '' || $request->input('merchant_id') === null) {

            return response(['status' => 'Unprocessable entity', 'code' => 999, 'description' => 'Merchant ID cannot be empty'], 422);

        }

        $user = User::where('user_name', $_SERVER['PHP_AUTH_USER'])->where('api_key', $_SERVER['PHP_AUTH_PW'])->where('status', 1)->first();
        if ($user->exists){
            if ( DB::table('users')->where('apiuser', $user->user_name)->first()->merchant_id !== $request->input('merchant_id') ) {
                return response(['status' => 'Unauthorized', 'code' => 999, 'description' => 'Merchant ID is wrong!'], 401);
            }
            return $next($request);
        }

        return response(['status' => 'Bad request', 'code' => 999, 'description' => 'Merchant not found. Please make sure you have your credentials set using basic Authentication!'], 400);

//        if (!isset($user->id)){
//
//            return response(['status' => 'Bad request', 'code' => 999, 'description' => 'Merchant not found. Please make sure you have your credentials set using basic Authentication!'], 400);
//
//        } elseif (DB::table('users')->where('apiuser', $user->user_name)->first()->merchant_id !== $request->input('merchant_id')) {
//
//            return response(['status' => 'Unauthorized', 'code' => 999, 'description' => 'Merchant ID is wrong!'], 401);
//
//        }

    }

}