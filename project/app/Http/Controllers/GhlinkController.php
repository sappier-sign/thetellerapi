<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16/08/2017
 * Time: 10:21 AM
 */

namespace App\Http\Controllers;


use App\Ghlink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GhlinkController extends Controller
{
    public function index($html)
    {
        echo $html;
    }

    public function saveResponse(Request $request)
    {
        echo $response = json_encode($request->all());
        $file = fopen('ghlink.txt', 'a+');
        fwrite($file, $response);
    }

    public function process(Request $request)
    {
        return Ghlink::encryptRequest(['amount' => $request->input('amount')]);
    }
}