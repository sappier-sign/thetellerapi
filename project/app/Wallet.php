<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 04/08/2017
 * Time: 10:10 AM
 */

namespace App;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Wallet extends Model
{
    protected $table = 'ttm_wallets';
    protected $hidden = [
        'id',
        'pass_code',
        'created_at',
        'updated_at'
    ];

    public function __construct()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function index()
    {
        return Wallet::all();
    }

    /**
     * @param $data
     * @param $key
     * @return string
     */
    public static function encryptData($data, $key)
    {
        return base64_encode(openssl_encrypt(json_encode($data), 'AES128', $key, true, substr($key, 0, 16)));
    }

    /**
     * @param $data
     * @param $key
     * @return mixed
     */
    public static function decryptData($data, $key)
    {
        $key = substr($key, 0, 32);
        return json_decode(openssl_decrypt(base64_decode($data), 'AES128', $key, true, substr($key, 0, 16)), true);
    }

    /**
     * @param array $new_wallet
     * @return array
     */
    public static function store(Array $new_wallet)
    {
        $wallet = new Wallet();
        $wallet->merchant_id    =   $new_wallet['merchant_id'];
        $wallet->user_id        =   $new_wallet['user_id'];
        $wallet->pass_code      =   $new_wallet['pass_code'];
        $wallet->details        =   $new_wallet['details'];
        $wallet->wallet_id      =   uniqid();

        if ($wallet->save()){
            return [
                'status'    => 'success',
                'code'      => 100,
                'wallet_id' => $wallet->wallet_id
            ];

        } else {
            return [
                'status'    => 'error',
                'code'      => 909,
                'reason'    => "Something went wrong, we could not save your details. Please try again later!"
            ];
        }
    }

    /**
     * @param $merchant_id
     * @param $user_id
     * @return mixed
     */
    public static function show($merchant_id, $user_id)
    {
        $wallets = Wallet::where('merchant_id', $merchant_id)->where('user_id', $user_id)->get();

        foreach ($wallets as $wallet) {
            $details = self::decryptData($wallet->details, $wallet->pass_code);

            if ($details['account_issuer'] === 'MAS' || $details['account_issuer'] === 'VIS'){

                $account_number = $details['account_number'];
                $details['account_number'] = substr($account_number, 0, 6).'******'.substr($account_number, -4);
            }

            $wallet->details = $details;
        }

        return $wallets;
    }

    /**
     * @param $merchant_id
     * @param $user_id
     * @param $id
     * @return array
     */
    public static function deleteWallet($merchant_id, $user_id, $id)
    {
        $wallet = Wallet::where('merchant_id', $merchant_id)->where('user_id', $user_id)->where('wallet_id', $id)->first();
        if ($wallet){
            if ($wallet->delete()){
                return [
                    'status'    => 'success',
                    'code'      => 100,
                    'reason'    => 'Wallet has been deleted!',
                    'wallet_id' =>  $id
                ];
            } else {
                return [
                    'status'    => 'error',
                    'code'      => 909,
                    'reason'    => "Something went wrong. The specified wallet could not be deleted at this time!"
                ];
            }

        } else {
            return [
                'status'    => 'error',
                'code'      => 909,
                'reason'    => "The specific wallet does not exist!"
            ];
        }
    }

    /**
     * @param $merchant_id
     * @param $wallet_id
     * @param $transaction_id
     * @param $pass_code
     * @param $processing_code
     * @param $amount
     * @param $desc
     * @param null $cvv
     * @param null $response_url
     * @return array|bool|mixed|string
     */
    public static function pay($merchant_id, $wallet_id, $transaction_id, $pass_code, $processing_code, $amount, $desc, $cvv = null, $response_url = null){
        $getMerchantAndWallet   =   self::getMerchantAndWallet($merchant_id, $wallet_id);
        $isInvalidPassCode      =   self::validatePassCode($getMerchantAndWallet[1], $pass_code);

        if ($isInvalidPassCode){ return $isInvalidPassCode; }

        $details    =   self::decryptData($getMerchantAndWallet[1]->details, $getMerchantAndWallet[1]->pass_code);

        $body = [
            'processing_code'   =>  $processing_code,
            'amount'            =>  $amount,
            'transaction_id'    =>  $transaction_id,
            'merchant_id'       =>  $merchant_id,
            'r-switch'          =>  $details['account_issuer'],
            'subscriber_number' =>  $details['account_number'],
            'desc'              =>  $desc
        ];

        $body = self::isValidDebitCard($details, ['MAS', 'VIS'], $body, $pass_code, $response_url, $cvv);

        if (isset($body['status'])){ return $body; }
        return self::sendRequest($getMerchantAndWallet[0], $body);
    }

