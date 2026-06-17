<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrAttachment extends Model
{
    protected $table = 'tr_attachments';

    protected $fillable = [
        'sppd_id',
        'reference_id',
        'module',
        'category',
        'type',
        'file_name',
        'file_path',
        'url',
        'created_by',
    ];

    public function sppd()
    {
        return $this->belongsTo(TrSppd::class, 'sppd_id');
    }

    public function scopeModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeAccommodation($query)
    {
        return $query->where('category', 'accommodation');
    }

    public function scopeTransport($query)
    {
        return $query->where('category', 'transport');
    }
}