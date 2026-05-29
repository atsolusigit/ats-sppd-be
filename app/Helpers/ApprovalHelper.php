<?php

namespace App\Helpers;

use App\Models\MstJabatanApproval;
use App\Models\TrSppd;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApprovalHelper
{
    public static function createApprovalSteps($sppdId, $flowId)
    {

        DB::table('tr_sppd_approvals')
            ->where('sppd_id', $sppdId)
            ->delete();
        /*
        |--------------------------------------------------------------------------
        | GET FLOW STEPS
        |--------------------------------------------------------------------------
        */

        $flows = MstJabatanApproval::query()
            ->where('approval_flow_id', $flowId)
            ->ordered()
            ->get();

        if ($flows->isEmpty()) {

            throw new \Exception(
                "Approval step tidak ditemukan untuk flow id {$flowId}"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | GET SPPD
        |--------------------------------------------------------------------------
        */

        $sppd = TrSppd::find($sppdId);

        if (!$sppd) {

            throw new \Exception(
                "SPPD dengan id {$sppdId} tidak ditemukan"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | GET REQUESTER
        |--------------------------------------------------------------------------
        */

        $requester = User::find($sppd->requester_id);

        if (!$requester) {

            throw new \Exception(
                "Requester user tidak ditemukan"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE APPROVALS
        |--------------------------------------------------------------------------
        */

        $inserted = 0;

        foreach ($flows as $flow) {

            $approverId = null;
            $approverJabatanId = null;

            /*
            |--------------------------------------------------------------------------
            | HIERARCHY MODE
            |--------------------------------------------------------------------------
            */

            if ($flow->approval_mode === 'hierarchy') {

                if (!$requester->jabatan_id) {
                    continue;
                }

                // jabatan requester
                $currentJabatan = DB::table('mst_jabatans')
                    ->where('id', $requester->jabatan_id)
                    ->first();

                if (!$currentJabatan) {
                    continue;
                }

                // parent jabatan
                $parentJabatan = DB::table('mst_jabatans')
                    ->where('id', $currentJabatan->parent_id)
                    ->first();

                if (!$parentJabatan) {
                    continue;
                }

                // approver user
                $approver = User::where(
                    'jabatan_id',
                    $parentJabatan->id
                )->first();

                if (!$approver) {
                    continue;
                }

                $approverId = $approver->id;
                $approverJabatanId = $parentJabatan->id;
            }

            /*
            |--------------------------------------------------------------------------
            | USER MODE
            |--------------------------------------------------------------------------
            */

            else if ($flow->approval_mode === 'user') {

                $approverId = $flow->target_user_id;
                $approverJabatanId = $flow->target_jabatan_id;
            }

            /*
            |--------------------------------------------------------------------------
            | JABATAN MODE
            |--------------------------------------------------------------------------
            */

            else if ($flow->approval_mode === 'jabatan') {

                $approver = User::where(
                    'jabatan_id',
                    $flow->target_jabatan_id
                )->first();

                if (!$approver) {
                    continue;
                }

                $approverId = $approver->id;
                $approverJabatanId = $flow->target_jabatan_id;
            }

            /*
            |--------------------------------------------------------------------------
            | DEPARTMENT MODE
            |--------------------------------------------------------------------------
            */

            else if ($flow->approval_mode === 'department') {

                $approver = User::where(
                    'department_id',
                    $flow->target_department_id
                )->first();

                if (!$approver) {
                    continue;
                }

                $approverId = $approver->id;
                $approverJabatanId = $approver->jabatan_id;
            }

            /*
            |--------------------------------------------------------------------------
            | ROLE MODE
            |--------------------------------------------------------------------------
            */

            else if ($flow->approval_mode === 'role') {

                $approver = User::where(
                    'role_id',
                    $flow->target_role_id
                )->first();

                if (!$approver) {
                    continue;
                }

                $approverId = $approver->id;
                $approverJabatanId = $approver->jabatan_id;
            }

            /*
            |--------------------------------------------------------------------------
            | SKIP IF NO APPROVER
            |--------------------------------------------------------------------------
            */

            if (!$approverId) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | INSERT APPROVAL
            |--------------------------------------------------------------------------
            */

            DB::table('tr_sppd_approvals')->insert([

                'sppd_id' => $sppdId,

                'approval_level' => $flow->approval_order,

                'approver_id' => $approverId,

                'approver_jabatan_id' => $approverJabatanId,

                'status' => 'waiting',

                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $inserted++;
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        if ($inserted <= 0) {

            throw new \Exception(
                "Approval gagal dibuat. Approver tidak ditemukan."
            );
        }

        return true;
    }
}