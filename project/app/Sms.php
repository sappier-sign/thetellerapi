<?php
/**
 * Created by PhpStorm.
 * User: MEST
 * Date: 5/15/2017
 * Time: 7:24 PM
 */

namespace App;


use Illuminate\Database\Eloquent\Model;
use Mockery\Exception;

class Sms extends Model
{
	protected $table = 'api_text_messages';
	protected $fillable = ['user_id', 'bulk_id', 'message_id', 'recipient', ];

    public static function send(Array $recipients, $from = 'PaySwitch', $user_id = null, $message)
    {
        $headers = [
            'Authorization: Basic '.base64_encode(env('SMS_USER').':'.env('SMS_KEY')),
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        $sms = new Sms();
        $sms->message = $message;
        $sms->user_id = $user_id;
        $sms->from  =   $from;

        try {

            $curl = curl_init('https://api.infobip.com/sms/1/text/multi');

            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(['messages' => ['to' => json_encode($recipients), 'text' => $message, 'from' => $from]]));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $curl_response = curl_exec($curl);
            if ($curl_response <> false){

                $response = json_decode($curl_response, true);
                $bulk_id    =   (isset($response['bulkId']))? $response['bulkId'] : null;

                $sms->bulk_id = $bulk_id;
                $messages = $response['messages'];

                foreach ($messages as $message) {
                    $sms->status        =   $message['status']['groupName'];
                    $sms->code          =   $message['status']['groupId'];
                    $sms->recipient     =   $message['to'];
                    $sms->reason        =   $message['status']['description'];
                    $sms->message_id    =   $message['messageId'];
                    $sms->pages         =   $message['smsCount'];
                    $sms->save();
                }

                return [
                    'code'  =>  ($sms->code)? '000' : 100,
                    'status'    =>  $sms->status,
                    'reason'    =>  $sms->reason
                ];

            } else {
                curl_error($curl);
            }
        } catch (Exception $exception) {
            return [$exception->getMessage()];
        }
	}

    public static function verifyPhoneNumber($phone_number, $user_id, $from = 'TheTeller')
    {
        $verification_code  =   substr(str_shuffle('0147852369'), 0, 4);
        $response = self::send([$phone_number], $from, $user_id, $verification_code);
        if ($response['code'] === '000'){
            $response['verification_code'] = $verification_code;
        }
        return $response;
	}
}