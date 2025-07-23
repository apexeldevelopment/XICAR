<?php

namespace App\Imports;

use App\Models\WeightUnit;
use App\Models\WeightUnitTranslation;
use App\Traits\ImageTrait;
use App\Traits\MerchantTrait;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Auth;
use DB;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use PhpOffice\PhpSpreadsheet\IOFactory;

class WalletReconcileImport implements SkipsEmptyRows, WithStartRow, ToArray
{
    use RemembersRowNumber, ImageTrait, MerchantTrait;

    protected $data = [];

    public function startRow(): int
    {
        return 2;
    }

    public function array(array $array)
    {
        $reconciled = [];
        foreach($array as $key => $row){
            if(empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]))
                continue;

            $reconciled[] = array(
                'driver_id' => $row[0],
                'type' => $row[2],
                'amount' => $row[1],
                'narration' => $row[3]
            );
        }

        $this->data = $reconciled;
        return $reconciled;

    }

    public function getData()
    {
        return $this->data;
    }
}
