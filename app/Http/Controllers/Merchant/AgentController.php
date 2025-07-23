<?php

namespace App\Http\Controllers\Merchant;

use App\Models\AccountType;
use App\Models\Agent;
use App\Models\Country;
use App\Models\Merchant;
use App\Traits\ImageTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;
use App\Traits\MerchantTrait;

class AgentController extends Controller
{
    use ImageTrait,MerchantTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $merchant_id = get_merchant_id();
        $merchant = Merchant::find($merchant_id);
        $account_types = $merchant->AccountType;
        $agents = Agent::where([['merchant_id', '=', $merchant_id]])->latest()->paginate(10);
        $string_file = $this->getStringFile($merchant_id);
        return view('merchant.agent.index',compact('agents','merchant', 'account_types','string_file'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function add(Request $request , $id = NULL)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $config = $merchant;
        $agent = !empty($id) ? Agent::Find($id) : null;
        $countries = $merchant->Country;
        $account_types = $config->AccountType->where('admin_delete', '!=', 1);
        $string_file = $this->getStringFile($merchant_id);
        if($account_types->count() <= 0){
            return redirect()->back()->withErrors(trans($string_file.'.create_account_type_first'));
        }
        return view('merchant.agent.create',compact('countries', 'account_types','agent'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function save(Request $request, $id=NULL)
    {
        $merchant_id = get_merchant_id();
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'email' => ['required',
                Rule::unique('agents')->where(function($query)use($merchant_id,$id){
                    $query->where([['merchant_id','=',$merchant_id]]);
                    $query->where([['id','!=',$id]]);
                })],
            'phone' => ['required',
                Rule::unique('agents')->where(function($query)use($merchant_id,$id){
                    $query->where([['merchant_id','=',$merchant_id]]);
                    $query->where([['id','!=',$id]]);
                })],
            'password' => 'required_without:id',
            'agent_logo' => 'required_without:id',
            'country' => 'required',
            'contact_person' => 'required',
            'address' => 'required',
//            'bank_name' => 'required',
//            'account_holder_name' => 'required',
//            'account_number' => 'required',
//            'online_transaction' => 'required',
//            'account_types' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput()->withErrors($errors);
        }
        // Begin Transaction
        DB::beginTransaction();
        try
        {
            $data = $request->except('_token', '_method');
            if(!empty($id))
            {
                $agent = Agent::find($id);
            }
            else
            {
                $agent = new Agent();
                $agent->alias_name = str_slug($request->input('name'));
                $agent->merchant_id = $merchant_id;
            }

            if($request->password){
                $password = Hash::make($request->password);
                $agent->password = $password;
            }
            if($request->hasFile('agent_logo')){
                $agent->agent_image = $this->uploadImage('agent_logo','agent_logo');
            }
            $agent->name = $request->name;
            $agent->phone = $request->phone;
            $agent->email = $request->email;
            $agent->contact_person = $request->contact_person;
            $agent->country_id = $request->country;
            $agent->address = $request->address;
//            $agent->bank_name = $request->bank_name;
//            $agent->account_holder_name = $request->account_holder_name;
//            $agent->account_number = $request->account_number;
//            $agent->online_transaction = $request->online_transaction;
//            $agent->account_type_id = $request->account_types;
            $agent->save();
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // Rollback Transaction
            DB::rollback();
            p($e->getTraceAsString());
            return redirect()->back()->withErrors($message);
        }
        // Commit Transaction
        DB::commit();
        $string_file = $this->getStringFile($merchant_id);
        return redirect()->route('merchant.agents')->withSuccess(trans($string_file.".saved_successfully"));
    }

    public function StatusUpdate(Request $request,$id)
    {
        $agent = Agent::find($id);
        $agent->status = $request->status;
        $string_file = $this->getStringFile($agent->merchant_id);
        $agent->save();
        return redirect()->back()->withSuccess(trans("$string_file.status_updated"));
    }
}
