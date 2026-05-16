<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    protected $table = 'whatsapp_logs';

    protected $fillable = ['order_id', 'recipient', 'message', 'status', 'response'];
}
