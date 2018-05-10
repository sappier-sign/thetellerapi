<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 5/2/2018
 * Time: 2:59 PM
 */

class TransactionTest extends TestCase
{
    public function testResponseMessage()
    {
        $code = '00';
        $this->assertEquals('000', \App\Transaction::responseMessage($code)['code']);
    }

    public function testGetSponsorCode()
    {
        $sponsor = 'ATL';
        $this->assertEquals(203, \App\Transaction::getSponsorCode($sponsor));
    }
}