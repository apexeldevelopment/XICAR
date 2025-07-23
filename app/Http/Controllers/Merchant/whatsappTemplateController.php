<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MerchantWhatsappTemplate;
use App\Models\Merchant;
use App\Traits\MerchantTrait;
use DB;


class whatsappTemplateController extends Controller
{
    use MerchantTrait;

    public function whatsappTemplate()
    {
        // $checkPermission = check_permission(1, 'view_email_configurations');
        // if ($checkPermission['isRedirect']) {
        //     return $checkPermission['redirectBack'];
        // }
        $merchant = get_merchant_id(false);
        $is_demo = $merchant->demo == 1 ? true : false;
        $merchant_id = $merchant->id;
        $start_event = MerchantWhatsappTemplate::where('merchant_id', $merchant_id)->where('event', 1004)->first();
        $end_event = MerchantWhatsappTemplate::where('merchant_id', $merchant_id)->where('event', 1005)->first();
        $book_event = MerchantWhatsappTemplate::where('merchant_id', $merchant_id)->where('event', 1002)->first();
        $cancelled_event = MerchantWhatsappTemplate::where('merchant_id', $merchant_id)->where('event', 1016)->first();
        $arrived_event = MerchantWhatsappTemplate::where('merchant_id', $merchant_id)->where('event', 1003)->first();
        $book_later_event = MerchantWhatsappTemplate::where('merchant_id', $merchant_id)->where('event', 999)->first();
        $book_later_start_to_pickup_event = MerchantWhatsappTemplate::where('merchant_id', $merchant_id)->where('event', 1012)->first();
        return view('merchant.random.whatsappTemplate', compact('start_event', 'end_event', 'book_event', 'cancelled_event', 'arrived_event', 'book_later_event', 'book_later_start_to_pickup_event'));
    }

    public function store(Request $request)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $string_file = $this->getStringFile(NULL, $merchant);
        // dd($request->all());

