<?php
session_start();
if (isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Sistem AHP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .header .subtitle {
            color: #7f8c8d;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .description {
            background: #f8f9fa;
            border-left: 4px solid #4facfe;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 5px;
        }

        .description p {
            color: #34495e;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #2c3e50;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fff;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4facfe;
            box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
        }

        .form-group input:hover {
            border-color: #bdc3c7;
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fee;
            color: #c53030;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #fed7d7;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .error-message::before {
            content: '⚠️';
            margin-right: 10px;
            font-size: 16px;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .footer p {
            color: #7f8c8d;
            font-size: 12px;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 10px;
            }

            .header h1 {
                font-size: 24px;
            }
        }

        /* Loading animation */
        .loading {
            display: none;
            text-align: center;
            margin-top: 10px;
        }

        .loading-spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #4facfe;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="header">
            <h1>Admin Login</h1>
            <p class="subtitle">Sistem Analytic Hierarchy Process</p>
        </div>

        <div class="description">
            <p>Selamat datang di sistem perhitungan AHP (Analytic Hierarchy Process). Silakan masukkan kredensial admin untuk mengakses dashboard pengelolaan kriteria dan alternatif keputusan.</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                Username atau password salah! Silakan coba lagi.
            </div>
        <?php endif; ?>

        <form method="post" action="cek_login.php" id="loginForm">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required placeholder="Masukkan username admin">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required placeholder="Masukkan password">
            </div>

            <button type="submit" class="login-btn">
                <span class="btn-text">Masuk ke Dashboard</span>
            </button>

            <div class="loading" id="loading">
                <div class="loading-spinner"></div>
                <span>Memproses login...</span>
            </div>
        </form>

        <div class="footer">
            <p>&copy; 2025 Sistem AHP. Semua hak dilindungi.</p>
        </div>
    </div>

    <script>
        // Optional: Add loading animation on form submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            document.querySelector('.login-btn').style.display = 'none';
            document.getElementById('loading').style.display = 'block';
        });
    </script>
</body>
</html>