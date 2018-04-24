<?php
    /**
     * Created by PhpStorm.
     * User: Admin
     * Date: 07/08/2017
     * Time: 4:08 PM
     */

    namespace App;


    use Illuminate\Database\Eloquent\Model;

    class Merchant extends Model
    {
        protected $table = 'users';

        protected $hidden = ['password', 'remember_token'];

        public function setWalletBalanceAttribute($wallet_balance): Merchant
        {
            $this->attributes['wallet_balance'] = str_pad($wallet_balance, 12, '0', STR_PAD_LEFT);
            return $this;
        }

        public function creditWallet($amount): array
        {
            $values = $this->convertToFloat($amount);
            $balance = ( $values['old'] + $values['new'] ) * 100;
            $this->setWalletBalanceAttribute($balance)->save();
            return [
                'status' => 'success',
                'code' => '000',
                'reason' => $this->getWalletBalanceAttribute()
            ];
        }

        public function debitWallet($amount): array
        {
            $values = $this->convertToFloat($amount);
            if ($values['new'] > $values['old']) {
                return [
                    'status' => 'fail',
                    'code' => 400,
                    'reason' => 'insufficient funds'
                ];
            }
            $balance = ( $values['old'] - $values['new'] ) * 100;
            $this->setWalletBalanceAttribute($balance)->save();

            return [
                'status' => 'success',
                'code' => '000',
                'reason' => $this->getWalletBalanceAttribute()
            ];
        }

        private function convertToFloat($string): array
        {
            $old = $this->getWalletBalance();
            $new = (int) $string / 100;
            return ['old' => $old, 'new' => $new];
        }

        public function getWalletBalance(): float
        {
            $wallet_balance = $this->attributes['wallet_balance'];

            $value = (int) $wallet_balance;

            return $value / 100;
        }

        public function getWalletBalanceAttribute()
        {
            return $this->attributes['wallet_balance'];
        }

        public function user()
        {
            return $this->belongsTo(User::class, 'apiuser', 'user_name');
        }

        public function terminals()
        {
            return $this->hasMany(Terminal::class, 'merchant_id', 'merchant_id');
        }

        public function rswitches()
        {
            return $this->belongsToMany(Rswitch::class, 'ttm_merchant_r_switch', 'merchant_id',
                'r_switch_id')->withTimestamps();
        }

        public static function addFloat($merchant_id, $account_number, $account_name, $expiry_date, $cvv)
        {
            /* Get merchant using merchant_id */
            $merchant = Merchant::where('merchant_id', $merchant_id)->first();
            if (is_null($merchant)) { /* if merchant is not found */
                return [
                    'status' => 'not found',
                    'code' => '400',
                    'reason' => 'merchant does not exist!'
                ];
            }

            // Get Previously added wallet
            $wallets = Wallet::getAllWallets($merchant_id, $merchant_id);
            // Check if wallet has been add already
            if (count($wallets)) {
                foreach ($wallets as $wallet) { // iterate and delete added wallets
                    Wallet::where('wallet_id', $wallet['wallet_id'])->delete();
                }
            }

            $pass_code = md5($cvv);

            $wallet = Wallet::encryptData([
                'merchant_id' => $merchant_id,
                'user_id' => $merchant_id,
                'pass_code' => $pass_code,
                'details' => [
                    'holder_name' => $merchant->company,
                    'wallet_number' => $account_number,
                    'wallet_name' => $account_name,
                    'expiry_date' => $expiry_date
                ]
            ]);

            /* Check if wallet was created successfully */
            if (isset($wallet['wallet_id'])) {
                $merchant->pass_code = $pass_code . $cvv;
                $merchant->wallet_id = $wallet['wallet_id'];

                /* Check if merchant records was updated */
                if ($merchant->save()) {
                    return $wallet;
                } else {
                    return [
                        'status' => 'failed',
                        'code' => 901,
                        'reason' => 'updating merchant details failed'
                    ];
                }
            } else {
                /* Wallet creating failed */
                return $wallet;
            }

        }

        public function assignMomos()
        {
            $old_rswitches = $this->rswitches()->pluck('r_switch_id')->toArray();
            $momoIDs = Rswitch::where('category', 'momo')->pluck('id');
            $this->rswitches()->sync(array_merge($old_rswitches, $momoIDs));
        }

        public function assignCards()
        {
            $old_rswitches = $this->rswitches()->pluck('r_switch_id')->toArray();
            $cards = Rswitch::where('category', 'cards')->pluck('id');
            $this->rswitches()->sync(array_merge($old_rswitches, $cards));
        }

        public function assignBanks()
        {
            $old_rswitches = $this->rswitches()->pluck('r_switch_id')->toArray();
            $banks = Rswitch::where('category', 'bank')->pluck('id');
            $this->rswitches()->sync(array_merge($old_rswitches, $banks));
        }

        public function assignAllRswitches()
        {
            $this->rswitches()->sync(Rswitch::pluck('id'));
        }
    }