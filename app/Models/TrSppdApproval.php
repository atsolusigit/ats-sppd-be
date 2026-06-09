<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrSppdApproval extends Model
{
    protected $table = 'tr_sppd_approvals';

    protected $fillable = [
        'sppd_id',
        'approval_level',
        'approval_key',
        'approver_id',
        'approver_jabatan_id',
        'status',
        'notes',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATION
    |--------------------------------------------------------------------------
    */

    /**
     * SPPD Header
     */
    public function sppd()
    {
        return $this->belongsTo(
            TrSppd::class,
            'sppd_id'
        );
    }

    /**
     * User approver
     */
    public function approver()
    {
        return $this->belongsTo(
            User::class,
            'approver_id'
        );
    }

    /**
     * Jabatan approver
     */
    public function approverJabatan()
    {
        return $this->belongsTo(
            MstJabatan::class,
            'approver_jabatan_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeWaiting($query)
    {
        return $query->where(
            'status',
            'waiting'
        );
    }

    public function scopeApproved($query)
    {
        return $query->where(
            'status',
            'approved'
        );
    }

    public function scopeRejected($query)
    {
        return $query->where(
            'status',
            'rejected'
        );
    }

    public function scopeRevision($query)
    {
        return $query->where(
            'status',
            'revision'
        );
    }
}