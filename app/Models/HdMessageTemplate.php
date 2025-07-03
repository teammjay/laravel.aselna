<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HdMessageTemplate extends Model
{
    // Set a custom table name
    protected $table = 'hd_message_template';

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

    protected $fillable = ['id','client_id','name','template_code','order_operation_type','store_type_id','print_to_file','replacement_order_shipped','created_by','updated_by','created_at','updated_at'];

    public function storeType(){
        return $this->belongsTo(StoreType::class, 'store_type_id');
    }

    public static function getOrderOperationTemplate($operationType, $replacementShipped, $returnLabelAttached)
    {
        $templates = self::all();

        foreach ($templates as $template) {
            $templateOperationType = $template->order_operation_type;

            if (is_null($templateOperationType) && !is_null($operationType)) {
                continue;
            }

            if (!is_null($templateOperationType) && (is_null($operationType) || $templateOperationType !== $operationType)) {
                continue;
            }

            $templateRequiresReplacementShipped = $template->replacement_order_shipped ?? false;
            if ($templateRequiresReplacementShipped && !$replacementShipped) {
                continue;
            }

            $templateRequiresReturnLabel = $template->print_to_file ?? false;
            if ($templateRequiresReturnLabel !== $returnLabelAttached) {
                continue;
            }

            return $template;
        }

        return null;
    }
}
