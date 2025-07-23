<?php

namespace App\Traits;

use App\Models\BusinessSegment\BusinessSegment;
use App\Models\CountryArea;
use App\Models\LaundryOutlet\LaundryOutlet;
use App\Models\SegmentGroup;
use DB;
use App\Models\Segment;
use App\Models\ServiceType;
use App\Models\Merchant;
use App\Models\Category;
use App;
use Illuminate\Http\Request;
use App\Models\Configuration;
use App\Http\Controllers\Helper\Merchant as MerchantController;
use Auth;

trait MerchantTrait
{

    public function getMerchantSegmentServices($merchant_id, $call_from = '', $segment_group_id = NULL, $arr_segment = [], $country_area_id = NULL, $arr_segment_not_in = false, $arr_services = [], $sub_group_for_admin = NULL, $service_type = "ALL", $show_category = false)
    {
        $query = Segment::select('id', 'name', 'slag', 'icon', 'segment_group_id', 'sub_group_for_app', 'sub_group_for_admin')
            ->with(['Merchant' => function ($q) use ($merchant_id) {
                $q->where('id', $merchant_id);
                $q->select('id', 'sequence');
                $q->orderBy('sequence', 'ASC');
            }])
            ->whereHas('Merchant', function ($q) use ($merchant_id) {
                $q->select('merchants.id as merchant_id');
                $q->where('merchant_id', $merchant_id);
                $q->select('id', 'sequence');
                $q->orderBy('sequence', 'ASC');
                // $q->where('is_coming_soon', 2);
            })
            ->with(['ServiceType.Merchant' => function ($qq) use ($merchant_id, $arr_services) {
                // $qq->select('segment_id', 'id', 'serviceName', 'type', 'additional_support', 'owner');
                //  $qq->whereHas('Merchant',function($qqq) use($merchant_id){
                //     $qqq->where('merchant_id',$merchant_id);
                //     $qqq->orderBy('sequence','ASC');
                // });
                $qq->where('merchant_id', $merchant_id);
                if (!empty($arr_services)) {
                    $qq->whereIn('service_type_id', $arr_services);
                }
            }])
            ->whereHas('ServiceType', function ($qqq) use ($merchant_id, $service_type) {

                //                p($service_type);
                if (!empty($service_type) && $service_type == "SELF_PICKUP") {
                    $qqq->where('type', 6);
                } elseif (!empty($service_type) && $service_type == "DELIVERY") {
                    $qqq->where('type', 1);
                }
            })
            ->whereHas('ServiceType.Merchant', function ($qqq) use ($merchant_id) {
                // $qqq->whereHas('Merchant',function($qqq) use($merchant_id){
                $qqq->where('merchant_id', $merchant_id);
                //                    $qqq->orderBy('sequence','ASC');
                // });
                //                $qqq->orderBy('sequence','ASC');
            });
        if (!empty($country_area_id)) {
            $query->with(['ServiceType.CountryArea' => function ($qqq) use ($country_area_id) {
                $qqq->where('country_area_id', $country_area_id);
            }]);
            $query->whereHas('ServiceType.CountryArea', function ($qqq) use ($country_area_id) {
                $qqq->where('country_area_id', $country_area_id);
            });
        }
        if (!empty($segment_group_id)) {
            if (is_array($segment_group_id)) {
                // p($segment_group_id);
                $query->whereIn('segment_group_id', $segment_group_id);
            } else {
                $query->where('segment_group_id', $segment_group_id);
            }
        }
        if (!empty($sub_group_for_admin)) {
            $query->where('sub_group_for_admin', $sub_group_for_admin);
        }
        if (!empty($arr_segment)) {
            if ($arr_segment_not_in == true) {
                $query->whereNotIn('id', $arr_segment);
            } else {
                $query->whereIn('id', $arr_segment);
            }
        }
        $query->join('merchant_segment', 'merchant_segment.segment_id', '=', 'id');
        $query->where('merchant_segment.merchant_id', $merchant_id);
        $query->orderBy('merchant_segment.sequence');
        //        $query->where('is_coming_soon','!=',1);
        $segment_services = $query->get();
        // p($segment_services);
        if ($call_from == 'api' || $call_from == "service_type_screen") {
            // get coming soon segment of merchant
            $is_coming_query = Segment::select('id', 'name', 'slag', 'icon', 'segment_group_id', 'sub_group_for_app', 'sub_group_for_admin')
                ->with(['Merchant' => function ($q) use ($merchant_id) {
                    $q->where('id', $merchant_id);
                    $q->select('id')->where('is_coming_soon', 1);
                }])
                ->whereHas('Merchant', function ($q) use ($merchant_id) {
                    $q->where('merchant_id', $merchant_id);
                    $q->orderBy('sequence', 'ASC')->where('is_coming_soon', 1);
                })
                ->with(['ServiceType.Merchant' => function ($qq) use ($merchant_id, $arr_services) {
                    // $qq->select('segment_id', 'id', 'serviceName', 'type', 'additional_support', 'owner');
                    // $qq->whereHas('Merchant',function($qqq) use($merchant_id){
                    $qq->where('merchant_id', $merchant_id);
                    //     $qqq->orderBy('sequence','ASC');
                    // });
                    if (!empty($arr_services)) {
                        $qq->whereIn('id', $arr_services);
                    }
                }])
                ->whereHas('ServiceType.Merchant', function ($qqq) use ($merchant_id) {
                    $qqq->where('merchant_id', $merchant_id);
                    $qqq->orderBy('sequence', 'ASC');
                });
            if (!empty($segment_group_id)) {
                if (is_array($segment_group_id)) {
                    $is_coming_query->whereIn('segment_group_id', $segment_group_id);
                } else {
                    $is_coming_query->where('segment_group_id', $segment_group_id);
                }
            }
            $coming_query = $is_coming_query->get();
            $segment_services = $segment_services->merge($coming_query);
        }

        $arr_segment = $segment_services->map(function ($item) use ($merchant_id, $call_from, $country_area_id, $service_type, $show_category) {
            $default_grad_1 = "#FFF0D9";
            $default_grad_2 = "#FFFCEF";
            if ((!empty($item->Merchant[0]['pivot']->segment_background_gradient_1) && $item->Merchant[0]['pivot']->segment_background_gradient_1 == "#000000") || (!empty($item->Merchant[0]['pivot']->segment_background_gradient_2) && $item->Merchant[0]['pivot']->segment_background_gradient_2 == "#000000")) {
                if ($item->slag == 'TAXI') {
                    $default_grad_1 = "#FFF0D9";
                    $default_grad_2 = "#FFFCEF";
                } elseif ($item->slag == 'DELIVERY') {
                    $default_grad_1 = "#BCD0FF";
                    $default_grad_2 = "#FFF4D1";
                }
            }


            $name = $item->Name($merchant_id);
            return  [
                'segment_id' => $item->id,
                'slag' => $item->slag,
                'is_coming_soon' => !empty($item->Merchant[0]['pivot']->is_coming_soon) ? $item->Merchant[0]['pivot']->is_coming_soon : 2,
                'sub_group_for_app' => $item->sub_group_for_app,
                'segment_group_id' => $item->segment_group_id,
                'price_card_owner' => $item->Merchant[0]['pivot']->price_card_owner, // 1 admin, 2  driver
                'name' => !empty($name) ? $name : $item->slag,
                'segment_icon' => isset($item->Merchant[0]['pivot']->segment_icon) && !empty($item->Merchant[0]['pivot']->segment_icon) ? get_image($item->Merchant[0]['pivot']->segment_icon, 'segment', $merchant_id, true) :
                    get_image($item->icon, 'segment_super_admin', NULL, false),
                'arr_services' => $this->serviceTypes($item->ServiceType, $call_from, $merchant_id, $country_area_id, $service_type),
                'arr_categories' => $item->slag == "TAXI" && $show_category ? $this->getTaxiCategory($merchant_id, $item->id, $country_area_id) : [],
                'dynamic_url' => !empty($item->Merchant[0]['pivot']->dynamic_url) ? $item->Merchant[0]['pivot']->dynamic_url : "",
                'segment_background_gradient_1' => !empty($item->Merchant[0]['pivot']->segment_background_gradient_1) ? $item->Merchant[0]['pivot']->segment_background_gradient_1 : "#FFF0D9",
                'segment_background_gradient_2' => !empty($item->Merchant[0]['pivot']->segment_background_gradient_2) ? $item->Merchant[0]['pivot']->segment_background_gradient_2 : "#FFFCEF",
                'segment_home_screen_image' => isset($item->Merchant[0]['pivot']->segment_home_screen_image) && !empty($item->Merchant[0]['pivot']->segment_home_screen_image) ? get_image($item->Merchant[0]['pivot']->segment_home_screen_image, 'segment', $merchant_id) : get_image('', '', $merchant_id, false),
            ];
        });
        if ($call_from == 'api') {
            return $arr_segment;
        } else {
            $arr_segment = $arr_segment->toArray();
            $arr_segment = array_column($arr_segment, NULL, 'segment_id');
            return $arr_segment;
        }
    }

