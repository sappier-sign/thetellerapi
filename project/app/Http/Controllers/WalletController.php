<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 07/08/2017
 * Time: 4:01 PM
 */

namespace App\Http\Controllers;


use App\Merchant;
use App\Transaction;
use App\User;
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
			'merchant_id' => 'bail|required|size:12',
			'user_id' => 'bail|required|size:12',
			'pass_code' => 'bail|required|size:32',
			'details.wallet_name' => 'bail|required|size:3|in:MTN,ATL,TGO,VIS,MAS,VDF',
			'details.wallet_number' => 'bail|required|min:10|numeric',
			'details.holder_name' => 'bail|required|string|min:4|max:30',
			'details.expiry_date' => 'bail|required_if:details.wallet_name,VIS,MAS|digits:4'
		], [
			'details.wallet_name.required' => 'Wallet name is required',
			'details.wallet_name.size' => 'The length of the wallet name must be 3 bytes',
			'details.wallet_name.in' => 'The specified wallet name is not in the allowed list',
			'details.wallet_number.required' => 'Wallet number is required',
			'details.wallet_number.min' => 'Wallet number must be 10 digits for mobile money and 16 for cards',
			'details.wallet_number.numeric' => 'Wallet number must be digits',
			'details.expiry_date.required_if' => 'Expiry date is required if wallet name is VIS or MAS',
			'details.cvv.required_if' => 'cvv is required if wallet name is VIS or MAS',
			'details.holder_name.required' => 'Holder name is required',
			'details.holder_name.string' => 'Holder name must be a string',
			'details.holder_name.min' => 'Holder name must be at least 4 bytes',
			'details.holder_name.max' => 'Holder name must be not more than 32 bytes long'
		]);

		//        check for fields that are not expected
		foreach ($request->all() as $key => $value) {
			if (!in_array($key, ['pass_code', 'user_id', 'merchant_id', 'details'])) {
				return [
					'status' => 'format error',
					'code' => 900,
					'reason' => $key . ' is not allowed'
				];
			}
		}

