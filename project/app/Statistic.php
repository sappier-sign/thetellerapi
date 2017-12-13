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
        $this->attributes['amount'] = number_format( (float) str_replace(',', '', $value), 2);
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

    public function persist($source, $amount)
    {
        $this->amount = $amount;
        $this->source = $source;
        $this->save();
        if ($this->getSourceCount($source) > 3){
            return '010';
        } elseif ($amount > 500){
            return '020';
        }
        return $this;
    }
}