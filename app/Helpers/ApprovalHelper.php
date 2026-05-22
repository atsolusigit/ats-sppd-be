<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class ApprovalHelper
{
    public static function createApprovalStep($sppdId, $flowId, $level)
    {
        $flow = DB::table('mst_approval_flow_details')
            ->where('approval_flow_id', $flowId)
            ->where('level', $level)
            ->first();

        if (!$flow) {
            return false;
        }

        DB::table('tr_sppd_approvals')->insert([
            'sppd_id' => $sppdId,
            'approval_level' => $level,
            'approver_id' => $flow->approver_id ?? null,
            'approver_jabatan_id' => $flow->approver_jabatan_id ?? null,
            'status' => 'waiting',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }
}