<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 4/25/2018
 * Time: 9:23 AM
 */

namespace App\Jobs;


use App\Merchant;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ChangePasswordJob extends \Illuminate\Queue\Jobs\Job
{

    private $request;
    private $merchant;

    public function __construct(Request $request, Merchant $merchant)
    {
        $this->request = $request;
        $this->merchant = $merchant;
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        $this->merchant->password = Hash::make($this->request->new_password);

        if (!$this->merchant->save()) {
            throw new Exception( 'wrong password');
        }

    }
}