<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Helper\AjaxController;
use App\Http\Requests\JobVacancyRequest;

use App\Models\InfoSetting;
use App\Models\JobVacancy;
use App\Models\JobVacancyTranslation;
use App\Traits\AreaTrait;
use App\Traits\MerchantTrait;
use Auth;
use App;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\AppliedJob;
use DB;

class JobVacancyController extends Controller
{
    use AreaTrait, MerchantTrait;

    public function __construct()
    {
        $info_setting = InfoSetting::where('slug', 'JOBVACANCY_MANAGEMENT')->first();
        view()->share('info_setting', $info_setting);
    }

    public function index(Request $request)
    {

        $merchant_id = get_merchant_id();
        $job_vacancies = JobVacancy::where('merchant_id',$merchant_id)->latest()->paginate(25);
        // $ajax = new AjaxController;
        // $segment_list = get_merchant_segment(true,null);
        return view('merchant.job-vacancy.index', compact('job_vacancies'));
    }

    public function add(Request $request, $id = NULL)
    {


       

        $job_vacancy = NULL;

        if (!empty($id)) {
            $job_vacancy = JobVacancy::findOrFail($id);

            // $area_id = $JobVacancy->country_area_id;
        }
        return view('merchant.job-vacancy.create', compact('job_vacancy'));
    }


    public function save(JobVacancyRequest $request, $id = NULL)
    {

        DB::beginTransaction();
        try {

            $merchant_id = get_merchant_id();
            if (!empty($id)) {
                $job_vacancy = JobVacancy::findOrFail($id);
            } else {
                $job_vacancy = new JobVacancy;
                //            'corporate_id' => $request->corporate_id,
                //            'country_area_id' => $request->area,
                $job_vacancy->merchant_id = $merchant_id;
                // $JobVacancy->country_area_id = $request->area;
                // will make segment dynamically
                $job_vacancy->segment_id = 180; // Job Offer Segment
            }



            $job_vacancy->status = $request->status;
            $job_vacancy->start_date = $request->start_date;
            $job_vacancy->end_date = $request->end_date;
            $job_vacancy->type = 1; // full tym

            $job_vacancy->save();


            $this->SaveLanguage($job_vacancy->id, $request, $merchant_id);
            DB::commit();
            $string_file = $this->getStringFile(NULL, $job_vacancy->Merchant);
            return redirect()->route('merchant.jobs.index')->withSuccess(trans("$string_file.saved_successfully"));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function SaveLanguage($id, $request, $merchant_id)
    {
        JobVacancyTranslation::updateOrCreate([
            'job_vacancy_id' => $id, 'locale' => App::getLocale()
        ], [
            'merchant_id' => $merchant_id,
            'title' => $request->title,
            'description' => $request->description,
            'organization' => $request->organization,
        ]);
    }

    public function destroy($id)
    {
        $JobVacancy = JobVacancy::findOrFail($id);
        $string_file = $this->getStringFile(NULL, $JobVacancy->Merchant);
        $JobVacancy->delete();
        return redirect()->route('merchant.jobs.index')->withSuccess(trans("$string_file.deleted_successfully"));
        //        return redirect()->back()->with('success',trans('admin.referral_delete'));
    }


    // public function ChangeStatus($id, $status)
    // {
    //     $validator = Validator::make(
    //         [
    //             'id' => $id,
    //             'status' => $status,
    //         ],
    //         [
    //             'id' => ['required'],
    //             'status' => ['required', 'integer', 'between:1,2'],
    //         ]
    //     );
    //     if ($validator->fails()) {
    //         return redirect()->back()->withErrors('There is an error changing the status');
    //     }
    //     $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
    //     $JobVacancy = JobVacancy::where([['merchant_id', '=', $merchant_id]])->findOrFail($id);
    //     $JobVacancy->promo_code_status = $status;
    //     $JobVacancy->save();
    //     return redirect()->route('JobVacancy.index')->with('success', 'JobVacancy Status Updated');
    //     //        return redirect()->route('JobVacancy.index');
    // }

    public function appliedJobs(Request $request)
    {

        $merchant_id = get_merchant_id();
        $applied_jobs = AppliedJob::where('merchant_id',$merchant_id)->latest()->paginate(25);
        // $ajax = new AjaxController;
        // $segment_list = get_merchant_segment(true,null);
        return view('merchant.job-vacancy.applied_job', compact('applied_jobs'));
    }

}
