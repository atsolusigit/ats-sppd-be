<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstApprovalFlow extends Model
{
    protected $table = 'mst_approval_flows';

    protected $fillable = [
        'name',
        'module',
        'department_id',
        'is_active',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * Relasi ke jabatan approval (workflow steps)
     */
    public function jabatanApprovals()
    {
        return $this->hasMany(MstJabatanApproval::class, 'approval_flow_id');
    }

    /**
     * Optional: department (kalau flow dibatasi per department)
     */
    public function department()
    {
        return $this->belongsTo(MstDepartment::class, 'department_id');
    }

    /**
     * Creator user
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Updater user
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope aktif flow
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}