<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 21/07/2017
 * Time: 1:10 PM
 */

namespace App;


use Illuminate\Database\Eloquent\Model;

class Zenith extends Model
{
    protected 	$globalPayId;
    public 		$expMonth;
    public 		$expYear;
    public 		$description;
    public 		$transactionID;
    public 		$referenceID;
    public 		$cardNumber;
    public 		$orderID;
    private     $details;
    protected $table = 'logs_zenith';

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

    private static function theJson ( $cardNumber, $expMonth, $expYear, $cvv, $amount, $mode, $description, $orderID, $referenceID )
    {
        $jrequest = array
        (
            "PaymentRequest" =>  array(
                "Merchant" => array(
                    "GlobalPayID" => env('ZEN_GP')
                ),
                "Card" => array(
                    "CardNumber" => $cardNumber,
                    "CardExpiryMonth" => $expMonth,
                    "CardExpiryYear" => $expYear,
                    "CardCvv" => $cvv
                ),
                "Payment" => array(
                    "Description" => $description,
                    "ReferenceID" => $referenceID,
                    "OrderID" => $orderID,
                    "Amount" => $amount
                ),
                "mode" => $mode
            )
        );

        return json_encode( $jrequest );

    }

    public static function debit( $cardNumber, $expMonth, $expYear, $cvv, $amount, $mode, $description, $orderID, $referenceID, $response_url )
    {
        $request = self::theJson( $cardNumber, $expMonth, $expYear, $cvv, $amount, $mode, $referenceID, $referenceID, $referenceID );
        $requestLen = strlen( $request );
        $header = array( "Content-Type: application/json", "Content-Length: $requestLen" );

        // $curl = curl_init( 'http://196.216.180.24/API/api/Pay' );
//        https://www.zenithbank.com.gh/api.globalpay/api/Pay
        $curl = curl_init( 'https://www.zenithbank.com.gh/api.globalpay/api/Pay' );
        curl_setopt( $curl, CURLOPT_HTTPHEADER, $header );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $curl, CURLOPT_POST, 1 );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $request );

        if ( curl_error( $curl ) ){
            return 900;
        } else {
            #Save request to the logs_zenith table
            $zenith = new Zenith();
            $zenith->card_number    =   substr($cardNumber, 0, 6).'******'.substr($cardNumber, -4);
            $zenith->cvv            =   '***';
            $zenith->expiry_month   =   $expMonth;
            $zenith->expiry_year    =   $expYear;
            $zenith->description    =   $description;
            $zenith->reference_id   =   $referenceID;
            $zenith->order_id       =   $orderID;
            $zenith->amount         =   $amount;
            $zenith->mode           =   $mode;
            $zenith->response_url   =   $response_url;
            $zenith->save();

            #Write the request data to our messages_logs.txt file
            Functions::writeZenith( $header = "TTLR TO ZENITH REQUEST FOREIGN", json_decode( $request, true ) );

            #Send Request
            $response = curl_exec($curl);
            $response = json_decode( $response, true );

            #Write the response data to our messages_logs.txt file
            Functions::writeZenith( $header = "ZENITH TO TTLR RESPONSE FOREIGN", $response );

            $response 		        = 	$response['PaymentResponse'];
            $zenith->response_code  =   $response['ResponseCode'];
            $zenith->payment_status =   $response['PaymentStatus'];
            $zenith->reason         =   $response['Reason'];
            $zenith->save();

            $paymentStatus	=	strtolower( $response[ 'PaymentStatus' ] );

            if ( strtolower($paymentStatus) === 'approved' ){
                return [100, ($response['ResponseCode'])? $response['ResponseCode'] : '0000'];
            } elseif ( $paymentStatus === 'vbv required' ) {
                return ['vbv required', $response['ResponseCode'], $response[ 'Reason' ]];
            } else {
                return [$response['ResponseCode'], $response[ 'Reason' ]];
            }
        }
    }


    public function validateTransaction ($ref){
        $curl 	=	curl_init();

        curl_setopt_array(
            $curl, array(
                CURLOPT_HTTPHEADER		=> array( 'Content-Type: application/json' ),
                CURLOPT_RETURNTRANSFER	=>	1,
                CURLOPT_URL				=>	"https://www.zenithbank.com.gh/api.globalpay/Service/confirmTransaction?ref=$ref&gpid=GPZEN017",
                CURLOPT_FOLLOWLOCATION	=>	1,
                CURLOPT_USERAGENT		=>	'TheTeller CURL',
            )
        );

//        return "https://www.zenithbank.com.gh/api.globalpay/Service/confirmTransaction?ref=$ref&gpid=GPZEN017";

        $fault	=	curl_error( $curl );
        if ( isset( $fault ) && $fault != '' ){
            return $fault;
        } else {

            $response	            =	json_decode( curl_exec( $curl ), true );
            Functions::writeZenith('ZENITH TO TTLR RESPONSE FOREIGN', $response);

            $zenith                 =   new Zenith();
            $zenith->status         =   $response['status'];
            $zenith->auth_id        =   $response['AuthID'];
            $zenith->ret_code       =   $response['retCode'];
            $zenith->date_time      =   $response['dateTime'];
            $zenith->product_id     =   $response['productID'];
            $zenith->customer_id    =   $response['customerID'];
            $zenith->type           =   $response['type'];
            $zenith->currency       =   $response['currency'];
            $zenith->t_id           =   $response['TID'];
            $zenith->refund_date    =   $response['refundDate'];
            $zenith->save();


            # Update the status of the transaction in the database using the ref === transaction_id
            $transaction = Transaction::where('fld_011', $response['refID'])->first();
            $fld_038     = substr($transaction->fld_038, 0, 3);
            $reason      = strtolower($response[ 'description' ]);
            $status      = ($response['status'] == true)? 'approved' : 'declined';

            if ((boolean) $response['status']) {
                if ( isset( $response[ 'description' ] ) && strtoupper( $response[ 'description' ] ) === "TRANSACTION PROCESSED SUCCESSFULLY" ) {
                    // successful transaction
                    if ((int) substr($transaction->fld_003, 0, 1) === 4){

                        $transfer = Transaction::deposit($transaction->toArray(), true);
                        return redirect($zenith->response_url."?code=".$transfer['code']."&transaction_id=$transaction->fld_037&reason=".$transfer['reason']."&status=".$transfer['status']);

                    } else {
                        $transaction->fld_038   =   $fld_038.'100';
                        $transaction->fld_039   =   '000';
                    }

                } else {
                    // failed transaction
                    $transaction->fld_038   =   $fld_038.'104';
                    $transaction->fld_039   =   '100';
                }
            } else {
                // transaction declined
                $transaction->fld_038   =   $fld_038.'100';
                $transaction->fld_039   =   '100';
            }

            $transaction->save();

            return redirect($zenith->response_url."?code=$transaction->fld_039&reason=$reason&status=$status&transaction_id=$transaction->fld_037");
        }
    }
}