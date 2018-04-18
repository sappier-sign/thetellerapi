<?php

namespace App;

use App\Mtn;
use Illuminate\Database\Eloquent\Model;

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 20/07/2017
 * Time: 10:44 PM
 */
class Transaction extends Model
{
    protected $table = 'ttm_transactions';
    protected $primaryKey = 'fld_011';
    const CREATED_AT = 'fld_012';
    const UPDATED_AT = null;
    public $incrementing = false;

    function __construct()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    protected $fillable = [];

    public static function saveStatistic($transaction)
    {
        $statistic = new Statistic();
        return $statistic->persist($transaction['fld_002'], Functions::toFloat($transaction['fld_004']), $transaction['fld_042']);
    }

    public static function saveTransaction($transaction)
    {
        $r_switch = Rswitch::where('short_code', $transaction['fld_057'])->first();

        if (is_null($r_switch)) {
            return [
                'status' => 'error',
                'code' => 441,
                'reasons' => 'Unknown r-switch' . $transaction['fld_057']
            ];
        }

        if ($r_switch->exists()) {
            if (!in_array($r_switch->id, Merchant::where('merchant_id', $transaction['fld_042'])->first()->rswitches()->pluck('r_switch_id')->toArray())) {
                return [
                    'status' => 'error',
                    'code' => '040',
                    'reason' => 'You are not allowed to transact with ' . $transaction['fld_057'] . ' (' . $r_switch->name . ')'
                ];
            }
        }

        if (is_null($transaction['rfu_002'])) {
            if (self::where('fld_037', $transaction['rfu_002'])->where('fld_042', $transaction['fld_042'])->count() === 0) {
                return [
                    'status' => 'error',
                    'code' => '030',
                    'reason' => 'Original transaction with id: ' . $transaction['rfu_002'] . ' does not exist'
                ];
            }
        }

        $saveStatistic = self::saveStatistic($transaction);
        if (in_array($saveStatistic, ['010', '020', '030'])) {
            return $saveStatistic;
        }

        if (Transaction::where('fld_037', $transaction['fld_037'])->where('fld_042', $transaction['fld_042'])->count()) {
            return false;
        }

        $trans = new Transaction();
        $trans->fld_002 = ((in_array($transaction['fld_057'], ['MAS', 'VIS'])) && strtoupper(substr($transaction['fld_002'], 0, 3)) <> 'TTM') ? substr($transaction['fld_002'], 0, 6) . '******' . substr($transaction['fld_002'], 12) : $transaction['fld_002'];
        $trans->fld_003 = $transaction['fld_003'];
        $trans->fld_004 = $transaction['fld_004'];
        $trans->fld_009 = $transaction['fld_009'];
        $trans->fld_011 = $transaction['fld_011'];
        $trans->fld_012 = date('Y-m-d H:i:s');
        $trans->fld_014 = $transaction['fld_014'];
        $trans->fld_037 = $transaction['fld_037'];
        $trans->fld_038 = 'pending';
        $trans->fld_042 = $transaction['fld_042'];
        $trans->fld_043 = Merchant::where('merchant_id', $transaction['fld_042'])->first()->company;
        $trans->fld_057 = $transaction['fld_057'];
        $trans->fld_103 = $transaction['fld_103'];
        $trans->fld_116 = $transaction['fld_116'];
        $trans->fld_117 = $transaction['fld_117'];
        $trans->fld_123 = $transaction['fld_123'];
        # Set Reserved For Future Use Fields
        $trans->rfu_001 = $transaction['rfu_001'];
        $trans->rfu_002 = $transaction['rfu_002'];
        $trans->rfu_003 = $transaction['rfu_003'];
        $trans->rfu_004 = $transaction['rfu_004'];
        $trans->rfu_005 = $transaction['rfu_005'];
        return $trans->save();
    }

