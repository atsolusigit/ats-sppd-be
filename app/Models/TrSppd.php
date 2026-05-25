<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class TrSppd extends Model
{
    // use SoftDeletes;

    protected $table = 'tr_sppd';

    protected $fillable = [
        'sppd_number',
        'jenis_dokumen',
        'cost_center',
        // 'jenis_perjalanan',
        'kegiatan',
        'ringkasan_agenda',

        'requester_id',

        'approval_status',
        'approval_flow_id',

        'requester_department_id',
        'requester_jabatan_id',
        'department_id',

        'total_transport',
        'total_accommodation',
        'grand_total',
    ];

    protected $casts = [
        'total_transport' => 'float',
        'total_accommodation' => 'float',
        'grand_total' => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATION
    |--------------------------------------------------------------------------
    */

    /**
     * Requester / pembuat sppd
     */
    public function requester()
    {
        return $this->belongsTo(
            User::class,
            'requester_id'
        );
    }

    /**
     * Peserta sppd
     */
    public function peserta()
    {
        return $this->hasMany(
            TrSppdPeserta::class,
            'sppd_id'
        );
    }

    /**
     * Approval history
     */
    public function approvals()
    {
        return $this->hasMany(
            TrSppdApproval::class,
            'sppd_id'
        );
    }

    public function approval_flow()
    {
        return $this->belongsTo(
            MstApprovalFlow::class, 
            'approval_flow_id'); 
    }

    public function department()
    {
        return $this->belongsTo(
            MstDepartment::class,
            'department_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeDraft($query)
    {
        return $query->where(
            'approval_status',
            'draft'
        );
    }

    public function scopeSubmitted($query)
    {
        return $query->where(
            'approval_status',
            'submitted'
        );
    }

    public function scopeApproved($query)
    {
        return $query->where(
            'approval_status',
            'approved'
        );
    }

    public function scopeRejected($query)
    {
        return $query->where(
            'approval_status',
            'rejected'
        );
    }
    

    /*
    |--------------------------------------------------------------------------
    | ACCESSOR
    |--------------------------------------------------------------------------
    */

    public function getTotalPesertaAttribute()
    {
        return $this->peserta->count();
    }
}