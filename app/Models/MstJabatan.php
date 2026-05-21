<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstJabatan extends Model
{
    use HasFactory;

    protected $table = 'mst_jabatans';

    protected $fillable = [
        'name',
        'code',
        'level',
        'department_id',
        'parent_id',
        'status',
    ];

    /**
     * Department dari jabatan
     */
    public function department()
    {
        return $this->belongsTo(MstDepartment::class, 'department_id');
    }

    /**
     * Struktur organisasi (atasan langsung)
     */
    public function parent()
    {
        return $this->belongsTo(MstJabatan::class, 'parent_id');
    }

    /**
     * Anak jabatan (hierarki bawah)
     */
    public function children()
    {
        return $this->hasMany(MstJabatan::class, 'parent_id');
    }

    /**
     * Mapping approval flow
     */
    public function approvalFlows()
    {
        return $this->hasMany(MstJabatanApproval::class, 'target_jabatan_id');
    }

    /**
     * Cek apakah jabatan bisa approve
     */
    public function canApprove()
    {
        return $this->approvalFlows()->exists();
    }
}