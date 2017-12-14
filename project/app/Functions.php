<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Functions extends Model
{
    // Write international airtime response to logfile
    public static function writeIntAirtime( $header, $string )
    {
        $data = "$header\r\n";
        if ( is_array( $string ) )
        {
            foreach ( $string as $key => $value )
            {
                if ( is_array( $value ) )
                {
                    foreach ( $value as $k => $v )
                    {
                        $data .= date( 'Y-m-d H:i:s' ).' | '.$k.' : '.$v."\r\n";
                    }
                }
                else
                {
                    $data .= date( 'Y-m-d H:i:s' ).' | '.$key.' : '.$value."\r\n";
                }
            }
        }

        else
        {
            $data .= date( 'Y-m-d H:i:s' ).' | '."$string\r\n";
        }

        if ( isset( $data ) && $data != '' ) {
            $filePath = 'all.txt';
            $fopen = fopen( $filePath, 'a+' );
            fwrite( $fopen, "$data\r\n" );
            fclose( $fopen );
        }

        File::append(storage_path('transactions/'.Date('Ymd').'.txt'), "$data\n");
    }

    // Write MTN data to our logfile
    public static function writeMTN( $header, $params )
    {
        $match = array( 'username', 'password', 'apiKey', 'vendorID' );
        $message = $header."\r\n";
        if ( is_object( $params ) ) {
            $params	=	get_object_vars( $params );
        }
        foreach ( $params as $key => $value ) {
            if ( is_array( $value ) ) {
                $message .= date( 'H:i:s' ).' | '."$key\r\n";
                $params = $value;
                foreach ( $params as $key => $value ) {

                    if ( in_array( $key, $match ) )
                    {
                        $message .= date( 'H:i:s' ).' | '.$key.' : '.self::maskAm( $value)."\r\n";
                    } else {
                        $message .= date( 'H:i:s' ).' | '."$key : $value\r\n";
                    }
                }
            } else {

                if ( is_object( $value ) ) {
                    $message .= date( 'H:i:s' ).' | '."$key\r\n";
                    $params	=	get_object_vars( $value	);
                    if ( is_array( $params ) ) {
                        $params = $value;
                        foreach ( $params as $key => $value ) {
                            if ( in_array( $key, $match ) )
                            {
                                $message .= date( 'H:i:s' ).' | '.$key.' : '.self::maskAm($value)."\r\n";
                            } else {
                                $message .= date( 'H:i:s' ).' | '."$key : $value\r\n";
                            }
                        }
                    }
                }	else {
                    if ( in_array( $key, $match ) )
                    {
                        $message .= date( 'H:i:s' ).' | '.$key.' : '.self::maskAm( $value)."\r\n";
                    } else {
                        $message .= date( 'H:i:s' ).' | '."$key : $value\r\n";
                    }
                }
            }
        }
        self::writeRequestWithTimestamp( $message );
    }

    public static function writeTigo( $header, $value )
    {
        $message = $header."\r\n";
        foreach ( $value as $array )
        {
            if ( isset( $array[ 'tag' ] ) && isset( $array[ 'value' ] ) && isset( $array[ 'type' ] ) && $array[ 'type' ] == 'complete' )
            {
                $point = 0;
                $current = '';
                $length = strlen( $array[ 'tag' ] );

                while ( $current != ':' && $point < $length )
                {
                    $current = substr( $array[ 'tag' ], $point, 1 );
                    $point++;
                }

                $array[ 'tag' ] = substr( $array[ 'tag' ], $point );
                $match = array( 'USERNAME', 'PASSWORD', 'CONSUMERID', 'WEBUSER', 'WEBPASSWORD', 'PARAMETERNAME', 'PARAMETERVALUE' );

                if ( !in_array( $array[ 'tag' ], $match ) )
                {
                    $message .= date( 'H:i:s' ).' | '.$array[ 'tag' ].' : '.$array[ 'value' ]."\r\n";
					$array[ 'value' ]	=	self::maskAm( $array[ 'value' ] );
                }
            }
        }
        self::writeRequestWithTimestamp( $message );
    }

    public static function maskAm( $value )
    {
        $length = strlen( $value );
        $new_value = '';
        $count = 0;

        while ( $count < $length )
        {
            $new_value .= '*';
            $count++;
        }
        return $new_value;
    }

    public static function writeAirtel( $header, $value )
    {
        $match = array('merchant_number');
        $message	=	"$header\r\n";
        foreach ( $value as $key => $value )
        {
            if ( in_array( $key, $match ) )
            {
                $message .= date( 'H:i:s' ).' | '.$key.' : '.self::maskAm( $value)."\r\n";
            } else {
                $message .= date( 'H:i:s' ).' | '."$key : $value\r\n";
            }
        }
        self::writeRequestWithTimestamp( $message );
    }

    public static function writeGhipss( $header, $params )
    {
        $credentials = array( 'merchantID', 'acquirerID', 'password' );
        $message = $header."\r\n";
        if ( is_array( $params ) ) {

            foreach ( $params as $key => $value ) {
                if ( is_array( $value ) ) {

                    $message	.=	date( 'H:i:s' ).' | '."$key\r\n";
                    $params		=	$value;

                    foreach ( $params as $key => $value ) {
                        if ( in_array( $key, $credentials ) ) {
                            $value	=	self::maskAm( $value );
                        }
                        $message	.=	date( 'H:i:s' ).' | '."$key	:	$value\r\n";
                    }
                }	else {
                    if ( in_array( $key, $credentials ) ) {
                        $value	=	self::maskAm( $value );
                    }
                    $message	.=	date( 'H:i:s' ).' | '."$key	:	$value\r\n";
                }
            }
        }

        else
        {
            $message	.=	date( 'H:i:s' ).' | '."$params\r\n";
        }

        self::writeRequestWithTimestamp( $message );
    }

    public static function writeZenith( $header, $messageToWrite )
    {
        $match = array('GlobalPayID');
        $message = $header."\r\n";
        if (!is_null($messageToWrite)){
            foreach ( $messageToWrite as $key => $value )
            {
                if ( is_array( $value ) )
                {
                    $message .= date( 'H:i:s' ). ' | ' ."$key\r\n";
                    $messageToWrite = $value;
                    foreach ( $messageToWrite as $key => $value )
                    {
                        if ( is_array( $value ) )
                        {
                            $message .= date( 'H:i:s' ). ' | ' ."$key\r\n";
                            $messageToWrite = $value;
                            foreach ( $messageToWrite as $key => $value )
                            {
                                if ($key === 'CardNumber'){
                                    $value = substr($value, 0, 6).'******'.substr($value, -4);
                                } elseif( $key === 'CardCvv'){
                                    $value = '***';
                                } else
                                    if ( $key === 'GlobalPayID'){
                                    $value = self::maskAm($value);
                                }
                                $message .= date( 'H:i:s' ). ' | ' ."$key : $value\r\n";
                            }

                        }

                        else
                        {
                            $message .= date( 'H:i:s' ). ' | ' ."$key : $value\r\n";
                        }
                    }

                }

                else
                {
                    $message .= date( 'H:i:s' ). ' | ' ."$key : $value\r\n";
                }
            }
        }

        // Format the data before writing to file
        return self::writeRequestWithTimestamp( $message );
    }

    public static function writeVodafone($header, $strings)
    {
        $message = $header."\r\n";
        if (isset($strings['array'])){
            foreach ($strings['array'] as $key => $value) {
                $message .= date( 'H:i:s' ). ' | ' ."$key : $value\r\n";
            }
        } else {
            foreach ( $strings as $array )
            {
                if ( isset( $array[ 'tag' ] ) && isset( $array[ 'value' ] ) && isset( $array[ 'type' ] ) && $array[ 'type' ] == 'complete' )
                {
                    $match = array( 'TOKEN', 'VFPIN', 'VENDORCODE' );

                    if ( !in_array( $array[ 'tag' ], $match ) )
                    {
                        $message .= date( 'H:i:s' ).' | '.$array[ 'tag' ].' : '.$array[ 'value' ]."\r\n";
                    } else {
                        $message .= date( 'H:i:s' ).' | '.$array[ 'tag' ].' : '.self::maskAm( $array[ 'value' ] )."\r\n";
                    }
                }
            }
        }

        return self::writeRequestWithTimestamp($message);
    }

    // Writes all the request and response from theteller and the source of fund
    // to the message log files on the server with the timestamp
    public static function writeRequestWithTimestamp ( $dataToWrite )
    {
        // File path to write
        $filePath = 'all.txt';

        $dataToWrite = date( "d-m-Y" ) . " | " . "$dataToWrite\r\n";
        $fopen = fopen( $filePath, 'a+' );
        fwrite( $fopen, $dataToWrite );
        fclose( $fopen );

        File::append(storage_path('transactions/'.Date('Ymd').'.txt'), "$dataToWrite");
    }

    // Writes all the request and response from theteller and the source of fund
    // to the message log files on the server without the timestamp
    public static function writeRequest ( $filePath, $dataToWrite )
    {
        $fopen = fopen( $filePath, 'a+' );
        fwrite( $fopen, $dataToWrite );
        fclose( $fopen );
    }

    public static function toFloat($minor_unit){
        $float = ((int)$minor_unit)/100;
        return round((float)$float,2);
    }
}