    public static function purchase($transaction)
    {
        $saveTransaction = Transaction::saveTransaction($transaction);
        if ($saveTransaction === true) {
            $response = Transaction::routeSwitch($transaction, 'purchase');
            return $response;
        } elseif ($saveTransaction === false) {
            return [
                'status' => 'failed',
                'code' => 959,
                'reason' => 'Duplicate transaction: transaction_id must be unique'
            ];
        } elseif (in_array($saveTransaction, ['010', '020', '030'])) {
            return self::responseMessage($saveTransaction);
        } elseif (isset($saveTransaction['code'])) {
            return $saveTransaction;
        }

    }

    public static function deposit($transaction, $transfer = false)
    {
        if (!$transfer) {
            if (Transaction::saveTransaction($transaction) === false) {
                return [
                    'status' => 'failed',
                    'code' => 959,
                    'reason' => 'Duplicate transaction: transaction_id must be unique'
                ];
            }
        } else {
            return $transfer = Transaction::routeSwitch($transaction, 'transfer');
        }

        return $deposit = Transaction::routeSwitch($transaction, 'deposit');
    }

    public static function transfer($transaction, $transacted_amount = null)
    {

        $response = null;
        $merchant_debiting = false;

        if (strtoupper(substr($transaction['fld_002'], 0, 4)) === 'TTM-') {
            self::saveTransaction($transaction);

            $merchant_debiting = true;
            $debitee = new Debit();
            $debitee->amount = number_format((float)$transaction['fld_004'], 2);
            $debitee->success = 0;
            $debitee->merchant_id = $transaction['fld_042'];
            $debitee->transaction_id = $transaction['fld_011'];
            $debitee->save();

            $purchase = [
                'status' => 'success',
                'code' => '000',
                'reason' => 'Merchant debited successfully'
            ];
        } else {
            $purchase = self::purchase($transaction);
        }

        if (isset($purchase['code']) && $purchase['code'] === '000') {

            if ($merchant_debiting) {
                $debitee->transaction_id = $deposit['fld_011'] = '00' . time();
            }
            $transaction['rfu_001'] = '00' . time();

            // If the Merchant is TheTeller Web or Mobile, Then use the transacted amount instead of the amount
            if ($transaction['fld_042'] === 'TTM-00000002' && $transacted_amount) {
                $transaction['fld_004'] = $transacted_amount;
            }

            $transfer = self::deposit($transaction, true);

            if ($transfer['code'] === '000') {
                $response = [
                    'status' => 'success',
                    'code' => '000',
                    'reason' => 'Transfer successful'
                ];

                if ($merchant_debiting) {
                    $debitee->success = 1;
                    $debitee->save();
                }
                return $response;
            } else {
                return $response = [
                    'status' => 'failed',
                    'code' => 909,
                    'reason' => 'Transfer failed, please contact support'
                ];
            }
        } elseif ($purchase === '010') {
            return $response = [
                'status' => 'declined',
                'code' => '010',
                'reason' => 'Suspicious transaction.'
            ];
        } elseif ($purchase === '020') {
            return $response = [
                'status' => 'declined',
                'code' => '020',
                'reason' => 'Allowed transaction amount exceeded!'
            ];
        } elseif ($purchase === '030') {
            return $response = [
                'status' => 'declined',
                'code' => '030',
                'reason' => 'Transactions below GHS 0.10 are not allowed!'
            ];
        } elseif ($purchase['code'] === 441) {
            return $purchase;
        }
        return $response = [
            'status' => 'failed',
            'code' => 909,
            'reason' => 'Transfer failed, please contact support'
        ];
    }

