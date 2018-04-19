<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 4/19/2018
 * Time: 9:08 AM
 */

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DesktopController extends Controller
{

    public function login(Request $request)
    {
        $this->validate($request, [
            'merchant_id' => 'bail|required|exists:users,merchant_id',
            'password' => 'bail|required|min:6'
        ]);

        $user = DB::table('users')->where('merchant_id', $request->input('merchant_id'))->first();

        if ($user <> null) {

            if (Hash::check($request->input('password'), $user->password)) {

                $api_user = DB::table('api_users')->where('user_name', $user->apiuser)->first();

                if ($api_user <> null) {

                    $pin = ( $user->pin ) ? 0 : 1;

                    return response([
                        'status' => 'success',
                        'code' => '000',
                        'api_key' => $api_user->user_name,
                        'api_user' => $api_user->api_key,
                        'merchant_id' => $user->merchant_id,
                        'set_pin' => $pin
                    ], 200,['Content-Type: application/json']);

                } else {
                    return response([
                        'status' => 'failed',
                        'code' => '401',
                        'reason' => 'API credentials not found'
                    ], 400);
                }

            } else {
                return response([
                    'status' => 'failed',
                    'code' => '401',
                    'reason' => 'credentials mismatch'
                ], 400);
            }

        } else {
            return response([
                'status' => 'failed',
                'code' => '401',
                'reason' => 'user not found'
            ], 400);
        }
    }

    public function verifyPin(Request $request)
    {
        $this->validate($request, [
            'merchant_id' => 'bail|required|exists:users,merchant_id',
            'password' => 'bail|required|min:6'
        ]);

        $user = DB::table('users')->where('merchant_id', $request->input('merchant_id'))->first();

        if ( $user <> null ) {

            if ( Hash::check($request->input('pin'), $user->pin) ){

                return response([
                    'status' => 'success',
                    'code' => '000',
                    'reason' => 'pin matched'
                ], 200);

            } else {

                return response([
                    'status' => 'failed',
                    'code' => '403',
                    'reason' => 'wrong pin'
                ], 200);

            }
        } else {

            return response([
                'status' => 'failed',
                'code' => '401',
                'reason' => 'user not found'
            ], 400);

        }
    }

    public function setPin(Request $request)
    {
        $this->validate($request, [
            'merchant_id' => 'bail|required|exists:users,merchant_id',
            'pin'   =>  'bail|required|min:4'
        ]);

        $user = DB::table('users')->where('merchant_id', $request->input('merchant_id'))->first();

        if ($user <> null) {

            $user->pin = Hash::make($request->input('pin'));

            if ($user->save()) {

                return response([
                    'status' => 'success',
                    'code' => '000',
                    'reason' => 'pin created'
                ], 200);

            } else {

                return response([
                    'status' => 'failed',
                    'code' => '500',
                    'reason' => 'pin creation failed'
                ], 200);

            }
        } else {

            return response([
                'status' => 'failed',
                'code' => '401',
                'reason' => 'user not found'
            ], 400);

        }
    }

    public function transfer(Request $request)
    {
        return 'transfer';
    }

    public function payment(Request $request)
    {
        return 'payment';
    }

    public function transactions(Request $request)
    {
        return 'transactions';
    }

}