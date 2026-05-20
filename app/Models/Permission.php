<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'mst_permissions';

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Role yang memiliki permission ini
     */
    public function roles()
    {
        return $this->belongsToMany(
            MstRole::class,
            'role_permissions',
            'permission_id',
            'role_id'
        );
    }
}