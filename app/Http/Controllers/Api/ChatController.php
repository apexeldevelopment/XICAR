<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\BusinessSegment\Order;
use App\Models\Chat;
use App\Models\HandymanOrder;
use App\Models\Onesignal;
use App\Models\UserDevice;
use App\Traits\ApiResponseTrait;
use App\Traits\MerchantTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Models\BusinessSegment\BusinessSegment;
use App\Models\User;

class ChatController extends Controller
{
    use ApiResponseTrait,MerchantTrait;
    public function UserSendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required_without_all:order_id,handyman_order_id|exists:bookings,id',
            'order_id' => 'required_without_all:booking_id,handyman_order_id|exists:orders,id',
            'handyman_order_id' => 'required_without_all:booking_id,order_id|exists:handyman_orders,id',
            'message' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $chat_source = null;
            $key = "";
            if(isset($request->booking_id) && !empty($request->booking_id)){
                $chat_source = Booking::with('Driver')->find($request->booking_id);
                $key = "booking_id";
            }elseif(isset($request->order_id) && !empty($request->order_id)){
                $chat_source = Order::with('Driver')->find($request->order_id);
                $key = "order_id";
            }elseif(isset($request->handyman_order_id) && !empty($request->handyman_order_id)){
                $chat_source = HandymanOrder::with('Driver')->find($request->handyman_order_id);
                $key = "handyman_order_id";
            }else{
                throw new \Exception("Invalid Option");
            }
            $string_file = $this->getStringFile(NULL,$chat_source->Merchant);
            $chat = Chat::where([[$key, '=', $request->$key]])->first();

            $app = array('message' => $request->message, 'sender' => 'USER', 'timestamp' => time(), $key => $chat_source->id, 'driver' => $chat_source->Driver->fullName, 'username' => $chat_source->User->UserName);
            if (empty($chat)) {
                $message_array[] = $app;
                $message = json_encode($message_array);
            } else {
                $oldArray = $chat->chat;
                $message = json_decode($oldArray, true);
                $message_array = $app;
                array_push($message, $message_array);
                $message = json_encode($message);
            }
            $chatmessage = Chat::updateOrCreate(
                [$key => $request->$key],
                ['chat' => $message]
            );
            $chatmessage->chat = $app;
            $chatmessage->$key = (int)$chatmessage->$key;
            if (!empty($chat_source->driver_id)) {
                $data = array(
                    'notification_type' => "CHAT",
                    'segment_type' => $chat_source->Segment->slag,
                    'segment_data' => $chatmessage,
                    'notification_gen_time' => time(),
                );
                $large_icon = get_image($chat_source->Merchant->BusinessLogo, 'business_logo', $chat_source->merchant_id, true);
                $title = Str::ucfirst($chat_source->User->UserName);
                $message = $request->message;
                $arr_param = ['driver_id' => $chat_source->driver_id, 'data' => $data, 'message' => $message, 'merchant_id' => $chat_source->merchant_id, 'title' => $title, 'large_icon' => $large_icon];
                Onesignal::DriverPushMessage($arr_param);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.success"),$chatmessage);
    }

    public function DriverSendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required_without_all:order_id,handyman_order_id|exists:bookings,id',
            'order_id' => 'required_without_all:booking_id,handyman_order_id|exists:orders,id',
            'handyman_order_id' => 'required_without_all:booking_id,order_id|exists:handyman_orders,id',
            'message' => 'required|string',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $chat_source = null;
            $key = "";
            if(isset($request->booking_id) && !empty($request->booking_id)){
                $chat_source = Booking::with('Driver')->find($request->booking_id);
                $key = "booking_id";
            }elseif(isset($request->order_id) && !empty($request->order_id)){
                $chat_source = Order::with('Driver')->find($request->order_id);
                $key = "order_id";
            }elseif(isset($request->handyman_order_id) && !empty($request->handyman_order_id)){
                $chat_source = HandymanOrder::with('Driver')->find($request->handyman_order_id);
                $key = "handyman_order_id";
            }else{
                throw new \Exception("Invalid Option");
            }

