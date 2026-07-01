<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Http\{Request, JsonResponse};

class NotifikasiController extends Controller
{
    // GET /api/notifikasi
    public function index(Request $request): JsonResponse
    {
        // For now, get all notifications (will add auth filter later)
        $notifikasi = Notifikasi::orderByDesc('created_at')
            ->limit(50)
            ->get();
        return response()->json(['data' => $notifikasi]);
    }

    // GET /api/notifikasi/unread-count
    public function unreadCount(): JsonResponse
    {
        $count = Notifikasi::where('status', 'Pending')->count();
        return response()->json(['unread_count' => $count]);
    }

    // POST /api/notifikasi/{id}/read
    public function markAsRead(int $id): JsonResponse
    {
        $notif = Notifikasi::findOrFail($id);
        $notif->update(['status' => 'Terkirim']);
        return response()->json(['message' => 'Notifikasi ditandai sudah dibaca.']);
    }

    // POST /api/notifikasi — Buat log notifikasi WA & Email baru
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tipe' => 'required|string',
            'judul' => 'required|string',
            'pesan' => 'required|string',
            'nomor_tujuan' => 'nullable|string',
            'referensi' => 'nullable|string',
        ]);

        $validTipes = ['WhatsApp', 'SMS', 'Email', 'Push', 'In-App'];
        $tipe = in_array($data['tipe'], $validTipes) ? $data['tipe'] : 'WhatsApp';

        $notif = Notifikasi::create([
            'user_id' => auth()->id() ?? 1,
            'tipe' => $tipe,
            'judul' => $data['judul'],
            'pesan' => $data['pesan'],
            'nomor_tujuan' => $data['nomor_tujuan'] ?? env('WA_SENDER_NUMBER', '0882-1529-0459'),
            'referensi' => $data['referensi'] ?? '-',
            'status' => 'Pending',
            'sent_at' => now(),
        ]);

        return response()->json(['message' => 'Notifikasi WA & Email berhasil dibuat.', 'data' => $notif], 201);
    }
}