    public static function routeSwitch($transaction, $action)
    {
        $merchant = Merchant::where('merchant_id', $transaction['fld_042'])->first();
        $merchant_name = $merchant->nick_name ?? $merchant->company;

        if ($action === 'transfer') {

            $this_transaction = Transaction::where('fld_037', $transaction['fld_037'])->where('fld_042', $transaction['fld_042'])->first();
            $this_transaction->fld_038 = 'pending';
            $this_transaction->save();

            if ($transaction['fld_117'] === 'MTN') {

                $mtn = new Mtn();
                return Transaction::transactionResponse($mtn->credit($transaction['fld_103'], Functions::toFloat
                ($transaction['fld_004']), $transaction['fld_011']), $transaction['fld_037'], $transaction['fld_042']);

            } elseif ($transaction['fld_117'] === 'TGO') {

                $tigo = new Tigo();
                return Transaction::transactionResponse($tigo->credit($transaction['fld_103'], Functions::toFloat($transaction['fld_004']), $transaction['fld_011']), $transaction['fld_037'], $transaction['fld_042']);

            } elseif ($transaction['fld_117'] === 'ATL') {

                $airtel = new Airtel();
                return Transaction::transactionResponse($airtel->credit($transaction['fld_103'], Functions::toFloat($transaction['fld_004']), $transaction['fld_116'], $transaction['fld_011']), $transaction['fld_037'], $transaction['fld_042']);
            } elseif ($transaction['fld_117'] === 'VDF') {

                return Transaction::transactionResponse(Vodafone::credit($transaction['fld_002'], Functions::toFloat($transaction['fld_004']), $transaction['fld_011']), $transaction['fld_037'], $transaction['fld_042']);
            }
        }

        if ($transaction['fld_057'] === 'MTN') { // if route switch is MTN
            $mtn = new Mtn();
            switch ($action) {
                case 'purchase':
                    if ($transaction['rfu_005'] === 'offline') {
                        return Transaction::transactionResponse($mtn->debitOffline($transaction['fld_002'], Functions::toFloat($transaction['fld_004']), $merchant_name, $transaction['fld_011']), $transaction['fld_037'], $transaction['fld_042']);
                    }
                    return Transaction::transactionResponse($mtn->debit($transaction['fld_002'], Functions::toFloat($transaction['fld_004']), $merchant_name, $transaction['fld_011']), $transaction['fld_037'], $transaction['fld_042']);
                    break;

                case 'deposit':
                    return Transaction::transactionResponse($mtn->credit($transaction['fld_002'], Functions::toFloat($transaction['fld_004']), $transaction['fld_011']), $transaction['fld_037'], $transaction['fld_042']);
                    break;
            }

        } elseif ($transaction['fld_057'] === 'VDF') { // if route switch is VODAFONE

            switch ($action) {
                case 'purchase':
                    return Transaction::transactionResponse(Vodafone::debit($transaction['fld_002'], Functions::toFloat($transaction['fld_004']), $transaction['fld_011'], $transaction['voucher_code']), $transaction['fld_037'], $transaction['fld_042']);
                    break;

                case 'deposit':
                    return Transaction::transactionResponse(Vodafone::credit($transaction['fld_002'], Functions::toFloat($transaction['fld_004']), $transaction['fld_011']), $transaction['fld_037'], $transaction['fld_042']);
                    break;
            }
        } elseif ($transaction['fld_057'] === 'TGO') { // if route switch is TIGO
            $tigo = new Tigo();
            switch ($action) {
                case 'purchase':
                    return Transaction::transactionResponse($tigo->debit($transaction['fld_002'], Functions::toFloat($transaction['fld_004']), $transaction['fld_116'], $transaction['fld_011']), $transaction['fld_037'], $transaction['fld_042']);
                    break;

                case 'deposit':
                    return Transaction::transactionResponse($tigo->credit($transaction['fld_002'], Functions::toFloat($transaction['fld_004']), $transaction['fld_011']), $transaction['fld_037'], $transaction['fld_042']);
                    break;
            }

        } elseif ($transaction['fld_057'] === 'ATL') { // if route switch is AIRTEL
            $airtel = new Airtel();
            switch ($action) {
                case 'purchase':
                    return Transaction::transactionResponse($airtel->debit($transaction['fld_002'], Functions::toFloat($transaction['fld_004']), $transaction['fld_116'], $transaction['fld_011']), $transaction['fld_037'], $transaction['fld_042']);
                    break;

                case 'deposit':
                    return Transaction::transactionResponse($airtel->credit($transaction['fld_002'], Functions::toFloat($transaction['fld_004']), $transaction['fld_116'], $transaction['fld_011']), $transaction['fld_037'], $transaction['fld_042']);
                    break;
            }
        } elseif ($transaction['fld_057'] === 'VIS' || $transaction['fld_057'] === 'MAS') { // if route switch is VISA
            $mode = ($transaction['fld_057'] === 'VIS') ? 'Visa' : 'Mastercard';
            switch ($action) {
                case 'purchase':
                    return Transaction::transactionResponse(Zenith::debit($transaction['fld_002'], $transaction['expMonth'], $transaction['expYear'], $transaction['cvv'], Functions::toFloat($transaction['fld_004']), $mode, $transaction['fld_116'], $transaction['fld_116'], $transaction['fld_011'], $transaction['response_url']), $transaction['fld_037'], $transaction['fld_042']);
                    break;

                default:
                    return ['something went wrong'];
            }
        } elseif ($transaction['fld_057'] === 'TLA') { // if route switch is TELA

        } elseif ($transaction['fld_057'] === 'GHL') { // if route switch is GHLINK

        } elseif ($transaction['fld_057'] === 'GIS') { // if route switch is GH INSTANT PAY

        } else { // if unknown route switch
            return [
                'status' => 'error',
                'code' => 441,
                'reason' => 'unknown payment source ' . $transaction['fld_057']
            ];
        }
    }

