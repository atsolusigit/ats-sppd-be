<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\MstHeatmap;
use App\Models\MstRole;


if (!function_exists('check_validation')) {
    /**
     * Format a number as currency.
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    function check_validation($request, $array_validation, $customMessages = [])
    {
        $rules = array_merge([
            'asdp.required'=> 'The asdp header is required', // custom message
        ], $customMessages);

        $validator = Validator::make($request, $array_validation, $rules);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            $message = ($firstError === 'Ukuran file maksimal 2MB.') ? $firstError : 'validasi gagal';

            $json = response()->json([
                'code' => 400,
                'status' => 'error_validation',
                'message' => $message,
                'data' => $validator->messages()
            ], 200);

            return [1, $json];
        }
        else
        {
            return [0, ''];
        }
    }
}

if (!function_exists('json')) {
    /**
     * Format a number as currency.
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    function json($code, $status, $title, $message, $data)
    {
        return response()->json([
            'code' => $code,
            'status' => $status,
            'title' => $title,
            'message' => $message,
            'data' => $data
        ]);
    }
}

if (!function_exists('time_elapsed_string')) {
    /**
     * Format a number as currency.
     *
     * @param float $amount
     * @param string $currency
     * @return string
     */
    function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}

if (!function_exists('encrypt_decrypt_db')) {

    function encrypt_decrypt_db($type, $value, $id)
    {
        // Safety: pastikan id valid integer/string
        $id = (string) $id;
        $key = 'SM' . $id;

        // ENCRYPT: kembalikan expression agar MySQL mengeksekusi AES_ENCRYPT saat update
        if ($type === 'enc') {
            // Quote value & key menggunakan PDO agar aman terhadap tanda kutip
            $pdo = DB::getPdo();
            $quotedValue = $pdo->quote($value);       // menghasilkan '...'
            $quotedKey = $pdo->quote($key);           // menghasilkan 'SM23'
            // Contoh return: DB::raw("AES_ENCRYPT('nilai', 'SM23')")
            return DB::raw("AES_ENCRYPT({$quotedValue}, {$quotedKey})");
        }

        // DECRYPT: jalankan AES_DECRYPT terhadap ciphertext yang disimpan (binary) menggunakan binding
        try {
            // Jika $value adalah instance of \Illuminate\Database\Query\Expression (rare), we can't bind it.
            // Normalnya $value adalah nilai dari DB (binary/varbinary) -> gunakan binding.
            $row = DB::selectOne(
                "SELECT CAST(AES_DECRYPT(?, ?) AS CHAR) AS result",
                [$value, $key]
            );

            return $row->result ?? null;
        } catch (\Throwable $e) {
            \Log::warning("encrypt_decrypt_db(decode) error for id {$id}: " . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('encrypt_decrypt_md5')) {
    /**
     * Mengenkripsi atau mendekripsi string menggunakan AES-256-CBC dengan key berbasis MD5 hash.
     *
     * @param string $action 'enc' untuk enkripsi, 'dec' untuk dekripsi
     * @param string $string String yang ingin dienkripsi atau didekripsi
     * @param string $salt (Opsional) Salt tambahan untuk key dan IV
     * @return string
     */
    function encrypt_decrypt_md5($action, $string, $salt = '')
    {
        $output = '';
        $encrypt_method = 'AES-256-CBC';
        $secret_key = $salt . 'SEMESTA-asfyasiuyfiy238sadfh';
        $secret_iv = $salt . 'SEMESTA-asfyasiuyfiy238sadfh';

        // Generate key
        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        if ($action === 'enc') {
            $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
            $output = base64_encode($output);
        } elseif ($action === 'dec' && $string !== '') {
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        }

        return $output;
    }
}


if (!function_exists('map_access_array')) {
    /**
     * Konversi array akses ke struktur boolean
     *
     * @param array $access
     * @return array
     */
    function map_access_array(array $access)
    {
        return [
            'view' => in_array('view', $access),
            'create' => in_array('create', $access),
            'update' => in_array('update', $access),
            'delete' => in_array('delete', $access),
        ];
    }
}



if (!function_exists('get_follow_up_info')) {
    /**
     * Mendapatkan informasi tindak lanjut berdasarkan data bulanan
     *
     * @param $header
     * @param $monthlyData
     * @return array
     */
    function get_follow_up_info($header, $monthlyData)
    {
        $currentYear = \Carbon\Carbon::now()->year;
        $currentMonth = \Carbon\Carbon::now()->month;

        $decemberData = $monthlyData->where('month', 12)->first();
        $isFollowUpRequired = false;
        $message = '';
        $followUpDetails = [];

        if ($header->year < $currentYear) {
            // $decemberData is array, so use array access
            if ($decemberData && $decemberData['status_risiko'] == 'open' && $decemberData['is_finalize']) {
                $isFollowUpRequired = true;
                $message = "Risiko di bulan Desember {$header->year} masih open dan sudah difinalisasi. Ini menjadi tindak lanjut di tahun {$currentYear}.";
                $followUpDetails = [
                    'follow_up_year' => $currentYear,
                    'original_year' => $header->year,
                    'december_status' => 'open_finalized'
                ];
            } elseif ($decemberData && $decemberData['status_risiko'] == 'close') {
                $message = "Semua risiko sudah close di tahun {$header->year}.";
            } else {
                $message = "Data Desember {$header->year} belum difinalisasi atau tidak ada data.";
            }
        } elseif ($header->year == $currentYear) {
            if ($currentMonth == 12) {
                if ($decemberData && $decemberData['status_risiko'] == 'open') {
                    $isFollowUpRequired = true;
                    $message = "Perhatian: Risiko di bulan Desember masih open. Ini akan menjadi tindak lanjut di tahun " . ($currentYear + 1) . ".";
                    $followUpDetails = [
                        'follow_up_year' => $currentYear + 1,
                        'original_year' => $header->year,
                        'december_status' => 'open_current'
                    ];
                } else {
                    $message = "Semua risiko sudah close untuk tahun ini.";
                }
            } else {
                $message = "Tahun risk masih berjalan. Evaluasi follow-up akan dilakukan di akhir tahun.";
            }
        } else {
            $message = "Tahun risk belum dimulai.";
        }

        return [
            'is_follow_up_required' => $isFollowUpRequired,
            'header_year' => $header->year,
            'current_year' => $currentYear,
            'current_month' => $currentMonth,
            'message' => $message,
            'december_data' => $decemberData,
            'follow_up_details' => $followUpDetails
        ];
    }
}

if (!function_exists('check_if_follow_up_required')) {
    /**
     * Cek apakah tindak lanjut diperlukan berdasarkan data bulanan
     *
     * @param $header
     * @param $monthlyData
     * @return bool
     */
    function check_if_follow_up_required($header, $monthlyData)
    {
        $currentYear = \Carbon\Carbon::now()->year;
        $decemberData = $monthlyData->where('month', 12)->first();

        return $header->year < $currentYear &&
               $decemberData &&
               $decemberData->status_risiko == 'open' &&
               $decemberData->is_finalize;
    }


    if (!function_exists('generate_monthly_data')) {
    /**
     * Generate monthly data for a risk header
     *
     * @param $riskHeader
     */
    function generate_monthly_data($riskHeader) {
        $year = $riskHeader->year;
        $currentMonth = \Carbon\Carbon::now()->month;
        $currentYear = \Carbon\Carbon::now()->year;

        $timelineStart = \Carbon\Carbon::parse($riskHeader->rcsa->timeline_bulan_awal)->startOfMonth();
        $timelineEnd = \Carbon\Carbon::parse($riskHeader->rcsa->timeline_bulan_akhir)->endOfMonth();

        for ($month = 1; $month <= 12; $month++) {
            // $status = ($year < $currentYear || ($year == $currentYear && $month < $currentMonth)) ? 'close' : 'open';
            $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();

            $status = ($startDate <= $timelineEnd && $endDate >= $timelineStart)
                ? 'open'
                : 'close';

            \App\Models\TrRiskMonthly::create([
                'header_id' => $riskHeader->id,
                'risk_code' => $riskHeader->risk_code,
                'process_code' => $riskHeader->process_code,
                'month' => $month,
                'status_risiko' => $status,
                'start_date' => $startDate->toDateString(),
                'expired_date' => $endDate->toDateString(),
                'rr_level_dampak' => $riskHeader->residual_target_level_dampak,
                'rr_level_kemungkinan' => $riskHeader->residual_target_level_kemungkinan,
                'rr_posisi_risiko' => $riskHeader->residual_target_posisi_risiko,
                'rr_level_risiko' => $riskHeader->residual_target_level_risiko,
                'is_finalize' => false,
            ]);
        }
    }
}



if (!function_exists('get_color_by_position')) {
    /**
     * Get heatmap color by risk position.
     *
     * @param int|null $position
     * @return string|null
     */
    function get_color_by_position($position)
    {
        if (!$position) {
            return null;
        }

        $riskRange = \App\Models\MstHeatmapRiskRange::where('start', '<=', $position)
            ->where('end', '>=', $position)
            ->first();

        return $riskRange ? $riskRange->color : null;
    }
}

if (!function_exists('clean_string')) {
    /**
     * Bersihkan string dari karakter tidak valid agar aman di JSON dan encoding UTF-8.
     *
     * @param mixed $string
     * @return mixed
     */
    function clean_string($string)
    {
        if (!is_string($string)) return $string;

        // Hapus karakter kontrol yang tidak diinginkan
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);

        // Pastikan encoding UTF-8 valid
        if (!mb_check_encoding($string, 'UTF-8')) {
            $detected = mb_detect_encoding($string, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($detected !== false) {
                $string = mb_convert_encoding($string, 'UTF-8', $detected);
            } else {
                // Remove byte sequences yang invalid
                $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);
            }
        }

        return trim($string);
    }
}

if (!function_exists('clean_recursive')) {
    /**
     * Bersihkan array/object secara rekursif menggunakan clean_string.
     *
     * @param mixed $data
     * @return mixed
     */
    function clean_recursive($data)
    {
        if (is_array($data)) {
            $cleaned = [];
            foreach ($data as $key => $value) {
                $cleanedKey = is_string($key) ? clean_string($key) : $key;
                $cleaned[$cleanedKey] = clean_recursive($value);
            }
            return $cleaned;
        }

        if (is_object($data)) {
            if (method_exists($data, 'toArray')) {
                return clean_recursive($data->toArray());
            }

            $cleaned = new \stdClass();
            foreach (get_object_vars($data) as $key => $value) {
                $cleanedKey = clean_string($key);
                $cleaned->$cleanedKey = clean_recursive($value);
            }
            return $cleaned;
        }

        if (is_string($data)) {
            return clean_string($data);
        }

        return $data;
    }
}

if (!function_exists('get_decrypted_username')) {
    function get_decrypted_username($userObject)
    {
        if (!$userObject || empty($userObject->id)) {
            return 'Unknown User';
        }

        try {
            $row = DB::selectOne("
                SELECT CAST(AES_DECRYPT(username, CONCAT('SM', ?)) AS CHAR) AS name
                FROM users WHERE id = ? LIMIT 1
            ", [$userObject->id, $userObject->id]);

            if ($row && !empty($row->name)) {
                return clean_string($row->name);
            }
        } catch (\Throwable $e) {
            \Log::warning("Decrypt username error for user {$userObject->id}: ".$e->getMessage());
        }

        return 'Unknown User';
    }
}

if (!function_exists('get_decrypted_name')) {
    function get_decrypted_name($userObject)
    {
        if (!$userObject || empty($userObject->id)) {
            return 'User Tidak diketahui';
        }

        try {
            $row = DB::selectOne("
                SELECT CAST(AES_DECRYPT(name, CONCAT('SM', ?)) AS CHAR) AS result
                FROM users WHERE id = ? LIMIT 1
            ", [$userObject->id, $userObject->id]);

            if ($row && !empty($row->result)) {
                return clean_string($row->result);
            }
        } catch (\Throwable $e) {
            \Log::warning("Decrypt name error for user {$userObject->id}: ".$e->getMessage());
        }

        return 'User Tidak diketahui';
    }
}

if (!function_exists('get_decrypted_email')) {
    function get_decrypted_email($userObject)
    {
        if (!$userObject || empty($userObject->id)) {
            return 'Email Tidak diketahui';
        }

        try {
            $row = DB::selectOne("
                SELECT CAST(AES_DECRYPT(email, CONCAT('SM', ?)) AS CHAR) AS result
                FROM users WHERE id = ? LIMIT 1
            ", [$userObject->id, $userObject->id]);

            if ($row && !empty($row->result)) {
                return clean_string($row->result);
            }
        } catch (\Throwable $e) {
            \Log::warning("Decrypt email error for user {$userObject->id}: ".$e->getMessage());
        }

        return 'Email Tidak diketahui';
    }
}

if (!function_exists('get_month_name')) {
    /**
     * Ambil nama bulan dalam bahasa Indonesia berdasarkan nomor bulan.
     *
     * @param int $month Nomor bulan (1-12)
     * @return string Nama bulan atau string kosong jika tidak valid
     */
    function get_month_name($month)
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $months[$month] ?? '';
    }
}

if (!function_exists('initialize_risk_matrix')) {
    /**
     * Inisialisasi matrix 5x5 dengan nilai 0
     *
     * @return array Matrix 5x5 untuk heatmap risiko
     */
    function initialize_risk_matrix()
    {
        $matrix = [];
        for ($likelihood = 1; $likelihood <= 5; $likelihood++) {
            for ($impact = 1; $impact <= 5; $impact++) {
                $matrix[$likelihood][$impact] = 0;
            }
        }
        return $matrix;
    }
}

if (!function_exists('initialize_risk_event_matrix')) {
    /**
     * Inisialisasi matrix 5x5 berisi array kosong untuk menyimpan detail peristiwa risiko
     *
     * @return array Matrix 5x5 untuk daftar peristiwa risiko
     */
    function initialize_risk_event_matrix()
    {
        $matrix = [];
        for ($likelihood = 1; $likelihood <= 5; $likelihood++) {
            for ($impact = 1; $impact <= 5; $impact++) {
                $matrix[$likelihood][$impact] = [];
            }
        }
        return $matrix;
    }
}

if (!function_exists('initialize_risk_summary')) {
    /**
     * Inisialisasi summary dengan kategori default
     *
     * @return array Summary kategori risiko dengan nilai 0
     */
    function initialize_risk_summary()
    {
        return [
            'Low' => 0,
            'Low to Moderate' => 0,
            'Moderate' => 0,
            'Moderate to High' => 0,
            'High' => 0
        ];
    }
}

if (!function_exists('format_matrix_for_response')) {
    /**
     * Format matrix untuk response yang mudah dikonsumsi frontend
     *
     * @param array $matrix Matrix 5x5 hasil perhitungan
     * @return array Formatted matrix untuk response
     */
    function format_matrix_for_response($matrix)
    {
        $formatted = [];
        foreach ($matrix as $likelihood => $impacts) {
            foreach ($impacts as $impact => $count) {
                if ($count > 0) {
                    $formatted[] = [
                        'likelihood' => $likelihood,
                        'impact' => $impact,
                        'count' => $count,
                        'position' => "{$likelihood}_{$impact}",
                        'score' => $likelihood * $impact,
                        'category' => get_risk_category_by_score($likelihood * $impact)
                    ];
                }
            }
        }
        return $formatted;
    }
}


}

if (!function_exists('get_month_name')) {
    /**
     * Ambil nama bulan dalam bahasa Indonesia berdasarkan nomor bulan.
     *
     * @param int $month Nomor bulan (1-12)
     * @return string Nama bulan atau string kosong jika tidak valid
     */
    function get_month_name($month)
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $months[$month] ?? '';
    }

}


if (!function_exists('get_next_role_level')) {
    function get_next_role_level()
    {
        $maxLevel = MstRole::max('level') ?? 0;
        return $maxLevel + 1;
    }
}

if (!function_exists('can_user_approve_simple')) {
    function can_user_approve_simple($userId, $departmentId)
    {
        try {
            $user = \App\Models\User::find($userId);

            if (!$user) {
                return false;
            }

            // PERBAIKAN: Hanya Superadmin (1) yang selalu bisa approve
            if ($user->role_id == 1) {
                return true;
            }

            // PERBAIKAN: Role 2 (Admin) dan Role 3 hanya bisa approve dari department yang sama
            if (($user->role_id == 2 || $user->role_id == 3) && $user->department_id == $departmentId) {
                return true;
            }

            return false;

        } catch (\Exception $e) {
            \Log::error('Error in can_user_approve_simple: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('get_approver_jabatan_simple')) {
    function get_approver_jabatan_simple($departmentId, $userId = null)
    {
        try {
            // Jika user_id diberikan, gunakan jabatan user tersebut
            if ($userId) {
                $user = \App\Models\User::find($userId);
                if ($user && $user->jabatan_id) {
                    return $user->jabatan_id;
                }
            }

            // Jika tidak ada jabatan user, cari jabatan di department
            $jabatan = \App\Models\MstJabatan::where('department_id', $departmentId)->first();

            return $jabatan ? $jabatan->id : null;

        } catch (\Exception $e) {
            \Log::error('Error in get_approver_jabatan_simple: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('get_approval_status_simple')) {
    function get_approval_status_simple($documentId)
    {
        try {
            $approval = \App\Models\MstApproval::where('document_id', $documentId)->first();

            if (!$approval) {
                return 'not_found';
            }

            return $approval->status; // 'pending', 'approved', 'rejected'

        } catch (\Exception $e) {
            \Log::error('Error in get_approval_status_simple: ' . $e->getMessage());
            return 'error';
        }
    }
}

if (!function_exists('has_permission')) {
    /**
     * Cek apakah user punya permission tertentu
     *
     * @param \App\Models\User $user
     * @param string $permissionName
     * @return bool
     */
    function has_permission($user, $permissionName)
    {
        if (!$user) return false;

        // Ambil semua role user
        $roles = $user->roles; // pastikan model User punya relasi roles()
        foreach ($roles as $role) {
            // Ambil semua permissions role
            foreach ($role->permissions as $perm) {
                if (strtolower($perm->name) === strtolower($permissionName)) {
                    return true;
                }
            }
        }
        return false;
    }
}

if (!function_exists('check_role')) {
    /**
     * Cek apakah user punya role tertentu,
     * jika tidak langsung return JSON response standar.
     *
     * @param \App\Models\User|null $user
     * @param array|int $allowedRoles
     * @return bool|\Illuminate\Http\JsonResponse
     */
    function check_role($user, $allowedRoles)
    {
        if (!$user) {
            return response()->json([
                'status'  => false,
                'code'    => 401,
                'message' => 'Unauthorized',
                'detail'  => 'User tidak terautentikasi',
                'data'    => null
            ], 401);
        }

        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }

        if (!in_array($user->role_id, $allowedRoles)) {
            return response()->json([
                'status'  => false,
                'code'    => 403,
                'message' => 'Tidak Diizinkan',
                'detail'  => 'Anda tidak memiliki akses',
                'data'    => null
            ], 403);
        }

        return true;
    }
}

if (!function_exists('detect_storage_disk')) {
    /**
     * Detect storage disk based on file URL
     *
     * @param string $filepath
     * @return string
     */
    function detect_storage_disk($filepath)
    {
        // DigitalOcean Spaces
        if (str_contains($filepath, 'digitaloceanspaces.com')) {
            return 'do_spaces';
        }

        // AWS S3
        if (str_contains($filepath, 's3.amazonaws.com') || str_contains($filepath, '.s3.')) {
            return 's3';
        }

        // Google Cloud Storage
        if (str_contains($filepath, 'storage.googleapis.com') || str_contains($filepath, 'storage.cloud.google.com')) {
            return 'gcs';
        }

        // Local storage
        if (str_contains($filepath, '/storage/') || !str_contains($filepath, 'http')) {
            return 'public';
        }

        // Default fallback
        return config('filesystems.default', 'public');
    }
}

if (!function_exists('extract_storage_path')) {
    /**
     * Extract relative storage path from full URL
     *
     * @param string $filepath
     * @param string|null $disk
     * @return string
     */
    function extract_storage_path($filepath, $disk = null)
    {
        // Auto-detect disk if not provided
        if ($disk === null) {
            $disk = detect_storage_disk($filepath);
        }

        // Jika sudah relative path (tidak ada http/https), return as is
        if (!str_contains($filepath, 'http://') && !str_contains($filepath, 'https://')) {
            return $filepath;
        }

        switch ($disk) {
            case 'do_spaces':
                // Extract path dari DigitalOcean Spaces URL
                // Format: https://fortisid.sgp1.digitaloceanspaces.com/semesta/filename.pdf
                $pattern = '/https?:\/\/[^\/]+\/([^?]+)/';
                if (preg_match($pattern, $filepath, $matches)) {
                    return $matches[1];
                }
                break;

            case 's3':
                // Extract path dari AWS S3 URL
                $parsed = parse_url($filepath);
                return ltrim($parsed['path'] ?? '', '/');

            case 'gcs':
                // Extract path dari Google Cloud Storage URL
                $parsed = parse_url($filepath);
                $path = ltrim($parsed['path'] ?? '', '/');
                $parts = explode('/', $path, 2);
                return $parts[1] ?? $path;

            case 'public':
                // Extract path dari local storage URL
                return str_replace([
                    config('app.url') . '/storage/',
                    url('/storage/'),
                    '/storage/'
                ], '', $filepath);

            default:
                // Fallback: try to extract anything after last domain/bucket part
                $parsed = parse_url($filepath);
                return ltrim($parsed['path'] ?? $filepath, '/');
        }

        // Fallback: return original if extraction failed
        return $filepath;
    }
}

if (!function_exists('delete_file_from_storage')) {
    /**
     * Delete file from storage safely (supports multiple storage providers)
     *
     * @param string $filepath
     * @return bool
     */
    function delete_file_from_storage($filepath)
    {
        try {
            $disk = detect_storage_disk($filepath);
            $path = extract_storage_path($filepath, $disk);

            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->delete($path);
            }

            // File not exists, consider as success
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete file from storage: ' . $e->getMessage(), [
                'filepath' => $filepath
            ]);
            return false;
        }
    }
}

/*
|--------------------------------------------------------------------------
| STRING & FORMAT
|--------------------------------------------------------------------------
*/

if (!function_exists('clean_string')) {
    function clean_string($string)
    {
        if (!is_string($string)) return $string;
        return trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $string));
    }
}

if (!function_exists('format_currency')) {
    function format_currency($value)
    {
        if (empty($value)) return '';

        if (is_numeric($value)) {
            return 'Rp.' . number_format($value, 0, ',', '.');
        }

        return $value;
    }
}

if (!function_exists('format_target')) {
    function format_target($quantitative, $qualitative)
    {
        $result = '';

        if (!empty($quantitative)) {
            $result .= format_currency($quantitative);
        }

        if (!empty($qualitative)) {
            $result .= ($result ? "\n" : '') . $qualitative;
        }

        return $result;
    }
}

if (!function_exists('format_percentage')) {
    function format_percentage($value, $total)
    {
        if (!is_numeric($value) || !is_numeric($total) || $total <= 0) {
            return null;
        }

        $percentage = ($value / $total) * 100;

        $formatted = number_format($percentage, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted . '%';
    }
}
if (!function_exists('get_risk_code_name')) {
    function get_risk_code_name($header)
    {
        if (!$header) return '';

        // Kalau sudah eager load relasi
        if (isset($header->riskCode) && $header->riskCode && $header->riskCode->isNotEmpty()) {
            return $header->riskCode->pluck('code')->implode(', ');
        }
                                                                                                                                                                                                                                                         
        if (empty($header->risk_code)) {
            return '';
        }

        $riskCodeIds = $header->risk_code;

        // NORMALISASI DATA
        // JSON string → array
        if (is_string($riskCodeIds)) {
            $decoded = json_decode($riskCodeIds, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $riskCodeIds = $decoded;
            }
        }

        // "1,2,3" → array
        if (is_string($riskCodeIds) && str_contains($riskCodeIds, ',')) {
            $riskCodeIds = explode(',', $riskCodeIds);
        }

        // single number → array
        if (is_numeric($riskCodeIds)) {
            $riskCodeIds = [$riskCodeIds];
        }

        if (!is_array($riskCodeIds) || empty($riskCodeIds)) {
            return '';
        }

        // QUERY DB
        return \Illuminate\Support\Facades\DB::table('mst_risk_code')
            ->whereIn('id', $riskCodeIds)
            ->orderBy('id')
            ->pluck('code')
            ->implode(', ');
    }
}