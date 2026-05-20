<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstJabatanApproval extends Model
{
    protected $table = 'mst_jabatan_approvals';

    protected $fillable = [
        'jabatan_id',
        'approval_flow_id',
        'approval_level',
    ];

    /**
     * Relasi ke flow approval (SPPD, Risk, dll)
     */
    public function flow()
    {
        return $this->belongsTo(MstApprovalFlow::class, 'approval_flow_id');
    }

    /**
     * Relasi ke jabatan (siapa yang approve)
     */
    public function jabatan()
    {
        return $this->belongsTo(MstJabatan::class, 'jabatan_id');
    }

    /**
     * Scope untuk sorting approval step
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('approval_level', 'asc');
    }
}