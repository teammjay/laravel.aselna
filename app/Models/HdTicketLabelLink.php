<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HdTicketLabelLink extends Model
{
    // Set a custom table name
    protected $table = 'hd_ticket_hd_ticket_label_link';

    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['hd_ticket_label_id','hd_ticket_id'];

    public function ticket(){
        return $this->belongsTo(HDMessage::class, 'hd_ticket_id');
    }

    public function label(){
        return $this->belongsTo(HdTicketLabel::class, 'hd_ticket_label_id');
    }
}