//        check for fields that are not expected
		foreach ($request->details as $key => $value) {
			if (!in_array($key, ['holder_name', 'expiry_date', 'wallet_name', 'wallet_number'])) {
				return [
					'status' => 'format error',
					'code' => 900,
					'reason' => $key . ' is not allowed'
				];
			}
		}


		if (is_null(Merchant::where('merchant_id', $request->input('merchant_id'))->first())) {
			return [
				'status' => 'error',
				'code' => 909,
				'reason' => "unknown merchant id!"
			];
		}

		$encrypted_data = Wallet::encryptData($request->all());

		return Wallet::persistWallet($encrypted_data, $request->all());

	}

	public function update(Request $request)
	{
		$this->validate($request, [
			'old_pass_code' => 'bail|required|size:32',
			'new_pass_code' => 'bail|required|size:32',
			'user_id' => 'bail|required|size:12',
			'merchant_id' => 'bail|required|size:12'
		]);
		return Wallet::updateWallet($request->all());
	}

	/**
	 * @param $merchant_id
	 * @param $user_id
	 * @return mixed
	 */
	public function show($merchant_id, $user_id)
	{
		return Wallet::getAllWallets($merchant_id, $user_id);
		if (Merchant::where('merchant_id', $merchant_id)->first()->id) {
			return Wallet::show($merchant_id, $user_id);
		} else {
			return [
				"status" => "Bad request",
				"code" => 999,
				"description" => "Merchant ID is not set!"
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
			'merchant_id'       => 'bail|required|size:12',
			'user_id'           => 'bail|required|size:12',
			'processing_code'   => 'bail|required|digits:6|in:000000,000100,000200',
			'wallet_id'         => 'bail|required|size:13|exists:ttm_wallets,wallet_id',
			'amount'            => 'bail|required|digits:12',
			'desc'              => 'bail|required|min:6',
			'transaction_id'    => 'bail|required|size:12',
			'pass_code'         => 'bail|required|size:32'
		], [
			'merchant_id.size' => 'Merchant id must be 12 characters long'
		]);

		return Wallet::pay($request->input('merchant_id'), $request->input('user_id'), $request->input('wallet_id'),
			$request->input('transaction_id'), $request->input('pass_code'), $request->input('processing_code'),
			$request->input('amount'), $request->input('desc'), $request->input('cvv', null),
			$request->input('3d_url_response'), $request->input('voucher_code', null));
	}

	/**
	 * @param Request $request
	 * @return array|bool|mixed|string
	 */
	public function transfer(Request $request)
	{
		$this->validate($request, [
			'merchant_id' => 'bail|required|size:12',
			'user_id' => 'bail|required|size:12',
			'processing_code' => 'bail|required|digits:6',
			'wallet_id' => 'bail|required|size:13|exists:ttm_wallets,wallet_id',
			'amount' => 'bail|required|digits:12',
			'desc' => 'bail|required|min:6',
			'transaction_id' => 'bail|required|size:12',
			'pass_code' => 'bail|required|size:32',
			'account_number' => 'bail|required|min:10',
			'account_issuer' => 'bail|required|size:3|in:MTN,VDF,ATL,TGO,GIP'
		], [
			'merchant_id.size' => 'Merchant id must be 12 characters long'
		]);
		return Wallet::transfer($request->input('merchant_id'), $request->input('user_id'),
			$request->input('wallet_id'), $request->input('transaction_id'),
			$request->input('pass_code'), $request->input('processing_code'),
			$request->input('amount'), $request->input('desc'),
			$request->input('cvv', null),
			$request->input('3d_url_response', null),
			$request->input('voucher_code', null),
			$request->input('account_issuer'), $request->input('account_number'));
	}

	/**
	 * @param Request $request
	 * @return array
	 */
	public function destroy(Request $request)
	{
		$this->validate($request, [
			'merchant_id' => 'bail|required|size:12|exists:users',
			'user_id' => 'bail|required|size:12',
			'wallet_id' => 'bail|required|min:1|exists:ttm_wallets'
		]);

		$merchant_id = $request->input('merchant_id');
		$user_id = $request->input('user_id');
		$id = $request->input('wallet_id');
		$response = Wallet::deleteWallet($merchant_id, $user_id, $id);

		if ($response['code'] === '000') {
            return response()->json("", 204);
        }

        return array_merge($request->all(), $response);
	}

    public function validateWallet(Request $request)
    {
        $this->validate($request, [
            'merchant_id'           => 'bail|required|size:12|exists:users',
            'amount'                => 'bail|required|digits:12',
            'cvv'                   => 'bail|required|digits:3',
            'details.wallet_name'   => 'bail|required|size:3|in:MTN,ATL,TGO,VIS,MAS,VDF',
            'details.wallet_number' => 'bail|required|min:10|numeric',
            'details.holder_name'   => 'bail|required|string|min:4|max:30',
            'details.expiry_date'   => 'bail|required_if:details.wallet_name,VIS,MAS|digits:4'
        ], [
            'merchant_id.size'      => 'Merchant id must be 12 characters long',
            'merchant_id.exists'    => 'Merchant does not exist',
        ]);



        $validation_code = substr(str_replace('.', '', microtime(true)), 0, 12);
        $data = $request->all();
        $data['pass_code'] = md5($validation_code);
        $merchant = Merchant::where('merchant_id', 'TTM-00000001')->first();

        $body = [];
        $body['cvv']                = $data['cvv'];
        $body['merchant_id']        = 'TTM-00000001';
        $body['rfu_001']            = $data['merchant_id'];
        $body['exp_month']          = substr($data['details']['expiry_date'], 0, 2);
        $body['exp_year']           = substr($data['details']['expiry_date'], -2);
        $body['amount']             = $data['amount'];
        $body['r-switch']           = $data['details']['wallet_name'];
        $body['desc']               = 'TheTeller Wallet Verification';
        $body['processing_code']    = (in_array($data['details']['wallet_name'], ['MAS', 'VIS']))? '000000' : '000200';
        $body['transaction_id']     = $validation_code;
        $body['pan']                = $data['details']['wallet_number'];
        $body['3d_url_response']    = 'https://api.theteller.net';

        $response = Wallet::sendRequest($merchant, $body);

        if (isset($response['code']) && $response['code'] === '000'){
            return [
                "reference"         =>  Wallet::encryptData($data, true),
                "status"            =>  "success",
                "code"              =>  "000",
                "reason"            =>  "Wallet debited",
                "validation_code"   =>  $validation_code,
                "amount"            =>  $data['amount']
            ];
        }

        return $response;
	}

    public function saveWallet(Request $request)
    {
        $this->validate($request, [
            "pass_code"         =>  "bail|required|size:32",
            "reference"         =>  "bail|required",
            "merchant_id"       =>  "bail|required|size:12|exists:users",
            "user_id"           =>  "bail|required|size:12",
            "amount"            =>  "bail|required|digits:12",
            "validation_code"   =>  "bail|required|digits:12"
        ]);

        if (Transaction::where('fld_042', 'TTM-00000001')->where('rfu_001', $request->merchant_id)->where('fld_004', $request->amount)->where('fld_037', $request->validation_code)->count()){
            $data = $request->all();

            $data['details'] = Wallet::decryptRef($data['reference'], md5($data['validation_code']));

            if (!$data['details']) {
                return array_merge($request->all(),[
                    "status"    =>  "error",
                    "code"      =>  900,
                    "reason"    =>  "reference failed validation"
                ]);
            }

            $encrypted_data = Wallet::encryptData($data);
            return Wallet::persistWallet($encrypted_data, $data);
        }

        return array_merge($request->all(), [
            "status"    =>  "error",
            "code"      =>  900,
            "reason"    =>  "data verification failed"
        ]);
	}
}