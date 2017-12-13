<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 17/08/2017
 * Time: 11:28 AM
 */

namespace App\Http\Controllers;


use App\Functions;
use App\Payswitch;
use App\Vodafone;
use App\Zenith;
use Illuminate\Http\Request;

class TestController extends Controller
{

    /**
     * @param Request $request
     * @return mixed|string
     */
    public function debit(Request $request)
    {
        return Vodafone::debit($request->input('number'), $request->input('amount'), time().'00', $request->input('voucher_code'));
    }

    public function credit(Request $request)
    {
        return Vodafone::credit($request->input('number'), $request->input('amount'), $request->input('transaction_id'));
    }

    public function payswitch()
    {
        return Payswitch::send();
    }

    public function testCurl()
    {
        $curl = curl_init();
        curl_setopt( $curl, CURLOPT_URL, "https://appsnmobileagent.com:8201/debitCustomerWallet");
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $curl, CURLOPT_HEADER, false );
        curl_setopt( $curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY );
//        curl_setopt( $curl, CURLOPT_HTTPHEADER, null );
        curl_setopt( $curl, CURLOPT_POST, true );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, null );
        curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $curl, CURLOPT_POSTREDIR, 3 );

        echo curl_exec($curl);
    }

    /**
     * @param Request $request
     */
    public function vodafoneResponse(Request $request)
    {
        $response = json_decode(json_encode(simplexml_load_string($request->getContent())), true);
        $vodafone = Vodafone::where('ext_trans_id', $response['transactionID'])->first();
        $vodafone->result_code      =   $response['resultCode'];
        $vodafone->result_message   =   $response['resultMessage'];
        $vodafone->save();

        Functions::writeVodafone('VODAFONE TTLR RESPONSE FOREIGN', ['array' => $response]);

        $file = fopen('/var/www/api.theteller.net/public_html/api/storage/app/vodafone.txt', 'w+');
        fwrite($file, json_encode($response));
        fclose($file);
    }

    public function vismasResponse($ref)
    {
        $zenith = new Zenith();
        return $zenith->validateTransaction($ref);
    }

    public function ghlinkView()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.theteller.net/ghlink",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\n\t\"amount\":\"1.00\"\n}",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
    }
}