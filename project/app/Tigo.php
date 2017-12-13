<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 21/07/2017
 * Time: 11:07 PM
 */

namespace App;


use Illuminate\Database\Eloquent\Model;

class Tigo extends Model
{
    private $details;
    private $log;
    protected $userName;
    protected $password;
    protected $consumerID;
    protected $webUser;
    protected $wPassword;
    protected $msisdn;
    public $transactionID;
    private $url;

    function __construct ()
    {
        $this->userName = env('TGO_USER_NAME');
        $this->password = env('TGO_PASSWORD');
        $this->consumerID = env('TGO_CONSUMER_ID');
        $this->webUser = env('TGO_WEB_USER');
        $this->wPassword = env('TGO_WPASSWORD');
        $this->msisdn = env('TGO_MSISDN');
        $this->externalCategory = env('TGO_EXTERNAL_CATEGORY');
        $this->externalChannel = env('TGO_EXTERNAL_CHANNEL');
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

    /**
     * @return mixed
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param mixed $log
     */
    public function setLog($log)
    {
        $this->log = $log;
    }



    /**
     * tigo cash debit function
     * @params string tigoNumber
     * @params double amount
     * @returns int resp: 100 for successful transactions and 900 for failed
     */

    function debit( $number, $amount, $serviceName, $transactionID  )
    {
        // $item is required and should be generated dynamically, it describes what the payment is for

        $this->url = "https://accessgw.tigo.com.gh:8443/live/PurchaseInitiate";
        $data = '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:v1="http://xmlns.tigo.com/MFS/PurchaseInitiateRequest/V1" xmlns:v2="http://xmlns.tigo.com/ParameterType/V2" xmlns:v3="http://xmlns.tigo.com/RequestHeader/V3">
			<SOAP-ENV:Header xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
			<cor:debugFlag xmlns:cor="http://soa.mic.co.af/coredata_1">true</cor:debugFlag>
			<wsse:Security>
			<wsse:UsernameToken>
			<wsse:Username>'.$this->userName.'</wsse:Username>
			<wsse:Password>'.$this->password.'</wsse:Password>
			</wsse:UsernameToken>
			</wsse:Security>
			</SOAP-ENV:Header>
			<SOAP-ENV:Body>
			<v1:PurchaseInitiateRequest>
			<v3:RequestHeader>
			<v3:GeneralConsumerInformation>
			<v3:consumerID>'.$this->consumerID.'</v3:consumerID>
			<v3:transactionID>Pay001</v3:transactionID>
			<v3:country>GHA</v3:country>
			<v3:correlationID>Pay01</v3:correlationID>
			</v3:GeneralConsumerInformation>
			</v3:RequestHeader>
			<v1:requestBody>
			<v1:customerAccount>
			<v1:msisdn>233'.substr($number, 1).'</v1:msisdn>
			</v1:customerAccount>
			<v1:initiatorAccount>
			<v1:msisdn>233276203025</v1:msisdn>
			</v1:initiatorAccount>
			<v1:paymentReference>'.$transactionID.'</v1:paymentReference>
			<v1:externalCategory>'.$this->externalCategory.'</v1:externalCategory>
			<v1:externalChannel>'.$this->externalChannel.'</v1:externalChannel>
			<v1:webUser>'.$this->webUser.'</v1:webUser>
			<v1:webPassword>'.$this->wPassword.'</v1:webPassword>
			<v1:merchantName>payswitch</v1:merchantName>
			<v1:itemName>PaySwitch Company Ltd.</v1:itemName>
			<v1:amount>'.$amount.'</v1:amount>
			<v1:minutesToExpire>2</v1:minutesToExpire>
			<v1:notificationChannel>2</v1:notificationChannel>
			</v1:requestBody>
			</v1:PurchaseInitiateRequest>
			</SOAP-ENV:Body>
			</SOAP-ENV:Envelope>';

        $curl = curl_init();
        curl_setopt( $curl, CURLOPT_URL, $this->url );
        curl_setopt(
            $curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/xml',
                'SOAPaction: http://xmlns.tigo.com/Service/PurchaseInitiate/V1/PurchaseInitiatePortType/PurchaseInitiateRequest'
            )
        );

        curl_setopt( $curl, CURLOPT_POST, 1 );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, "$data" );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 2 );
        curl_setopt( $curl, CURLOPT_SSLCERT, '/etc/pki/tls/certs/ag_partner.crt.pem' );
        curl_setopt( $curl, CURLOPT_SSLCERTTYPE, 'PEM' );
        curl_setopt( $curl, CURLOPT_SSLKEY, '/etc/pki/tls/certs/ag_partner.key.pem' );
        curl_setopt( $curl, CURLOPT_SSLCERTPASSWD, 'tigo123!' );
        curl_setopt( $curl, CURLOPT_SSLKEYPASSWD, 'tigo123!' );

        if ( $response = curl_error( $curl ) ){
            // if error occurs and request fails. At this point, request has not been initiated and causes of failure could be due to errors in code due to recent changes made either locally or by Tigo
            mail( 'sappiah@payswitch.com.gh', 'tiGO Cash Failure', $response );
            return 900;
        } else {
            // Prepare and write the request data to our messages_logs.txt file
            xml_parse_into_struct( xml_parser_create( ), $data, $one, $two );
            Functions::writeTigo( $header = "TTLR TO TIGO REQUEST FOREIGN", $one );

            $response = curl_exec( $curl );

            // Prepare and write the request data to our messages_logs.txt file
            xml_parse_into_struct( xml_parser_create( ), $response, $array1, $array2 );
            Functions::writeTigo( $header = 'TIGO TO TTLR RESPONSE FOREIGN', $array1 );

            if (isset($array1[ 10 ][ "value" ]) && $array1[ 10 ][ "value" ] === "purchaseinitiate-3022-0001-S"){
                return $this->isCallBack( $transactionID );
            } elseif ($array1[ 14 ][ "value" ] === "purchaseinitiate-3022-4501-V"){
                $response = $this->tranStatus('purchaseinitiate-3022-4501-V');
            } elseif ($array1[ 14 ][ "value" ] === "purchaseinitiate-3022-3002-E"){
                $response = $this->tranStatus('purchaseinitiate-3022-3002-E');
            } else {
                $response = $this->tranStatus('purchase-3008-3017-F');
            }
            return $response;
        }
    }

    public function credit( $tigoNumber, $amount, $transactionID )
    {
        $this->url = "https://accessgw.tigo.com.gh:8443/live/Purchase?wsdl";
        $data ='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:v2="http://xmlns.tigo.com/MFS/PurchaseRequest/V2" xmlns:v3="http://xmlns.tigo.com/RequestHeader/V3" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:v21="http://xmlns.tigo.com/ParameterType/V2">
			<soapenv:Header  xmlns:cor="http://soa.mic.co.af/coredata_1">
			<cor:debugFlag>true</cor:debugFlag>
			<wsse:Security>
			<wsse:UsernameToken>
			<wsse:Username>'.$this->userName.'</wsse:Username>
			<wsse:Password>'.$this->password.'</wsse:Password>
			</wsse:UsernameToken>
			</wsse:Security>
			</soapenv:Header>
			<soapenv:Body>
			<v2:PurchaseRequest>
			<v3:RequestHeader>
			<v3:GeneralConsumerInformation>
			<v3:consumerID>'.$this->consumerID.'</v3:consumerID>
			<!--Optional:-->
			<v3:transactionID>'.$transactionID.'</v3:transactionID>
			<v3:country>GHA</v3:country>
			<v3:correlationID>'.$transactionID.'</v3:correlationID>
			</v3:GeneralConsumerInformation>
			</v3:RequestHeader>
			<v2:requestBody>
			<v2:sourceWallet>
			<!--You have a CHOICE of the next 2 items at this level-->
			<v2:msisdn>'.$this->msisdn.'</v2:msisdn>
			</v2:sourceWallet>
			<!--Optional:-->
			<v2:targetWallet>
			<!--You have a CHOICE of the next 2 items at this level-->
			<v2:msisdn>233'.substr($tigoNumber, 1).'</v2:msisdn>
			</v2:targetWallet>
			<v2:password>3025</v2:password>
			<v2:amount>'.$amount.'</v2:amount>
			<v2:internalSystem>Yes</v2:internalSystem>
			<v2:additionalParameters>
			<!--Zero or more repetitions:-->
			<v21:ParameterType>
			<v21:parameterName>ExternalChannel</v21:parameterName>
			<v21:parameterValue>default</v21:parameterValue>
			</v21:ParameterType>
			<v21:ParameterType>
			<v21:parameterName>ExternalCategory</v21:parameterName>
			<v21:parameterValue>default</v21:parameterValue>
			</v21:ParameterType>
			<v21:ParameterType>
			<v21:parameterName>WebUser</v21:parameterName>
			<v21:parameterValue>'.$this->webUser.'</v21:parameterValue>
			</v21:ParameterType>
			<v21:ParameterType>
			<v21:parameterName>WebPassword</v21:parameterName>
			<v21:parameterValue>'.$this->wPassword.'</v21:parameterValue>
			</v21:ParameterType>
			</v2:additionalParameters>
			</v2:requestBody>
			</v2:PurchaseRequest>
			</soapenv:Body>
			</soapenv:Envelope>';

        $curl = curl_init();
        curl_setopt( $curl, CURLOPT_URL, $this->url );
        curl_setopt
        (
            $curl, CURLOPT_HTTPHEADER, array
            (
                'Content-Type: application/xml',
                'SOAPaction: http://xmlns.tigo.com/Service/PurchaseInitiate/V1/PurchaseInitiatePortType/PurchaseInitiateRequest'
            )
        );

        curl_setopt( $curl, CURLOPT_POST, 1 );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, "$data" );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 2 );
        curl_setopt( $curl, CURLOPT_SSLCERT, '/etc/pki/tls/certs/ag_partner.crt.pem' );
        curl_setopt( $curl, CURLOPT_SSLCERTTYPE, 'PEM' );
        curl_setopt( $curl, CURLOPT_SSLKEY, '/etc/pki/tls/certs/ag_partner.key.pem' );
        curl_setopt( $curl, CURLOPT_SSLCERTPASSWD, 'tigo123!' );
        curl_setopt( $curl, CURLOPT_SSLKEYPASSWD, 'tigo123!' );

        if ( $response = curl_error( $curl ) )
        {
            // if error occurs and request fails. At this point, request has not been initiated and causes of failure could be due to errors in code due to recent changes made either locally or by Tigo
            mail( 'sappiah@payswitch.com.gh', 'tiGO Cash Failure', $response );
            return 900;
        } else {
            // Prepare and write the request data to our all.txt file
            xml_parse_into_struct( xml_parser_create( ), $data, $one, $two );
            Functions::writeTigo( $header = "TTLR TO TIGO REQUEST FOREIGN", $one );

            // an int of value 100 is required if request is approved successfully, else 300 will be returned
            $response = curl_exec( $curl );
            xml_parse_into_struct( xml_parser_create( ), $response, $array1, $array2 );
            $response = $array1;

            // Prepare and write the request data to our all.txt file
            Functions::writeTigo( $header = 'TIGO TO TTLR RESPONSE FOREIGN', $array1 );

            if ( isset( $response[ 10 ][ 'value' ] ) && $response[ 10 ][ 'value' ]  == "purchase-3008-0000-S" ){
                return $this->tranStatus( 'purchase-3008-0000-S' );
            } else {
                return $this->tranStatus( $response[ 14 ][ 'value' ] );
            }
        }
    }

    /**
     * checks if request has been approved by user
     * @params string $correlation ID
     * @returns int $resp: 100 on success and 300 on fail
     */
    function isCallBack( $transactionID )
    {
        $sleep  = 10;
        $end    = 125;
        $json   = null;

        // wait for 25 seconds and check if request has been approved
        sleep( $sleep );
        while ( $sleep < $end )
        {
            // open response file and read content in variable $content
            $file = '/var/www/api.theteller.net/public_html/logs/tigo.txt';
            $file = fopen( $file, 'r' );
            $content = '';
            while ( !feof( $file ) )
            {
                $content .= fgets( $file );
            }
            fclose( $file );

            // convert the content of response file into an array
            $json = json_decode( "[ $content ]", true );
            foreach ( $json as $array ){
                // loop and iterate through each array and check for corresponding correlation ID
                // $array = get_object_vars( $array );
                if ( isset( $array[ 'correlationID' ] ) && $array[ 'correlationID' ] == $transactionID ){
                    $sleep = $end;
                    $resp = $array[ 'code' ];
                    break;
                } else {
                    $resp = 'purchase-3008-3017-F';
                }
            }
            if ( $sleep < $end ) {
                $sleep += 2;
                sleep( 2 );
            } else {
                break;
            }

        }

        return $this->tranStatus( $resp );
    }

    function tranStatus( $value )
    {
        switch ( $value )
        {
            // successful transaction
            case 'purchase-3008-0000-S':
                return [100, $value];
                break;

            // insufficient funds
            case 'purchase-3008-3017-E':
                return [101, $value];
                break;

            // Unregistered number for debiting
            case 'purchase-3008-4501-V':
                return [102, $value];
                break;

            // Unregistered number for crediting
            case 'purchase-3008-3037-E':
                return [102, $value];
                break;

            // wrong PIN or time out
            case 'purchase-3008-3017-F':
                return [103, $value];
                break;

            // Invalid amount or general failure
            case 'purchaseinitiate-3022-4501-V':
                return [105, $value];
                break;

            // Invalid amount or general failure
            case 'purchaseinitiate-3022-3002-E':
                return [107, $value];
                break;

            default:
                return [900, $value];
                break;
        }
    }
}