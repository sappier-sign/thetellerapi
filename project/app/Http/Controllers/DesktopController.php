<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 4/19/2018
 * Time: 9:08 AM
 */

namespace App\Http\Controllers;

use App\Jobs\MerchantLoginCommand;
use App\Jobs\MerchantLoginJob;
use App\Jobs\SetPinJob;
use App\Jobs\VerifyPinJob;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use http\Exception\BadConversionException;
use http\Exception\RuntimeException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\UnauthorizedException;
use InvalidArgumentException;
use UnexpectedValueException;

class DesktopController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }


    private function jwt()
    {
        $payload = [
            'iss' => 'theteller-api',
            'sub' => $this->request->merchant_id,
            'iat' => time(),
            'exp' => time() + 60 * 60
        ];

        return JWT::encode($payload, env('JWT_SECRET'), 'HS512');
    }

    public function login()
    {
        $this->validate($this->request, [
            'merchant_id' => 'bail|required|exists:users,merchant_id',
            'password' => 'bail|required|min:6'
        ]);

        try {
            $job = new MerchantLoginJob($this->request);
            dispatch($job);

            return response([
                'status' => 'success',
                'code' => 1000,
                'set_pin' => $job->isSetPin(),
                'token' => $this->jwt()
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
            'pin' => 'bail|required|min:6',
            'token' => 'bail|required'
        ]);

        try {

            $job = ( new VerifyPinJob($this->request) );
            $this->dispatch($job);

            return response([
                'status' => 'success',
                'code' => 1000,
                'reason' => 'pin matched',
                'token' => $this->jwt()
            ], 200);

        } catch (\Exception $exception) {
            return response([
                'status' => 'failed',
                'code' => '403',
                'reason' => $exception->getMessage()
            ], 200);
        }
    }

    public function setPin()
    {
        $this->validate($this->request, [
            'token' => 'bail|required',
            'pin'   =>  'bail|required|min:4'
        ]);

        try {

            $job = (new SetPinJob($this->request));
            dispatch($job);
            return response([
                'status' => 'success',
                'code' => 1000,
                'reason' => 'pin creation successful'
            ], 400);

        } catch (ModelNotFoundException $exception) {
            return response([
                'status' => 'failed',
                'code' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ], 400);
        } catch (BadConversionException $exception) {
            return response([
                'status' => 'error',
                'code' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ], 200);
        } catch (RuntimeException $exception) {
            return response([
                'status' => 'error',
                'code' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ], 200);
        } catch (SignatureInvalidException $exception) {
            return response([
                'status' => 'error',
                'code' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ], 200);
        } catch (ExpiredException $exception) {
            return response([
                'status' => 'error',
                'code' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ], 200);
        } catch (UnexpectedValueException $exception) {
            return response([
                'status' => 'error',
                'code' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ], 200);
        } catch (InvalidArgumentException $exception) {
            return response([
                'status' => 'error',
                'code' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ], 200);
        } catch (\Exception $exception) {
            return response([
                'status' => 'error',
                'code' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ], 200);
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