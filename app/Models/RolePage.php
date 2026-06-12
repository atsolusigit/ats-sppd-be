<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePage extends Model
{
    protected $table = 'tr_role_page';

    protected $fillable = [
        'role_id',
        'page_id',
        'access',
        'created_by',
    ];

    public function role()
    {
        return $this->belongsTo(MstRole::class, 'role_id');
    }

    public function page()
    {
        return $this->belongsTo(MstPage::class, 'page_id');
    }
}