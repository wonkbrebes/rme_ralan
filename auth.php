<?php
/**
 * Middleware Autentikasi, Role-Based Access Control (RBAC), & PAM
 * Sistem Informasi Manajemen Rumah Sakit (SIMRS) - RME
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Memeriksa apakah pengguna sudah login.
 * Jika belum dan halaman bukan login.php/logout.php, alihkan ke login.php.
 */
function check_auth() {
    $script_name = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $page = $_GET['page'] ?? '';

    // Halaman yang diizinkan tanpa login
    $public_pages = ['login.php', 'logout.php'];
    if (in_array($script_name, $public_pages, true) || $page === 'login' || $page === 'logout') {
        return;
    }

    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Sesi berakhir, silakan login kembali.', 'redirect' => 'login.php']);
            exit;
        }
        header("Location: login.php");
        exit;
    }
}

// Jalankan pengecekan auth
check_auth();

/**
 * Mendapatkan ID pengguna aktif
 */
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Mendapatkan Nama pengguna aktif
 */
function get_current_user_name() {
    return $_SESSION['name'] ?? 'Pengguna RS';
}

/**
 * Mendapatkan Role pengguna aktif
 */
function get_current_user_role() {
    return $_SESSION['role'] ?? 'admisi';
}

/**
 * Memeriksa apakah role pengguna aktif termasuk dalam daftar yang diizinkan.
 * Superuser selalu diizinkan untuk melihat/mengakses semua modul.
 */
function has_role($allowed_roles = []) {
    if (is_string($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    $current_role = strtolower(get_current_user_role());
    if ($current_role === 'superuser' || $current_role === 'supervisor') {
        return true;
    }
    return in_array($current_role, array_map('strtolower', $allowed_roles), true);
}

/**
 * Membatasi akses halaman hanya untuk role tertentu.
 * Jika tidak diizinkan, alihkan atau tampilkan pesan error RBAC.
 */
function require_role($allowed_roles = []) {
    if (!has_role($allowed_roles)) {
        $current_role = strtoupper(get_current_user_role());
        $allowed_str = strtoupper(implode(', ', (array)$allowed_roles));
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => "Akses Ditolak: Role Anda ($current_role) tidak memiliki izin akses ke modul ini (Diperlukan: $allowed_str)."]);
            exit;
        }
        ?>
        <div style="max-width: 600px; margin: 80px auto; padding: 30px; background: #FFF5F5; border: 2px solid #FEB2B2; border-radius: 16px; text-align: center; font-family: 'Inter', sans-serif; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
            <div style="font-size: 48px; margin-bottom: 15px;">🔒</div>
            <h2 style="color: #9B2C2C; margin-top: 0; font-size: 22px;">Akses Ditolak (RBAC Protection)</h2>
            <p style="color: #742A2A; font-size: 15px; line-height: 1.6;">
                Role akun Anda saat ini adalah <strong><?= htmlspecialchars($current_role) ?></strong>.<br>
                Halaman ini hanya dapat diakses oleh role: <strong><?= htmlspecialchars($allowed_str) ?></strong> atau <strong>SUPERUSER</strong>.
            </p>
            <div style="margin-top: 25px;">
                <a href="index.php?page=dashboard" style="display: inline-block; padding: 12px 24px; background: #3182CE; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">Kembali ke Dashboard</a>
            </div>
        </div>
        <?php
        exit;
    }
}

/**
 * Pengecekan keamanan PAM (Privileged Access Management)
 * Khusus untuk Superuser saat melakukan perubahan data kritis atau penghapusan data.
 * Membutuhkan otorisasi dari Supervisor (PIN atau persetujuan dalam tabel pam_approvals).
 */
function check_pam_authorization($action_type, $reason = "Perubahan data kritis oleh Superuser") {
    $role = strtolower(get_current_user_role());
    // Supervisor langsung berhak tanpa PAM, role biasa harus melewati alur standar, superuser butuh pengawasan
    if ($role !== 'superuser') {
        return true;
    }

    // Cek apakah ada PIN Supervisor yang dikirim melalui POST atau Header
    $pin = $_POST['pam_supervisor_pin'] ?? $_SERVER['HTTP_X_PAM_PIN'] ?? '';
    if (!empty($pin)) {
        // PIN default Supervisor adalah '123456' atau 'password123'
        if ($pin === '123456' || $pin === 'password123') {
            // Catat log PAM approval otomatis
            try {
                if (function_exists('db_insert')) {
                    db_insert('pam_approvals', [
                        'requested_by' => get_current_user_id(),
                        'action_type' => $action_type,
                        'status' => 'APPROVED',
                        'approved_by' => get_current_user_id(),
                        'reason' => "[PIN AUTHORIZED] " . $reason
                    ]);
                }
            } catch (Exception $e) {
                // Abaikan jika error log
            }
            return true;
        } else {
            throw new Exception("PIN Supervisor tidak valid! Otorisasi PAM ditolak.");
        }
    }

    // Jika tidak ada PIN, cek apakah sudah ada tiket persetujuan di tabel pam_approvals
    try {
        if (function_exists('db_select_one')) {
            $row = db_select_one("SELECT id FROM pam_approvals WHERE requested_by = :uid AND action_type = :act AND status = 'APPROVED' AND created_at > (CURRENT_TIMESTAMP - INTERVAL '15 minutes') ORDER BY id DESC LIMIT 1", [
                'uid' => get_current_user_id(),
                'act' => $action_type
            ]);
            if ($row) {
                return true;
            }
        }
    } catch (Exception $e) {
        // Abaikan
    }

    // Jika belum ada otorisasi
    throw new Exception("🔒 PAM REQUIRED: Tindakan '$action_type' oleh Superuser wajib mendapatkan otorisasi PIN dari Supervisor untuk keamanan sistem.");
}