    /**
     * @param $merchant_id
     * @param $wallet_id
     * @param $transaction_id
     * @param $pass_code
     * @param $processing_code
     * @param $amount
     * @param $desc
     * @param null $cvv
     * @param null $response_url
     * @param $account_issuer
     * @param $account_number
     * @return array|bool|mixed|string
     */
    public static function transfer($merchant_id, $wallet_id, $transaction_id, $pass_code, $processing_code, $amount, $desc, $cvv = null, $response_url = null, $account_issuer, $account_number)
    {
        $getMerchantAndWallet   =   self::getMerchantAndWallet($merchant_id, $wallet_id);
        $isInvalidPassCode      =   self::validatePassCode($getMerchantAndWallet[1], $pass_code);

        if ($isInvalidPassCode){ return $isInvalidPassCode; }

        $details    =   self::decryptData($getMerchantAndWallet[1]->details, $getMerchantAndWallet[1]->pass_code);

        $body = [
            'processing_code'   =>  $processing_code,
            'amount'            =>  $amount,
            'transaction_id'    =>  $transaction_id,
            'merchant_id'       =>  $merchant_id,
            'r-switch'          =>  $details['account_issuer'],
            'subscriber_number' =>  $details['account_number'],
            'desc'              =>  $desc,
            'account_issuer'    =>  $account_issuer,
            'account_number'    =>  $account_number
        ];

        $body = self::isValidDebitCard($details, ['MAS', 'VIS'], $body, $pass_code, $response_url, $cvv);

        if (isset($body['status'])){ return $body; }
        return self::sendRequest($getMerchantAndWallet[0], $body);
    }

    /**
     * @param $merchant_id
     * @param $wallet_id
     * @return array
     */
    public static function getMerchantAndWallet($merchant_id, $wallet_id)
    {
        $merchant  =   Merchant::where('merchant_id', $merchant_id)->first();
        if (isset($merchant->id)){
            $wallet = Wallet::where('wallet_id', $wallet_id)->first();
            if (isset($wallet->wallet_id)){
                return [$merchant, $wallet];
            } else {
                return ['status' => 'error', 'code' => 999, 'description' => 'Wallet does not exist'];
            }
        } else {
            return ['status' => 'error', 'code' => 999, 'description' => 'Merchant does not exist'];
        }
    }

    /**
     * @param $wallet
     * @param $pass_code
     * @return array|bool
     */
    public static function validatePassCode($wallet, $pass_code)
    {
        if (substr($wallet->pass_code, 0, 32) <> $pass_code){
            return [
                'status'    =>  'failed',
                'code'      =>  909,
                'reason'    =>  'Wrong pass code!'
            ];
        }
        return false;
    }

    /**
     * @param array $details
     * @param array $cards
     * @param array $body
     * @param $pass_code
     * @param $response_url
     * @param $cvv
     * @return array
     */
    public static function isValidDebitCard(Array $details, Array $cards, Array $body, $pass_code, $response_url, $cvv)
    {
        if (in_array($details['account_issuer'], $cards)){
            $body['pan']        =   $details['account_number'];
            $expiry             =   explode('/', $details['expiry']);
            $body['exp_month']  =   $expiry[0];
            $body['exp_year']   =   $expiry[1];
            $body['3d_url_response']    =   $response_url;

            $key = substr($pass_code, 0, 32);
            unset($body['subscriber_number']);

            $body['cvv']        =  str_replace('"', '', openssl_decrypt(base64_decode($cvv), 'AES128', $key, true, substr($key, 0, 16)));

            if ($body['cvv'] == false){
                return [
                    'status'    =>  'failed',
                    'code'      =>  909,
                    'reason'    =>  'Decryption failed. Please check cvv encryption and try again!'
                ];
            } else {
                return $body;
            }
        } else {
            return $body;
        }
    }

    /**
     * @param $merchant
     * @param $body
     * @return mixed|string
     */
    public static function sendRequest($merchant, $body)
    {
        $user       =   User::where('user_name', $merchant->apiuser)->first();
        $headers    =   [
            'Content-Type: application/json',
            'Authorization: Basic '.base64_encode($user->user_name.":".$user->api_key)
        ];

        $curl = curl_init('https://api.theteller.net/v1.1/transaction/process');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if (curl_error($curl)){
            return curl_error($curl);
        } else {
            return json_decode(curl_exec($curl), true);
        }
    }

    public static function updateWallet($request, $merchant_id, $user_id, $account_number)
    {
        $wallet = Wallet::where('merchant_id', $merchant_id)->where('user_id', $user_id)->where('account_number', $account_number)->first();
        $wallet->holder_name = ucwords(strtolower($request['holder_name']));
        $wallet->expiration     =   $request['expiration'];
    }
}