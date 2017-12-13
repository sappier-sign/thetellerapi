<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 11/10/2017
 * Time: 2:03 PM
 */

namespace App\Http\Middleware;
use App\Merchant;
use App\Terminal;
use Closure;

class AuthPos
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->input('merchant_id', null) === null){ //Check if the merchant id is set
            return ['status' => 'error', 'code' => 422, 'reason' => 'merchant_id is not set'];
        } elseif ($request->input('terminal_id', null) === null){ //Check if the terminal id is set
            return ['status' => 'error', 'code' => 422, 'reason' => 'terminal_id is not set'];
        } elseif (Merchant::where('merchant_id', $request->merchant_id)->count() === 0) { //Check if the merchant id belongs to a user
            return ['status' => 'error', 'code' => 422, 'reason' => 'unknown merchant!'];
        } elseif (Terminal::where('merchant_id', $request->merchant_id)->where('t_id', $request->terminal_id)->count() === 0){
            return ['status' => 'error', 'code' => 422, 'reason' => 'terminal id does not belong to merchant'];
        }
        return $next($request);
    }
}