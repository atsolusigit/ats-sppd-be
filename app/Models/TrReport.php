<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrReport extends Model
{
    protected $table = 'tr_report';

    protected $fillable = [
        'sppd_id',
        'tujuan_perjalanan',
        'ringkasan_hasil_kegiatan',
        'lampiran',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'lampiran' => 'array'
    ];

    public function sppd()
    {
        return $this->belongsTo(
            TrSppd::class,
            'sppd_id'
        );
    }
}