<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstPage extends Model
{
    protected $table = 'mst_page';

    protected $fillable = [
        'name',
        'head_url',
        'parent_id',
        'icon',
        'sort_order',
        'is_web',
        'is_mobile',
        'created_by',
        'deleted_by',
        'status',
        'user_id',
    ];

    /**
     * User Page Mapping
     */
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'user_page',
            'page_id',
            'user_id'
        );
    }

    /**
     * Role Page Mapping
     */
    public function roles()
    {
        return $this->belongsToMany(
            MstRole::class,
            'tr_role_page',
            'page_id',
            'role_id'
        )
        ->withPivot('access')
        ->withTimestamps();
    }

    /**
     * Parent Menu
     */
    public function parent()
    {
        return $this->belongsTo(
            MstPage::class,
            'parent_id'
        );
    }

    /**
     * Child Menus
     */
    public function children()
    {
        return $this->hasMany(
            MstPage::class,
            'parent_id'
        )
        ->orderBy('sort_order');
    }
}