    public static function transactionResponse($response, $id, $merchant_id)
    {
        $transaction = Transaction::where('fld_037', $id)->where('fld_042', $merchant_id)->where('fld_039', '<>', '000')->first();

        if (!is_null($transaction)) {

            $sponsor = null;

            if ($transaction->fld_057 === 'TLA') {
                $sponsor = 101;
            } elseif ($transaction->fld_057 === 'VIS') {
                $sponsor = 102;
            } elseif ($transaction->fld_057 === 'MAS') {
                $sponsor = 103;
            } elseif ($transaction->fld_057 === 'GHL') {
                $sponsor = 104;
            } elseif ($transaction->fld_057 === 'MTN') {
                $sponsor = 201;
            } elseif ($transaction->fld_057 === 'TGO') {
                $sponsor = 202;
            } elseif ($transaction->fld_057 === 'ATL') {
                $sponsor = 203;
            } elseif ($transaction->fld_057 === 'VDF') {
                $sponsor = 204;
            }

            switch ($response[0]) {
                case 100:
                    $transaction->fld_038 = $sponsor . $response[0];
                    $transaction->fld_039 = '000';
                    $transaction->save();
                    return [
                        'status' => 'approved',
                        'code' => '000',
                        'reason' => 'Transaction successful!'
                    ];
                    break;

                case 101:
                    $transaction->fld_038 = $sponsor . $response[0];
                    $transaction->fld_039 = '100';
                    $transaction->save();
                    return [
                        'status' => 'declined',
                        'code' => 101,
                        'reason' => 'Insufficient funds in wallet'
                    ];
                    break;

                case 102:
                    $transaction->fld_038 = $sponsor . $response[0];
                    $transaction->fld_039 = '100';
                    $transaction->save();
                    return [
                        'status' => 'declined',
                        'code' => 102,
                        'reason' => 'Number not registered for mobile money!'
                    ];
                    break;

                case 103:
                    $transaction->fld_038 = $sponsor . $response[0];
                    $transaction->fld_039 = '100';
                    $transaction->save();
                    return [
                        'status' => 'declined',
                        'code' => 103,
                        'reason' => 'Wrong PIN or transaction timed out!'
                    ];
                    break;

                case 104:
                    $transaction->fld_038 = $sponsor . $response[0];
                    $transaction->fld_039 = '100';
                    $transaction->save();
                    return [
                        'status' => 'declined',
                        'code' => 104,
                        'reason' => 'Transaction declined or terminated!'
                    ];
                    break;

                case 105:
                    $transaction->fld_038 = $sponsor . $response[0];
                    $transaction->fld_039 = '100';
                    $transaction->save();
                    return [
                        'status' => 'declined',
                        'code' => 105,
                        'reason' => 'Invalid amount or general failure. Try changing transaction id!',
                    ];
                    break;

                case 106:
                    $transaction->fld_038 = $sponsor . $response[0];
                    $transaction->fld_039 = '100';
                    $transaction->save();
                    return [
                        'status' => 'declined',
                        'code' => 106,
                        'reason' => 'Duplicate transaction ID!'
                    ];
                    break;

                case 111:
                    $transaction->fld_038 = $sponsor . $response[0];
                    $transaction->fld_039 = '101';
                    $transaction->save();
                    return [
                        'status' => 'success',
                        'code' => 111,
                        'reason' => 'Payment request sent successfully'
                    ];
                    break;

                case 96:
                    $transaction->fld_038 = $sponsor . $response[0];
                    $transaction->fld_039 = '100';
                    $transaction->save();
                    return [
                        'status' => 'declined',
                        'code' => 106,
                        'reason' => $response[1]
                    ];
                    break;

                case '05':
                    $transaction->fld_038 = $sponsor . $response[0];
                    $transaction->fld_039 = '100';
                    $transaction->save();
                    return [
                        'status' => 'declined',
                        'code' => 106,
                        'reason' => $response[1]
                    ];
                    break;

                case 2:
                    $transaction->fld_038 = $sponsor . $response[0];
                    $transaction->fld_039 = '100';
                    $transaction->save();
                    return [
                        'status' => 'declined',
                        'code' => 106,
                        'reason' => $response[1]
                    ];
                    break;

                case 107:
                    $transaction->fld_038 = $sponsor . $response[0];
                    $transaction->fld_039 = '100';
                    $transaction->save();
                    return [
                        'status' => 'network down',
                        'code' => 107,
                        'reason' => 'Network error please try again later!'
                    ];
                    break;

                case 110:
                    $transaction->fld_038 = $sponsor . $response[0];
                    $transaction->fld_039 = '100';
                    $transaction->save();
                    return [
                        'status' => 'network busy',
                        'code' => 107,
                        'reason' => 'USSD is busy, please try again later!'
                    ];
                    break;
                case 114:
                    $transaction->fld_038 = $sponsor . $response[0];
                    $transaction->fld_039 = '100';
                    $transaction->save();
                    return [
                        'status' => 'error',
                        'code' => 114,
                        'reason' => 'Invalid Voucher code'
                    ];
                    break;

                case '-900': //Cards for deactivated accounts
                    $transaction->fld_038 = $sponsor . $response[0];
                    $transaction->fld_039 = '100';
                    $transaction->save();
                    return [
                        'status' => 'declined',
                        'code' => 901,
                        'reason' => 'System error: The requested name is valid, but no data of the requested type was found'
                    ];
                    break;

                case 'vbv required':
                    $transaction->fld_038 = $sponsor . $response[0];
                    $transaction->fld_039 = '101';
                    $transaction->save();
                    return [
                        'status' => 'vbv required',
                        'code' => 200,
                        'reason' => $response[2]
                    ];
                    break;

                default:
                    if (is_array($response) && count($response) > 2) {
                        if (isset($response['status'])) {
                            $transaction->fld_038 = $response['description'];
                            $transaction->fld_039 = 'vbv required';
                            $transaction->save();
                        }
                        return $response;
                    } else {
                        $transaction->fld_038 = $sponsor . $response[0];
                        $transaction->fld_039 = '100';
                        $transaction->save();
                        return [
                            'status' => 'declined',
                            'code' => 109,
                            'reason' => 'Error occurred please try again later!'
                        ];
                    }
                    break;
            }
        } else {
            $transaction = Transaction::where('fld_037', $id)->where('fld_042', $merchant_id)->first();
            if (!is_null($transaction)) {
                if ($transaction->fld_039 === '000') {

                    return [
                        'status' => 'approved',
                        'code' => '000',
                        'reason' => 'Transaction successful!'
                    ];

                }
            } else {

                return [
                    'status' => 'not found',
                    'code' => '404',
                    'reason' => 'Transaction not found!'
                ];

            }

        }

        return [
            'status' => 'error',
            'code' => '405',
            'reason' => 'An error occurred whiles handling the response. Please try again!'
        ];

    }

