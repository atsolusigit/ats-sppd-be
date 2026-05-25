<?php

namespace App\Helpers;

use App\Models\MstJabatanApproval;
use Illuminate\Support\Facades\DB;

class ApprovalHelper
{
    public static function createApprovalSteps($sppdId, $flowId)
    {
        $flows = MstJabatanApproval::query()
            ->where('approval_flow_id', $flowId)
            ->ordered()
            ->get();

        if ($flows->isEmpty()) {
            return false;
        }

        foreach ($flows as $flow) {

            DB::table('tr_sppd_approvals')->insert([

                'sppd_id' => $sppdId,

                'approval_level' => $flow->approval_order,

                'approver_id' => $flow->target_user_id,

                'approver_jabatan_id' => $flow->target_jabatan_id,

                'status' => 'waiting',

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return true;
    }
}