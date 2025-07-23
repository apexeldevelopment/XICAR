<?php
/**
 * Created by PhpStorm.
 * User: apporio
 * Date: 19/4/23
 * Time: 3:49 PM
 */

namespace App\Exports;


use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomExport implements FromArray, WithHeadings
{
    protected $data;
    protected $heading;

    public function __construct(array $heading, array $data)
    {
        $this->heading = $heading;
        $this->data = $data;
    }

    public function _construct(array $heading, array $data){

    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->heading;
    }

}
