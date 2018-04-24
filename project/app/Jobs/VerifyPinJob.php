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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class VerifyPinJob extends \Illuminate\Queue\Jobs\Job
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle()
    {
        $token = explode(' ', $this->request->header('Authorization'))[1];
        $data = get_object_vars(JWT::decode($token, env('JWT_SECRET'), ['HS512']));

        $merchant = Merchant::where('merchant_id', $data['sub'])->first();

        if (!Hash::check($this->request->input('pin'), $merchant->pin)) {
            throw new \UnexpectedValueException('wrong pin', 401, null);
        }
    }

}