    /**
     * GET CATEGORIES OF TAXI SEGMENT TO SHOW ON HOME SCREEN
     */
    public function getTaxiCategory($merchant_id, $segment_id, $area_id)
    {
        $service_id = 1; // normal service type
        $arr_category = Category::with(['VehicleType' => function ($q) use ($merchant_id, $service_id, $segment_id, $area_id) {
            $q->where([['service_type_id', '=', $service_id], ['country_area_id', '=', $area_id], ['merchant_id', '=', $merchant_id], ['segment_id', '=', $segment_id]]);
        }])->whereHas('VehicleType', function ($q) use ($merchant_id, $service_id, $segment_id, $area_id) {
            $q->where([['service_type_id', '=', $service_id], ['country_area_id', '=', $area_id], ['merchant_id', '=', $merchant_id], ['segment_id', '=', $segment_id]]);
        })->where([['merchant_id', '=', $merchant_id], ['delete', '=', NULL], ['status', '=', 1]])->orderBy('sequence', 'ASC')->get();

        $arr_categories =   $arr_category->map(function ($category) use ($merchant_id) {
            return [
                'category_id' => $category->id,
                'name' => $category->Name($merchant_id),
                'category_image' => $category->category_image ? get_image($category->category_image, 'category', $category->merchant_id) : ""
            ];
        });

        return $arr_categories;
    }

    /**
     * GET CATEGORIES OF Handyman TO SHOW ON HOME SCREEN
     */
    public function getHandymanCategories($merchant_id, $segment_id)
    {
        $arr = [];
        $arr_categories = App\Models\HandymanCategory::whereHas('ServiceTypes')->where([['merchant_id', '=', $merchant_id], ['segment_id', '=', $segment_id], ['status', '=', 1]])->get();
        if (!empty($arr_categories)) {
            $arr =   $arr_categories->map(function ($category) use ($merchant_id) {
                return [
                    'category_id' => $category->id,
                    'name' => $category->Category,
                    'category_image' => get_image($category->icon, 'category', $category->merchant_id),
                    'description' => $category->Description,
                    'services_count' => count($category->ServiceTypes)
                ];
            });
        }
        return $arr;
    }

    public function serviceTypes($arr_data, $call_from, $merchant_id = NULL, $country_area_id = NULL, $service_type = "")
    {
        $arr_service = [];
        $data_list =  [];
        foreach ($arr_data as $data) {
            if (isset($data->Merchant[0]) && ($country_area_id  == NULL || (!empty($country_area_id) && $data->CountryArea->count() > 0))) {
                if ($service_type == "ALL" || ($service_type == "SELF_PICKUP" && $data->type == 6) || ($service_type == "DELIVERY" && $data->type != 6) || (in_array($service_type, ["Intra-City", "Inter-City"]))) {
                    $serviceName = $data->serviceName;
                    $locale_service_name = $data->ServiceName($merchant_id);
                    // $locale_service_description = $call_from != 'api' ? $data->ServiceDescription($merchant_id) : "";
                    $locale_service_description = !empty($data->ServiceDescription($merchant_id)) ? $data->ServiceDescription($merchant_id) : "";
                    $data_list = array(
                        'id' => $data->id,
                        'segment_id' => $data->segment_id, // $data->serviceName
                        'serviceName' => $serviceName,
                        'service_sequence' => isset($data->Merchant[0]['pivot']) ? $data->Merchant[0]['pivot']->sequence : 0,
                        'service_is_recommended' => isset($data->Merchant[0]['pivot']) && $data->Merchant[0]['pivot']->is_recommended == 1 ? true : false,
                        'service_icon' => isset($data->Merchant[0]['pivot']) && !empty($data->Merchant[0]['pivot']->service_icon) ? get_image($data->Merchant[0]['pivot']->service_icon, 'service', $merchant_id) : "",
                        'locale_service_name' => !empty($locale_service_name) ? $locale_service_name : $serviceName, //!empty($data->ServiceName($merchant_id)) ? $data->ServiceName($merchant_id) : $data->serviceName,
                        // 'locale_service_description' => ($call_from != 'api' && !empty($locale_service_description)) ? $locale_service_description : "",
                        'locale_service_description' => (!empty($locale_service_description)) ? $locale_service_description : "",
                        'type' => $data->type,
                    );
                    if ($call_from == 'api') {
                        $arr_service[] = $data_list;
                    } else {
                        $arr_service[$data->id] = $data_list;
                    }
                }
            }
        }
        // sort the sequences of services
        array_multisort(array_column($arr_service, 'service_sequence'), SORT_ASC, $arr_service);
        return $arr_service;
    }

    public function getMerchantServicesByArea($area_id, $segment_id, $vehicle_type_id, $return_type = '', $segment_group = null, $calling_for = 'USER')
    {
        $arr_services = [];
        if ($segment_group == 1 || $segment_group == 3) {
            $areas = CountryArea::with(['ServiceTypes' => function ($q) use ($area_id, $segment_id, $vehicle_type_id, $calling_for) {
                $q->join('country_area_vehicle_type as cavt', 'cavt.service_type_id', '=', 'service_types.id');
                if (!empty($vehicle_type_id)) {
                    // in case of food and grocery price card
                    $q->where('cavt.vehicle_type_id', $vehicle_type_id);
                }
                // in case of dine_in part should not show in driver price card
                if ($calling_for == 'DRIVER' && $segment_id == 3) {
                    $q->where([['type', '!=', 7]]);
                }
                $q->where('cavt.segment_id', $segment_id);
                $q->where('cavt.country_area_id', $area_id);
            }])->where([['id', '=', $area_id]])->first();
        } elseif ($segment_group == 2) {
            $areas = CountryArea::with(['ServiceTypes' => function ($q) use ($area_id, $segment_id, $calling_for) {
                // in case of dine_in part should not show in driver price card
                if ($calling_for == 'DRIVER' && $segment_id == 3) {
                    $q->where([['type', '!=', 7]]);
                }
                $q->where('country_area_service_type.segment_id', $segment_id);
            }])->where([['id', '=', $area_id]])->first();
        }
        if (!empty($areas->id)) {
            $service_type = $areas->ServiceTypes;
            if ($return_type == 'array') {
                foreach ($service_type as $value) {
                    $arr_services[$value->id] = $value->serviceName;
                }
            } else {
                $arr_services = $service_type;
            }
        }
        return $arr_services;
    }