            $string_file = $this->getStringFile(NULL,$chat_source->Merchant);
            $chat = Chat::where([[$key, '=', $request->$key]])->first();
            $app = array('message' => $request->message, 'sender' => 'DRIVER', 'timestamp' => time(), $key => $chat_source->id, 'driver' => $chat_source->Driver->fullName, 'username' => $chat_source->User->UserName);
            if (empty($chat)) {
                $message_array[] = $app;
                $message = json_encode($message_array);
            } else {
                $oldArray = $chat->chat;
                $message = json_decode($oldArray, true);
                $message_array = $app;
                array_push($message, $message_array);
                $message = json_encode($message);
            }
            $chatmessage = Chat::updateOrCreate(
                [$key => $request->$key],
                ['chat' => $message]
            );
            $user_id = $chat_source->user_id;
            //$title = "Message of booking #".$booking->merchant_booking_id;
            $title = $chat_source->Driver->first_name." ".$chat_source->Driver->last_name;
            $data['notification_type'] ="CHAT";
            $data['segment_type'] = $chat_source->Segment->slag;
            $data['segment_data'] = $app;
            $arr_param = ['user_id'=>$user_id,'data'=>$data,'message'=>$request->message,'merchant_id'=>$chat_source->merchant_id,'title'=>$title,'large_icon'=>''];
            Onesignal::UserPushMessage($arr_param);
//            $arr_param = ['user_id'=>$user_id,'data'=>$app,'message'=>$message,'merchant_id'=>$booking->merchant_id,'title'=>"Driver message",'large_icon'=>""];
//            Onesignal::UserPushMessage($arr_param);
            $chatmessage->chat = $app;
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.success"), $chatmessage);
//        return response()->json(['result' => "1", 'message' => trans("$string_file.success"), 'data' => $chatmessage]);
    }

    public function ChatHistory(Request $request)
    {
        // Type is used for driver api version response.
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required_without_all:order_id,handyman_order_id|exists:bookings,id',
            'order_id' => 'required_without_all:booking_id,handyman_order_id|exists:orders,id',
            'handyman_order_id' => 'required_without_all:booking_id,order_id|exists:handyman_orders,id',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        try{
            $string_file = $this->getStringFile($request->merchant_id);
            $chat_source = null;
            $key = "";
            if(isset($request->booking_id) && !empty($request->booking_id)){
                $chat_source = Booking::with('Driver')->find($request->booking_id);
                $key = "booking_id";
            }elseif(isset($request->order_id) && !empty($request->order_id)){
                $chat_source = Order::with('Driver')->find($request->order_id);
                $key = "order_id";
            }elseif(isset($request->handyman_order_id) && !empty($request->handyman_order_id)){
                $chat_source = HandymanOrder::with('Driver')->find($request->handyman_order_id);
                $key = "handyman_order_id";
            }else{
                throw new \Exception("Invalid Option");
            }
            $chatmessage = Chat::where([[$key, '=', $request->$key]])->first();
            if(isset($request->type) && $request->type == 2){
                $data = array(
                    "user_image" => get_image($chat_source->User->UserProfileImage, 'user', $chat_source->merchant_id),
                    "user_name" => $chat_source->User->UserName,
                    "phone" => $chat_source->User->UserPhone,
                    "status_text" => $key == "booking_id" ? $chat_source->booking_status : $chat_source->order_status,
                    "booking_id" => $key == "booking_id" ? $chat_source->booking_id : "",
                    "order_id" => $key == "order_id" ? $chat_source->order_id : "",
                    "handyman_order_id" => $key == "handyman_order_id" ? $chat_source->handyman_order_id : "",
                    "number" => $key == "booking_id" ? $chat_source->merchant_booking_id : $chat_source->merchant_order_id,
                    "chat" => !empty($chatmessage) ? json_decode($chatmessage->chat, true) : []
                );
                return $this->successResponse(trans("$string_file.success"),$data);
            }else{
                if(!empty($chatmessage)){
                    $chatmessage->chat = json_decode($chatmessage->chat, true);
                }
                return response()->json(['result' => "1", 'message' => trans("$string_file.success"), 'data' => $chatmessage]);
            }
        }catch (\Exception $e){
            return $this->failedResponse($e->getMessage());
        }
    }

    public function UserSendMessageToStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_segment_id' => 'required|exists:business_segments,id',
            'message' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $user = $request->user('api');
            $business_segment = BusinessSegment::find($request->business_segment_id);
            $string_file = $this->getStringFile(NULL,$business_segment->Merchant);
            $chat = Chat::where([['business_segment_id', '=', $request->business_segment_id],['user_id','=',$user->id]])->first();
            $app = array('message' => $request->message, 'sender' => 'USER', 'timestamp' => time(), 'business_segment' => $business_segment->full_name, 'username' => $user->UserName);
            if (empty($chat)) {
                $message_array[] = $app;
                $message = json_encode($message_array);
            } else {
                $oldArray = $chat->chat;
                $message = json_decode($oldArray, true);
                $message_array = $app;
                array_push($message, $message_array);
                $message = json_encode($message);
            }
            $chatmessage = Chat::updateOrCreate(
                ['business_segment_id' => $request->business_segment_id, 'user_id' => $user->id],
                ['chat' => $message,'business_segment_id' => $request->business_segment_id, 'user_id' => $user->id]
            );
            $chatmessage->chat = $app;

            $data = array(
                'notification_type' => "CHAT_STORE_USER",
                'segment_type' => $business_segment->Segment->slag,
                'segment_data' => $chatmessage,
                'notification_gen_time' => time(),
            );
            $large_icon = get_image($business_segment->Merchant->BusinessLogo, 'business_logo', $business_segment->merchant_id, true);
            $title = Str::ucfirst($user->UserName);
            $message = $request->message;
            $arr_param = ['business_segment_id' => $business_segment->id, 'data' => $data, 'message' => $message, 'merchant_id' => $business_segment->merchant_id, 'title' => $title, 'large_icon' => $large_icon];

            $res = Onesignal::BusinessSegmentPushMessage($arr_param);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.success"),$chatmessage);
    }

    public function StoreSendMessageToUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            return $this->failedResponse($errors[0]);
        }
        DB::beginTransaction();
        try {
            $business_segment = $request->user('business-segment-api');
            $user = User::find($request->user_id);
            $string_file = $this->getStringFile(NULL,$user->Merchant);

            $chat = Chat::where([['business_segment_id', '=', $business_segment->id],['user_id','=',$user->id]])->first();

            $app = array('message' => $request->message, 'sender' => 'BUSINESS_SEGMENT', 'timestamp' => time(), 'business_segment' => $business_segment->full_name, 'username' => $user->UserName);
            if (empty($chat)) {
                $message_array[] = $app;
                $message = json_encode($message_array);
            } else {
                $oldArray = $chat->chat;
                $message = json_decode($oldArray, true);
                $message_array = $app;
                array_push($message, $message_array);
                $message = json_encode($message);
            }
            $chatmessage = Chat::updateOrCreate(
                ['business_segment_id' => $business_segment->id, 'user_id' => $user->id],
                ['chat' => $message,'business_segment_id' => $business_segment->id, 'user_id' => $user->id]
            );

            $user_id = $user->id;
            $title = Str::ucfirst($business_segment->full_name);
            $data['notification_type'] ="CHAT_STORE_USER";
            $data['segment_type'] = $business_segment->Segment->slag;
            $data['segment_data'] = $app;
            $data['business_segment_id'] = $business_segment->id;
            $arr_param = ['user_id'=>$user->id,'data'=>$data,'message'=>$message,'merchant_id'=>$user->merchant_id,'title'=>$title,'large_icon'=>''];
            $res = Onesignal::UserPushMessage($arr_param);
//            $arr_param = ['user_id'=>$user_id,'data'=>$app,'message'=>$message,'merchant_id'=>$booking->merchant_id,'title'=>"Driver message",'large_icon'=>""];
//            Onesignal::UserPushMessage($arr_param);
            $chatmessage->chat = $app;
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->failedResponse($e->getMessage());
        }
        DB::commit();
        return $this->successResponse(trans("$string_file.success"), $chatmessage);