    public static function responseMessage($code)
    {
        switch ($code) {
            case 100:
                return [
                    'status' => 'approved',
                    'code' => '000',
                    'reason' => 'Transaction processed successfully!'
                ];
                break;

            case 101:
                return [
                    'status' => 'declined',
                    'code' => 101,
                    'reason' => 'Insufficient funds in wallet'
                ];
                break;

            case 102:
                return [
                    'status' => 'declined',
                    'code' => 102,
                    'reason' => 'Number not registered for mobile money!'
                ];
                break;

            case 103:
                return [
                    'status' => 'declined',
                    'code' => 103,
                    'reason' => 'Wrong PIN or transaction timed out!'
                ];
                break;

            case 111:
                return [
                    'status' => 'success',
                    'code' => 111,
                    'reason' => 'Payment request sent successfully'
                ];
                break;

            case 104:
                return [
                    'status' => 'declined',
                    'code' => 104,
                    'reason' => 'Transaction declined or terminated!'
                ];
                break;

            case 105:
                return [
                    'status' => 'declined',
                    'code' => 105,
                    'reason' => 'Invalid amount or general failure. Try changing transaction id!',
                ];
                break;

            case 106:
                return [
                    'status' => 'declined',
                    'code' => 106,
                    'reason' => 'Duplicate transaction ID!'
                ];
                break;

            case 107:
                return [
                    'status' => 'network down',
                    'code' => 107,
                    'reason' => 'Network error please try again later!'
                ];
                break;

            case '-900':
                return [
                    'status' => 'declined',
                    'code' => 901,
                    'reason' => 'System error: The requested name is valid, but no data of the requested type was found'
                ];
                break;

            case 'vbv required':
                return [
                    'status' => 'vbv required',
                    'code' => 200,
                    'reason' => 'Card has been temporarily declined. Please try with a different card!'
                ];
                break;

            case '62':
                return [
                    'status' => 'Declined',
                    'code' => 200,
                    'reason' => 'Restricted Card'
                ];
                break;

            case '2':
                return [
                    'status' => 'Declined',
                    'code' => 200,
                    'reason' => 'Bank Declined Transaction'
                ];
                break;

            case '05':
                return [
                    'status' => 'Declined',
                    'code' => 200,
                    'reason' => 'Do not honor'
                ];
                break;

            case '54':
                return [
                    'status' => 'Declined',
                    'code' => 200,
                    'reason' => 'Card expired'
                ];
                break;

            case '010':
                return [
                    'status' => 'Declined',
                    'code' => '010',
                    'reason' => 'Suspicious transaction'
                ];
                break;

            case '020':
                return [
                    'status' => 'Declined',
                    'code' => '020',
                    'reason' => 'Allowed transaction amount exceeded!'
                ];
                break;

            case '030':
                return [
                    'status' => 'Declined',
                    'code' => '030',
                    'reason' => 'Transaction amount below GHS 0.10 are not allowed.'
                ];
                break;

            case 114:
                return [
                    'status' => 'error',
                    'code' => 114,
                    'reason' => 'Invalid Voucher code'
                ];
                break;

            default:
                return [
                    'status' => 'declined',
                    'code' => 109,
                    'reason' => 'Error occurred please try again later!'
                ];
                break;
        }
    }

