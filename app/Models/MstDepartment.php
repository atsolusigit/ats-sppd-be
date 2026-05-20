<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstDepartment extends Model
{
    protected $table = 'mst_departments';

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'created_by',
    ];

    /**
     * Users dalam department (many-to-many)
     */
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'tr_user_department',
            'department_id',
            'user_id'
        );
    }

    /**
     * Jabatan dalam department
     */
    public function jabatans()
    {
        return $this->hasMany(MstJabatan::class, 'department_id');
    }

    /**
     * Risk / business data
     */
    public function riskHeaders()
    {
        return $this->hasMany(TrRiskHeader::class, 'department_id');
    }

    /**
     * Pembuat department
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}