<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login SIMRS | RSUD Puruk Cahu</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: #0f172a;
            background-image:
                radial-gradient(ellipse at 20% 50%, rgba(14, 116, 144, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(245, 158, 11, 0.08) 0%, transparent 50%);
        }
        .login-container {
            width: 100%; max-width: 420px; padding: 20px;
        }
        .login-card {
            background: #1e293b; border: 1px solid #334155;
            border-radius: 20px; padding: 44px 36px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        }
        .login-header { text-align: center; margin-bottom: 36px; }
        .login-icon {
            width: 64px; height: 64px; margin: 0 auto 18px;
            background: linear-gradient(135deg, #0c4a6e, #0e7490);
            border-radius: 18px; display: flex; align-items: center; justify-content: center;
            font-size: 30px; box-shadow: 0 8px 25px rgba(14,116,144,0.35);
        }
        .login-header h2 { font-size: 22px; font-weight: 800; color: #f1f5f9; margin-bottom: 6px; }
        .login-header p { font-size: 13px; color: #94a3b8; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #94a3b8; margin-bottom: 8px; }
        .form-group input {
            width: 100%; padding: 13px 16px;
            background: #0f172a; border: 1px solid #334155; border-radius: 12px;
            color: #f1f5f9; font-size: 14px; font-family: inherit;
            outline: none; transition: all 0.3s;
        }
        .form-group input:focus { border-color: #22d3ee; box-shadow: 0 0 0 3px rgba(34,211,238,0.12); }
        .form-group input::placeholder { color: #475569; }
        .remember-row {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px; font-size: 13px;
        }
        .remember-row label { color: #94a3b8; display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .remember-row a { color: #22d3ee; text-decoration: none; }
        .remember-row a:hover { text-decoration: underline; }
        .btn-login {
            width: 100%; padding: 14px; border: none; border-radius: 12px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #1a1a2e; font-size: 15px; font-weight: 700; font-family: inherit;
            cursor: pointer; transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(245,158,11,0.3);
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(245,158,11,0.5); }
        .login-footer {
            text-align: center; margin-top: 28px;
            font-size: 13px; color: #475569;
        }
        .login-footer a { color: #22d3ee; text-decoration: none; }
        .login-footer a:hover { text-decoration: underline; }
        .alert {
            padding: 12px 16px; border-radius: 10px; font-size: 13px;
            margin-bottom: 20px; display: none;
        }
        .alert.error { display: block; background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }
        .alert.success { display: block; background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div class="login-icon">🏥</div>
            <h2>Login SIMRS</h2>
            <p>RSUD Puruk Cahu — Kabupaten Murung Raya</p>
        </div>

        <div class="alert" id="loginAlert"></div>

        <form id="loginForm" onsubmit="return handleLogin(event)">
            <div class="form-group">
                <label for="email">Email / NIP</label>
                <input type="text" id="email" placeholder="nama@rsud-purukcahu.go.id" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Kata Sandi</label>
                <input type="password" id="password" placeholder="••••••••" required>
            </div>
            <div class="remember-row">
                <label><input type="checkbox"> Ingat saya</label>
                <a href="#">Lupa kata sandi?</a>
            </div>
            <button type="submit" class="btn-login">🔐 Masuk ke SIMRS</button>
        </form>

        <div class="login-footer">
            <a href="/">← Kembali ke Halaman Utama</a>
        </div>
    </div>
</div>

<script>
function handleLogin(e) {
    e.preventDefault();
    const alert = document.getElementById('loginAlert');
    // For now, redirect directly to dashboard (auth will be implemented later)
    alert.className = 'alert success';
    alert.innerHTML = '✅ Login berhasil! Mengalihkan ke dashboard...';
    setTimeout(() => { window.location.href = '/app'; }, 1000);
    return false;
}
</script>
</body>
</html>
