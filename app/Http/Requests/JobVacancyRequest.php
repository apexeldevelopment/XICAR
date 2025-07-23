<?php

namespace App\Http\Requests;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class JobVacancyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules(Request $request)
    {
        $merchant_id = Auth::user()->id;
        $job_vacancy_id = $request->id;
        // $segment_id = $request->segment_id;
        return [
//            'price_card_ids' => 'required_without:id',
            // 'area' => 'required_without:id',
            // 'segment_id' => 'required_without:id',
          
            'title' => 'required',
            'description' => 'required',
            'status' => 'required',
            
        ];
    }
}
