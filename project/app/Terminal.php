<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 11/10/2017
 * Time: 4:44 PM
 */

namespace App;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Terminal extends Model
{
    protected $table = 'ttm_terminals';


    public function merchant()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id', 'merchant_id');
    }

    public static function createTerminal($merchant_id)
    {
        $pin = substr(str_shuffle('014785236984756932107412589603'), 2, 4);
        $terminal = new Terminal();
        $terminal->merchant_id  =   $merchant_id;
        $terminal->pin          =   Hash::make($pin);

        if ($terminal->save()){
            $terminal->t_id     =   str_pad($terminal->id,8,'0',STR_PAD_LEFT);
            $terminal->save();
            return [
                'terminal_id'   =>  $terminal->t_id,
                'merchant_id'   =>  $terminal->merchant_id,
                'pin'           =>  $pin
            ];
        }

        return [
            'status'    =>  'failed',
            'code'      =>  423,
            'reason'    =>  'Terminal could not be created at this time. Please try again later.'
        ];
    }

    public static function terminalSignUp($terminal_id, $merchant_id, $pin, $imei)
    {
        $terminal   =   Terminal::where('t_id', $terminal_id)->where('merchant_id', $merchant_id)->first();
        if ($terminal->signed_up){
            return [
                'status'    =>  'failed',
                'code'      =>  900,
                'reason'    =>  'Terminal registration failed, terminal already exist'
            ];
        }

        $terminal->imei         =   $imei;
        $terminal->signed_up    =   true;
        if ($terminal->save()){
            return [
                'status'    =>  'success',
                'code'      =>  100,
                'reason'    =>  'Terminal registration successful'
            ];
        }

        return [
            'status'    =>  'failed',
            'code'      =>  900,
            'reason'    =>  'Terminal registration failed'
        ];
    }

    public static function terminalSignIn($imei, $pin)
    {
        $terminal   =   Terminal::where('imei', $imei)->first();
        if (isset($terminal->id)){
            if (Hash::check($pin, $terminal->pin)){
                return [
                    'status'    =>  'success',
                    'code'      =>  100,
                    'reason'    =>  'Terminal found'
                ];
            }

            return [
                'status'    =>  'pin mismatch',
                'code'      =>  100,
                'reason'    =>  'Wrong pin'
            ];
        }

        return [
            'status'    =>  'fail',
            'code'      =>  900,
            'reason'    =>  'Terminal does not exist'
        ];
    }

    public static function setTerminalPin($terminal_id, $merchant_id, $new_pin)
    {
        $terminal       =   Terminal::where('t_id', $terminal_id)->where('merchant_id', $merchant_id)->first();
        $terminal->pin  =   Hash::make($new_pin);
        if ($terminal->save()){
            return [
                'status'    =>  'success',
                'code'      =>  100,
                'reason'    =>  'Pin change was successful'
            ];
        }

        return [
            'status'    =>  'failed',
            'code'      =>  900,
            'reason'    =>  'Pin could not be changed at this time'
        ];
    }

    public static function resetTerminalPin($terminal_id)
    {
        $pin            =   substr(str_shuffle('0147852369'), 2, 4);
        $terminal       =   Terminal::where('t_id', $terminal_id)->first();

        if ($terminal->id){
            $terminal->pin  =   bcrypt($pin);
            if ($terminal->save()){
                return [
                    'status'    =>  'success',
                    'code'      =>  100,
                    'pin'       =>  $pin,
                    'terminal_id'   =>  $terminal_id
                ];
            }

            return [
                'status'    =>  'failed',
                'code'      =>  900,
                'reason'    =>  'Pin reset failed. Please try again later!'
            ];
        }

        return [
            'status'    =>  'failed',
            'code'      =>  900,
            'reason'    =>  'The specified id does not match any terminal'
        ];
    }
}