    public static function getMerchantTransactionUsingTransactionId($merchant_id, $transaction_id)
    {
        $transaction = Transaction::where('fld_042', $merchant_id)->where('fld_037', $transaction_id)->get(['fld_038', 'fld_012', 'fld_037', 'fld_039', 'fld_004'])[0];

        if (isset($transaction->fld_038)) {
            if ($transaction->fld_039 === '101') {
                $status = 'pending';
            } else {
                $status = self::responseMessage(substr($transaction->fld_038, 3))['reason'];
            }

            return [
                'status' => ($transaction->fld_039 === '000') ? 'Approved' : 'Declined',
                'code' => $transaction->fld_039,
                'reason' => $status,
                'amount' => $transaction->fld_004,
                'transaction_id' => $transaction->fld_037,
                'transaction_date' => $transaction->fld_012->toDateTimeString()
            ];
        }

        return [
            'status' => 'failed',
            'code' => 900,
            'reason' => 'Transaction does not exist',
            'amount' => '',
            'transaction_id' => $transaction_id,
            'transaction_date' => ''
        ];
    }

    public static function getFarmersTransactionsUsingFarmerId($farmer_id)
    {
        $transactions = Transaction::where('rfu_001', $farmer_id)->get(['fld_038', 'fld_012', 'fld_037', 'fld_039', 'fld_004', 'rfu_002', 'rfu_003', 'rfu_004', 'rfu_005']);
        $_transactions = [];

        if (count($transactions)) {
            foreach ($transactions as $transaction) {
                if ($transaction->fld_039 === '101') {
                    $status = 'pending';
                } else {
                    $status = self::responseMessage(substr($transaction->fld_038, 3))['reason'];
                }

                array_push($_transactions, [
                    'status' => ($transaction->fld_039 === '000') ? 'Approved' : 'Declined',
                    'code' => $transaction->fld_039,
                    'reason' => $status,
                    'amount' => $transaction->fld_004,
                    'transaction_id' => $transaction->fld_037,
                    'transaction_date' => $transaction->fld_012->toDateTimeString(),
                    'farmer_id' => $farmer_id,
                    'volume' => $transaction->rfu_002,
                    'location' => $transaction->rfu_003,
                    'lbc' => $transaction->rfu_004
                ]);
            }

            return $_transactions;
        }

        return [
            'status' => 'success',
            'code' => '000',
            'reason' => 'No transactions found',
            'farmer_id' => $farmer_id
        ];
    }

