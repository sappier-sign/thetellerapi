<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 4/19/2018
 * Time: 9:08 AM
 */

namespace App\Http\Controllers;

use App\Jobs\MerchantLoginJob;
use App\Jobs\SetPinJob;
use App\Jobs\VerifyPinJob;
use App\Transaction;
use Carbon\Carbon;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use http\Exception\BadConversionException;
use http\Exception\RuntimeException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use InvalidArgumentException;
use UnexpectedValueException;

class DesktopController extends Controller
{
    private $request;
    private $merchant_id;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function setMerchantId()
    {
        $token = explode(' ', $this->request->header('Authorization'))[1];
        $this->merchant_id = get_object_vars(JWT::decode($token, env('JWT_SECRET'), ['HS512']))['sub'];
        return $this;
    }


    private function jwt($merchant_id = null)
    {
        $payload = [
            'iss' => 'theteller-api',
            'sub' => ( $merchant_id <> null )? $merchant_id : $this->merchant_id,
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
                'merchant_id' => $job->getMerchant()->merchant_id,
                'merchant_name' => $job->getMerchant()->company,
                'set_pin' => $job->isSetPin(),
                'token' => $this->jwt($job->getMerchant()->merchant_id)
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

    public function changePin()
    {
        $this->validate($this->request, [
            'old_pin' => 'required|digits:6',
            'new_pin' => 'required|digits:6'
        ]);
    }

    public function transfer()
    {
        return 'transfer';
    }

    public function payment()
    {
        return 'payment';
    }

    public function GetTransactions()
    {
        $payments = $transfers = [];

        $transactions = Transaction::where('fld_042', $this->setMerchantId()->merchant_id)->whereDate('fld_012', Carbon::now()->toDateString())->get();

        foreach ($transactions as $transaction) {

            if ( substr($transaction->fld_003, 0, 2) === '00') {
                array_push($payments, $transaction);
            } else {
                array_push($transfers, $transaction);
            }
        }

        return [
            "payments" => $payments,
            "transfers" => $transfers
        ];
    }

}