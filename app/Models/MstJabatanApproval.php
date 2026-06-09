<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstJabatanApproval extends Model
{
    protected $table = 'mst_jabatan_approvals';

    protected $fillable = [
        'approval_flow_id',
        'approval_order',

        'approval_mode',
        'target_level',
        'approval_key',

        'target_jabatan_id',
        'target_department_id',
        'target_role_id',
        'target_user_id',

        'is_required',
        'can_reject',
        'can_revision',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Flow approval (SPPD / Risk / dll)
     */
    public function flow()
    {
        return $this->belongsTo(MstApprovalFlow::class, 'approval_flow_id');
    }

    /**
     * Target jabatan (optional)
     */
    public function targetJabatan()
    {
        return $this->belongsTo(MstJabatan::class, 'target_jabatan_id');
    }

    /**
     * Target department (optional)
     */
    public function targetDepartment()
    {
        return $this->belongsTo(MstDepartment::class, 'target_department_id');
    }

    /**
     * Target role (optional)
     */
    public function targetRole()
    {
        return $this->belongsTo(MstRole::class, 'target_role_id');
    }

    /**
     * Target user (direct approval)
     */
    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Urutan approval flow
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('approval_order', 'asc');
    }

    /**
     * Filter berdasarkan flow
     */
    public function scopeByFlow($query, $flowId)
    {
        return $query->where('approval_flow_id', $flowId);
    }

    /**
     * Filter berdasarkan mode
     */
    public function scopeByMode($query, $mode)
    {
        return $query->where('approval_mode', $mode);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Cek apakah approval ini mandatory
     */
    public function isRequired(): bool
    {
        return (bool) $this->is_required;
    }

    /**
     * Cek apakah bisa reject
     */
    public function canReject(): bool
    {
        return (bool) $this->can_reject;
    }

    /**
     * Cek apakah bisa request revision
     */
    public function canRevision(): bool
    {
        return (bool) $this->can_revision;
    }
}