    public static function getFarmersTransactionsUsingFarmerIdAndTransactionId($farmer_id, $transaction_id)
    {
        $transaction = Transaction::where('rfu_001', $farmer_id)->where('fld_037', $transaction_id)->get(['fld_038', 'fld_012', 'fld_037', 'fld_039', 'fld_004', 'rfu_002', 'rfu_003', 'rfu_004', 'rfu_005']);

        if (count($transaction)) {
            if ($transaction[0]->fld_039 === '101') {
                $status = 'pending';
            } else {
                $status = self::responseMessage(substr($transaction[0]->fld_038, 3))['reason'];
            }

            return [
                'status' => ($transaction[0]->fld_039 === '000') ? 'Approved' : 'Declined',
                'code' => $transaction[0]->fld_039,
                'reason' => $status,
                'amount' => $transaction[0]->fld_004,
                'farmer_id' => $farmer_id,
                'volume' => $transaction[0]->rfu_002,
                'location' => $transaction[0]->rfu_003,
                'lbc' => $transaction[0]->rfu_004,
                'transaction_id' => $transaction[0]->fld_037,
                'transaction_date' => $transaction[0]->fld_012->toDateTimeString()
            ];
        }

        return [
            'status' => 'failed',
            'code' => 900,
            'reason' => 'Transaction does not exist',
            'amount' => '',
            'transaction_id' => $transaction_id,
            'transaction_date' => '',
            'farmer_id' => $farmer_id
        ];

    }

    public static function getTransactionStatus($merchant_id, $transaction_id)
    {
        $transaction = Transaction::where('fld_042', $merchant_id)->where('fld_037', $transaction_id)->first();
        if (!is_null($transaction)) {
            if (($transaction->fld_039 != '000') && ($transaction->fld_057 === 'MTN')) {
                $mtn = new Mtn();
                $mtn_transaction = Mtn::where('thirdpartyID', $transaction->fld_011)->first();

                if (!is_null($mtn_transaction)) {
                    $status = $mtn->checkInvoiceOffline($mtn_transaction->invoiceNo);
                    return self::transactionResponse($status, $transaction_id, $merchant_id);
                }
                return [
                    'status' => 'failed',
                    'code' => 999,
                    'reason' => 'Transaction not found'
                ];

            }
            return self::responseMessage(substr($transaction->fld_038, 3));
        }
        return [
            'status' => 'failed',
            'code' => 999,
            'reason' => 'Transaction not found'
        ];
    }
}