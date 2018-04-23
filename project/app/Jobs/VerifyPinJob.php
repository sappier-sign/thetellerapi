<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 4/20/2018
 * Time: 2:48 PM
 */

namespace App\Jobs;


use App\Merchant;
use Firebase\JWT\JWT;
use http\Exception\UnexpectedValueException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class VerifyPinJob extends \Illuminate\Queue\Jobs\Job
{
    private $request;
    private $data;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle()
    {
        $data = JWT::decode($this->request->input('token'), env('JWT_SECRET'), ['HS512']);
        $this->data = get_object_vars($data);
        $merchant = Merchant::where('merchant_id', $this->request->input('pin'), $this->data['sub'])->first();

        if (!Hash::check($this->request->input('pin'), $merchant->pin)) {
            throw new UnexpectedValueException('wrong pin', 401);
        }
    }

}