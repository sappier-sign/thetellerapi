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

    protected $hidden   =   ['password', 'remember_token'];

    public function terminals()
    {
        return $this->hasMany(Terminal::class, 'merchant_id', 'merchant_id');
    }

    public function rswitches(){
        return $this->belongsToMany(Rswitch::class,'ttm_merchant_r_switch','merchant_id','r_switch_id')->withTimestamps();
    }

    public static function addMerchantFloat($merchant_id, $account_number, $account_name, $expiry_date, $cvv)
    {
        /* Get merchant using merchant_id */
        $merchant = Merchant::where('merchant_id', $merchant_id)->first();
        if(is_null($merchant)) { /* if merchant is not found */
            return [
                'status'    =>  'not found',
                'code'      =>  '400',
                'reason'    =>  'merchant does not exist!'
            ];
        }

        $pass_code = md5($cvv);

        $wallet = Wallet::encryptData([
            'merchant_id'   =>  $merchant_id,
            'user_id'       =>  $merchant_id,
            'pass_code'     =>  $pass_code,
            'details'       =>  [
                'holder_name'   =>  $merchant->company,
                'wallet_number' =>  $account_number,
                'wallet_name'   =>  $account_name,
                'expiry_date'   =>  $expiry_date
            ]
        ]);

        /* Check if wallet was created successfully */
        if (isset($wallet['wallet_id'])) {
            $merchant->pass_code = $pass_code.$cvv;
            $merchant->wallet_id = $wallet['wallet_id'];

            /* Check if merchant records was updated */
            if ($merchant->save()) {
                return $wallet;
            } else {
                return [
                    'status'    =>  'failed',
                    'code'      =>  901,
                    'reason'    =>  'updating merchant details failed'
                ];
            }
        } else {
            /* Wallet creating failed */
            return $wallet;
        }

    }

    public function assignMomos(){
        $old_rswitches = $this->rswitches()->pluck('r_switch_id')->toArray();
        $momoIDs = Rswitch::where('category', 'momo')->pluck('id');
        $this->rswitches()->sync(array_merge($old_rswitches, $momoIDs));
    }

    public function assignCards(){
        $old_rswitches = $this->rswitches()->pluck('r_switch_id')->toArray();
        $cards = Rswitch::where('category', 'cards')->pluck('id');
        $this->rswitches()->sync(array_merge($old_rswitches, $cards));
    }

    public function assignBanks(){
        $old_rswitches = $this->rswitches()->pluck('r_switch_id')->toArray();
        $banks = Rswitch::where('category', 'bank')->pluck('id');
        $this->rswitches()->sync(array_merge($old_rswitches, $banks));
    }
    
    public function assignAllRswitches(){
        $this->rswitches()->sync(Rswitch::pluck('id'));
    }
}