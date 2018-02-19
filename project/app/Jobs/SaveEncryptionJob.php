<?php

namespace App\Jobs;

use App\Wallet;

class SaveEncryptionJob extends Job
{
	protected $wallet;


	/**
	 * EncryptionJob constructor.
	 * @param Request $request
	 */
	public function __construct($user_id, $pass_code, $wallet_id, $merchant_id, $details)
    {
        $this->wallet = [
        	'user_id' => $user_id,
			'pass_code' => $pass_code,
			'wallet_id' => $wallet_id,
			'merchant_id' => $merchant_id,
			'details' => $details
		];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Wallet::create($this->wallet);
    }
}