//        return response()->json(['result' => "1", 'message' => trans("$string_file.success"), 'data' => $chatmessage]);
    }

    public function ChatHistoryBetweenStoreAndUser(Request $request)
    {
        // Type is used for driver api version response.
        $validator = Validator::make($request->all(), [
            'for' => 'required|in:USER,BUSINESS_SEGMENT',
            'business_segment_id' => 'required_if:for,=,USER',
            'user_id' => 'required_if:for,=,BUSINESS_SEGMENT',
        ]);
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            if(isset($request->type) && $request->type == 2){
                return $this->failedResponse($errors[0]);
            }else{
                return $this->failedResponse($errors[0]);
            }
        }
        try{
            $string_file = $this->getStringFile($request->merchant_id);

            if($request->for=='USER'){
                $user = $request->user('api');
                $business_segment = BusinessSegment::find($request->business_segment_id);
                $chatmessage = Chat::where([['user_id', '=', $user->id],['business_segment_id', '=', $request->business_segment_id]])->first();

                $data = array(
                    "user_image" => get_image($business_segment->business_logo, 'business_logo', $business_segment->merchant_id),
                    "user_name" => $business_segment->full_name,
                    "chat" => !empty($chatmessage) ? json_decode($chatmessage->chat, true) : []
                );
            }
            else{
                $business_segment = $request->user('business-segment-api');
                $chatmessage = Chat::where([['user_id', '=', $request->user_id],['business_segment_id', '=', $business_segment->id]])->first();
                $user = User::find($request->user_id);
                $data = array(
                    "user_image" => get_image($user->UserProfileImage, 'user', $user->merchant_id),
                    "user_name" => $user->UserName,
                    "chat" => !empty($chatmessage) ? json_decode($chatmessage->chat, true) : []
                );
            }

            return $this->successResponse(trans("$string_file.success"),$data);
        }catch (\Exception $e){
            if(isset($request->type) && $request->type == 2){
                return $this->failedResponse($e->getMessage());
            }else{
                return $this->failedResponse($e->getMessage());
            }
        }
    }

    public function ChatList(Request $request){
        $string_file = $this->getStringFile($request->merchant_id);
        $business_segment = $request->user('business-segment-api');
        $merchant_id = $request->merchant_id;
        $chatList = Chat::where([['business_segment_id', '=', $business_segment->id]])->orderBy('updated_at','DESC')->paginate(25);

        $list = $chatList->map(function ($item) use ( $merchant_id, $string_file) {
                $user = User::find($item->user_id);
                    return [
                        'user_id' =>$user->id,
                        'user_image' =>get_image($user->UserProfileImage, 'user', $user->merchant_id),
                        'user_name' =>$user->UserName
                    ];
                });

        $chatList = $chatList->toArray();
        $next_page_url = isset($chatList['next_page_url']) && !empty($chatList['next_page_url']) ? $chatList['next_page_url'] : "";
        $current_page = isset($chatList['current_page']) && !empty($chatList['current_page']) ? $chatList['current_page'] : 0;

        $response =[
            'current_page'=>$current_page,
            'next_page_url'=>$next_page_url,
            'response_data'=>$list
        ];
        return $this->successResponse(trans("$string_file.success"),$response);
    }
}
