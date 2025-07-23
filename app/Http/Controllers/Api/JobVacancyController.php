<?php

namespace App\Http\Controllers\Api;

use App\Models\JobVacancy;
use App\Models\JobVacancyTranslation;

use App\Traits\MerchantTrait;
use Auth;
use App;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\AppliedJob;
use DB;
use App\Traits\ApiResponseTrait;
use App\Traits\ImageTrait;

class JobVacancyController extends Controller
{
    use  ImageTrait, MerchantTrait, ApiResponseTrait;

    public function jobVacanies(Request $request)
    {
        try {
            $user = $request->user('api');
            $user_id = $user->id;
            $date = date('Y-m-d');
            $merchant_id = $request->merchant_id;
            $segment_id = $request->segment_id;
            $search_text = $request->search_text;
            $string_file = $this->getStringFile($merchant_id);
            $query = JobVacancy::where([['segment_id', '=', $segment_id], ['merchant_id', '=', $merchant_id], ['status', '=', 1]])
                ->where(function ($q) use ($date) {
                    $q->where('start_date', '<=', $date)->orWhere('start_date', null);
                })
                ->where(function ($q) use ($date) {
                    $q->where('end_date', '>=', $date)->orWhere('end_date', null);
                })
                ->with(['AppliedJobs' => function ($q) use ($user_id) {
                    $q->where('user_id', $user_id);
                }]);
            if ($search_text) {
                $query->whereHas('LanguageSingle', function ($qq) use ($search_text) {
                    $qq->where('title', 'LIKE', '%' . $search_text . '%');
                });
            }
            $job_vacancies =  $query->get();
            $return_data = $job_vacancies->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->LanguageSingle->title,
                    'description' => $item->LanguageSingle->description,
                    'organization' => $item->LanguageSingle->organization,
                    'posted_at' => $item->start_date,
                    'already_applied'=>$item->AppliedJobs->count() > 0 ? true:false
                ];
            });

            return $this->successResponse(trans("$string_file.success"), $return_data);
        } catch (\Throwable $th) {
            return $this->failedResponse($th->getMessage());
        }
    }

    /**
     * Jo apply request from user
     */
    public function applyJob(Request $request)
    {
        $user = $request->user('api');
        $validator = Validator::make($request->all(), [
            'job_vacancy_id' => [
                'required',
                Rule::exists('job_vacancies', 'id')->where(function ($query) use ($request) {
                    $query->where('merchant_id', $request->merchant_id);
                }),
            ],
            'cv' => 'required',

        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }

        try {
            $string_file = $this->getStringFile(NULL, $user->Merchant);
            $applied = AppliedJob::where('user_id', $user->id)->where('job_vacancy_id', $request->job_vacancy_id)->first();
            if ($applied && $applied->id) {
                return $this->failedResponse(trans("$string_file.job_already_applied"));
            }

            $applied_job = new AppliedJob;
            $cv = $this->uploadImage('cv', 'user_document', $user->merchant_id);
            $applied_job->merchant_id = $user->merchant_id;
            $applied_job->user_id = $user->id;
            $applied_job->job_vacancy_id = $request->job_vacancy_id;
            $applied_job->cv = $cv;
            $applied_job->notes = $request->notes;
            $applied_job->save();
            return $this->successResponse(trans("$string_file.applied_success"));
        } catch (\Throwable $th) {
            return $this->failedResponse($th->getMessage());
        }
    }
}
