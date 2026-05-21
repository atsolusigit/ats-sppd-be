<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class TrSppdPenginapan extends Model
{
    // use SoftDeletes;

    protected $table = 'td_sppd_accommodations';

    protected $fillable = [
        'participant_id',

        'jenis_penginapan',
        'nama_tempat',
        'lokasi',

        'check_in',
        'check_out',

        'estimasi_biaya',

        'keterangan',

        'nama_lengkap',
        'no_hp',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'estimasi_biaya' => 'float',
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