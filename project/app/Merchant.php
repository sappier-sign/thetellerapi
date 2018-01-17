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