<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 20/07/2017
 * Time: 10:41 PM
 */

namespace App\Http\Controllers;

use App\Functions;
use App\Merchant;
use App\Payswitch;
use App\Transaction;
use App\User;
use App\Wallet;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\If_;

class TransactionController extends Controller
{
    public function __construct()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    public function index()
    {

    }

    public function create(Request $request)
    {
//        Functions::logRequest($request);
        $this->validate($request, [
            'merchant_id'       => 'bail|required|size:12|alpha_dash',
            'processing_code'   => 'bail|required|digits:6|in:000000,400000,400010,400020,400100,400110,400120,400200,400210,400220,404000,404010,404020,000200',
            'transaction_id'    => 'bail|required|digits:12',
            'desc'              => 'bail|required|min:10|max:100',
            'amount'            => 'bail|required|digits:12',

            'pass_code'         => 'bail|size:32|required_if:r-switch,FLT',
            'r-switch'          => 'bail|required|in:TGO,MTN,ATL,MAS,VIS,VDF,FLT|size:3',
            'voucher_code'      => 'bail|required_if:r-switch,VDF',
            'subscriber_number' => 'bail|required_if:processing_code,000200|required_if:processing_code,400200|min:10|max:12',
            '3d_url_response'   => 'bail|required_if:processing_code,000000|required_if:processing_code,000100|required_if:processing_code,400000|required_if:processing_code,400100|required_if:processing_code,400110|required_if:processing_code,400120|url',
            'cvv'               => 'bail|required_if:processing_code,000000|required_if:processing_code,000100|required_if:processing_code,400000|required_if:processing_code,400100|required_if:processing_code,400110|required_if:processing_code,400120|min:3|max:4',
            'pan'               => 'bail|required_if:processing_code,000000|required_if:processing_code,000100|required_if:processing_code,400000|required_if:processing_code,400100|required_if:processing_code,400110|required_if:processing_code,400120|digits_between:16,20',
            'exp_month'         => 'bail|required_if:processing_code,000000|required_if:processing_code,000100|required_if:processing_code,400000|required_if:processing_code,400100|required_if:processing_code,400110|required_if:processing_code,400120|digits:2',
            'exp_year'          => 'bail|required_if:processing_code,000000|required_if:processing_code,000100|required_if:processing_code,400000|required_if:processing_code,400100|required_if:processing_code,400110|required_if:processing_code,400120|digits:2',
            'account_issuer'    => 'bail|required_if:processing_code,400000|required_if:processing_code,400100|required_if:processing_code,400200|required_if:processing_code,400110|required_if:processing_code,400120|required_if:processing_code,400010|required_if:processing_code,400020',
            'account_number'    => 'bail|required_if:processing_code,400000|required_if:processing_code,400100|required_if:processing_code,400200|required_if:processing_code,400110|required_if:processing_code,400120|required_if:processing_code,400010|required_if:processing_code,400020'
        ], [
            'processing_code.in'=> 'The selected transaction type is invalid. Please refer to the documentation.',
            'amount.digits'      => 'Format error: Amount must be 12 digits. Eg 000000000100 for GHS 1.00',
            'merchant_id.size'  => 'Merchant id must be 12 characters long'
        ]);

        if (User::where('user_name', $request->header('php-auth-user'))->count() < 1){
            return [
                'status'        => 'failed',
                'code'          => 979,
                'description'   => 'Error: Api user not found, please check authorization headers'
            ];
        }

        $actions                = explode(' ', User::where('user_name', $request->header('php-auth-user'))->first()->actions);
        $purchase_with_card     = ['000000', '000100'];
        $deposit_transactions   = ['400000', '400010', '400100', '400110', '400120', '400200', '400210', '400220', '404000', '404010', '404020'];
        $transaction_types      = ['000000', '000100', '400000', '400100', '400110', '400120', '404000', '404010', '404020'];

        if (is_null($request->header('Content-Type')) || $request->header('Content-Type') <> 'application/json') {
            return [
                'status'        => 'failed',
                'code'          => 979,
                'description'   => 'Error: Content type is not set or is not application/json'
            ];
        } elseif (in_array($request->input('processing_code'), ['000000', '000100', '000200']) && !in_array('purchase', $actions)) {
            return [
                'status'        => 'failed',
                'code'          => 979,
                'description'   => 'Error: You are not allowed to perform debit transactions!'
            ];
        } elseif ($request->input('processing_code') === '21' && !in_array('credit', $actions)) {
            return [
                'status'        => 'failed',
                'code'          => 979,
                'description'   => 'Error: You are not allowed to perform credit transactions!'
            ];
        } elseif (in_array($request->input('processing_code'), ['400000', '400100', '400110', '400120', '400200', '400210', '400220']) && !in_array('transfer', $actions)) {
            return [
                'status'        => 'failed',
                'code'          => 979,
                'description'   => 'Error: You are not allowed to transfer funds!'
            ];
        } elseif (count($request->all()) > 17 && in_array($request->input('processing_code'), $purchase_with_card)) {
            return [
                'status'        => 'failed',
                'code'          => 979,
                'description'   => 'Error: Expected number of fields for card purchase transactions. Fields should not be more than 15, '.count($request->all()).' given.'
            ];
        } elseif (count($request->all()) > 12 && $request->input('processing_code') === '000200') {
            return [
                'status'        => 'failed',
                'code'          => 979,
                'description'   => 'Error: Expected number of fields for mobile wallet purchase transactions should not be more than 12, '.count($request->all()).' given.'
            ];
        } elseif (!in_array($request->input('r-switch'), ['VIS', 'MAS']) && in_array($request->input('processing_code'), $purchase_with_card)) {
            return [
                'status'        => 'failed',
                'code'          => 979,
                'description'   => 'Format error: r-switch must match transaction type. '.$request->input('r-switch').' is not a card.'
            ];
        } elseif (!in_array($request->input('r-switch'), ['MTN', 'TGO', 'ATL', 'VDF']) && $request->input('processing_code') === '000200') {
            return [
                'status'        => 'failed',
                'code'          => 979,
                'description'   => 'Format error: r-switch must match transaction type. '.$request->input('r-switch').' is not a mobile wallet.'
            ];
        }

        # Start Processing The Transaction

        $transaction = [];

        $transaction['fld_002']         =   $request->input('subscriber_number', null);
        $transaction['voucher_code']    =   $request->input('voucher_code', null);
        $transaction['fld_003'] = $request->input('processing_code');
        $transaction['fld_004'] = $request->input('amount');
        $transaction['fld_009'] = $request->input('device_type', 'N');
        $transaction['fld_011'] = substr(explode(' ', microtime())[1], 0, 4).str_shuffle(explode('.', explode(' ', microtime())[0])[1]);
        $transaction['fld_014'] = null;
        $transaction['fld_037'] = $request->input('transaction_id');

        $transaction['fld_042'] = $request->input('merchant_id');
        $transaction['fld_057'] = $request->input('r-switch');
        $transaction['fld_116'] = $request->input('desc');

        $transaction['fld_103'] = $request->input('account_number', null);
        $transaction['fld_117'] = $request->input('account_issuer', null);
        $transaction['fld_041'] = $request->input('terminal_id', null);
        $transaction['fld_123'] = null;

        # Set Reserved For Future Use Fields
        $transaction['rfu_001'] = $request->input('rfu_001', 'null');
        $transaction['rfu_002'] = $request->input('rfu_002', 'null');
        $transaction['rfu_003'] = $request->input('rfu_003', 'null');
        $transaction['rfu_004'] = $request->input('rfu_004', 'null');
        $transaction['rfu_005'] = $request->input('rfu_005', 'null');
        $response = [];

        if (in_array($request->input('processing_code'), $purchase_with_card)) {
            $transaction['fld_002']     =   $request->input('pan');
            $transaction['cvv']         =   $request->input('cvv');
            $transaction['expMonth']    =   $request->input('exp_month');
            $transaction['expYear']     =   $request->input('exp_year');
            $transaction['cvv']         =   $request->input('cvv');
            $transaction['expMonth']    =   $request->input('exp_month');
            $transaction['expYear']     =   $request->input('exp_year');
            $transaction['fld_014']     =   $transaction['expYear'].'/'.$transaction['expMonth'];
            $transaction['fld_123']     =   $transaction['cvv'];
            $transaction['response_url']=   $request->input('3d_url_response');

            $response = array_merge($request->all(), Transaction::purchase($transaction));

        } elseif ($request->input('processing_code') === '000200' && count($request->all()) > 9) {

            $response = array_merge($request->all(), ['code' => 900, 'status' => 'error', 'reason' => 'Too many fields passed. Maximum number of fields must be 9!']);

        } elseif ($request->input('processing_code') === '000200') {

            $response = array_merge($request->all(), Transaction::purchase($transaction));

        } elseif ($request->input('processing_code') === '21') {
            $transaction['fld_003'] = '21';
            $transaction['fld_004'] = $request->input('deposit_amount');
            $transaction['fld_002'] = $request->input('deposit_wallet_number');
            $transaction['fld_057'] = $request->input('deposit_wallet_name');

            $response = array_merge($request->all(), Transaction::purchase($transaction));

        } elseif (in_array($request->input('processing_code'), $deposit_transactions)) { // If transaction type is funds transfer

			if (!in_array($request->input('merchant_id'), ['TTM-00000002', 'TTM-00000001', 'TTM-00000035'])) { // if the merchant is
				// not theTeller then do not process the transfer
				return array_merge($request->all(), ['status' => 'failed', 'code' => 900, 'reason' => 'transaction could not be completed']);
			}

            // If From Account Type is Card
            if (substr($request->input('processing_code'), 2, 2) === '00' || substr($request->input('processing_code'), 2, 2) === '01'){

                if (count($request->all()) > 16){
                    $response = $request->all();
                    if (isset($response['reference'])){
                        unset($response['reference']);
                    }

                    if (isset($response['pan'])){
                        $response['cvv'] = '***';
                        $response['pan'] = substr($response['pan'],0, 6).'******'.substr($response['pan'], -4);
                    }
                    return array_merge($response, ['code' => 900, 'status' => 'error', 'reason' => 'Too many fields passed. Maximum number of fields must be 16!']);
                }

                $transaction['fld_002']     =   $request->input('pan');
                $transaction['cvv']         =   $request->input('cvv');
                $transaction['expMonth']    =   $request->input('exp_month');
                $transaction['expYear']     =   $request->input('exp_year');
                $transaction['cvv']         =   $request->input('cvv');
                $transaction['expMonth']    =   $request->input('exp_month');
                $transaction['expYear']     =   $request->input('exp_year');
                $transaction['fld_014']     =   $transaction['expYear'].'/'.$transaction['expMonth'];
                $transaction['fld_123']     =   $transaction['cvv'];
                $transaction['response_url']=   $request->input('3d_url_response');

                $transferred = Transaction::transfer($transaction, $request->input('transacted_amount', null));
                $response = array_merge($request->all(), $transferred);

            } elseif (substr($request->input('processing_code'), 2, 2) === '02'){ // If From Account Type is Mobile Wallet
                $transferred = Transaction::transfer($transaction, $request->input('transacted_amount', null));
                $response = array_merge($request->all(), $transferred);
            } elseif (substr($request->input('processing_code'), 2, 2) === '40') { /* If from Account is Merchant Float */
                /* Get all wallets attached to merchant */
                $wallet = Wallet::getAllWallets($request->input('merchant_id'), $request->input('merchant_id'));
                /* Check if wallets were found */
                if (count($wallet)) {
                    /* Get merchant */
                    $merchant = Merchant::where('merchant_id', $request->input('merchant_id'))->first();
                    if (is_null($merchant)) {
                        return [
                            'status'    =>  'not found',
                            'code'      =>  444,
                            'reason'    =>  'merchant does not exists'
                        ];
                    }


                    $decrypted_wallet = Wallet::decryptData(Wallet::where('wallet_id', $wallet[0]['wallet_id'])->first());
                    $transaction['fld_002'] = $decrypted_wallet['wallet_number'];
                    $transaction['cvv'] = substr($merchant->pass_code, -3);
                    $transaction['fld_057'] = $decrypted_wallet['wallet_name'];
                    $transaction['response_url'] = 'https://api.theteller.net';
                    $transaction['expMonth'] = substr($decrypted_wallet['expiry_date'], 0, 2);
                    $transaction['expYear'] = substr($decrypted_wallet['expiry_date'], -2);

                    $transfer = Transaction::transfer($transaction);

                    if (isset($transfer['status']) && $transfer['status'] === 'vbv required'){
                        $transfer['status'] = 'failed';
                        $transfer['reason'] ='Merchant debit failed. Please contact support';
                    }

                    return array_merge($request->all(), $transfer);

                } else { /* If wallet does not exist */
                    return [
                        'status'    =>  'not found',
                        'code'      =>  444,
                        'reason'    =>  'not wallets found'
                    ];
                }
            }
        }

        if (isset($response['reference'])){
            unset($response['reference']);
        }

        if (isset($response['pan'])){
            $response['cvv'] = '***';
            $response['pan'] = substr($response['pan'],0, 6).'******'.substr($response['pan'], -4);
        }
        return $response;

    }

    public function getTransactionStatus(Request $request, $transaction_id)
    {
        if ($request->hasHeader('Merchant-Id')){
            return Transaction::getTransactionStatus($request->header('Merchant-Id'), $transaction_id);
        }
        return [
            'status'    => 'failed',
            'code'  =>  999,
            'reason'    => 'Header: Merchant-Id is not set'
        ];
    }

    public function getOrderPaymentState($order_id)
    {
        return Payswitch::class;
    }

    public function getMerchantTransactionUsingTransactionId($merchant_id, $transaction_id)
    {
        return Transaction::getMerchantTransactionUsingTransactionId($merchant_id, $transaction_id);
    }

    public function getFarmersTransactionsUsingFarmerId($farmer_id)
    {
        return Transaction::getFarmersTransactionsUsingFarmerId($farmer_id);
    }

    public function getFarmersTransactionsUsingFarmerIdAndTransactionId($farmer_id, $transaction_id)
    {
        return Transaction::getFarmersTransactionsUsingFarmerIdAndTransactionId($farmer_id, $transaction_id);
    }

}