<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MstRole extends Model
{
    use HasFactory;

    protected $table = 'mst_roles';

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'is_default',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * ====================================
     * USERS
     * ====================================
     */
    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    /**
     * ====================================
     * PAGES / MENUS
     * ====================================
     */
    public function pages()
    {
        return $this->belongsToMany(
            MstPage::class,
            'tr_role_page',
            'role_id',
            'page_id'
        )
        ->withPivot('access')
        ->withTimestamps();
    }

    /**
     * ====================================
     * PERMISSIONS
     * ====================================
     */
    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role_id',
            'permission_id'
        )
        ->withTimestamps();
    }
}