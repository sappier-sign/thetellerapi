<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 4/20/2018
 * Time: 12:16 PM
 */

namespace App\Jobs;


use App\Merchant;
use App\User;
use Firebase\JWT\JWT;
use http\Exception\RuntimeException;
use Illuminate\Http\Request;
use http\Exception\BadConversionException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;

class SetPinJob extends \Illuminate\Queue\Jobs\Job
{
    private $request;
    private $merchant;
    private $data;

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }



    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle()
    {
        $token = explode(' ', $this->request->header('Authorization'))[1];
        $data = get_object_vars(JWT::decode($token, env('JWT_SECRET'), ['HS512']));

        if ( isset($data['sub']) ) {
            $this->merchant = Merchant::where('merchant_id', $data['sub'])->first();

            if ($this->merchant <> null) {

                $this->merchant->pin = Hash::make($this->request->input('pin'));

                if ($this->merchant->save() <> true) {
                    throw new RuntimeException('pin creation failed', 505);
                }
            } else {
                throw new ModelNotFoundException('merchant not found', 401);
            }
        } else {
            throw new BadConversionException('token decryption failed', 500);
        }
    }

}