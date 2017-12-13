<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 07/08/2017
 * Time: 4:01 PM
 */

namespace App\Http\Controllers;


use App\Merchant;
use App\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    public function index()
    {

    }

    /**
     * @param Request $request
     * @return array
     */
    public function create(Request $request)
    {
        $this->validate($request, [
            'merchant_id'       =>  'bail|required|size:12',
            'user_id'           =>  'bail|required|size:12',
            'pass_code'         =>  'bail|required|size:32',
            'details'           =>  'bail|required'
        ]);

        if (count(Merchant::where('merchant_id', $request->input('merchant_id'))->first()) <> 1){
            return [
                'status'    => 'error',
                'code'      => 909,
                'reason'    => "unknown merchant id!"
            ];
        }

        $wallet = [];
        $wallet['merchant_id']      =   $request->input('merchant_id');
        $wallet['user_id']          =   $request->input('user_id');
        $wallet['details']          =   $request->input('details');
        $wallet['pass_code']        =   $request->input('pass_code').uniqid();

        $decrypted = openssl_decrypt(base64_decode($wallet['details']), 'AES128', $wallet['pass_code'], true, substr($wallet['pass_code'], 0, 16));
        if ($decrypted === false){
            return [
                'status'    => 'error',
                'code'      => 909,
                'reason'    => 'Unknown encryption method. Please make sure you pass the right pass code and encrypted data with base 64 encoding!'
            ];
        } else {
            $decrypt = json_decode($decrypted, true);
            $cards = ['MAS', 'VIS'];

            if (!isset($decrypt['account_number']) || $decrypt['account_number'] === ''){
                return [
                    'status'    => 'error',
                    'code'      => 909,
                    'reason'    => 'account number is required!'
                ];
            } elseif (!isset($decrypt['account_issuer']) || $decrypt['account_issuer'] === ''){
                return [
                    'status'    => 'error',
                    'code'      => 909,
                    'reason'    => 'account issuer is required!'
                ];
            } elseif (!isset($decrypt['holder']) || $decrypt['holder'] === ''){
                return [
                    'status'    => 'error',
                    'code'      => 909,
                    'reason'    => 'holder field is required'
                ];
            } elseif (in_array($decrypt['account_issuer'], $cards) && ($decrypt['expiry'] === '' || !isset($decrypt['expiry']))){
                return [
                    'status'    => 'error',
                    'code'      => 909,
                    'reason'    => 'Expiry is required if account_issuer is VIS or MAS'
                ];
            }
        }

        return Wallet::store($wallet);

    }

    /**
     * @param $merchant_id
     * @param $user_id
     * @return mixed
     */
    public function show($merchant_id, $user_id)
    {
        if (Merchant::where('merchant_id', $merchant_id)->first()->id){
            return Wallet::show($merchant_id, $user_id);
        } else {
            return [
                "status"        =>  "Bad request",
                "code"          =>  999,
                "description"   =>  "Merchant ID is not set!"
            ];
        }

    }

    /**
     * @param Request $request
     * @return array|bool|mixed|string
     */
    public function pay(Request $request)
    {
        $this->validate($request, [
            'merchant_id'       =>  'bail|required|size:12',
            'processing_code'   =>  'bail|required|digits:6',
            'wallet_id'         =>  'bail|required|size:13',
            'amount'            =>  'bail|required|min:3',
            'desc'              =>  'bail|required|min:6',
            'transaction_id'    =>  'bail|required|size:12',
            'pass_code'         =>  'bail|required'
        ],[
            'merchant_id.size'   =>  'Merchant id must be 12 characters long'
        ]);

        return Wallet::pay($request->input('merchant_id'), $request->input('wallet_id'), $request->input('transaction_id'), $request->input('pass_code'), $request->input('processing_code'), $request->input('amount'), $request->input('desc'), $request->input('cvv', null), $request->input('response_url', null));
    }

    /**
     * @param Request $request
     * @return array|bool|mixed|string
     */
    public function transfer(Request $request)
    {
        $this->validate($request, [
            'merchant_id'       =>  'bail|required|size:12',
            'processing_code'   =>  'bail|required|digits:6',
            'wallet_id'         =>  'bail|required|size:13',
            'amount'            =>  'bail|required|min:3',
            'desc'              =>  'bail|required|min:6',
            'transaction_id'    =>  'bail|required|size:12',
            'pass_code'         =>  'bail|required|max:46',
            'account_number'    =>  'bail|required|min:10',
            'account_issuer'    =>  'bail|required|size:3'
        ]);

        return Wallet::transfer($request->input('merchant_id'), $request->input('wallet_id'), $request->input('transaction_id'), $request->input('pass_code'), $request->input('processing_code'), $request->input('amount'), $request->input('desc'), $request->input('cvv', null), $request->input('response_url', null), $request->input('account_issuer'),$request->input('account_number'));
    }

    /**
     * @param Request $request
     * @return array
     */
    public function destroy(Request $request){
        $this->validate($request, [
            'merchant_id'   =>  'bail|required|size:12',
            'user_id'       =>  'bail|required|size:12',
            'wallet_id'     =>  'bail|required|min:1'
        ]);

        $merchant_id    =   $request->input('merchant_id');
        $user_id        =   $request->input('user_id');
        $id             =   $request->input('wallet_id');
        return Wallet::deleteWallet($merchant_id, $user_id, $id);
    }
}