<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 21/07/2017
 * Time: 3:17 PM
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rswitch extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'r_switches';

    protected $fillable = ['name', 'short_code'];

    /**
     * @param $value
     */
    public function setShortCodeAttribute($value)
    {
        $short_code = strtolower($value);
        $this->attributes['short_code'] = strtoupper($short_code);
    }

    /**
     * @param $value
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = strtolower($value);
    }

    /**
     * @return string
     */
    public function getNameAttribute($value)
    {
        return ucfirst($value);
    }

    public function merchants(){
        return $this->belongsToMany(Merchant::class,'ttm_merchant_r_switch','r_switch_id','merchant_id')->withTimestamps();
    }

}