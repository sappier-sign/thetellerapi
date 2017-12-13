<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 21/07/2017
 * Time: 3:17 PM
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class Airtel extends Model
{
    private $details;
    protected $client_id;
    protected $client_secret;
    protected $merchant_number;
    protected $nick_name;
    protected $url;
    private $end_point;
    private $ts;
    public $amount;
    public $customer_number;
    public $transaction_id;
    public $reference;

    function __construct( )
    {
        $this->client_id        = env('ATL_CLIENT_ID');
        $this->client_secret    = env('ATL_CLIENT_SECRET');
        $this->nick_name        = env('ATL_NICK_NAME');
        $this->merchant_number  = env('ATL_MERCHANT_NUMBER');
        $this->ts               = date('Y-m-d H:i:s');
    }

    /**
     * @return mixed
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param mixed $details
     */
    public function setDetails($details)
    {
        $this->details = $details;
    }

    /*
    * Airtel debit function
    * @params string customer_number
    * @params double amount
    * @params string reference
    * @returns int 100 on success and 900 on fail
    */
    public function debit( $customer_number, $amount, $serviceName, $transactionRRN )
    {
        $serviceName = substr( $serviceName, 0, 15 );

        $this->end_point = 'debitCustomerWallet';
        $this->url = "https://appsnmobileagent.com:8201/$this->end_point";
        $data = array
        (
            "customer_number" 			=> $customer_number,
            "merchant_number" 			=> $this->merchant_number,
            "amount" 					=> $amount,
            "exttrid" 					=> $transactionRRN,
            "reference" 				=> $serviceName,
            "nickname" 					=> $this->nick_name,
            "ts" 						=> $this->ts
        );

        // generating Authorization signature with sha256 encryption
        $data = json_encode( $data );
        $body = "/$this->end_point$data";
        $signature = hash_hmac( 'sha256', $body, $this->client_secret );
        $auth = "$this->client_id:$signature";
        $header = array(
            "Authorization: $auth"
        );

        $curl = curl_init();
        curl_setopt( $curl, CURLOPT_URL, $this->url );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $curl, CURLOPT_HEADER, false );
        curl_setopt( $curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY );
        curl_setopt( $curl, CURLOPT_HTTPHEADER, $header );
        curl_setopt( $curl, CURLOPT_POST, true );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
        curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $curl, CURLOPT_POSTREDIR, 3 );

        $request = json_decode( $data, true );

        // Prepare and write the request data to our messages_logs.txt file
        Functions::writeAirtel( $header = "TTLR TO AIRTEL REQUEST FOREIGN", $request );
        $details = $this->getDetails();
        $details[':msg_004'] = 'RES';

        if ( $response = curl_error( $curl ) )
        {
            // if error occurs and request fails. At this point, request has not been initiated and causes of failure could be due to errors in code due to recent changes made either locally or by Airtel
            mail( 'sappiah@payswitch.com.gh', 'Airtel Money', json_encode( $response ) );
            return $this->tranStatus( 900 );
        }

        else
        {
            // open and read response file for call back response from Airtel
            $response = curl_exec( $curl );

            $response = json_decode( $response, true );

            // Prepare and write the response data to our messages_logs.txt file
            Functions::writeAirtel( $header = "AIRTEL TTLR REPONSE FOREIGN", $response );

            curl_close( $curl );

            if ( $response[ 'resp_code' ] == '121' )
            {
                $start = 10;
                $add = 5;
                sleep( 10 );
                while ( $start < 100 )
                {
                    $file = '/var/www/api.theteller.net/public_html/logs/airtel.txt';

                    // open response file and read content in variable $content
                    $fopen = fopen( $file, 'r' );
                    $content = fgets( $fopen );
                    fclose( $fopen );

                    $responses = explode("[break]", $content);

                    foreach ($responses as $this_response) {
                        $_response = json_decode($this_response, true);
                        if (count($_response)){
                            if ($_response['trans_ref'] == $transactionRRN) {
                                $response = $_response;
                                $start = 100;
                            } else {
                                $response = '00068';
                            }
                        }

                    }

                    $start += 2;
                    if ($start < 100){
                        sleep( 2 );
                    } else {
                        break;
                    }
                }

                if ( is_array ( $response ) ) {
                    $resp = $this->tranStatus( $response[ 'trans_status' ] );
                } else {
                    $resp = $this->tranStatus( $response );
                }

            } else {
                $resp = $this->tranStatus ( $response[ 'resp_code' ] );
                $details[':msg_015'] = $response['resp_code'];
                $details[':msg_017'] = $response['resp_desc'];
            }

            return $resp;
        }

    }

    /*
    * function that checks the status of transactions
    * @params string $responseCode
    * @returns int - 100 on success
    */
    public function tranStatus( $responseCode )
    {
        switch ( $responseCode )
        {
            // successful transaction
            case '200':
                return [100, $responseCode];
                break;

            // insufficient funds
            case '60019':
                return [101, $responseCode];
                break;

            // Unregisterdd number for debiting
            case '102':
                return [102, $responseCode];
                break;

            // Unregisterdd number for crediting
            case '99051':
                return [102, $responseCode];
                break;

            // wrong PIN or time out
            case '00068':
                return [103, $responseCode];
                break;

            // Transaction declined or terminted
            case '114':
                return [104, $responseCode];
                break;

            // Transaction declined or terminted
            case '107':
                return [104, $responseCode];
                break;

            // Invalid amount or general failure
            case '104':
                return [105, $responseCode];
                break;

            // Invalid amount
            case '010022':
                return [105, $responseCode];
                break;

            // Invalid amount
            case '00017':
                return [106, $responseCode];
                break;

            // NetWork Busy
            case '116':
                return [110, $responseCode];
                break;

            // request failed
            default:
                return [900, $responseCode];
                break;
        }
    }

    public function credit( $customer_number, $amount, $description, $transactionRRN )
    {
        $description = substr( $description, 0, 15 );

        $this->end_point = 'creditCustomerWallet';
        $this->url = "https://appsnmobileagent.com:8201/$this->end_point";

//        if ((float) $amount >= 300.00){
//            return $this->tranStatus( '00017' ) ;
//        }

        $data = array
        (
            "customer_number" 			=> $customer_number,
            "merchant_number" 			=> $this->merchant_number,
            "amount" 					=> $amount,
            "exttrid" 					=> $transactionRRN,
            "reference" 				=> $description,
            "ts" 						=> $this->ts
        );

        $data = json_encode( $data );
        $body = "/$this->end_point$data";
        $signature = hash_hmac( 'sha256', $body, $this->client_secret );
        $auth = "$this->client_id:$signature";
        $header = array(
            "Authorization: $auth"
        );

        $curl = curl_init();
        curl_setopt( $curl, CURLOPT_URL, $this->url );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $curl, CURLOPT_HEADER, false );
        curl_setopt( $curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY );
        curl_setopt( $curl, CURLOPT_HTTPHEADER, $header );
        curl_setopt( $curl, CURLOPT_POST, true );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );

        $request = json_decode( $data, true );

        // Prepare and write the request data to our messages_logs.txt file
        Functions::writeAirtel( $header = "TTLR TO AIRTEL REQUEST FOREIGN", $request );

        if ( ($response = curl_error( $curl ) ) )
        {
            mail( 'sappiah@payswitch.com.gh', 'Airtel Money', json_encode( $response ) );
            return $this->tranStatus( 900 );
        }

        else
        {
            $response = curl_exec( $curl );
            $response = json_decode($response, true);

            curl_close( $curl );

            // Prepare and write the request data to our messages_logs.txt file
            Functions::writeAirtel( $header = "AIRTEL TO TTLR REPONSE FOREIGN", $response );

            return $this->tranStatus( $response [ 'resp_code' ] ) ;
        }

    }
}