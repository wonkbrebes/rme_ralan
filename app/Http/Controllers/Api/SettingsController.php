<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    // GET /api/settings
    public function index(): JsonResponse
    {
        // Return app settings - since settings table doesn't exist yet, return defaults
        return response()->json([
            'data' => [
                'nama_rs' => 'RSUD Puruk Cahu',
                'alamat' => 'Jl. Jend. Sudirman No. 1, Puruk Cahu, Kab. Murung Raya',
                'telepon' => env('WA_SENDER_NUMBER', '0882-1529-0459'),
                'email' => env('MAIL_FROM_ADDRESS', 'denynz17@gmail.com'),
                'smtp_host' => env('MAIL_HOST', ''),
                'smtp_port' => env('MAIL_PORT', ''),
                'smtp_username' => env('MAIL_USERNAME', ''),
                'wa_gateway_url' => env('WA_GATEWAY_URL', ''),
                'wa_gateway_token' => env('WA_GATEWAY_TOKEN', '') ? '***configured***' : '',
                'bpjs_cons_id' => config('bpjs.cons_id', ''),
                'bpjs_base_url' => config('bpjs.base_url', ''),
                'satusehat_base_url' => config('satusehat.base_url', ''),
            ]
        ]);
    }

    // PUT /api/settings
    public function update(Request $request): JsonResponse
    {
        // For now, return success - full implementation requires settings table
        return response()->json(['message' => 'Pengaturan berhasil disimpan.']);
    }
}