    //    public function getSegmentServicesXXX($request)
    //    {
    //        $segment_id = $request->segment_id;
    //        $merchant_id = $request->merchant_id;
    //        $segment_group_id = $request->segment_group_id;
    //        $segment_price_card_id = $request->segment_price_card_id;
    //        // p($segment_price_card_id);
    //        //p($segment_group_id);
    //        $driver_id = $request->has('driver_id') ? $request->driver_id : NULL;
    //        // p($driver_id);
    //        $country_area_id = $request->area;
    //        $price_card_owner = $request->has('price_card_owner') ?  $request->price_card_owner : 1;
    //        // p($price_card_owner);
    //        $query = ServiceType::select('service_types.id','service_types.serviceName')
    //            ->with(['Merchant'=> function ($q) use ($segment_id,$merchant_id) {
    //               $q->where('merchant_id', $merchant_id);
    //               $q->orderBy('sequence');
    //           }])
    //            ->whereHas('Merchant', function ($q) use ($segment_id,$merchant_id) {
    //                $q->where('merchant_id', $merchant_id);
    //                $q->orderBy('sequence');
    //            })
    //            ->whereHas('CountryArea', function ($q) use ($segment_id,$merchant_id,$country_area_id) {
    //                $q->where('country_area_id', $country_area_id);
    //            })
    //            ->with(['Driver'=> function ($q) use ($segment_id,$merchant_id,$driver_id) {
    //                $q->where('driver_id', $driver_id);
    //                $q->where('segment_id', $segment_id);
    //            }])
    ////            ->whereHas('Driver', function ($q) use ($segment_id,$merchant_id,$driver_id) {
    ////                $q->where('driver_id', $driver_id);
    ////                $q->where('segment_id', $segment_id);
    ////            })
    //            ->where('service_types.segment_id',$segment_id);
    //        if($segment_group_id == 2)
    //        {
    //            $query->with(['SegmentPriceCardDetail'=>function($q) use($price_card_owner,$segment_price_card_id,$driver_id,$merchant_id,$country_area_id){
    //
    //                $q->addSelect('id','service_type_id','amount');
    //                if(!empty($segment_price_card_id))
    //                {
    //                    $q->where('segment_price_card_id', $segment_price_card_id);
    //                }
    //                $q->whereHas('SegmentPriceCard', function ($qq) use ($segment_price_card_id,$driver_id,$price_card_owner,$merchant_id, $country_area_id) {
    //                    $qq->where('merchant_id', $merchant_id);
    //                    if(!empty($segment_price_card_id))
    //                    {
    //                        $qq->where('id', $segment_price_card_id);
    //                    }
    //                    if($price_card_owner == 2)
    //                    {
    //                        $qq->where('driver_id', $driver_id);
    //                    }
    //                    if(!empty($country_area_id))
    //                    {
    //                        $qq->where('country_area_id', $country_area_id);
    //                    }
    //                });
    //            }]);
    //            // if($price_card_owner == 1 ) {
    //            //     $query->whereHas('SegmentPriceCardDetail', function ($q) use ($segment_price_card_id) {
    //            //         $q->addSelect('id','service_type_id','amount');
    //            //         $q->where('segment_price_card_id', $segment_price_card_id);
    //            //     });
    //            // }
    //        }
    //        $arr_services = $query->get();
    //        if(count($arr_services)> 0)
    //        {
    //            $arr_services = $arr_services->map(function ($item, $key) use($merchant_id) {
    //                // p($item->SegmentPriceCardDetail);
    //                return array(
    //                    'id' => $item->id,
    //                    'name' => !empty($item->ServiceName($merchant_id)) ? $item->ServiceName($merchant_id) : $item->serviceName,
    //                    'amount' =>isset($item->SegmentPriceCardDetail->amount) ? $item->SegmentPriceCardDetail->amount : 0,
    //                    'segment_price_card_detail_id' => isset($item->SegmentPriceCardDetail->id)   ? $item->SegmentPriceCardDetail->id :null,
    //                    'selected' => isset($item->Driver[0]->id)  && !empty($item->Driver[0]->id) ? true :false,
    //                    'description' => !empty($item->ServiceDescription($merchant_id)) ? $item->ServiceDescription($merchant_id) : "",
    //                    'service_icon' =>isset($data->Merchant[0]['pivot']) && !empty($data->Merchant[0]['pivot']->service_icon) ? get_image($data->Merchant[0]['pivot']->service_icon,'service',$merchant_id) : "",
    //                );
    //            });
    //        }
    //        return $arr_services;
    //    }

    /**
     * Pool Service Can be fetched in this api becuase driver may have multiple vehicle
     */
    public function getSegmentServices($request)
    {
        $segment_id = $request->segment_id;
        $merchant_id = $request->merchant_id;
        $segment_group_id = $request->segment_group_id;
        $segment_price_card_id = $request->segment_price_card_id;
        // p($segment_price_card_id);
        //p($segment_group_id);
        $driver_id = $request->has('driver_id') ? $request->driver_id : NULL;
        // p($driver_id);
        $country_area_id = $request->area;
        $price_card_owner = $request->has('price_card_owner') ?  $request->price_card_owner : 1;
        $merchant_helper = new MerchantController();
        // p($price_card_owner);
        // p($merchant_id);
        $query = ServiceType::select('service_types.id', 'service_types.serviceName')
            ->with(['Merchant' => function ($q) use ($segment_id, $merchant_id) {
                $q->where('merchant_id', $merchant_id);
                $q->orderBy('sequence');
            }])
            ->whereHas('Merchant', function ($q) use ($segment_id, $merchant_id) {
                $q->where('merchant_id', $merchant_id);
                $q->orderBy('sequence');
            })
            ->whereHas('CountryArea', function ($q) use ($segment_id, $merchant_id, $country_area_id) {
                $q->where('country_area_id', $country_area_id);
            })
            ->with(['Driver' => function ($q) use ($segment_id, $merchant_id, $driver_id) {
                $q->where('driver_id', $driver_id);
                $q->where('segment_id', $segment_id);
            }])
            //            ->whereHas('Driver', function ($q) use ($segment_id,$merchant_id,$driver_id) {
            //                $q->where('driver_id', $driver_id);
            //                $q->where('segment_id', $segment_id);
            //            })
            ->where('service_types.segment_id', $segment_id)
            ->where('service_types.type', '!=', 6);
        if ($segment_group_id == 2) {
            $query->with(['SegmentPriceCardDetail' => function ($q) use ($price_card_owner, $segment_price_card_id, $driver_id, $merchant_id, $country_area_id) {

                $q->addSelect('id', 'service_type_id', 'amount');
                if (!empty($segment_price_card_id)) {
                    $q->where('segment_price_card_id', $segment_price_card_id);
                }
                $q->whereHas('SegmentPriceCard', function ($qq) use ($segment_price_card_id, $driver_id, $price_card_owner, $merchant_id, $country_area_id) {
                    $qq->where('merchant_id', $merchant_id);
                    if (!empty($segment_price_card_id)) {
                        $qq->where('id', $segment_price_card_id);
                    }
                    if ($price_card_owner == 2) {
                        $qq->where('driver_id', $driver_id);
                    }
                    if (!empty($country_area_id)) {
                        $qq->where('country_area_id', $country_area_id);
                    }
                });
            }]);
            if (isset($request->handyman_category_id) && !empty($request->handyman_category_id)) {
                $query->whereHas("HandymanCategory", function ($q) use ($request) {
                    $q->where("id", $request->handyman_category_id);
                });
            }
            // if($price_card_owner == 1 ) {
            //     $query->whereHas('SegmentPriceCardDetail', function ($q) use ($segment_price_card_id) {
            //         $q->addSelect('id','service_type_id','amount');
            //         $q->where('segment_price_card_id', $segment_price_card_id);
            //     });
            // }
        }
        $arr_services = $query->get();
        //        p($arr_services);
        if (count($arr_services) > 0) {
            $arr_services = $arr_services->map(function ($item, $key) use ($merchant_id, $merchant_helper) {
                // p($item->SegmentPriceCardDetail);
                // if($item->id == 11)
                // {
                //   //p($item->Merchant[0]);
                // }
                return array(
                    'id' => $item->id,
                    'name' => !empty($item->ServiceName($merchant_id)) ? $item->ServiceName($merchant_id) : $item->serviceName,
                    'amount' => isset($item->SegmentPriceCardDetail->amount) ? $item->SegmentPriceCardDetail->amount : 0,
                    'amount_string' => isset($item->SegmentPriceCardDetail->amount) ? (string)$merchant_helper->PriceFormat($item->SegmentPriceCardDetail->amount, $merchant_id) : "0",
                    'segment_price_card_detail_id' => isset($item->SegmentPriceCardDetail->id)   ? $item->SegmentPriceCardDetail->id : null,
                    'selected' => isset($item->Driver[0]->id)  && !empty($item->Driver[0]->id) ? true : false,
                    'description' => !empty($item->ServiceDescription($merchant_id)) ? $item->ServiceDescription($merchant_id) : "",
                    'service_icon' => isset($item->Merchant[0]['pivot']) && !empty($item->Merchant[0]['pivot']->service_icon) ? get_image($item->Merchant[0]['pivot']->service_icon, 'service', $merchant_id) : "",
                    'service_sequence' => isset($item->Merchant[0]['pivot']) ? $item->Merchant[0]['pivot']->sequence : 0,
                );
            });
            // sort the sequences of $arr_services
            $arr_services = $arr_services->toArray(); // it must be array
            array_multisort(array_column($arr_services, 'service_sequence'), SORT_ASC, $arr_services);
        }
        return $arr_services;
    }

