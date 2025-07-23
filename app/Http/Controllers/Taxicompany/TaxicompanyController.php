<?php

namespace App\Http\Controllers\Taxicompany;

use App\Models\TaxiCompaniesWalletTransaction;
use App\Models\TaxiCompany;
use App\Traits\RatingTrait;
use App\Traits\MerchantTrait;
use DB;
use URL;
use Auth;
use Storage;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentOptionsConfiguration;
use App\Http\Controllers\PaymentMethods\StripeCardPayment;
use App\Models\TaxiCompanyCashout;

class TaxicompanyController extends Controller
{
    use RatingTrait,MerchantTrait;

    public function __construct()
    {
        //$this->middleware('auth:admin');
    }

    public function dashboard()
    {
        $taxicompany = get_taxicompany();
        $taxicompany_id = $taxicompany->id;
        $curreny = isset($taxicompany->Country->isoCode) ? $taxicompany->Country->isoCode : 'INR';
        $booking = Booking::where([['taxi_company_id', $taxicompany_id]])->count();
        $earings = $curreny.' '.Booking::where([['taxi_company_id', $taxicompany_id], ['booking_status', '=', 1005]])->join('booking_transactions', 'bookings.id', '=', 'booking_transactions.booking_id')->sum('booking_transactions.driver_total_payout_amount');
        $complete = Booking::where([['taxi_company_id', $taxicompany_id], ['booking_status', '=', 1005]])->count();
        $users = User::where([['taxi_company_id', $taxicompany_id], ['user_delete', '=', NULL]])->count();
        $drivers = Driver::where([['taxi_company_id', $taxicompany_id], ['driver_delete', '=', NULL],['signupStep','=',9]])->count();
        $activebookings = Booking::where([['taxi_company_id', $taxicompany_id]])->whereIn('booking_status', [1001, 1002, 1003, 1004])->count();
        $cancelbookings = Booking::where([['taxi_company_id', $taxicompany_id]])->whereIn('booking_status', [1006, 1007, 1008])->count();
        $wallet_money = $curreny.' '.$taxicompany->wallet_money;
        return view('taxicompany.dashboard', compact('complete', 'activebookings', 'booking', 'users', 'drivers', 'earings', 'cancelbookings', 'wallet_money'));
    }
    
    public function Profile()
    {
        $taxicompany = get_taxicompany();
        return view('taxicompany.random.profile', compact('taxicompany'));
    }

    public function UpdateProfile(Request $request)
    {
        $taxicompany = get_taxicompany();
        $request->validate([
            'name' => 'required',
            'email' => ['required',
                Rule::unique('taxi_companies')->where(function($query)use($taxicompany){
                    $query->where([['merchant_id','=',$taxicompany->merchant_id]]);
                })->ignore($taxicompany->id)
            ],
            'phone' => ['numeric','required',
                Rule::unique('taxi_companies')->where(function($query)use($taxicompany){
                    $query->where([['merchant_id','=',$taxicompany->merchant_id]]);
                })->ignore($taxicompany->id)
            ],
            'contact_person' => 'required',
            'address' => 'required',
            'password' => 'required_if:edit_password,1'
        ]);
        DB::beginTransaction();
        try {
            $taxicompany->name = $request->name;
            $taxicompany->email = $request->email;
            $taxicompany->phone = $request->phone;
            $taxicompany->address = $request->address;
            $taxicompany->contact_person = $request->contact_person;
            if ($request->edit_password == 1) {
                $password = Hash::make($request->password);
                $taxicompany->password = $password;
            }
            $taxicompany->save();
        } catch (\Exception $e) {
            $message = $e->getMessage();
            p($message);
            // Rollback Transaction
            DB::rollback();
        }
        DB::commit();
        return redirect()->back()->with('message181', trans('admin.message181'));
    }

    public function Ratings()
    {
        $ratings = $this->getAllRating(true, 'TAXICOMPANY');
        return view('taxicompany.random.ratings', compact('ratings'));
    }

