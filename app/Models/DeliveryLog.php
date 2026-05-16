<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryLog extends Model
{
    protected $fillable = ['order_id', 'admin_id', 'action', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
