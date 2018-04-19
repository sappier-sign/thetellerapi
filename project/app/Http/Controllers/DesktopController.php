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

                    return response([
                        'status' => 'success',
                        'code' => '000',
                        'api_key' => $api_user->user_name,
                        'api_user' => $api_user->api_key,
                        'merchant_id' => $user->merchant_id,
                        'set_pin' => 1
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
        return 'verify pin';
    }

    public function setPin(Request $request)
    {
        return 'set pin';
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