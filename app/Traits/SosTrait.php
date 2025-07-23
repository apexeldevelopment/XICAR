<?php
namespace App\Traits;

use App\Models\AllSosRequest;
use Auth;
use App\Models\SosRequest;

trait SosTrait
{
  public function getAllSosRequest($pagination = true, $sos_version = 1)
  {
      $merchant = Auth::user('merchant')->load('CountryArea');
      $merchant_id = $merchant->parent_id != 0 ? $merchant->parent_id : $merchant->id;
      if($sos_version == 1){
          $query = SosRequest::where([['merchant_id', '=', $merchant_id]])->latest();
          if (!empty($merchant->CountryArea->toArray())) {
              $area_ids = array_pluck($merchant->CountryArea, 'id');
              $query->whereIn('country_area_id', $area_ids);
          }
      }
      else{
              $query = AllSosRequest::with(["Booking", "Booking.Driver", "Booking.CountryArea"])
              ->where('merchant_id', '=', $merchant_id)
              ->latest();

          if (!empty($merchant->CountryArea->toArray())) {
              $area_ids = $merchant->CountryArea->pluck('id');
              $query->whereHas('Booking', function($q) use ($area_ids) {
                  $q->whereIn('country_area_id', $area_ids);
              });
          }
      }
      return $pagination ? $query->paginate(25) : $query;
  }
}