    //    public function segmentGroup($merchant_id)
    //    {
    //        $arr_groups = SegmentGroup::select('id','group_name')
    //            ->with(['Segment'=>function($q) use($merchant_id){
    //                $q->addSelect('id','name','segment_group_id');
    //                $q->whereHas('Merchant',function($qq) use($merchant_id){
    //                    $qq->where('merchant_id',$merchant_id);
    //                });
    //            }])
    //            ->get();
    //        foreach ($arr_groups as $key => $groups) {
    //            $groups->Segment->transform(function ($item, $key) {
    //                $item->name = $item->language_single['name'];
    //                $item->id = $item->id;
    //                return $item;
    //            });
    //////                ->sortBy('AreaName')->values();
    //        }
    //
    //        return $arr_groups;
    //    }

    public function segmentGroup($merchant_id, $return_type = "", $string_file = "", $except_segment_group = [])
    {

        $arr_groups = SegmentGroup::select('id', 'group_name')
            ->with(['Segment' => function ($q) use ($merchant_id) {
                $q->addSelect('id', 'segment_group_id', 'icon', 'name as segment_name');
                $q->whereHas('Merchant', function ($qq) use ($merchant_id) {
                    $qq->where('merchant_id', $merchant_id);
                });
                $q->with(['ServiceType' => function ($qq) use ($merchant_id) {
                    $qq->addSelect('id', 'segment_id', 'serviceName');
                    $qq->whereHas('MerchantServiceType', function ($qqq) use ($merchant_id) {
                        $qqq->where('merchant_id', $merchant_id);
                    });
                }]);
            }])->whereHas('Segment', function ($q) use ($merchant_id) {
                $q->addSelect('id', 'segment_group_id', 'icon', 'name as segment_name');
                $q->whereHas('Merchant', function ($qq) use ($merchant_id) {
                    $qq->where('merchant_id', $merchant_id);
                });
                $q->with(['ServiceType' => function ($qq) use ($merchant_id) {
                    $qq->addSelect('id', 'segment_id', 'serviceName');
                    $qq->whereHas('MerchantServiceType', function ($qqq) use ($merchant_id) {
                        $qqq->where('merchant_id', $merchant_id);
                    });
                }]);
            })->where(function ($q) use ($except_segment_group) {
                if (isset($except_segment_group) && is_array($except_segment_group)) {
                    $q->whereNotIn('id', $except_segment_group);
                } else {
                    $q->where('id', '!=', $except_segment_group);
                }
            })->get();
        // p($return_type);
        if ($return_type == "drop_down") {
            $return_group = [];
            foreach ($arr_groups as $key => $groups) {
                if ($groups->Segment->count() > 0) {
                    $group_name = "";
                    if ($groups->id == 1) {
                        $group_name = trans("$string_file.vehicle_based");
                    } elseif ($groups->id == 2) {
                        $group_name = trans("$string_file.helper_based");
                    } elseif ($groups->id == 4) {
                        $group_name = trans("$string_file.bus_booking");
                    }
                    // $group_name = $groups->id == 1 ? trans("$string_file.vehicle_based") : trans("$string_file.helper_based");
                    $return_group[$groups->id] = $group_name;
                }
            }
            $return = [
                'arr_group' => $return_group,
                'single_group' => count($return_group) == 1 ? 1 : 0
            ];
            return $return;
        }
        foreach ($arr_groups as $key => $groups) {
            // This is static text because app developer check is working according to this text
            if ($groups->id == 1) {
                $groups->group_name = trans("$string_file.vehicle_based");
            } elseif ($groups->id == 2) {
                $groups->group_name = trans("$string_file.helper_based");
            } elseif ($groups->id == 4) {
                $groups->group_name = trans("$string_file.bus_booking");
            }
            //$groups->group_name = $groups->id == 1 ? trans("$string_file.vehicle_based") : trans("$string_file.helper_based");
            //            $groups->group_name = $groups->id == 1 ? trans("$string_file.vehicle_based") : trans("$string_file.helper_based");
            $groups->Segment->transform(function ($item, $key) use ($merchant_id) {
                $merchant_segment =  $item->Merchant->where('id', $merchant_id);
                $merchant_segment = collect($merchant_segment->values());
                // $item->icon = get_image($item->icon, 'segment_super_admin', NULL, false);
                $item->icon = isset($merchant_segment[0]['pivot']->segment_icon) && !empty($merchant_segment[0]['pivot']->segment_icon) ? get_image($merchant_segment[0]['pivot']->segment_icon, 'segment', $merchant_id, true, false) : get_image($item->icon, 'segment_super_admin', NULL, false);
                $item->name = $item->segment_name;
                $item->segment_name = $item->Name($merchant_id);
                unset($item->name);
                unset($item->segment_group_id);
                unset($item->ServiceType);
                unset($item->Merchant);
                return $item;
            });
        }
        return $arr_groups;
    }

    public function getMerchantPaymentMethod($arr_payment_method, $merchant_id)
    {
        $arr_payment = [];
        foreach ($arr_payment_method as $method) {
            $arr_payment[$method->id] = $method->MethodName($merchant_id) ? $method->MethodName($merchant_id) : $method->payment_method;
        }
        return $arr_payment;
    }

    public function getAdditionalSupportServices($merchant_id = null, $support = NULL)
    {
        $merchant_id = empty($merchant_id) ? get_merchant_id() : $merchant_id;
        $merchant_services = Merchant::with(['ServiceType' => function ($q) use ($support) {
            $q->where('additional_support', $support);
        }])->find($merchant_id);

        $arr_services = [];
        foreach ($merchant_services['ServiceType'] as $service) {
            $arr_services[$service->id] =  $service->serviceName;
        }
        return $arr_services;
    }

