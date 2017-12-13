<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 12/10/2017
 * Time: 3:34 PM
 */

namespace App\Http\Controllers;


use App\Terminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TerminalController extends Controller
{
    public function signUp(Request $request)
    {
        $this->validate($request, [
            'terminal_id'   =>  'bail|required|digits:8',
            'merchant_id'   =>  'bail|required|alpha_dash|size:12',
            'pin'           =>  'bail|required|digits:4',
            'imei'          =>  'bail|required|size:16'
        ]);

        Hash::make('admin');

        return Terminal::terminalSignUp($request->terminal_id, $request->merchant_id, $request->pin, $request->imei);
    }

    public function setPin(Request $request)
    {
        $this->validate($request, [
            'terminal_id'   =>  'bail|required|digits:8',
            'merchant_id'   =>  'bail|required|alpha_dash|size:12',
            'new_pin'       =>  'bail|required|digits:6'
        ]);

        return Terminal::setTerminalPin($request->terminal_id, $request->merchant_id, $request->new_pin);
    }

    public function signIn(Request $request)
    {
        $this->validate($request, [
            'imei'  =>  'bail|required|size:16',
            'pin'   =>  'bail|required|digits:6'
        ]);
        return Terminal::terminalSignIn($request->imei, $request->pin);
    }
}