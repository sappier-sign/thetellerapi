<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 17/08/2017
 * Time: 5:25 PM
 */

namespace App;


use HtmlParser\ParserDom;
use Illuminate\Database\Eloquent\Model;

class Payswitch extends Model
{
    public static function registerPurchase($amount)
    {
        $order_id   =   '00'.time();
        $curl = curl_init('https://web.rbsuat.com/pcbpc/rest/register.do');
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $curl, CURLOPT_POST, 1 );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt($curl, CURLOPT_POSTFIELDS, "language=en&userName=payswitch-api&password=payswitch&orderNumber=$order_id&amount=$amount&currency=810&returnUrl=https://api.theteller.net/v1.1/3ds/response");

        $response = json_decode(curl_exec($curl), true);
        return (isset($response['orderId']))? $response['orderId'] : false;

    }

    public static function payment()
    {
        $data = [
            'language' => 'en',
            'userName' => 'payswitch-api',
            'password' => 'payswitch',
            'MDORDER'  => self::registerPurchase(100),
            '$PAN'     => '4111111111111111',
            '$CVC'     => '123',
            'YYYY'     => '2019',
            'MM'       => '12',
            'TEXT'     => 'solomon'
        ];

        $curl = curl_init('https://web.rbsuat.com/pcbpc/rest/paymentorder.do');
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $curl, CURLOPT_POST, 1 );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );

        return $response = json_decode(curl_exec($curl), true);

    }

    public static function getOrderPaymentState($order_id)
    {

        $curl = curl_init('https://web.rbsuat.com/pcbpc/rest/getOrderStatus.do?userName=payswitch-api&password=payswitch&orderId='.$order_id);
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, false );

        $response = json_decode(curl_exec($curl), true);

        if (isset($response['ErrorCode']) && (int) $response['ErrorCode'] === 0){

            $status = 'not found';
            $code   = 100;
            $reason = 'order does not exist';

        } elseif ( (int) $response['OrderStatus'] === 0){

            $status = 'declined';
            $code   = 100;
            $reason = 'order registered but not paid!';

        } elseif ( (int) $response['OrderStatus'] === 2){

            $status = 'approved';
            $code   = '000';
            $reason = 'order paid successfully';

        } else {

            $status = 'declined';
            $code   = 100;
            $reason = $response[''];
        }
    }

    public static function acsResponse($url, $pares, $md)
    {
        $data = [
            'PaRes' =>  $pares,
            'MD'    =>  $md
        ];

        $curl = curl_init($url);
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $curl, CURLOPT_POST, 1 );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );

        return (curl_exec($curl));
    }
}