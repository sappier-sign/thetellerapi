<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 20/07/2017
 * Time: 11:26 PM
 */

namespace App;


use DateTime;
use Illuminate\Database\Eloquent\Model;
use SoapClient;

class Mtn extends Model
{
    private $details;
    private $mtn_debit;
    protected $url;
    protected $uName;
    protected $pWord;
    protected $wsdl;
    protected $vendorID;
    protected $apiKey;
    public $thirdPartyID;
    public $subscriberID;
    public $amount;

    protected $table = 'logs_mtn';
    protected $fillable = ['mesg', 'expiry', 'username', 'password', 'name', 'info', 'amt', 'mobile', 'billprompt', 'thirdpartyID'];

    public function __construct()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        $this->url      = env('MTN_URL');
        $this->username = env('MTN_USER_NAME');
        $this->password = env('MTN_PASSWORD');
        $this->wsdl     = array('trace' => 1, 'cache_wsdl' => 'WSDL_CACHE_NONE', 'location' => 'http://68.169.57.64:8080/transflow_webclient/services/InvoicingService.InvoicingServiceHttpSoap11Endpoint', 'connection_timeout' => 10);
        $this->vendorID = env('MTN_VENDOR_ID');
        $this->apiKey   = env('MTN_API_KEY');
    }

    public static function masked($string)
    {
        $length = strlen($string);
        $new_value = '';
        $count = 0;

        while ($count < $length) {
            $new_value .= '*';
            $count++;
        }
        return $new_value;
    }

    public function setDetails(array $details)
    {
        $this->details = $details;
    }

    public function getDetails()
    {
        return $this->details;
    }

    function getFunctions()
    {
        $client = new SoapClient($this->url);
        $response = $client->__getFunctions();
        return $response;
    }

    public function debit($number, $amt, $serviceName, $thirdpartyID)
    {
        $expiry = new DateTime('tomorrow');
        $expiry = $expiry->format('Y-m-d');
        $params = array
        (
            'mesg' => 'Request to pay for bill is being processed. Your invoice number is {inv}.',
            'expiry' => $expiry,
            'username' => $this->username,
            'password' => $this->password,
            'name' => 'PaySwitch Company Ltd.',
            'info' => $serviceName,
            'amt' => $amt,
            'mobile' => $number,
            'billprompt' => '2',
            'thirdpartyID' => $thirdpartyID
        );

        // Prepare and write the request data to our messages_logs.txt file
        Functions::writeMTN($header = "TTLR TO MTN REQUEST FOREIGN", $params);

        $this->mtn_debit = new Mtn();
        $this->mtn_debit->username = self::masked($this->mtn_debit['username']);
        $this->mtn_debit->password = self::masked($this->mtn_debit['password']);
        $this->mtn_debit->mesg = 'Request to pay for bill is being processed. Your invoice number is {inv}.';
        $this->mtn_debit->expiry = $expiry;
        $this->mtn_debit->name = 'PaySwitch Company Ltd.';
        $this->mtn_debit->info = $serviceName;
        $this->mtn_debit->amt = $amt;
        $this->mtn_debit->mobile = $number;
        $this->mtn_debit->billprompt = '2';
        $this->mtn_debit->thirdpartyID = $thirdpartyID;
        $this->mtn_debit->save();

        $client = new SoapClient($this->url, $this->wsdl);

        //check for faults in soap call
        $response = $client->__soapCall('postInvoice', array($params));

        if (is_soap_fault($response)) {
            return $response = 900;
        } else {
            // Prepare and write the response data to our messages_logs.txt file
            Functions::writeMTN($header = "MTN TO TTLR RESPONSE FOREIGN", $response);

            $response = get_object_vars($response);
            $response = get_object_vars($response['return']);

            $this->mtn_debit->responseCode = '1000';
            $this->mtn_debit->responseMessage = $response['responseMessage'];
            $this->mtn_debit->invoiceNo = $response['invoiceNo'];
            $this->mtn_debit->save();

            $invoiceNo = $response['invoiceNo'];
            $checkInvoice = $this->checkInvoice($invoiceNo);
            $checkStatus = $this->checkStatus($checkInvoice);
            return $checkStatus;
        }
    }

    public function debitOffline($number, $amt, $serviceName, $thirdpartyID)
    {
        $expiry = new DateTime('tomorrow');
        $expiry = $expiry->format('Y-m-d');
        $params = array
        (
            'mesg' => 'Request to pay for bill is being processed. Your invoice number is {inv}.',
            'expiry' => $expiry,
            'username' => $this->username,
            'password' => $this->password,
            'name' => 'PaySwitch Company Ltd.',
            'info' => $serviceName,
            'amt' => $amt,
            'mobile' => $number,
            'billprompt' => '2',
            'thirdpartyID' => $thirdpartyID
        );

        // Prepare and write the request data to our messages_logs.txt file
        Functions::writeMTN($header = "TTLR TO MTN REQUEST FOREIGN", $params);

        $this->mtn_debit = new Mtn();
        $this->mtn_debit->username = self::masked($this->mtn_debit['username']);
        $this->mtn_debit->password = self::masked($this->mtn_debit['password']);
        $this->mtn_debit->mesg = 'Request to pay for bill is being processed. Your invoice number is {inv}.';
        $this->mtn_debit->expiry = $expiry;
        $this->mtn_debit->name = 'PaySwitch Company Ltd.';
        $this->mtn_debit->info = $serviceName;
        $this->mtn_debit->amt = $amt;
        $this->mtn_debit->mobile = $number;
        $this->mtn_debit->billprompt = '2';
        $this->mtn_debit->thirdpartyID = $thirdpartyID;
        $this->mtn_debit->save();

        $client = new SoapClient($this->url, $this->wsdl);

        //check for faults in soap call
        $response = $client->__soapCall('postInvoice', array($params));

        if (is_soap_fault($response)) {
            return $response = 900;
        } else {
            // Prepare and write the response data to our messages_logs.txt file
            Functions::writeMTN($header = "MTN TO TTLR RESPONSE FOREIGN", $response);

            $response = get_object_vars($response);
            $response = get_object_vars($response['return']);

            $this->mtn_debit->responseCode = '1000';
            $this->mtn_debit->responseMessage = $response['responseMessage'];
            $this->mtn_debit->invoiceNo = $response['invoiceNo'] ?? "null";
            $this->mtn_debit->save();

            if ($response['responseCode'] === '0000') {
                return $checkStatus = $this->checkStatus('1000');
            }
            return $this->checkStatus($response['responseCode']);
        }
    }

    public function checkInvoice($invoiceNo)
    {
        $params = array
        (
            'username' => $this->username,
            'password' => $this->password,
            'invoiceNo' => $invoiceNo
        );

        // Prepare and write the request data to our messages_logs.txt file
        Functions::writeMTN($header = "TTLR TO MTN REQUEST FOREIGN", $params);

        $client = new SoapClient($this->url, $this->wsdl);

        //wait for 8 seconds and check for status of the invoice
        sleep(12);
        $response = $client->__soapCall('checkInvStatus', array($params));

        // Prepare and write the response data to our messages_logs.txt file
        Functions::writeMTN($header = "MTN TO TTLR RESPONSE FOREIGN", $response);

        //check for faults in soap Call
        if (is_soap_fault($response)) {
            return 900;
        } else {
            $response = get_object_vars($response);
            $response = get_object_vars($response['return']);
            $responseCode = $response['responseCode'];
            $response_to_array = [];

            if ($response['responseCode'] == '21VD') {
                $timeOut = 80;
                $try = 5;
                sleep(15);

                // Loop for 60 seconds
                while ($try < $timeOut) {

                    $response = $client->__soapCall('checkInvStatus', array($params));    // MAKE SOAP CALL

                    // Prepare and write the request data to our messages_logs.txt file
//                    Functions::writeMTN( $header = "TTLR TO MTN REQUEST FOREIGN", $response );

                    //	PARSE VALUES OF SOAP RESPONSE OBJECT INTO AN ARRAY
                    $response = get_object_vars(get_object_vars($response)['return']);

                    if ($response['responseCode'] == '21VD') {    //	CHECK IF INVOICE IS PENDING PAYMENT

                        $try += 10;    //	INCREMENT THE VALUE OF TRY

                    } elseif ($response['responseCode'] == '0000') { // Check if invoice has been paid
                        $responseCode = $response['responseCode'];
                        break;

                    } else {    //	IF INVOICE HAS NOT BEEN PAID
                        $responseCode = $response['responseCode'];
                        break;    // ASSIGN THE CODE STATUS OF THE INVOICE
                    }

                    sleep(10);    //WAIT FOR SOME SECONDS AND RUN THE LOOP AGAIN
                }
                $this->mtn_debit->responseCode = $response['responseCode'];
                $this->mtn_debit->responseMessage = $response['responseMessage'];
                $this->mtn_debit->save();
                return $responseCode;
            }
            $this->mtn_debit->responseCode = $response['responseCode'];
            $this->mtn_debit->responseMessage = $response['responseMessage'];
            $this->mtn_debit->save();
            $responseCode = $response['responseCode'];
            return $responseCode;
        }
    }

    public function checkInvoiceOffline($invoiceNo)
    {
        $params = array
        (
            'username' => $this->username,
            'password' => $this->password,
            'invoiceNo' => $invoiceNo
        );

        // Prepare and write the request data to our messages_logs.txt file
        Functions::writeMTN($header = "TTLR TO MTN REQUEST FOREIGN", $params);

        $client = new SoapClient($this->url, $this->wsdl);

        //wait for 8 seconds and check for status of the invoice
        sleep(12);
        $response = $client->__soapCall('checkInvStatus', array($params));

        // Prepare and write the response data to our messages_logs.txt file
        Functions::writeMTN($header = "MTN TO TTLR RESPONSE FOREIGN", $response);

        //check for faults in soap Call
        if (is_soap_fault($response)) {
            return 900;
        } else {
            $response = get_object_vars($response);
            $response = get_object_vars($response['return']);

            $this->mtn_debit = Mtn::where('invoiceNo', $invoiceNo)->first();
            $this->mtn_debit->responseCode = $response['responseCode'];
            $this->mtn_debit->responseMessage = $response['responseMessage'];
            $this->mtn_debit->save();

            return $this->checkStatus($response['responseCode']);
        }
    }

    public function checkStatus($status)
    {
        if (is_array($status)) {
            if (isset($status['return'])) {
                $status = get_object_vars($status['return'])['responseCode'];
            } else {
                $status = $status['responseCode'] ?? $status[1];
            }
        }
        switch ($status) {
            // successful transaction
            case '0000':
                return [100, $status];
                break;

            // insufficient funds
            case '527':
                return [101, $status];
                break;

            // Unregistered number for debiting
            case '515':
                return [102, $status];
                break;

            // wrong PIN or time out
            case '21VD':
                return [103, $status];
                break;

            // Transaction declined or terminated
            case '100':
                return [104, $status];
                break;

            // expired invoice
            case '21EX':
                return [103, $status];
                break;

            // Invalid amount or general failure
            case '22VD':
                return [105, $status];
                break;

            // Duplicate transaction id
            case '03':
                return [106, $status];
                break;

            // Network failure
            case '682':
                return [107, $status];
                break;

            case '11SY':
                return [110, $status];
                break;

            case '1000':
                return [111, $status];
                break;

            default:
                return [900, $status];
                break;
        }
    }

    public function credit($subscriberID, $amount, $transactionID)
    {
        $balance = $this->getBalance();
        $balance = floatval($balance);
        $amount = floatval($amount);

//        if ($amount >= 300){
//            return $this->checkStatus( '527' );
//        }

        if ($balance >= $amount) {
            $this->url = 'http://68.169.59.49:8080/vpova/services/vpovaservice?wsdl';
            $params = array
            (
                'vendorID' => $this->vendorID,
                'subscriberID' => $subscriberID,
                'thirdpartyTransactionID' => $transactionID,
                'amount' => $amount,
                'apiKey' => $this->apiKey
            );

            // Prepare and write the request data to our messages_logs.txt file
            Functions::writeMTN($header = "TTLR TO MTN REQUEST FOREIGN", $params);

            $client = new SoapClient($this->url);
            $response = $client->__soapCall('DepositToWallet', array($params));

            // Prepare and write the response data to our messages_logs.txt file
            Functions::writeMTN($header = "MTN TO TLLR RESPONSE FOREIGN", $response);

            $response = get_object_vars($response);
            $response = $response['return'];
            $response = get_object_vars($response);

            #Get and Set response fields
            $details = $this->getDetails();
            $details[':msg_015'] = $response['responseCode'];
            $details[':msg_017'] = $response['responseMessage'];

            $response = $response['responseCode'];

            if ($response === '01') {
                $statusCode = '0000';
            } else {
                $statusCode = $response;
            }

            return $this->checkStatus($statusCode);
        } else {
            return $this->checkStatus('527');
        }
    }

    public function getBalance()
    {
        $this->url = 'http://68.169.59.49:8080/vpova/services/vpovaservice?wsdl';
        $params = array
        (
            'vendorID' => $this->vendorID,
            'thirdpartyTransactionID' => 'MqEYQxz09438',
            'apiKey' => $this->apiKey
        );

        $client = new SoapClient($this->url);
        $response = $client->__soapCall('getAccountBalance', array($params));
        $response = get_object_vars($response);
        $response = $response['return'];
        $response = get_object_vars($response);
        return (float)$response['balance'];

    }
}