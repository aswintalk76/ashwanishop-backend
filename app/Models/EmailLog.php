<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = ['order_id', 'user_id', 'type', 'recipient', 'subject', 'status', 'error'];
}
