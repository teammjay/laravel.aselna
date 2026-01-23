<?php

namespace App\Models;

use App\Constants\Operators;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShippingServiceRule extends Model
{
    // Set a custom table name
    protected $table = 'shipping_service_rule';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }


    protected $fillable = ['id','min_weight_value','min_weight_weight_unit','max_weight_value','max_weight_weight_unit','priority','min_price','max_price','signature_confirmation','client_id','version','weight_operator','price_operator','round_weight_value','round_weight_weight_unit','package_type_filter_id','shipping_carrier_service_id','color','quantity_operator','min_quantity','max_quantity','created_by','updated_by','deleted_by','deleted_at','created_at','updated_at'];

    public function packageType(){
        return $this->belongsTo(ShippingPackage::class,'package_type_filter_id');
    }

    public function ShippingCarrierService(){
        return $this->belongsTo(ShippingCarrierService::class,'shipping_carrier_service_id');
    }

    public static function getOrderShippingCarrierService($weight){

        $weight= $weight * 0.03527396195;
        Log::info('Converted Weight in Pounds: '.$weight);
        $rules = SELF::with('ShippingCarrierService','ShippingCarrierService.ShippingCarrier')->whereNotNull('shipping_carrier_service_id')->orderBy('priority','asc')->get();
        if(count($rules ?? []) > 0){
            foreach($rules as $rule){
                $value1 = $rule->min_weight_value;
                $value2 = $rule->max_weight_value;
                $operator = Operators::getOperatorsName($rule->weight_operator);

                if (SELF::compareWeight($weight, $operator, $value1, $value2)) {
                    return $rule;
                }

            }
        }
        return null;
    }

    public static function compareWeight($weight, $operator, $value1, $value2)
    {
        switch ($operator) {
            case '<':
                return $weight < $value1;
            case '<=':
                return $weight <= $value1;
            case '>':
                return $weight > $value1;
            case '>=':
                return $weight >= $value1;
            case '==':
                return $weight == $value1;
            case '!=':
                return $weight != $value1;
            case 'between':
                if ($value2 != '') {
                    return $weight >= $value1 && $weight <= $value2;
                }
                return false;
            default:
                return false;
        }
    }
}
