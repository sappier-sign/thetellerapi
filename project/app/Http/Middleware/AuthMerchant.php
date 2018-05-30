<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 4/23/2018
 * Time: 2:26 PM
 */

namespace App\Http\Middleware;

use App\Merchant;
use Closure;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;

class AuthMerchant
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->hasHeader('Authorization')) {

            if ($request->header('Authorization') <> '') {

                $bearer_token = explode(' ', $request->header('Authorization'));

                if (count($bearer_token) === 2) {

                    if ($bearer_token[0] === 'Bearer') {

                        try {

                            $merchant_id = get_object_vars(JWT::decode($bearer_token[1], env('JWT_SECRET'), ['HS512']))['sub'];

                            if (Merchant::where('merchant_id', $merchant_id)->count()) {

                                return $next($request);

                            } else {

                                return response()->json([
                                    'status' => 'not authorized',
                                    'code' => 6000,
                                    'reason' => 'Merchant does not exist'
                                ], 401);

                            }

                        } catch (ExpiredException $exception) {

                            return response()->json([
                                'status' => 'not authorized',
                                'code' => 6000,
                                'reason' => 'Session expired'
                            ], 401);
                        }


                    } else {
                        return response()->json([
                            'status' => 'not authorized',
                            'code' => 401,
                            'reason' => 'Authorization must be of type "Bearer", ' . $bearer_token[0] . ' gotten'
                        ], 401);
                    }
                } else {

                    return response()->json([
                        'status' => 'not authorized',
                        'code' => 401,
                        'reason' => 'Authorization value malformed'
                    ], 401);

                }
            } else {

                return response()->json([
                    'status' => 'not authorized',
                    'code' => 401,
                    'reason' => 'Authorization header is empty'
                ], 401);

            }
        } else {
            return response()->json([
                'status' => 'not authorized',
                'code' => 401,
                'reason' => 'Authorization header is missing'
            ], 401);
        }
    }

}