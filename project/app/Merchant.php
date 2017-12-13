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
}