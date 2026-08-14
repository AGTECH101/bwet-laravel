<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExportHistory extends Model
{
    protected $fillable = [
        'user_id', 'export_format', 'batch_id', 'export_type',
        'file_name', 'file_size', 'exported_at', 'expires_at'
    ];

    protected $casts = [
        'exported_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function batch()
    {
        return $this->belongsTo(Poultry\Batch::class);
    }
}