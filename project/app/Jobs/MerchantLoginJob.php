<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 4/20/2018
 * Time: 8:47 AM
 */

namespace App\Jobs;


use App\Merchant;
use App\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\UnauthorizedException;

class MerchantLoginJob extends \Illuminate\Queue\Jobs\Job
{
    private $user;
    private $merchant;
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return mixed
     */
    public function getMerchant()
    {
        return $this->merchant;
    }

    public function isSetPin()
    {
        return in_array($this->user->pin, [ null, '' ]);
    }


    public function handle()
    {
        $this->user = Merchant::where('merchant_id', $this->request->input('merchant_id'))->first();
        $this->merchant = User::where('user_name', $this->user->apiuser)->first();

        if ($this->user <> null) {

            if (Hash::check($this->request->input('password'), $this->user->password)) {

                if ($this->merchant === null) {

                    throw new UnauthorizedException('merchant not found!', 401);

                }

            } else {
                throw new UnauthorizedException('merchant not found!', 401);
            }

        } else {
            throw new ModelNotFoundException('user not found');
        }
    }
}