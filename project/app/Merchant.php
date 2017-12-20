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

    public function assignMomos(){
        $momoIDs = Rswitch::where('category', 'momo')->pluck('id');
        $this->rswitches()->sync($momoIDs);
    }

    public function assignCards(){
        $cards = Rswitch::where('category', 'cards')->pluck('id');
        $this->rswitches()->sync($cards);
    }

    public function assignBanks(){
        $banks = Rswitch::where('category', 'bank')->pluck('id');
        $this->rswitches()->sync($banks);
    }
    
    public function assignAllRswitches(){
        $this->rswitches()->sync(Rswitch::pluck('id'));
    }
}