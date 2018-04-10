<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 30/11/2017
 * Time: 7:54 PM
 */

namespace App;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Statistic extends Model
{
    protected $table = 'ttm_statistics';


    public function __construct()
    {
    }

    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = Functions::toFloat($value);
    }

    public function setSourceAttribute($value)
    {
        if (count($value) > 10){
            $this->attributes['source'] = Functions::maskAm($value);
        } else {
            $this->attributes['source'] = $value;
        }
    }

    public function getSourceCount($source)
    {
        return Statistic::where('source', $source)->whereDate('created_at', Carbon::today()->toDateString())->count();
    }

    public function getInitialTransactions($source)
    {
        return Statistic::where('source', $source)->whereDate('created_at', Carbon::today()->toDateString())->orderBy('amount', 'desc')->get();
    }

    public function persist($source, $amount, $merchant_id)
    {
        $this->amount = $amount;
        $this->source = $source;
        $this->save();
        if ($this->source <> '0249621938'){
            if ($this->getSourceCount($source) > 300){
                return '010';
            }
        }

        $apiuser = Merchant::where('merchant_id', $merchant_id)->first()->apiuser; // Get Merchant apiuser name

        if ($amount < 0.1){
            return '030';
        } elseif ($amount > (float) User::where('user_name', $apiuser)->first()->amount_limit){ // Get the merchant amount limit and compare again the incoming transaction amount
            return '020';
        }
        return $this;
    }
}