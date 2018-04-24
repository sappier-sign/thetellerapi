<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 4/24/2018
 * Time: 10:27 AM
 */

namespace App\Http\Controllers;

use App\Merchant;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    private $merchant;
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->merchant = Merchant::where('merchant_id', $this->request->input('merchant_id', explode('/', $this->request->path())[1]))->first();

    }

    public function credit()
    {
        $this->validate($this->request, [
            'merchant_id' => 'required|size:12|exists:users',
            'amount' => 'required|digits:12'
        ]);

        return $this->merchant->creditWallet($this->request->input('amount'));
    }

    public function debit()
    {
        $this->validate($this->request, [
            'merchant_id' => 'required|size:12|exists:users',
            'amount' => 'required|digits:12'
        ]);

       return $this->merchant->debitWallet($this->request->input('amount'));
    }

    public function check()
    {
        return [
            'status' => 'success',
            'code' => '000',
            'reason' => $this->merchant->wallet_balance
        ];
    }
}