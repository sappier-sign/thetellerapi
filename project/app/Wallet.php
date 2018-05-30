<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 04/08/2017
 * Time: 10:10 AM
 */

namespace App;


use App\Jobs\EncryptionJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Wallet extends Model
{
	protected $table = 'ttm_wallets';
	protected $hidden = [
		'id',
		'pass_code',
		'created_at',
		'updated_at'
	];

//    protected $encryption_algo = 'aes256';
//    protected $details;
//    protected $pass_code;
//    protected $merchant_id;
//    protected $user_id;
//    protected $wallet_id;

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
     * @param $request
     * @return array
     */
    public static function encryptData($request, $validate = false)
	{
	    if (!$validate) {
            if (self::walletExists($request)) {
                return [
                    'status' => 'failed',
                    'code' => 900,
                    'reason' => 'wallet already exist'
                ];
            }
        }

		$data = json_encode($request['details']);
		return openssl_encrypt($data, 'aes256', $request['pass_code'], 0,
			Wallet::generateIv($request['pass_code']));
	}

    public static function persistWallet($encrypted_wallet, $data)
    {
        $wallet = new Wallet();
        return $wallet->saveWallet(
            $data['pass_code'],
            $data['user_id'],
            $encrypted_wallet,
            $data['merchant_id']);
	}

	public static function decryptData(Wallet $wallet)
	{
		$decryption = openssl_decrypt(
			$wallet->details, 'aes256',
			substr($wallet->pass_code, 0, 32),
			0,
			self::generateIv($wallet->pass_code)
		);

		return json_decode($decryption, true);
	}

    public static function decryptRef($hash, $pass_code)
    {
        $decryption = openssl_decrypt(
            $hash, 'aes256',
            substr($pass_code, 0, 32),
            0,
            self::generateIv($pass_code)
        );

        return json_decode($decryption, true);
	}

	public static function showWallet(Wallet $wallet)
	{
		$data = self::decryptData($wallet);

		if (in_array($data['wallet_name'], ['MAS', 'VIS'])) {
			$data['wallet_number'] = substr($data['wallet_number'], 0, 6) . '******' . substr($data['wallet_number'],
					-4);
		}

		return [
			'wallet_id' => $wallet->wallet_id,
			'user_id' => $wallet->user_id,
			'merchant_id' => $wallet->merchant_id,
			'details' => $data
		];
	}

	private function saveWallet($pass_code, $user_id, $details, $merchant_id)
	{
		$this->wallet_id = uniqid();

		$wallet = new Wallet();
		$wallet->pass_code = $pass_code . md5($this->wallet_id);
		$wallet->merchant_id = $merchant_id;
		$wallet->user_id = $user_id;
		$wallet->wallet_id = $this->wallet_id;
		$wallet->merchant_id = $merchant_id;
		$wallet->details = $details;

		if ($wallet->save()) {
			return self::showWallet($wallet);
		} else {
			return [
				'status' => 'failed',
				'code' => 900,
				'reason' => 'wallet could not be save. Please try again'
			];
		}
	}

	public static function getAllWallets($merchant_id, $user_id)
	{
		$all = [];
		$wallets = Wallet::where(['merchant_id' => $merchant_id, 'user_id' => $user_id])->get();
		foreach ($wallets as $wallet) {
			$all[] = self::showWallet($wallet);
		}
		return $all;
	}

	/**
	 * Generate Initialization Vector for data encryption
	 * @param $key
	 * @return bool|string
	 */
	public static function generateIv($key)
	{
		return substr($key, 15, 16);
	}

	/**
	 * @param array $new_wallet
	 * @return array
	 */
	public static function store(Array $new_wallet)
	{
		$wallet = new Wallet();
		$wallet->merchant_id = $new_wallet['merchant_id'];
		$wallet->user_id = $new_wallet['user_id'];
		$wallet->pass_code = $new_wallet['pass_code'];
		$wallet->details = $new_wallet['details'];
		$wallet->wallet_id = uniqid();

		if ($wallet->save()) {
			return [
				'status' => 'success',
				'code' => 100,
				'wallet_id' => $wallet->wallet_id
			];

		} else {
			return [
				'status' => 'error',
				'code' => 909,
				'reason' => "Something went wrong, we could not save your details. Please try again later!"
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

			if ($details['account_issuer'] === 'MAS' || $details['account_issuer'] === 'VIS') {

				$account_number = $details['account_number'];
				$details['account_number'] = substr($account_number, 0, 6) . '******' . substr($account_number, -4);
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
		if ($wallet) {
			if ($wallet->delete()) {
				return [
					'status' => 'success',
					'code' => '000',
					'reason' => 'Wallet has been deleted!',
					'wallet_id' => $id
				];
			} else {
				return [
					'status' => 'error',
					'code' => 909,
					'reason' => "Something went wrong. The specified wallet could not be deleted at this time!"
				];
			}

		} else {
			return [
				'status' => 'error',
				'code' => 909,
				'reason' => "The specific wallet does not exist!"
			];
		}
	}

	/**
	 * @param $user_id
	 * @param $merchant_id
	 * @param $pass_code
	 * @return bool|null
	 */
	public static function matchPassCode($user_id, $merchant_id, $pass_code)
	{
		$wallet = self::where(['merchant_id' => $merchant_id, 'user_id' => $user_id])->first();
		if ($wallet) {
			if (substr($wallet->pass_code, 0, 32) === $pass_code) {
				return true;
			}
			return false;
		}
		return null;
	}

    /**
     * @param $request
     * @return bool
     */
    public static function walletExists($request)
	{
		if (in_array($request['details']['wallet_name'], ['MAS', 'VIS'])) {
			$wallet_number = substr($request['details']['wallet_number'], 0, 6) . '******' . substr
				($request['details']['wallet_number'], -4);
		} else {
			$wallet_number = $request['details']['wallet_number'];
		}

		foreach (self::getAllWallets($request['merchant_id'], $request['user_id']) as $allWallet) {
			if ($allWallet['details']['wallet_number'] === $wallet_number) {
				return true;
			}
		}
	}

    /**
     * @param $merchant_id
     * @param $user_id
     * @param $wallet_id
     * @param $transaction_id
     * @param $pass_code
     * @param $processing_code
     * @param $amount
     * @param $desc
     * @param null $cvv
     * @param null $response_url
     * @param $voucher_code
     * @return array|mixed|string
     */
    public static function pay($merchant_id, $user_id, $wallet_id, $transaction_id, $pass_code, $processing_code,
                               $amount, $desc, $cvv = null, $response_url = null, $voucher_code)
	{

		// returns array [merchant, details, $pass_code]
		$getMerchantAndWallet = self::getMerchantAndWallet($merchant_id, $wallet_id);
		$isInvalidPassCode = self::matchPassCode($user_id, $merchant_id, $pass_code);

		if ($isInvalidPassCode) {
			$details = self::decryptData($getMerchantAndWallet[1]);

			$body = [
				'processing_code' => $processing_code,
				'amount' => $amount,
				'transaction_id' => $transaction_id,
				'merchant_id' => $merchant_id,
				'r-switch' => $details['wallet_name'],
				'subscriber_number' => $details['wallet_number'],
				'desc' => $desc,
				'voucher_code' => $voucher_code
			];

			$body = self::isValidDebitCard($details, ['MAS', 'VIS'], $body, $response_url, $cvv);
			return self::sendRequest($getMerchantAndWallet[0], $body);
		} else {
			return [
				'status' => 'failed',
				'code' => 900,
				'reason' => 'pass code mismatch'
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
	 * @param $account_issuer
	 * @param $account_number
	 * @return array|bool|mixed|string
	 */
	public static function transfer($merchant_id, $user_id, $wallet_id, $transaction_id, $pass_code, $processing_code,
									$amount, $desc, $cvv = null, $response_url = null, $voucher_code = null,
									$account_issuer, $account_number)
	{
		// returns array [merchant, details, $pass_code]
		$getMerchantAndWallet = self::getMerchantAndWallet($merchant_id, $wallet_id);
		$isInvalidPassCode = self::matchPassCode($user_id, $merchant_id, $pass_code);

		if ($isInvalidPassCode) {
			$details = self::decryptData($getMerchantAndWallet[1]);

			$body = [
				'processing_code' => $processing_code,
				'amount' => $amount,
				'transaction_id' => $transaction_id,
				'merchant_id' => $merchant_id,
				'r-switch' => $details['wallet_name'],
				'subscriber_number' => $details['wallet_number'],
				'desc' => $desc,
				'voucher_code' => $voucher_code,
				'account_issuer' => $account_issuer,
				'account_number' => $account_number
			];

			$body = self::isValidDebitCard($details, ['MAS', 'VIS'], $body, $response_url, $cvv);
			return self::sendRequest($getMerchantAndWallet[0], $body);
		} else {
			return [
				'status' => 'failed',
				'code' => 900,
				'reason' => 'pass code mismatch'
			];
		}
	}

	/**
	 * @param $merchant_id
	 * @param $wallet_id
	 * @return array
	 */
	public static function getMerchantAndWallet($merchant_id, $wallet_id)
	{
		$merchant = Merchant::where('merchant_id', $merchant_id)->first();
		if (isset($merchant->id)) {
			$wallet = Wallet::where('wallet_id', $wallet_id)->first();
			if (isset($wallet->wallet_id)) {
				return [$merchant, $wallet, $wallet->pass_code];
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
		if (substr($wallet->pass_code, 0, 32) <> $pass_code) {
			return [
				'status' => 'failed',
				'code' => 909,
				'reason' => 'Wrong pass code!'
			];
		}
		return false;
	}

	public static function isValidDebitCard(Array $details, Array $cards, Array $body, $response_url, $cvv)
	{
		if (in_array($details['wallet_name'], $cards)) {
			$body['pan'] = $details['wallet_number'];
			$body['exp_month'] = substr($details['expiry_date'], 0, 2);
			$body['exp_year'] = substr($details['expiry_date'], -2);
			$body['3d_url_response'] = $response_url;
			$body['cvv'] = $cvv;

			if (isset($body['subscriber_number'])) {
				unset($body['subscriber_number']);
			}

			if ($details['wallet_name'] <> 'VDF' && isset($body['voucher_code'])) {
				unset($body['voucher_code']);
			}

			return $body;
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
		$user = User::where('user_name', $merchant->apiuser)->first();
		$headers = [
			'Content-Type: application/json',
			'Authorization: Basic ' . base64_encode($user->user_name . ":" . $user->api_key)
		];

		$curl = curl_init('https://api.theteller.net/v1.1/transaction/process');
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		if (curl_error($curl)) {
			return curl_error($curl);
		} else {
			return json_decode(curl_exec($curl), true);
		}
	}

	public static function updateWallet($request)
	{
		$pass_code_matched = self::matchPassCode($request['user_id'], $request['merchant_id'],
			$request['old_pass_code']);
		if ($pass_code_matched) {
			$wallets = Wallet::where('merchant_id', $request['merchant_id'])
				->where('user_id', $request['user_id'])
				->get();

			foreach ($wallets as $wallet) {
				$details = self::decryptData($wallet);
				$wallet->details = openssl_encrypt(json_encode($details), 'aes256', $request['new_pass_code'], 0,
					Wallet::generateIv($request['new_pass_code']));
				$wallet->pass_code = $request['new_pass_code'] . md5(uniqid());
				$wallet->save();
			}

			return [
				'status' => 'success',
				'code' => '000',
				'reason' => 'Pass code updated successfully'
			];
		}

		return [
			'status' => 'failed',
			'code' => 900,
			'reason' => 'Pass code mismatch'
		];
	}
}