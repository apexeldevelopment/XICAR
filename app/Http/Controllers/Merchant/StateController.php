<?php

namespace App\Http\Controllers\Merchant;

use App\Models\Country;
use App\Models\LangName;
use App\Models\State;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        /*if (!Auth::user('merchant')->can('view_countries')) {
            abort(404, 'Unauthorized action.');
        }*/
        return redirect()->back();
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $states = State::where([['admin_delete',0], ['merchant_id', $merchant_id]])->latest()->paginate(25);
    
        return view('merchant.states.index', compact('states'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        /*if (!Auth::user('merchant')->can('create_states')) {
            abort(404, 'Unauthorized action.');
        }*/
        return redirect()->back();
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $countries = Country::where([['merchant_id', $merchant_id]])->latest()->get();
        return view('merchant.states.create', compact('countries'));
    }

    public function edit($id)
    {
        return redirect()->back();
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $edit = State::where([['admin_delete',0],['merchant_id',$merchant_id]])->FindorFail($id);
        $countries = Country::where([['merchant_id', $merchant_id]])->latest()->get();
        return view('merchant.states.edit', compact('edit','countries'));

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return redirect()->back();
        Validator::make($request->all(),[
            'name'=>'required',
            'state_description'=> 'nullable|max:200',
            'country_id' => 'required|exists:countries,id',
            'status' => 'integer|required|between:0,1'
        ],[
            'status.between' => trans('admin.invalid_status'),
            'country_id.exists' => trans('admin.invalid_country_id'),
        ])->validate();

        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $request->request->add(['merchant_id'=>$merchant_id]);
        $data = $request->except('_token','_method','name','state_description');
        $lang_data = $request->only(['name', 'state_description']);
        $store = new State($data);
        if($store->save()):
            $this->saveLangStates(collect($lang_data), $store);
            request()->session()->flash('message', trans('admin.state_added'));
            return redirect()->route('states.index');
        else:
            request()->session()->flash('error', trans('admin.state_addederror'));
            return redirect()->route('states.index');
        endif;
    }

    private function saveLangStates(Collection $collection, State $model_data)
    {
        $collect_lang_data = $collection->toArray();
        $update_lang_pro = $model_data->LangStateSingle;
        if(!empty($update_lang_pro)){
            $update_lang_pro['name'] = $collect_lang_data['name'];
            $update_lang_pro['field_one'] = $collect_lang_data['state_description'];
            $update_lang_pro->save();
        }else{
            $language_data = new LangName([
                'merchant_id' => $model_data->merchant_id,
                'locale' => \App::getLocale(),
                'name' => $collect_lang_data['name'],
                'field_one' => $collect_lang_data['state_description'],
            ]);
            $saved_lang_data = $model_data->LangStates()->save($language_data);
        }
    }

    public function update(Request $request, $id)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $request->request->add(['id'=>$id]);
        Validator::make($request->all(),[
            'id'=>['required',
                Rule::exists('states', 'id')->where(function ($query) use($merchant_id){
                    $query->where([['admin_delete',0]]);
                })],
            'name'=>'required',
            'state_description'=> 'nullable|max:200',
            'country_id' => 'required|exists:countries,id',
            'status' => 'integer|required|between:0,1'
        ],[
            'status.between' => trans('admin.invalid_status'),
            'id.exists' => trans('admin.invalid_state_id'),
            'country_id.exists' => trans('admin.invalid_country_id'),
        ])->validate();

        $update = State::where([['merchant_id',$merchant_id],['admin_delete',0]])->findorfail($id);
        $data = $request->except('_token','_method','id','name','state_description');
        $update->country_id = $data['country_id'];
        $update->status = $data['status'];
        $lang_data = $request->only(['name', 'state_description']);
        if($update->save()):
            $this->saveLangStates(collect($lang_data), $update);
            request()->session()->flash('message', trans('admin.state_updated'));
            return redirect()->route('states.index');
        else:
            request()->session()->flash('error', trans('admin.state_addederror'));
            return redirect()->route('states.index');
        endif;

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    public function Change_Status(Request $request, $id = null , $status = null)
    {
        return redirect()->back();
        $request->request->add(['status'=>$status,'id'=>$id]);
        Validator::make($request->all(),[
            'id'=>'required|exists:states,id',
            'status' => 'integer|required|between:0,1'
        ],[
            'status.between' => trans('admin.invalid_status'),
            'id.exists' => trans('admin.invalid_state_id'),
        ])->validate();
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $change = State::where([['admin_delete',0],['merchant_id',$merchant_id]])->FindorFail($id);
        $change->status = $status;
        $change->save();
        if ($status == 1)
        {
            request()->session()->flash('message', trans('admin.state_activated'));
        } else {
            request()->session()->flash('error', trans('admin.state_deactivated'));
        }
        return redirect()->route('states.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $merchant_id = Auth::user('merchant')->parent_id != 0 ? Auth::user('merchant')->parent_id : Auth::user('merchant')->id;
        $delete = State::where([['merchant_id',$merchant_id],['admin_delete',0]])->findorfail($id);
        $delete->status = 0;
        $delete->admin_delete = 1;
        $delete->save();
        request()->session()->flash('error', trans('admin.state_deleted'));
        echo trans('admin.state_deleted');
        //return redirect()->route('subscription.index');
    }
}