        DB::beginTransaction();
        try {

            if (isset($request->ride_book_template_name)) {
                if (!isset($request->ride_book_template_lang)) {
                    return redirect()->back()->withErrors("Template Name and Template Language both are required to save a booking event template !");
                }
            }
            $ride_book_variables = (isset($request->ride_book_variables)) ? implode(",", $request->ride_book_variables) : null;
            $ride_book_template = MerchantWhatsappTemplate::where("merchant_id", $merchant_id)->where("event", 1002)->first();
            $ride_book_data_data = [
                "merchant_id" => $merchant_id,
                "event" => 1002,
                "template_name" => (isset($request->ride_book_template_name)) ? $request->ride_book_template_name : null,
                "template_language" => (isset($request->ride_book_template_lang)) ? $request->ride_book_template_lang : null,
                "template_variables" => (isset($ride_book_variables)) ? $ride_book_variables : null,
            ];

            if (isset($ride_book_template)) {
                $ride_book_template->update($ride_book_data_data);
            } else {
                MerchantWhatsappTemplate::create($ride_book_data_data);
            }


            if (isset($request->ride_later_book_template_name)) {
                if (!isset($request->ride_later_book_template_lang)) {
                    return redirect()->back()->withErrors("Template Name and Template Language both are required to save a booking event template !");
                }
            }
            $ride_later_book_variables = (isset($request->ride_later_book_variables)) ? implode(",", $request->ride_later_book_variables) : null;
            $ride_later_template = MerchantWhatsappTemplate::where("merchant_id", $merchant_id)->where("event", 999)->first();
            $ride_later_data = [
                "merchant_id" => $merchant_id,
                "event" => 999,
                "template_name" => (isset($request->ride_later_book_template_name)) ? $request->ride_later_book_template_name : null,
                "template_language" => (isset($request->ride_later_book_template_lang)) ? $request->ride_later_book_template_lang : null,
                "template_variables" => (isset($ride_later_book_variables)) ? $ride_later_book_variables : null,
            ];
            if (isset($ride_later_template)) {
                $ride_later_template->update($ride_later_data);
            } else {
                MerchantWhatsappTemplate::create($ride_later_data);
            }



            if (isset($request->ride_start_template_name)) {
                if (!isset($request->ride_start_template_lang)) {
                    return redirect()->back()->withErrors("Template Name and Template Language both are required to save a start event template !");
                }
            }
            $ride_start_variables = (isset($request->ride_start_variables)) ? implode(",", $request->ride_start_variables) : null;
            $ride_start_template = MerchantWhatsappTemplate::where("merchant_id", $merchant_id)->where("event", 1004)->first();
            $ride_start_data = [
                "merchant_id" => $merchant_id,
                "event" => 1004,
                "template_name" => (isset($request->ride_start_template_name)) ? $request->ride_start_template_name : null,
                "template_language" => (isset($request->ride_start_template_lang)) ? $request->ride_start_template_lang : null,
                "template_variables" => (isset($ride_start_variables)) ? $ride_start_variables : null,
            ];

            if (isset($ride_start_template)) {
                $ride_start_template->update($ride_start_data);
            } else {
                MerchantWhatsappTemplate::create($ride_start_data);
            }




            if (isset($request->ride_end_template_name)) {
                if (!isset($request->ride_end_template_lang)) {
                    return redirect()->back()->withErrors("Template Name and Template Language both are required to save a start event template !");
                }
            }
            $ride_end_variables = (isset($request->ride_end_variables)) ? implode(",", $request->ride_end_variables) : null;
            $ride_end_template = MerchantWhatsappTemplate::where("merchant_id", $merchant_id)->where("event", 1005)->first();
            $ride_end_data = [
                "merchant_id" => $merchant_id,
                "event" => 1005,
                "template_name" => (isset($request->ride_end_template_name)) ? $request->ride_end_template_name : null,
                "template_language" => (isset($request->ride_end_template_lang)) ? $request->ride_end_template_lang : null,
                "template_variables" => (isset($ride_end_variables)) ? $ride_end_variables : null,
            ];
            if (isset($ride_end_template)) {
                $ride_end_template->update($ride_end_data);
            } else {
                MerchantWhatsappTemplate::create($ride_end_data);
            }



            if (isset($request->cancelled_template_name)) {
                if (!isset($request->cancelled_template_lang)) {
                    return redirect()->back()->withErrors("Template Name and Template Language both are required to save a cancelled event template !");
                }
            }
            $cancelled_variables = (isset($request->cancelled_variables)) ? implode(",", $request->cancelled_variables) : null;
            $cancelled_template = MerchantWhatsappTemplate::where("merchant_id", $merchant_id)->where("event", 1016)->first();
            $cancelled_data = [
                "merchant_id" => $merchant_id,
                "event" => 1016,
                "template_name" => (isset($request->cancelled_template_name)) ? $request->cancelled_template_name : null,
                "template_language" => (isset($request->cancelled_template_lang)) ? $request->cancelled_template_lang : null,
                "template_variables" => (isset($cancelled_variables)) ? $cancelled_variables : null,
            ];
            if (isset($cancelled_template)) {
                $cancelled_template->update($cancelled_data);
            } else {
                MerchantWhatsappTemplate::create($cancelled_data);
            }



            if (isset($request->arrived_template_name)) {
                if (!isset($request->arrived_template_lang)) {
                    return redirect()->back()->withErrors("Template Name and Template Language both are required to save a arrived event template !");
                }
            }
            $arrived_variables = (isset($request->arrived_variables)) ? implode(",", $request->arrived_variables) : null;
            $arrived_template = MerchantWhatsappTemplate::where("merchant_id", $merchant_id)->where("event", 1003)->first();
            $arrived_data = [
                "merchant_id" => $merchant_id,
                "event" => 1003,
                "template_name" => (isset($request->arrived_template_name)) ? $request->arrived_template_name : null,
                "template_language" => (isset($request->arrived_template_lang)) ? $request->arrived_template_lang : null,
                "template_variables" => (isset($arrived_variables)) ? $arrived_variables : null,
            ];
            if (isset($arrived_template)) {
                $arrived_template->update($arrived_data);
            } else {
                MerchantWhatsappTemplate::create($arrived_data);
            }
            
            
            if (isset($request->ride_later_start_to_pickup_template_name)) {
                if (!isset($request->ride_later_start_to_pickup_template_lang)) {
                    return redirect()->back()->withErrors("Template Name and Template Language both are required to save a arrived event template !");
                }
            }
            $start_to_pickup_variables = (isset($request->ride_later_start_to_pickup_variables)) ? implode(",", $request->ride_later_start_to_pickup_variables) : null;
            $start_to_pickup_template = MerchantWhatsappTemplate::where("merchant_id", $merchant_id)->where("event", 1012)->first();
            $start_to_pickup_data = [
                "merchant_id" => $merchant_id,
                "event" => 1012,
                "template_name" => (isset($request->ride_later_start_to_pickup_template_name)) ? $request->ride_later_start_to_pickup_template_name : null,
                "template_language" => (isset($request->ride_later_start_to_pickup_template_lang)) ? $request->ride_later_start_to_pickup_template_lang : null,
                "template_variables" => (isset($start_to_pickup_variables)) ? $start_to_pickup_variables : null,
            ];
            if (isset($start_to_pickup_template)) {
                $start_to_pickup_template->update($start_to_pickup_data);
            } else {
                MerchantWhatsappTemplate::create($start_to_pickup_data);
            }
            
        } catch (\Exception $e) {
            $message = $e->getMessage();
            DB::rollback();
            return redirect()->back()->withErrors($message);
        }

        DB::commit();
        return redirect()->back()->withSuccess(trans("$string_file.saved_successfully"));
    }
}