    public function getSegmentService($segment_id, $merchant_id, $return = '')
    {
        $service_type = ServiceType::select('id', 'serviceName')
            ->whereHas('MerchantServiceType', function ($q) use ($segment_id, $merchant_id) {
                $q->where('merchant_id', $merchant_id);
                $q->orderBy('sequence');
            })->where('segment_id', $segment_id)->first();

        if ($return == 'id') {
            return $service_type->id;
        }
        return $service_type;
    }
    public function getMerchantBusinessSegment(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $segment_id =  $request->segment_id;
        $user_id =  $request->user_id;
        $is_fav = $request->is_favourite;
        $is_search = $request->is_search;
        $search_text = $request->search_text;
        $is_popular = $request->is_popular;
        $distance_unit = 1;
        $radius = $distance_unit == 2 ? 3958.756 : 6367;
        $distance = $request->distance;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        // 1 means demo login
        $user = $request->user('api');
        $user_type = $user->login_type == 1 ?  $user->login_type : NULL;
        $locale = \App::getLocale();

        $arr_categories_id = [];
        if (!empty($search_text) && ($is_search == 1 || $is_search == 2)) {
            $arr_category = Category::select('id')
                ->with(['LangCategory' => function ($q) use ($search_text, $locale) {
                    $q->where('name', 'like', "%$search_text%")
                        ->where(function ($q) use ($locale) {
                            $q->where('locale', $locale);
                            $q->orWhere('locale', '!=', NULL);
                        });
                }])
                ->whereHas('LangCategory', function ($q) use ($search_text, $locale) {
                    $q->where('name', 'like', "%$search_text%")
                        ->where(function ($q) use ($locale) {
                            $q->where('locale', $locale);
                            $q->orWhere('locale', '!=', NULL);
                        });
                })
                ->whereHas('Segment', function ($q) use ($segment_id) {
                    $q->where('segment_id', $segment_id);
                })
                ->where('merchant_id', $merchant_id)
                ->where('delete', NULL)
                ->get();
            $arr_categories_id = array_pluck($arr_category, 'id');
        }

        $haversineSQL = '( ' . $radius . ' * acos( cos( radians(' . $latitude . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( latitude ) ) ) )';
        // p($haversineSQL);
        $query = BusinessSegment::with('StyleManagement')->select('id', 'country_area_id', 'full_name', 'country_id', 'delivery_time', 'minimum_amount', 'business_logo', 'is_popular', 'minimum_amount_for', 'open_time', 'close_time', 'latitude', 'longitude', 'rating')
            ->addSelect(DB::raw($haversineSQL . ' AS distance'))
            ->where([['merchant_id', '=', $merchant_id], ['segment_id', '=', $segment_id], ['status', '=', 1]])
            ->with('ActivePromoCode')
            ->where(function ($q) use ($is_search, $search_text, $locale, $arr_categories_id) {
                if (!empty($search_text) && ($is_search == 1 || $is_search == 2)) {
                    $q->where('full_name', 'like', "%$search_text%");
                    $q->orWhereIn('business_segments.id', function ($query) use ($search_text, $locale, $arr_categories_id) {
                        $query->select('p.business_segment_id')
                            ->from('products as p')
                            ->join('language_products as lp', 'p.id', '=', 'lp.product_id')
                            ->where(function ($q) use ($search_text, $locale) {
                                $q->where('lp.name', 'like', "%$search_text%");
                                $q->where(function ($q) use ($locale) {
                                    $q->where('locale', $locale);
                                    $q->orWhere('locale', '!=', NULL);
                                });
                            })
                            ->orWhere(function ($q) use ($arr_categories_id) {
                                if (!empty($arr_categories_id)) {
                                    $q->whereIn('category_id', $arr_categories_id);
                                }
                            });
                    });
                }
            })
            ->where(function ($q) use ($is_popular) {
                if (!empty($is_popular) && $is_popular == "YES") {
                    $q->where('is_popular', 1);
                }
            })
            ->where('country_area_id', $request->area)
            ->where('is_deleted', '!=', 1)
            ->where(function ($query) {
                $query->whereNull('subscription_expired')
                    ->orWhere('subscription_expired', 2);
            })
            ->orderBy('distance');
        if (!empty($is_fav) && $is_fav == "YES") {
            $query->whereHas('FavouriteBusinessSegment', function ($qq) use ($user_id, $segment_id) {
                $qq->where([['user_id', '=', $user_id], ['segment_id', '=', $segment_id]]);
            });
        }
        // means radius check works only in case of normal user not demo
        if ($user_type != 1) {
            $query->whereRaw($haversineSQL . '<= ?', [$distance]);
        }
        $arr_restaurant =  $query->paginate(10);
        // p($arr_restaurant);
        return $arr_restaurant;
    }


    public function getMerchantSegmentDetails($merchant_id, $arr_segment = NULL)
    {
        $query = Segment::select('id', 'name', 'slag', 'icon', 'segment_group_id', 'sub_group_for_app')
            ->with(['Merchant' => function ($q) use ($merchant_id) {
                $q->where('id', $merchant_id);
                $q->select('id');
            }])
            //            ->where('is_coming_soon','!=',1)
            ->whereHas('Merchant', function ($q) use ($merchant_id) {
                $q->where('merchant_id', $merchant_id);
                $q->orderBy('sequence', 'ASC');
            });
        if (!empty($arr_segment)) {
            $query->whereIn('id', $arr_segment);
        }
        $segments = $query->get();
        $arr_segment = $segments->map(function ($item) use ($merchant_id) {
            return [
                'segment_id' => $item->id,
                'slag' => $item->slag,
                'sub_group_for_app' => $item->sub_group_for_app,
                'segment_group_id' => $item->segment_group_id,
                'name' => !empty($item->Name($merchant_id)) ? $item->Name($merchant_id) : $item->slag,
                'segment_icon' => isset($item->Merchant[0]['pivot']->segment_icon) && !empty($item->Merchant[0]['pivot']->segment_icon) ? get_image($item->Merchant[0]['pivot']->segment_icon, 'segment', $merchant_id, true) : get_image($item->icon, 'segment_super_admin', NULL, false),
            ];
        });
        return $arr_segment;
    }

    /******************************** Language String Module Start ******************************/

    public function getStringFile($merchant_id = NULL, $merchant = [])
    {
        $file = "";
        if (!empty($merchant)) {
            $file = $merchant->string_file;
        } elseif (!empty($merchant_id)) {
            $merchant = Merchant::select('string_file')->Find($merchant_id);
            $file = isset($merchant->string_file) && !empty($merchant->string_file) ? $merchant->string_file : "";
        }
        if (!empty($file)) {
            $locale = App::getLocale();
            $file_path = base_path() . '/resources/lang/' . $locale . '/' . $file . '.php';

            if (!file_exists($file_path)) {
                $file = "all_in_one";
            }
        } else {
            $file = "all_in_one";
        }
        return $file;
    }

    public function getStringFileConfig()
    {
        return [
            'all_in_one' => "All Segments(all_in_one.php)",
            'taxi' => "Taxi(taxi.php)",
            'delivery' => "Delivery(delivery.php)",
            'food' => "Food (food.php)",
            'grocery' => "Grocery (grocery.php)",
            'handyman' => "Handyman(Plumber,Cleaning, Electrician) (handyman.php)",
        ];
    }
    public function merchantType($merchant)
    {
        $merchant_type = "";
        $segment_type = array_pluck($merchant->Segment, 'segment_group_id');
        if (in_array(1, $segment_type) && in_array(2, $segment_type)) {
            $merchant_type = "BOTH";
        } elseif (in_array(1, $segment_type)) {
            $merchant_type = "VEHICLE";
        } elseif (in_array(2, $segment_type)) {
            $merchant_type = "HANDYMAN";
        }
        return $merchant_type;
    }
    /******************************** Language String Module End ********************************/

    /**
     * @param $merchant_id
     * @return bool
     * @summery : Check merchant having handyman segment and promocode is enable or not for that
     * @author Bhuvanesh Soni
     */
    public function merchantHandymanPromocode($merchant_id)
    {
        $merchant = Merchant::find($merchant_id);
        $handyman_apply_promocode = false;
        $arr_segment_group =   $this->segmentGroup($merchant_id, $return_type = "drop_down", "");
        $merchant_segment_group = isset($arr_segment_group['arr_group']) ?  array_keys($arr_segment_group['arr_group']) : [];
        if (in_array(2, $merchant_segment_group) && isset($merchant->HandymanConfiguration->advance_payment_of_min_bill) && $merchant->HandymanConfiguration->advance_payment_of_min_bill == false && $merchant->HandymanConfiguration->price_type_config == "FIXED") {
            $handyman_apply_promocode = true;
        }
        return $handyman_apply_promocode;
    }

    // set step value in html form based on trip calculation
    public function stepValue($merchant_id)
    {

        $settings = Configuration::select('trip_calculation_method')->where([['merchant_id', '=', $merchant_id]])->first();
        switch ($settings->trip_calculation_method) {
            case "1":
                $step = 1;
                break;
            case "2":
                $step = 0.01;
                break;
            case "3":
                $step = 0.01;
                break;
            case "4":
                $step = 0.001;
                break;
            default:
                $step = 1;
        }
        return $step;
    }

    // popular restaurant/stores for home screen
    public function getMerchantPopularBusinessSegment(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $distance_unit = 1;
        $radius = $distance_unit == 2 ? 3958.756 : 6367;
        $distance = $request->distance;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $segment_slug = $request->segment_slug;
        // 1 means demo login
        $user = $request->user('api');
        $user_type = $user->login_type == 1 ?  $user->login_type : NULL;

        $haversineSQL = '( ' . $radius . ' * acos( cos( radians(' . $latitude . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( latitude ) ) ) )';
        $query = BusinessSegment::with('StyleManagement')->select('id', 'country_area_id', 'segment_id', 'full_name', 'country_id', 'delivery_time', 'minimum_amount', 'business_logo', 'is_popular', 'minimum_amount_for', 'open_time', 'close_time', 'latitude', 'longitude', 'rating')
            ->addSelect(DB::raw($haversineSQL . ' AS distance'))
            ->where([['merchant_id', '=', $merchant_id], ['is_popular', '=', 1], ['status', '=', 1]])
            ->where('country_area_id', $request->area)
            ->orderBy('distance')
            // ->with(['ActivePromoCode'=>function($q){}])
        ;
        // means radius check works only in case of normal user not demo
        if ($user_type != 1) {
            $query->whereRaw($haversineSQL . '<= ?', [$distance]);
        }

        $query->whereHas('Segment', function ($q) use ($segment_slug, $merchant_id) {
            $q->whereIn('sub_group_for_app', [1, 2]) // food and food's clone
                ->whereHas('Merchant', function ($q2) use ($merchant_id) {
                    $q2->where('merchant_id', $merchant_id);
                    $q2->where('is_coming_soon', 2);
                });
            //                if($segment_slug ="FOOD")
            //                {
            //                    $q->where('sub_group_for_app',1); // food and food's clone
            //                }
            //                else
            //                {
            //                    $q->where('sub_group_for_app',2); // grocery and grocery's clones
            //                }

        });

        $arr_restaurant =  $query->get();
        return $arr_restaurant;
    }

    // popular restaurant/stores for home screen
    public function getUserFavouriteBusinessSegment(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $distance_unit = 1;
        $radius = $distance_unit == 2 ? 3958.756 : 6367;
        $distance = $request->distance;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $segment_slug = $request->segment_slug;
        // 1 means demo login
        $user = $request->user('api');
        $user_type = $user->login_type == 1 ?  $user->login_type : NULL;

        $haversineSQL = '( ' . $radius . ' * acos( cos( radians(' . $latitude . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( latitude ) ) ) )';
        $query = BusinessSegment::with('StyleManagement')->select('id', 'country_area_id', 'segment_id', 'full_name', 'country_id', 'delivery_time', 'minimum_amount', 'business_logo', 'is_popular', 'minimum_amount_for', 'open_time', 'close_time', 'latitude', 'longitude', 'rating')
            ->addSelect(DB::raw($haversineSQL . ' AS distance'))
            ->where([['merchant_id', '=', $merchant_id], ['is_popular', '=', 1], ['status', '=', 1]])
            ->where('country_area_id', $request->area)
            ->orderBy('distance');
        // means radius check works only in case of normal user not demo
        if ($user_type != 1) {
            $query->whereRaw($haversineSQL . '<= ?', [$distance]);
        }

        $query->whereHas('Segment', function ($q) use ($segment_slug) {
            $q->whereIn('sub_group_for_app', [1, 2]); // food and food's clone
            //                if($segment_slug ="FOOD")
            //                {
            //                    $q->where('sub_group_for_app',1); // food and food's clone
            //                }
            //                else
            //                {
            //                    $q->where('sub_group_for_app',2); // grocery and grocery's clones
            //                }

        });

        $arr_restaurant =  $query->get();
        return $arr_restaurant;
    }

    function demoSpecialPermission($merchant_object = NULL, $merchant_id = NULL)
    {

        $is_demo = false;
        $data_permission = [];
        $edit_permission = true;
        $export_permission = true;
        $change_status_permission = true;
        $delete_permission = true; // default for non demo merchant @endphp

        if (empty($merchant_object) && !empty($merchant_id)) {
            $merchant_object = Merchant::select('id', 'demo')->find($merchant_id);
        }
        // check if merchant is demo
        if (!empty($merchant_object) && $merchant_object->demo == 1) {
            $demo_configuration = $merchant_object->DemoConfiguration;
            $data_permission = !empty($demo_configuration->data_permission) ? json_decode($demo_configuration->data_permission, true) : [];
            $is_demo = true;
        }
        $logged_in_merchant = (Auth::guard('merchant')->check()) ? Auth::user('merchant') : [];

        if ($is_demo && (empty($logged_in_merchant) || (!empty($logged_in_merchant) && $logged_in_merchant->parent_id != NULL))) {
            $edit_permission = false;
            $export_permission = false;
            $change_status_permission = false;
            $delete_permission = false; // default for demo merchant without permission @endphp
            if (in_array('edit', $data_permission)) {
                $edit_permission = true; // default for demo merchant with permission @endphp
            }

            if (in_array('export', $data_permission)) {
                $export_permission = true; // default for demo merchant with permission @endphp
            }
            if (in_array('delete', $data_permission)) {
                $delete_permission = true; // default for demo merchant with permission @endphp
            }
            if (in_array('change_status', $data_permission)) {
                $change_status_permission = true; // default for demo merchant with permission @endphp
            }
        }

        return   [
            'is_demo' => $is_demo,
            'permissions' => $data_permission,
            'edit_permission' => $edit_permission,
            'export_permission' => $export_permission,
            'delete_permission' => $delete_permission,
            'change_status_permission' => $change_status_permission,
        ];
    }

    //    public function getFoodGroceryClone($merchant_id)
    //    {
    //        $segments = Segment::select('id', 'name', 'slag', 'icon', 'segment_group_id', 'sub_group_for_app', 'sub_group_for_admin')
    //            ->whereIn("sub_group_for_app", [1, 2])
    //            ->whereHas('Merchant', function ($q) use ($merchant_id) {
    //                $q->select('merchants.id as merchant_id');
    //                $q->where('merchant_id', $merchant_id);
    //                $q->select('id', 'sequence');
    //                $q->orderBy('sequence', 'ASC');
    //                $q->where('is_coming_soon', 2);
    //            })->get()->pluck("slag")->toArray();
    //        return $segments;
    //    }
    public function getBusinessSegmentWithProducts(Request $request)
    {
        $user = $request->user('api');
        $merchant_id = $request->merchant_id;
        $distance_unit = 1;
        $radius = $distance_unit == 2 ? 3958.756 : 6367;
        $distance = $request->distance;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $segment_slug = $request->segment_slug;
        $segment_id = $request->segment_id;
        $search_by = $request->search_by; // store or  product
        $search_text = $request->search_text; // store or  product
        $locale = \App::getLocale();
        $merchant_helper = new MerchantController();
        $trip_calculation_method = $user->Merchant->Configuration->trip_calculation_method;
        $format_price = $user->Merchant->Configuration->format_price;
        // 1 means demo login
        $user = $request->user('api');
        $user_type = $user->login_type == 1 ?  $user->login_type : NULL;

        $haversineSQL = '( ' . $radius . ' * acos( cos( radians(' . $latitude . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( latitude ) ) ) )';
        $query = BusinessSegment::select('id', 'country_area_id', 'segment_id', 'full_name', 'country_id', 'delivery_time', 'minimum_amount', 'business_logo', 'is_popular', 'minimum_amount_for', 'open_time', 'close_time', 'latitude', 'longitude', 'rating')
            ->addSelect(DB::raw($haversineSQL . ' AS distance'))
            ->where([['merchant_id', '=', $merchant_id], ['status', '=', 1], ['segment_id', '=', $request->segment_id]])
            ->where('country_area_id', $request->area)
            ->orderBy('distance');
        $qq = $query;
        $multi_store = $qq->count();
        // means radius check works only in case of normal user not demo
        if ($user_type != 1) {
            $query->whereRaw($haversineSQL . '<= ?', [$distance]);
        }
        $query->with(["Product" => function ($qq) use ($search_text, $locale, $merchant_id, $segment_id, $search_by) {
            $qq->select('id', 'business_segment_id', 'merchant_id', 'segment_id', 'sku_id', 'product_cover_image', 'food_type', 'status', 'manage_inventory')
                ->where([['status', '=', 1], ['delete', '=', NULL]])
                ->with(['LanguageProduct' => function ($q) use ($search_text, $locale, $search_by) {
                    $q->select('id', 'product_id', 'name', 'locale', 'merchant_id', 'description', 'ingredients');
                    if ($search_by == "PRODUCT") {
                        $q->where('name', 'LIKE', "%" . $search_text . "%")->where('locale', $locale); // food and food's clone
                    }
                }])
                ->whereHas('LanguageProduct', function ($q) use ($search_text, $locale, $search_by) {
                    if ($search_by == "PRODUCT") {
                        $q->where('name', 'LIKE', "%" . $search_text . "%")->where('locale', $locale); // food and food's clone
                    }
                })
                ->with(['ProductVariant' => function ($qq) use ($merchant_id, $segment_id) {
                    $qq->select('id', 'product_id', 'weight_unit_id', 'product_price', 'weight', 'status', 'is_title_show', 'discount');
                    $qq->where([['status', '=', 1], ['delete', '=', NULL]]);
                    $qq->with(['ProductInventory' => function ($q) use ($merchant_id, $segment_id) {
                        $q->select('id', 'product_variant_id', 'current_stock');
                        $q->where([['merchant_id', '=', $merchant_id], ['segment_id', '=', $segment_id]]);
                    }]);
                }]);

            if ($search_by == "CATEGORY") {
                $qq->whereHas('Category', function ($q) use ($search_text, $locale) {
                    $q->where([['status', '=', 1], ['delete', '=', NULL]])
                        ->whereHas('LangCategory', function ($qqq) use ($search_text, $locale) {
                            $qqq->select("id", "name", "locale")
                                ->where('name', 'like', '%' . $search_text . '%')
                                ->where('locale', '=', $locale);
                        });
                });
            }
        }])->whereHas("Product", function ($qq) use ($search_text, $locale, $search_by) {

            $qq->where([['status', '=', 1], ['delete', '=', NULL]]);

            if ($search_by == "CATEGORY") {
                $qq->whereHas('Category', function ($q) use ($search_text, $locale) {
                    $q->where([['status', '=', 1], ['delete', '=', NULL]])
                        ->whereHas('LangCategory', function ($qqq) use ($search_text, $locale) {
                            $qqq->select("id", "name", "locale")
                                ->where('name', 'like', '%' . $search_text . '%')
                                ->where('locale', '=', $locale);
                        });
                });
            }

            $qq->whereHas('LanguageProduct', function ($q) use ($search_text, $locale, $search_by) {
                if ($search_by == "PRODUCT") {
                    $q->where('name', 'LIKE', "%" . $search_text . "%")->where('locale', $locale); // food and food's clone
                }
            })
                ->whereHas('ProductVariant', function ($qq) {
                    $qq->select('id', 'product_id', 'weight_unit_id', 'product_price', 'weight', 'status', 'is_title_show');
                    $qq->where([['status', '=', 1], ['delete', '=', NULL]]);
                });
        });

        if ($search_by == "STORE") {
            $query->where('full_name', 'LIKE', "%" . $search_text . "%");
        }

        $query->whereHas('Segment', function ($q) use ($segment_slug) {
            $q->whereIn('sub_group_for_app', [1, 2]); // food and food's clone
        });

        $arr_restaurant =  $query->get();
        $string_file = $this->getStringFile(NULL, $user->Merchant);
        $currency = "";
        $unit = "km";
        if($user->Country){
            $currency = $user->Country->isoCode;
            $unit = $user->Country->distance_unit;
        }else{
            $currency = $user->CountryArea->Country->isoCode;
            $unit = $user->CountryArea->Country->distance_unit;
        }

        
        $arr_restaurant = $arr_restaurant->map(function ($item, $key) use (
            $merchant_id,
            $unit,
            $string_file,
            $merchant_helper,
            $format_price,
            $trip_calculation_method,
            $currency,
            $multi_store,
            $locale,
            $segment_id
        ) {
            // only setting timezone
            date_default_timezone_set($item->CountryArea['timezone']);
            // $current_time = date('H:i');
            $is_business_segment_open = false;
            $current_day = date('w');
            $arr_open_time = json_decode($item->open_time, true);
            $arr_close_time = json_decode($item->close_time, true);
            $open_time = isset($arr_open_time[$current_day]) ? $arr_open_time[$current_day] : NULL;
            $close_time = isset($arr_close_time[$current_day]) ? $arr_close_time[$current_day] : NULL;

            //  Changes for midnight store time
            if ($open_time > $close_time) {
                $close_time_n = date('Y-m-d H:i:s', strtotime($close_time . ' +1 day'));
            } else {
                $close_time_n = date('Y-m-d H:i:s', strtotime($close_time));
            }
            $open_time_n = date('Y-m-d H:i:s', strtotime($open_time));
            $current_time_n = date("Y-m-d H:i:s");
            if ($open_time_n < $current_time_n && $close_time_n > $current_time_n) {
                $is_business_segment_open = true;
            }

            $arr_product = [];
            if ($item->Product) {
                $arr_product =   $item->Product->map(function ($product) use ($merchant_id, $unit, $string_file, $merchant_helper, $format_price, $trip_calculation_method, $currency) {

                    $first_variant = $product->ProductVariant->count() > 0 ?  $product->ProductVariant[0] : null;
                    if ($first_variant) {

                        $product_locale = $product->LanguageProduct ?  $product->LanguageProduct[0] : null;
                        $discounted_price = !empty($first_variant->discount) && $first_variant->discount > 0 ? ($first_variant->product_price - $first_variant->discount) : "";

                        $product_image = $product->ProductImage && $product->ProductImage->count() > 0 ? get_image($product->ProductImage[0]->product_image, 'product_image', $merchant_id) : '';
                        $cover_image = !empty($product->product_cover_image) ? get_image($product->product_cover_image, 'product_cover_image', $merchant_id) : $product_image;

                        return [
                            'id' => $first_variant->id, // first variant id
                            'product_id' => $product->id,
                            'product_name' => $product_locale->name,
                            //                                'product_name' => $product_variant->is_title_show == 1 ? $product_variant->Name($merchant_id) : $product_lang->name,
                            'product_cover_image' => $cover_image,
                            'product_image' => $product_image,
                            'currency' => "$currency",
                            // 'product_price' => (string) $merchant_helper->TripCalculation($first_variant->product_price, $merchant_id, $trip_calculation_method),
                            // 'discount' => !empty($first_variant->discount) ? $merchant_helper->TripCalculation($first_variant->discount, $merchant_id, $trip_calculation_method) : "",
                            // 'discounted_price' => !empty($discounted_price) ? (string) $merchant_helper->TripCalculation($discounted_price, $merchant_id, $trip_calculation_method) : "",
                            'product_price' => (string) $merchant_helper->PriceFormat($merchant_helper->TripCalculation($first_variant->product_price, $merchant_id, $trip_calculation_method), NULL, $format_price, $trip_calculation_method),
                            'discount' => !empty($first_variant->discount) ? $merchant_helper->PriceFormat($merchant_helper->TripCalculation($first_variant->discount, $merchant_id, $trip_calculation_method), NULL, $format_price, $trip_calculation_method) : "",
                            'discounted_price' => !empty($discounted_price) ? (string) $merchant_helper->PriceFormat($merchant_helper->TripCalculation($discounted_price, $merchant_id, $trip_calculation_method), NULL, $format_price, $trip_calculation_method) : "",
                            'food_type' => $product->food_type,
                            'product_description' => !empty($product_locale->description) ? $product_locale->description : "",
                            'ingredients' => !empty($product_locale->ingredients) ? $product_locale->ingredients : "",
                            'weight_unit' => $first_variant->weight . ' ' . $unit,
                            'manage_inventory' => $product->manage_inventory,
                            'stock_quantity' => isset($first_variant->ProductInventory->id) ? $first_variant->ProductInventory->current_stock : NULL,
                            'product_availability' => $first_variant->status == 1 ? true : false,
                            // 'id' => $product->id
                            // 'name' => $product->id
                            // 'style_name' => $style->Name($merchant_id)
                        ];
                    }
                })->filter();
            }
            return array(
                'business_segment_id' => $item->id,
                'title' => $item->full_name,
                'time' => "$item->delivery_time " . trans("$string_file.minute"),
                'currency' => "$currency",
                'arr_products' => $arr_product,
                'rating' => !empty($item->rating) ? $item->rating : "2.5",
                'is_business_segment_open' => $is_business_segment_open,
                'multi_store' => $multi_store > 0 ? true : false
            );
        });
        return !empty($search_text) ? $arr_restaurant : [];
    }

    public function getFoodGroceryClone($merchant_id)
    {
        return Segment::select('id', 'name', 'slag', 'icon', 'segment_group_id', 'sub_group_for_app', 'sub_group_for_admin')
            ->whereIn("sub_group_for_app", [1, 2])
            ->whereHas('Merchant', function ($q) use ($merchant_id) {
                $q->select('merchants.id as merchant_id');
                $q->where('merchant_id', $merchant_id);
                $q->select('id', 'sequence');
                $q->orderBy('sequence', 'ASC');
                // $q->where('is_coming_soon', 2);
            })->get()->pluck("slag")->toArray();
    }

    public function getMerchantVehicleName($merchant_id)
    {
        $vehicleTypeName = DB::table('vehicle_types')
            ->where([
                ['vehicle_types.merchant_id', '=', $merchant_id],
                ['vehicle_types.admin_delete', '=', NULL]
            ])
            ->join('language_vehicle_types', 'language_vehicle_types.vehicle_type_id', '=', 'vehicle_types.id')
            ->pluck('language_vehicle_types.vehicleTypeName', 'vehicle_types.id');
        return $vehicleTypeName;
    }

    public function getDvlaDetails($registration_number, $merchant)
    {
        $data  = [
            "registrationNumber" => $registration_number,
        ];
        $x_api_key = $merchant->Configuration->dvla_key;
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://driver-vehicle-licensing.api.gov.uk/vehicle-enquiry/v1/vehicles',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'x-api-key: ' . $x_api_key,
                'Content-Type: application/json'
            ),
        ));

        $res = curl_exec($curl);
        return $res;
    }



    public function getMerchantLaundryOutlets(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $segment_id =  $request->segment_id;
        $user_id =  $request->user_id;
        $is_fav = $request->is_favourite;
        $is_search = $request->is_search;
        $search_text = $request->search_text;
        $is_popular = $request->is_popular;
        $distance_unit = 1;
        $radius = $distance_unit == 2 ? 3958.756 : 6367;
        $distance = $request->distance;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        // 1 means demo login
        $user = $request->user('api');
        $user_type = $user->login_type == 1 ?  $user->login_type : NULL;
        $locale = \App::getLocale();

        $arr_categories_id = [];
        if (!empty($search_text) && $is_search == 1) {
            $arr_category = Category::select('id')
                ->with(['LangCategory' => function ($q) use ($search_text, $locale) {
                    $q->where('name', 'like', "%$search_text%")
                        ->where(function ($q) use ($locale) {
                            $q->where('locale', $locale);
                            $q->orWhere('locale', '!=', NULL);
                        });
                }])
                ->whereHas('LangCategory', function ($q) use ($search_text, $locale) {
                    $q->where('name', 'like', "%$search_text%")
                        ->where(function ($q) use ($locale) {
                            $q->where('locale', $locale);
                            $q->orWhere('locale', '!=', NULL);
                        });
                })
                ->whereHas('Segment', function ($q) use ($segment_id) {
                    $q->where('segment_id', $segment_id);
                })
                ->where('merchant_id', $merchant_id)
                ->where('delete', NULL)
                ->get();
            $arr_categories_id = array_pluck($arr_category, 'id');
        }

        $haversineSQL = '( ' . $radius . ' * acos( cos( radians(' . $latitude . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( latitude ) ) ) )';
        // p($haversineSQL);
        $query = LaundryOutlet::select('id', 'country_area_id', 'full_name', 'country_id', 'minimum_amount', 'business_logo', 'is_popular', 'open_time', 'close_time', 'latitude', 'longitude', 'rating', 'segment_id')
            ->addSelect(DB::raw($haversineSQL . ' AS distance'))
            ->where([['merchant_id', '=', $merchant_id], ['status', '=', 1]])
            //            ->with('ActivePromoCode')
            ->where(function ($q) use ($is_search, $search_text, $locale, $arr_categories_id) {
                if (!empty($search_text) && $is_search == 1) {
                    $q->where('full_name', 'like', "%$search_text%");
                    $q->orWhereIn('laundry_outlets.id', function ($query) use ($search_text, $locale, $arr_categories_id) {
                        $query->select('p.laundry_outlet_id')
                            ->from('laundry_services as p')
                            ->join('language_laundry_services as lp', 'p.id', '=', 'lp.product_id')
                            ->where(function ($q) use ($search_text, $locale) {
                                $q->where('lp.name', 'like', "%$search_text%");
                                $q->where(function ($q) use ($locale) {
                                    $q->where('locale', $locale);
                                    $q->orWhere('locale', '!=', NULL);
                                });
                            })
                            ->orWhere(function ($q) use ($arr_categories_id) {
                                if (!empty($arr_categories_id)) {
                                    $q->whereIn('category_id', $arr_categories_id);
                                }
                            });
                    });
                }
            })
            ->where(function ($q) use ($is_popular) {
                if (!empty($is_popular) && $is_popular == "YES") {
                    $q->where('is_popular', 1);
                }
            })
            ->where('country_area_id', $request->area)
            ->orderBy('distance');
        //        if (!empty($is_fav) && $is_fav == "YES") {
        //            $query->whereHas('FavouriteBusinessSegment', function ($qq) use ($user_id, $segment_id) {
        //                $qq->where([['user_id', '=', $user_id], ['segment_id', '=', $segment_id]]);
        //            });
        //        }
        // means radius check works only in case of normal user not demo
        if ($user_type != 1) {
            $query->whereRaw($haversineSQL . '<= ?', [$distance]);
        }
        $arr_outlets =  $query->paginate(10);
        // p($arr_restaurant);
        return $arr_outlets;
    }


    public function getMerchantPopularLaundryOutlet(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $distance_unit = 1;
        $radius = $distance_unit == 2 ? 3958.756 : 6367;
        $distance = $request->distance;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $user = $request->user('api');
        $user_type = $user->login_type == 1 ?  $user->login_type : NULL;

        $haversineSQL = '( ' . $radius . ' * acos( cos( radians(' . $latitude . ') ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(' . $longitude . ') ) + sin( radians(' . $latitude . ') ) * sin( radians( latitude ) ) ) )';
        $query = LaundryOutlet::select('id', 'country_area_id', 'segment_id', 'full_name', 'country_id', 'minimum_amount', 'business_logo', 'is_popular', 'open_time', 'close_time', 'latitude', 'longitude', 'rating')
            ->addSelect(DB::raw($haversineSQL . ' AS distance'))
            ->where([['merchant_id', '=', $merchant_id], ['is_popular', '=', 1], ['status', '=', 1]])
            ->where('country_area_id', $request->area)
            ->orderBy('distance');

        if ($user_type != 1) {
            $query->whereRaw($haversineSQL . '<= ?', [$distance]);
        }
        $query->whereHas('Segment', function ($q) {
            $q->where('sub_group_for_app', 6);
        });
        return $query->get();
    }

    public function getMerchantCategory(Request $request)
    {
        $merchant_id = $request->merchant_id;
        $segment_id =  $request->segment_id;
        $user_id =  $request->user_id;
        $is_fav = $request->is_favourite;
        $is_search = $request->is_search;
        $search_text = $request->search_text;
        $is_popular = $request->is_popular;
        $distance_unit = 1;
        $radius = $distance_unit == 2 ? 3958.756 : 6367;
        $distance = $request->distance;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        // 1 means demo login
        $user = $request->user('api');
        $user_type = $user->login_type == 1 ?  $user->login_type : NULL;
        $locale = \App::getLocale();
        $arr_category = Category::select('id', 'category_image')
            ->with(['LangCategory' => function ($q) use ($search_text, $locale) {
                $q->select('id', 'name', 'locale') // Make sure to select 'name'
                    ->where(function ($q) use ($locale) {
                        $q->where('locale', $locale)
                            ->orWhere('locale', '!=', null);
                    });
            }])
            ->whereHas('LangCategory', function ($q) use ($search_text, $locale) {
                $q->where(function ($q) use ($locale) {
                    $q->where('locale', $locale)
                        ->orWhere('locale', '!=', null);
                });
            })
            ->whereHas('Segment', function ($q) use ($segment_id) {
                $q->where('segment_id', $segment_id);
            })
            ->where(['merchant_id' => $merchant_id, 'is_home_screen' => 1])
            ->whereNot('category_parent_id', 0)
            ->whereNull('delete')
            ->get()
            ->map(function ($category) use ($merchant_id, $search_text) {
                $category->name = $category->Name($merchant_id);

                if (!empty($search_text) && stripos($category->name, $search_text) !== false) {
                    $category->is_selected = true;
                } else {
                    $category->is_selected = false;
                }
                return $category;
            });

        return $arr_category;
    }
}
