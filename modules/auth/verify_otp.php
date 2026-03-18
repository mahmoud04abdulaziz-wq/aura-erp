<?php
/**
 * AURA ERP — OTP Verification Page
 * Validates the one-time password after initial login.
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/db_connect.php';

// Must have a pending OTP session
if (!isset($_SESSION['otp_pending_user_id'])) {
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
    exit;
}

$userId = $_SESSION['otp_pending_user_id'];
$demoOtp = $_SESSION['otp_code_display'] ?? null; // For demo only
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOtp = trim($_POST['otp'] ?? '');

    if (strlen($enteredOtp) !== 6) {
        $error = 'Please enter the 6-digit verification code.';
    } else {
        // Fetch latest unused, non-expired OTP for this user
        $stmt = $pdo->prepare(
            "SELECT otp_id, otp_hash, expires_at 
             FROM otp_logs 
             WHERE user_id = ? AND is_used = 0 AND expires_at > NOW()
             ORDER BY otp_id DESC 
             LIMIT 1"
        );
        $stmt->execute([$userId]);
        $otpRecord = $stmt->fetch();

        if (!$otpRecord) {
            $error = 'OTP has expired. Please log in again.';
            unset($_SESSION['otp_pending_user_id'], $_SESSION['otp_code_display']);
        } elseif (!password_verify($enteredOtp, $otpRecord['otp_hash'])) {
            $error = 'Invalid verification code. Please try again.';
        } else {
            // Mark OTP as used
            $stmt = $pdo->prepare("UPDATE otp_logs SET is_used = 1 WHERE otp_id = ?");
            $stmt->execute([$otpRecord['otp_id']]);

            // Load full user data and set session
            $stmt = $pdo->prepare(
                "SELECT u.user_id, u.role_id, u.employee_id, r.role_name, e.first_name, e.last_name
                 FROM users u
                 JOIN roles r ON u.role_id = r.role_id
                 JOIN employees e ON u.employee_id = e.employee_id
                 WHERE u.user_id = ?"
            );
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if ($user) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['employee_id'] = $user['employee_id'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['employee_name'] = $user['first_name'] . ' ' . $user['last_name'];

                // Load permissions
                $permStmt = $pdo->prepare(
                    "SELECT p.module_access 
                     FROM role_permissions rp
                     JOIN permissions p ON rp.permission_id = p.permission_id
                     WHERE rp.role_id = ?"
                );
                $permStmt->execute([$user['role_id']]);
                $_SESSION['permissions'] = $permStmt->fetchAll(PDO::FETCH_COLUMN);

                // Log
                $pdo->prepare(
                    "INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'LOGIN_OTP', 'OTP verified, login successful', 'Success')"
                )->execute([$user['user_id']]);
            }

            // Clean up
            unset($_SESSION['otp_pending_user_id'], $_SESSION['otp_code_display']);

            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP |
        <?= APP_NAME ?>
    </title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .login-page {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 3rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.08);
            position: relative;
            z-index: 10;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .otp-icon {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .otp-icon i {
            font-size: 3rem;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .otp-title {
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .otp-title h2 {
            font-size: 1.5rem;
            color: #1a1a2e;
            margin: 0;
        }

        .otp-subtitle {
            text-align: center;
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }

        .otp-input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-family: 'Inter', monospace;
            font-size: 1.75rem;
            text-align: center;
            letter-spacing: 0.75em;
            color: #1f2937;
            background: #fafafa;
            box-sizing: border-box;
            transition: all 0.2s;
        }

        .otp-input:focus {
            outline: none;
            border-color: #6366f1;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .btn-verify {
            width: 100%;
            padding: 0.9rem;
            border: none;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1.25rem;
        }

        .btn-verify:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.35);
        }

        .demo-otp-box {
            background: rgba(99, 102, 241, 0.08);
            border: 1px dashed rgba(99, 102, 241, 0.3);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .demo-otp-box span {
            font-size: 0.75rem;
            color: #6b7280;
            display: block;
            margin-bottom: 0.25rem;
        }

        .demo-otp-box code {
            font-size: 1.5rem;
            font-weight: 700;
            color: #6366f1;
            letter-spacing: 0.3em;
        }

        .flash-error {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: #6b7280;
            font-size: 0.85rem;
            text-decoration: none;
        }

        .back-link:hover {
            color: #6366f1;
        }
    </style>
</head>

<body>
    <div class="app-container" style="min-height: 100vh; display: flex; align-items: center; justify-content: center;">
        <div class="bg-animation">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>

        <div class="login-page">
            <div class="login-card">
                <div class="otp-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <div class="otp-title">
                    <h2>Two-Factor Verification</h2>
                </div>
                <p class="otp-subtitle">Enter the 6-digit code to verify your identity</p>

                <?php if ($demoOtp): ?>
                    <div class="demo-otp-box">
                        <span>🔐 Demo Mode — Your OTP Code:</span>
                        <code><?= htmlspecialchars($demoOtp) ?></code>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="flash-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="text" name="otp" class="otp-input" maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                        placeholder="------" required autofocus autocomplete="one-time-code">
                    <button type="submit" class="btn-verify">
                        <i class="fa-solid fa-check-circle"></i> Verify & Sign In
                    </button>
                </form>

                <a href="<?= BASE_URL ?>/modules/auth/login.php" class="back-link">
                    <i class="fa-solid fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</body>

</html>