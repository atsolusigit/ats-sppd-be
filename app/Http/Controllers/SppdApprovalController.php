<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TrSppd;
use App\Models\TrSppdApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;

class SppdApprovalController extends Controller
{
    /**
     * APPROVE / REJECT / REVISION
     */
    public function action(Request $request, $sppdId)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $request->validate([
            'status' => 'required|in:approved,rejected,revision',
            'notes'  => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {

            $sppd = TrSppd::with('approvalFlow')->findOrFail($sppdId);

            /*
            |--------------------------------------------------------------------------
            | GET CURRENT APPROVAL LEVEL
            |--------------------------------------------------------------------------
            */

            $currentLevel = $sppd->current_approval_level ?? 0;

            $approval = TrSppdApproval::where('sppd_id', $sppdId)
                ->where('approval_level', $currentLevel + 1)
                ->first();

            if (!$approval) {
                return response()->json([
                    'status' => false,
                    'message' => 'Approval level tidak ditemukan'
                ], 200);
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE APPROVAL
            |--------------------------------------------------------------------------
            */

            $approval->update([
                'status' => $request->status,
                'notes' => $request->notes,
                'approver_id' => $user->id,
                'approved_at' => Carbon::now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | LOGIC STATUS
            |--------------------------------------------------------------------------
            */

            if ($request->status === 'approved') {

                $sppd->current_approval_level += 1;

                $isLastLevel = TrSppdApproval::where('sppd_id', $sppdId)
                    ->where('status', 'waiting')
                    ->doesntExist();

                if ($isLastLevel) {
                    $sppd->approval_status = 'approved';
                }

            } elseif ($request->status === 'rejected') {

                $sppd->approval_status = 'rejected';

            } elseif ($request->status === 'revision') {

                $sppd->approval_status = 'revision';
            }

            $sppd->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Approval berhasil diproses',
                'data' => [
                    'sppd_id' => $sppd->id,
                    'approval_status' => $sppd->approval_status,
                    'current_level' => $sppd->current_approval_level
                ]
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('SPPD Approval Error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET APPROVAL HISTORY
     */
    public function history($sppdId)
    {
        $data = TrSppdApproval::where('sppd_id', $sppdId)
            ->orderBy('approval_level', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'level' => $item->approval_level,
                    'status' => $item->status,
                    'notes' => $item->notes,
                    'approved_at' => $item->approved_at,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }
}