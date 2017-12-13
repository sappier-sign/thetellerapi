<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 16/08/2017
 * Time: 11:09 AM
 */

namespace App;

use HtmlParser\ParserDom;
use Illuminate\Database\Eloquent\Model;
use Mockery\Exception;

class Ghlink extends Model
{

    public static function encryptRequest(Array $request)
    {
        $redirect           = 'https://gigs-test.ghipss.com:7543/EcomPayment/RedirectAuthLink';
        $test               = 'https://gigs-test.ghipss.com:7543/EcomPayment/DirectAuthLink';
        $live               = 'https://gigs.ghipss.com:7543/EcomPayment/DirectAuthLink';
        $amount             = str_replace('.','', $request['amount']);
        $PurchaseAmt        = str_pad($amount, 12, '0', STR_PAD_LEFT);
        $MerID              = env('GHL_MER_ID');
        $AcqID              = env('GHL_ACQ_ID');
        $MerIDTest          = env('GHL_MER_ID_TEST');
        $AcqIDTest          = env('GHL_ACQ_ID_TEST');
        $PasswordTest       = env('GHL_PASSWORD_TEST');
        $PurchaseCurrency   = 936;
        $PurchaseCurrencyExponent   = 2;
        $Password           = env('GHL_PASSWORD');
        $OrderID            = time();
        $CaptureFlag        = 'M';
        $Signature          = base64_encode(pack('H*', sha1($PasswordTest.$MerIDTest.$AcqIDTest.$OrderID.$PurchaseAmt.$PurchaseCurrency)));
        $SignatureMethod    = 'SHA1';
        $MerRespURL         = 'https://api.theteller.net/v1.1/ghlink/response';

        $curl = curl_init($redirect);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POSTFIELDS, "Version=1.0.0&CardNo=6086320010001226&CardExpDate=0518&MerID=$MerIDTest&PurchaseAmt=$PurchaseAmt&AcqID=$AcqIDTest&OrderID=$OrderID&PurchaseCurrency=$PurchaseCurrency&CaptureFlag=$CaptureFlag&Signature=$Signature&SignatureMethod=$SignatureMethod&PurchaseCurrencyExponent=$PurchaseCurrencyExponent&MerRespURL=$MerRespURL");

        return $response = curl_exec($curl);

        if ($response <> false){
            $html = new ParserDom($response);
            $reason         = $html->find('input[name=ReasonCodeDesc]', 0)->getAttr('value');
            $responseCode   = $html->find('input[name=ResponseCode]', 0)->getAttr('value');
            $reasonCode     = $html->find('input[name=ReasonCode]', 0)->getAttr('value');
            $orderId        = $html->find('input[name=OrderID]', 0)->getAttr('value');
            $referenceNo    = $html->find('input[name=ReferenceNo]', 0)->getAttr('value');
            $authCode       = $html->find('input[name=AuthCode]', 0)->getAttr('value');
            return [
                'reason'        =>  $reason,
                'transaction'   =>  $orderId,
                'code'          =>  $responseCode
            ];
        } else {
            return curl_error($curl);
        }

    }
}