<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 4/19/2018
 * Time: 9:08 AM
 */

namespace App\Http\Controllers;

use App\Jobs\MerchantLoginCommand;
use App\Merchant;
use Firebase\JWT\JWT;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\UnauthorizedException;

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

//        return JWT::encode($payload, env('JWT_SECRET'));
    }

    public function login()
    {
        $this->validate($this->request, [
            'merchant_id' => 'bail|required|exists:users,merchant_id',
            'password' => 'bail|required|min:6'
        ]);

        try {
            $job = new MerchantLoginCommand($this->request);
            dispatch($job);

            return response([
                'status' => 'success',
                'code' => 1000,
                'set_pin' => 0,
                'token' => null
            ], 200, ['Content-Type: application/json']);

        } catch (UnauthorizedException $exception) {
            return response([
                'status' => 'failed',
                'code' => '401',
                'reason' => $exception->getMessage()
            ], 400);
        } catch (ModelNotFoundException $exception) {
            return response([
                'status' => 'failed',
                'code' => '401',
                'reason' => $exception->getMessage()
            ], 400);
        } catch (\Exception $exception) {
            return response([
                'status' => 'failed',
                'code' => '401',
                'reason' => 'something very bad happened. Kindly try soon!'
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