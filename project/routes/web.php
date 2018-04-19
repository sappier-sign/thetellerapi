<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$app->get('/', function () use ($app) {
    return $app->version();
});

$app->post('/', function () use ($app) {
    return $app->version();
});

$app->get('ghlinkable', function (){
    return view('ghlink');
});

$app->get('testAcs', function (){
    return view('pareq');
});

$app->group(['prefix' => 'v1.1/wallets'], function ($app){
	$app->post('add.do', 'WalletController@create');
	$app->get('{merchant_id}/{user_id}', 'WalletController@show');
	$app->delete('remove.do', 'WalletController@destroy');
	$app->put('edit.do', 'WalletController@update');
	$app->post('payment.do', 'WalletController@pay');
	$app->post('transfer.do', 'WalletController@transfer');
});

$app->post('ghlinkable', 'TestController@ghlinkView');

$app->post('vodafone/debit', 'TestController@debit');
$app->post('vodafone/credit', 'TestController@credit');

$app->post('ghlink', 'GhlinkController@process');

$app->post('payswitch', 'TestController@payswitch');

$app->post('testCurl', 'TestController@testCurl');


$app->post('transaction/process', 'TransactionController@create');
$app->post('v1.1/vodafone/response', 'TestController@vodafoneResponse');


$app->group(['prefix' => 'v1.1', 'middleware' => ['auth']], function ($app){

    $app->post('transaction/process', 'TransactionController@create');
    $app->post('ghlink/response', 'GhlinkController@saveResponse');
    $app->get('ghlink/response/{html}', 'GhlinkController@index');
    $app->post('verify/phone_number', function (\Illuminate\Http\Request $request){
        return \App\Sms::verifyPhoneNumber($request->input('phone_number'), $request->input('user_id'), $request->input('from'));
    });
});

$app->get('v1.1/vismas/response', function (){

    $test = New \App\Http\Controllers\TestController();
    if (app('request')->input('ref')){
        return $test->vismasResponse(app('request')->input('ref'));
    }
    return 'Request could not be processed at this time';
});

$app->get('v1.1/3ds/response', function (){
    $order_id = $_GET['orderId'];

    echo json_encode($_GET);
});

$app->post('v1.1/sms/send', 'SmsController@send');

$app->get('v1.1/users/transactions/{transaction_id}/status', 'TransactionController@getTransactionStatus');

$app->get('v1.1/transactions/{merchant_id}/{transaction_id}', 'TransactionController@getMerchantTransactionUsingTransactionId');
$app->get('v1.1/farmers/transactions/{farmer_id}', 'TransactionController@getFarmersTransactionsUsingFarmerId');
$app->get('v1.1/farmers/transactions/{farmer_id}/{transaction_id}', 'TransactionController@getFarmersTransactionsUsingFarmerIdAndTransactionId');

$app->group(['prefix' => 'pos'], function ($app){
    $terminalController = new \App\Http\Controllers\TerminalController();
    $app->post('sign_up', ['middleware' => 'authpos', function(\Illuminate\Http\Request $request) use($terminalController) {
        return $terminalController->signUp($request);
    }]);

    $app->post('set_pin', ['middleware' => 'authpos', function(\Illuminate\Http\Request $request) use($terminalController) {
        return $terminalController->setPin($request);
    }]);

    $app->post('sign_in', 'TerminalController@signIn');

    $transactionController = new \App\Http\Controllers\TransactionController();

    $app->post('purchase', ['middleware' => 'authpos', function(\Illuminate\Http\Request $request) use($transactionController) {
        return $transactionController->create($request);
    }]);

    $app->post('transfer', ['middleware' => 'authpos', function(\Illuminate\Http\Request $request) use($transactionController){
        return $transactionController->create($request);
    }]); // zend_extension=/usr/lib64/php/modules/xdebug.so
});

$app->group(['prefix' => 'corporate'], function ($app){
    $app->post('login.do', function (\Illuminate\Http\Request $request){
        \Illuminate\Support\Facades\Log::info(json_encode($request->all()));
    });

    $app->post('verify.pin', 'DesktopController@verifyPin');
    $app->post('set.pin', 'DesktopController@setPin');
    $app->get('transactions', 'DesktopController@getTransactions');
    $app->post('payment.do', 'DesktopController@payemnt');
    $app->post('transfer.do', 'DesktopController@transfer');
});