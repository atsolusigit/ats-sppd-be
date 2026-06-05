<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrReportApproval extends Model
{
    protected $table = 'tr_report_approvals';

    protected $fillable = [
        'report_id',
        'approval_level',
        'approver_id',
        'approver_jabatan_id',
        'status',
        'notes',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function report()
    {
        return $this->belongsTo(
            TrReport::class,
            'report_id'
        );
    }

    public function approver()
    {
        return $this->belongsTo(
            User::class,
            'approver_id'
        );
    }
}