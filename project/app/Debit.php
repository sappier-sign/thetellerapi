<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 13/11/2017
 * Time: 3:23 PM
 */

namespace App;


use Illuminate\Database\Eloquent\Model;

class Debit extends Model
{
    protected $table = 'merchant_debits';

    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'merchant_id');
    }
}