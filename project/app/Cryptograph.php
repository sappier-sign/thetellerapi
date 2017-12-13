<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 04/08/2017
 * Time: 9:42 AM
 */

namespace App;


use Illuminate\Support\Facades\Crypt;

class Cryptograph
{
    public static function encrypt($string)
    {
        return Crypt::encrypt($string);
    }

    public static function decrypt($hash)
    {
        return Crypt::decrypt($hash);
    }

}