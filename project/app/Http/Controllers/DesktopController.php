<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 4/19/2018
 * Time: 9:08 AM
 */

namespace App\Http\Controllers;

use App\Merchant;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DesktopController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    private function jwt(Merchant $merchant)
    {
        $payload = [
            'iss' => 'theteller-api',
            'sub' => $merchant->id,
            'iat' => time(),
            'exp' => time() + 60 * 60
        ];

        return JWT::encode($payload, env('JWT_SECRET'));
    }

    public function login()
    {
        $this->validate($this->request, [
            'merchant_id' => 'bail|required|exists:users,merchant_id',
            'password' => 'bail|required|min:6'
        ]);

        $user = Merchant::where('merchant_id', $this->request->input('merchant_id'))->first();

        if ($user <> null) {

            if (Hash::check($this->request->input('password'), $user->password)) {

                $api_user = DB::table('api_users')->where('user_name', $user->apiuser)->first();

                if ($api_user <> null) {

                    $pin = ( $user->pin ) ? 0 : 1;

                    return response([
                        'status' => 'success',
                        'code' => '000',
                        'api_key' => $api_user->user_name,
                        'api_user' => $api_user->api_key,
                        'merchant_id' => $user->merchant_id,
                        'set_pin' => $pin,
                        'token' => $this->jwt($user)
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

    public function verifyPin()
    {
        $this->validate($this->request, [
            'merchant_id' => 'bail|required|exists:users,merchant_id',
            'password' => 'bail|required|min:6'
        ]);

        $user = DB::table('users')->where('merchant_id', $this->request->input('merchant_id'))->first();

        if ( $user <> null ) {

            if ( Hash::check($this->request->input('pin'), $user->pin) ){

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

    public function setPin()
    {
        $this->validate($this->request, [
            'merchant_id' => 'bail|required|exists:users,merchant_id',
            'pin'   =>  'bail|required|min:4'
        ]);

        $user = DB::table('users')->where('merchant_id', $this->request->input('merchant_id'))->first();

        if ($user <> null) {

            $user->pin = Hash::make($this->request->input('pin'));

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

    public function transfer()
    {
        return 'transfer';
    }

    public function payment()
    {
        return 'payment';
    }

    public function transactions()
    {
        return 'transactions';
    }

}