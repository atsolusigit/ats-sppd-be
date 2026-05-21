<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class TrSppdPeserta extends Model
{
    // use SoftDeletes;

    protected $table = 'td_sppd_participants';

    protected $fillable = [
        'sppd_id',
        'user_id',
        'nama',
        'nip',
        'jabatan',
        'kota_asal',
        'kota_tujuan',
        'tempat_sppd',
        'dari_tanggal',
        'sampai_tanggal',
        'total_transport',
        'total_accommodation',
        'total_estimation',
    ];

    protected $casts = [
        'dari_tanggal' => 'date',
        'sampai_tanggal' => 'date',

        'total_transport' => 'float',
        'total_accommodation' => 'float',
        'total_estimation' => 'float',
    ];
    
    /*
    |--------------------------------------------------------------------------
    | RELATION
    |--------------------------------------------------------------------------
    */

    public function sppd()
    {
        return $this->belongsTo(
            TrSppd::class,
            'sppd_id'
        );
    }

    public function transportasi()
    {
        return $this->hasMany(
            TrSppdTransportasi::class,
            'participant_id'
        );
    }

    public function penginapan()
    {
        return $this->hasMany(
            TrSppdPenginapan::class,
            'participant_id'
        );
    }
}