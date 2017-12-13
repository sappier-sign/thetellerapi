<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 04/08/2017
 * Time: 4:14 AM
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class Vodafone extends Model
{
    protected $table = 'logs_vodafone';
    private $vendor_code;
    private $token;
    private $pin;
    private $url;

    public function __construct()
    {
        $this->vendor_code  = env('VF_VENDOR_CODE');
        $this->token        = env('VF_TOKEN');
        $this->pin          = env('VF_PIN');
        $this->url          = env('VF_URL');
    }

    public static function debit($number, $amount, $transaction_id, $voucher_code)
    {
        $vodafone = new Vodafone();
        $vodafone->amount           = $amount;
        $vodafone->phone_number     = $number;
        $vodafone->transaction_id   = $transaction_id;
        $vodafone->voucher_code     = $voucher_code;
        $vodafone->save();

        $request = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ser=\"http://services.apiservices.vodafone.com.gh/\">\r\n\t<soapenv:Header/>\r\n\t<soapenv:Body>\r\n\t\t<ser:paymentService>\r\n\t\t\t<PaymentRequest>\r\n\t\t\t\t<Token>".$vodafone->token."</Token>\r\n\t\t\t\t<ResultURL>https://api.theteller.net/v1.1/vodafone/response</ResultURL>\r\n\t\t\t\t<VFPin>".$vodafone->pin."</VFPin>\r\n\t\t\t\t<VoucherCode>$voucher_code</VoucherCode>\r\n\t\t\t\t<PhoneNumber>$number</PhoneNumber>\r\n\t\t\t\t<Amount>$amount</Amount>\r\n\t\t\t\t<VendorCode>".$vodafone->vendor_code."</VendorCode>\r\n\t\t\t\t<TransactionID>$transaction_id</TransactionID>\r\n\t\t\t</PaymentRequest>\r\n\t\t</ser:paymentService>\r\n\t</soapenv:Body>\r\n</soapenv:Envelope>";

        return self::postRequest($request, $transaction_id);
    }

    public static function credit($number, $amount, $transaction_id)
    {
        $vodafone = new Vodafone();
        $vodafone->amount           = $amount;
        $vodafone->phone_number     = $number;
        $vodafone->transaction_id   = $transaction_id;
        $vodafone->transaction_type = 'credit';
        $vodafone->save();

        $request = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ser=\"http://services.apiservices.vodafone.com.gh/\">\r\n\t<soapenv:Header/>\r\n\t<soapenv:Body>\r\n\t\t<ser:creditService>\r\n\t\t\t<CreditRequest>\r\n\t\t\t\t<Token>".$vodafone->token."</Token>\r\n\t\t\t\t<ResultURL>https://api.theteller.net/v1.1/vodafone/response</ResultURL>\r\n\t\t\t\t<VFPin>".$vodafone->pin."</VFPin>\r\n\t\t\t\t<PhoneNumber>$number</PhoneNumber>\r\n\t\t\t\t<Amount>$amount</Amount>\r\n\t\t\t\t<VendorCode>".$vodafone->vendor_code."</VendorCode>\r\n\t\t\t\t<TransactionID>$transaction_id</TransactionID>\r\n\t\t\t</CreditRequest>\r\n\t\t</ser:creditService>\r\n\t</soapenv:Body>\r\n</soapenv:Envelope>";

        return self::postRequest($request, $transaction_id);

    }

    public static function postRequest($request, $transaction_id)
    {
        $vodafone = new Vodafone();
        $curl = curl_init($vodafone->url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ["cache-control: no-cache", "content-type: text/xml",]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);

        xml_parse_into_struct(xml_parser_create(), $request, $value, $index);
        Functions::writeVodafone('TTLR VODAFONE REQUEST FOREIGN', $value);

        $errors = curl_error($curl);
        if ($errors <> false){
            return $errors;
        } else {

            return self::response(curl_exec($curl), $transaction_id);
        }

    }

    public static function response($response, $transaction_id)
    {
        xml_parse_into_struct(xml_parser_create(), $response, $data, $index);

        $vodafone = Vodafone::where('transaction_id', $transaction_id)->first();

        $vodafone->status_code     = (isset($data[4]))? $data[4]['value'] : 'nothing';
        $vodafone->status_message  = (isset($data[5]))? $data[5]['value'] : 'nothing';
        $vodafone->ext_trans_id    = (isset($data[6]))? $data[6]['value'] : 'nothing';
        $vodafone->save();

        $results = [
            'statusCode'    =>  $vodafone->status_code,
            'statusMessage' =>  $vodafone->status_message,
            'ext_trans_id'  =>  $vodafone->ext_trans_id
        ];

        Functions::writeVodafone('VODAFONE TTLR RESPONSE FOREIGN', ['array' => $results]);
        if ($results['statusCode'] == 0){
            return self::getTransactionStatus($results['ext_trans_id']);
        } else {
            return [107];
        }
    }

    public static function getTransactionStatus($ext_trans_id)
    {
        $end = 2;
        sleep(1);

        while (true){
            $GLOBALS['transaction'] = Vodafone::where('ext_trans_id', $ext_trans_id)->first();
            if ($GLOBALS['transaction']->result_code <> null && $end < 10){
                break;
            } elseif ($end >= 10){
                break;
            }
            sleep(2);
            $end += 2;
        }

        switch ($GLOBALS['transaction']->result_code){
            case '0':
                $response_code = 100;
                break;
            case '1':
                $response_code = 101;
                break;
            case '2002':
                $response_code = 102;
                break;
            default:
//                $response_code = 107;
                $response_code = $GLOBALS['transaction']->result_code;
                break;
        }

        return [$response_code];
    }

}