    public function SearchRating(Request $request)
    {
        $query = $this->getAllRating(false);
        if ($request->booking_id) {
            $keyword = $request->booking_id;
            $query->WhereHas('Booking', function ($q) use ($keyword) {
                $q->where('merchant_booking_id',$keyword);
            });
        }
        if ($request->rider) {
            $keyword = $request->rider;
            $query->WhereHas('Booking', function ($q) use ($keyword) {
                $q->WhereHas('User', function ($qu) use ($keyword) {
                    $qu->where('UserName', 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")->orWhere('UserPhone', 'LIKE', "%$keyword%");
                });
            });
        }
        if ($request->driver) {
            $driverKeyword = $request->driver;
            $query->WhereHas('Booking', function ($q) use ($driverKeyword) {
                $q->WhereHas('Driver', function ($qu) use ($driverKeyword) {
                    $qu->where('fullName', 'LIKE', "%$driverKeyword%")->orWhere('email', 'LIKE', "%$driverKeyword%")->orWhere('phoneNumber', 'LIKE', "%$driverKeyword%");
                });
            });
        }
        $ratings = $query->paginate(25);
        return view('taxicompany.random.ratings', compact('ratings'));
    }

    public function Wallet()
    {
        $taxi_company = get_taxicompany();
        $wallet_transactions = TaxiCompaniesWalletTransaction::where([['taxi_company_id', '=', $taxi_company->id]])->paginate(25);
        return view('taxicompany.random.wallet', compact('wallet_transactions', 'taxi_company'));
    }

    public function walletRecharge(){
        $taxi_company = get_taxicompany();
        $payment_config = PaymentOptionsConfiguration::where([['payment_option_id', '=', 1], ['merchant_id', '=', $taxi_company->merchant_id]])->first();
        $currency = $taxi_company->Country->isoCode;
        if (!empty($payment_config) > 0) {
            return view("taxicompany.random.wallet-recharge", compact('payment_config', 'currency'));
        }
        return redirect()->back()->with('success',"stripe is not configured");
    }
    
    public function cashouts(Request $request){
        $data = [];
        $taxi_company = get_taxicompany();
        $merchant_id = $taxi_company->merchant_id;

        $request->merge(['search_route'=>route('taxicompany.cashouts')]);
        // $order_con = new WalletTransactionController;
        
        $request->merge(['calling_view'=>"cashout-list"]);
        // $search_view = $order_con->walletSearchView($request);
        // $data['arr_search'] = $request->all();
        // $business_segment = get_business_segment(false);
        $cashout_requests = TaxiCompanyCashout::where('taxi_company_id',$taxi_company->id)
                            // ->where(function ($query) use ($request) {
                            //         if($request->start) {
                            //             $start_date = date('Y-m-d',strtotime($request->start));
                            //             $end_date = date('Y-m-d ',strtotime($request->end));
                            //             $query->whereBetween(DB::raw('DATE(created_at)'), [$start_date,$end_date]);
                            //         }
                            //     })
                            ->latest()->paginate(25);
        return view('taxicompany.random.wallet-cashout',compact('taxi_company','cashout_requests'));
    }
    
    public function cashoutRequest(Request $request){
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return redirect()->back()->withInput($request->input())->withErrors($errors);
        }
        // dd($request->all());
        DB::beginTransaction();
        try{
           $taxi_company = get_taxicompany();
            $merchant_id = $taxi_company->merchant_id;
            $string_file = $this->getStringFile($merchant_id);
            $taxiCompanyCashout = TaxiCompanyCashout::where(['taxi_company_id'=>$taxi_company->id,'cashout_status'=>0])->get();
            $taxi_company_cashout_amount_requested = 0;
            foreach($taxiCompanyCashout as $cashout){
                $taxi_company_cashout_amount_requested += $cashout->amount;
            }

            $taxi_company_remaining_amount = $taxi_company->wallet_money - $taxi_company_cashout_amount_requested;
            if($request->amount > $taxi_company_remaining_amount){
                return redirect()->back()->with('error',trans("$string_file.some_pending_amount_which_already_exceeded_from_wallet_amount"));
            }
            
            if($taxi_company->wallet_money < $request->amount){
                return redirect()->back()->with('error',trans("$string_file.low_wallet_warning"));
            }
            
            TaxiCompanyCashout::create([
                'taxi_company_id' => $taxi_company->id,
                'merchant_id' => $taxi_company->merchant_id,
                'amount' => $request->amount,
            ]);
            DB::commit();
        }catch (\Exception $e){
            DB::rollBack();
            p($e->getMessage());
            return redirect()->back()->withErrors($e->getMessage());
        }
        return redirect()->back()->with('success',trans("$string_file.cashout_request_registered_successfully"));
    }
}
