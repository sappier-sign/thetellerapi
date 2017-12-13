<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 10/10/2017
 * Time: 12:32 PM
 */

namespace App\Http\Controllers;


use App\Sms;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function send(Request $request)
    {
        $this->validate($request, [
            'recipients'    =>  'bail|required|Array',
            'message'       =>  'bail|required|',
            'from'          =>  'bail|required'
        ]);
        return Sms::send($request->recipients, $request->from, null, $request->message);
    }
}