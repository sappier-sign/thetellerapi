<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 4/10/2018
 * Time: 3:43 PM
 */

namespace App\Console\Commands;

use App\Mtn;
use App\Transaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;


class MtnTransactionStatusCommand extends Command
{
    protected $signature = 'check:mtn';

    protected $description = 'check the status of all unsuccessful mtn transactions';

    public function handle()
    {
        try {
            Transaction::where('fld_057', 'MTN')
                ->where('fld_039', '<>', '000')
                ->whereBetween('fld_012', [Carbon::now()->subHours(24)->toDateTimeString(), Carbon::now()->toDateTimeString()])
                ->orderBy('fld_012', 'desc')
                ->chunk(100, function ($transactions) {

                    foreach ($transactions as $transaction) {
                        $mtn = new Mtn();
                        $mtn_transaction = Mtn::where('thirdpartyID', $transaction->fld_011)->first();

                        print_r($mtn_transaction->toArray());

                        if (!is_null($mtn_transaction)) {
                            print_r($status = $mtn->checkInvoiceOffline($mtn_transaction->invoiceNo));
                            print_r(Transaction::transactionResponse($status, $transaction->fld_037, $transaction->fld_042));

                            $mtn_transaction = Mtn::where('thirdpartyID', $transaction->fld_011)->first();

                            print_r($mtn_transaction->toArray());
                        }

                    }
                });

        } catch (Exception $exception) {
            return $exception->getMessage();
        }
    }

}