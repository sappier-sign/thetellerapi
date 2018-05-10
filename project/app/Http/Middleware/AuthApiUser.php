<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 12/09/2017
 * Time: 2:49 PM
 */

namespace App\Http\Middleware;


use App\Functions;
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


        $user = User::where('user_name', $_SERVER['PHP_AUTH_USER'])->where('api_key', $_SERVER['PHP_AUTH_PW'])->first();
        $found = $user->id ?? false;
        if ($found){
            if ($user->status){
                $merchant = DB::table('users')->where('apiuser', $user->user_name)->first();
                if ( $merchant->merchant_id !== $request->input('merchant_id') ) {
                    return response(['status' => 'Unauthorized', 'code' => 999, 'description' => 'Merchant ID is wrong!'], 401);
                }

                if (substr($request->input('processing_code'), 0, 2) === '40') {
                    $amount = Functions::toFloat($request->input('amount'));
                    $balance = Functions::toFloat($merchant->wallet_balance);

                    if ($balance < $amount) {
                        return response(['status' => 'error', 'code' => 999, 'description' => 'Insufficient funds in merchant float'], 200);
                    }
                }

                return $next($request);
            } else {
                return response(['status' => 'Unauthorized', 'code' => 998, 'description' => 'Merchant deactivated. Please contact support'], 401);
            }

        }

        return response(['status' => 'Unauthorized', 'code' => 999, 'description' => 'Merchant not found. Please make sure you have your credentials set using basic Authentication!'], 400);

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