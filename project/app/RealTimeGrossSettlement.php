<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 5/3/2018
 * Time: 3:07 PM
 */

namespace App;


class RealTimeGrossSettlement
{
    private $file_name;
    private $cycle;
    private $date;

//    private $things = [
//        [
//            'acc'           => '0143001109000',
//            'file_date'     => '100519',
//            'debit'         => '000000000000000000',
//            'credit'        => '000000000000000100',
//            'description'   => 'theTeller',
//            'blank'         => '           ',
//            'bank_reference' => 'GCB',
//            'current_date'  => '030518'
//        ],
//        [
//            'acc'           => '0143001109000',
//            'file_date'     => '100519',
//            'debit'         => '000000000000000000',
//            'credit'        => '000000000000000100',
//            'description'   => 'theTeller',
//            'blank'         => '           ',
//            'bank_reference' => 'GCB',
//            'current_date'  => '030518'
//        ],
//        [
//            'acc'           => '0143001109000',
//            'file_date'     => '100519',
//            'debit'         => '000000000000000000',
//            'credit'        => '000000000000000100',
//            'description'   => 'theTeller',
//            'blank'         => '           ',
//            'bank_reference' => 'GCB',
//            'current_date'  => '030518'
//        ],
//        [
//            'acc'           => '0143001109000',
//            'file_date'     => '100519',
//            'debit'         => '000000000000000000',
//            'credit'        => '000000000000000100',
//            'description'   => 'theTeller',
//            'blank'         => '           ',
//            'bank_reference' => 'GCB',
//            'current_date'  => '030518'
//        ],
//        [
//            'acc'           => '0143001109000',
//            'file_date'     => '100519',
//            'debit'         => '000000000000000000',
//            'credit'        => '000000000000000100',
//            'description'   => 'theTeller',
//            'blank'         => '           ',
//            'bank_reference' => 'GCB',
//            'current_date'  => '030518'
//        ]
//    ];

    /**
     * RealTimeGrossSettlement constructor.
     * @param array $data
     * @param int $cycle
     * @param null $date
     */
    public function __construct(array $data , int $cycle = 1, $date = null)
    {
        $this->data = $data;
        $this->date = $date;
        $this->cycle = $cycle;
        $this->generateFileName();

    }

    /**
     * Generate RTGS File
     */
    public function generateFileName(): void
    {
        if ($this->date === null) {
            $date = date('dmy');
        }

        $this->file_name = 'XXXPCL_RTGS_'.$date.'_00'.$this->cycle.'.txt';
    }

    /** Write Content to RTGS File
     * @return string
     */
    public function generateFile(): bool
    {
        $content = '';
        foreach ($this->data as $datum) {
            $content .= implode($datum, '')."\n";
        }

        try {
            $file = fopen($this->file_name, 'w+');
            fwrite($file, $content);
            fclose($file);
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }
}