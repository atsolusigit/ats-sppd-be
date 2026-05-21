<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrSppdTransportasi extends Model
{
    use SoftDeletes;

    protected $table = 'td_sppd_transportations';

    protected $fillable = [
        'participant_id',

        'jenis_transportasi',
        'nama_travel',

        'asal_keberangkatan',
        'tujuan_keberangkatan',

        'waktu',

        'estimasi_biaya',

        'keterangan',

        'nama_lengkap',
        'no_hp',
    ];

    protected $casts = [
        'estimasi_biaya' => 'float',
        'waktu' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATION
    |--------------------------------------------------------------------------
    */

    public function peserta()
    {
        return $this->belongsTo(
            TrSppdPeserta::class,
            'participant_id'
        );
    }
}