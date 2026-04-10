<?php
/**
 * AURA ERP — Login Page
 * Handles email/password authentication with optional MFA/OTP flow.
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/db_connect.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/app.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT u.user_id, u.password_hash, u.account_status, u.mfa_enabled, u.role_id,
                    r.role_name, e.first_name, e.last_name, e.employee_id
             FROM users u
             JOIN roles r ON u.role_id = r.role_id
             JOIN employees e ON u.employee_id = e.employee_id
             WHERE u.email = ?"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Invalid email or password.';
        } elseif ($user['account_status'] !== 'Active') {
            $error = 'Your account has been suspended. Contact the administrator.';
        } elseif (!password_verify($password, $user['password_hash'])) {
            $error = 'Invalid email or password.';
        } else {
            // Authentication passed
            if ($user['mfa_enabled']) {
                // Generate OTP
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $otpHash = password_hash($otp, PASSWORD_BCRYPT);

                $stmt = $pdo->prepare(
                    "INSERT INTO otp_logs (user_id, otp_hash, expires_at, is_used) 
                     VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE), 0)"
                );
                $stmt->execute([$user['user_id'], $otpHash]);

                // Store pending user in session for OTP verification
                $_SESSION['otp_pending_user_id'] = $user['user_id'];
                $_SESSION['otp_code_display'] = $otp; // For demo: show OTP on verify page

                header('Location: ' . BASE_URL . '/modules/auth/verify_otp.php');
                exit;
            } else {
                // No MFA — set session directly
                setUserSession($user, $pdo);
                header('Location: ' . BASE_URL . '/app.php');
                exit;
            }
        }
    }
}

/**
 * Set session variables for the authenticated user.
 */
function setUserSession(array $user, PDO $pdo): void
{
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['employee_id'] = $user['employee_id'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['employee_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_email'] = $user['email'] ?? '';

    // Load permissions
    $stmt = $pdo->prepare(
        "SELECT p.module_access 
         FROM role_permissions rp
         JOIN permissions p ON rp.permission_id = p.permission_id
         WHERE rp.role_id = ?"
    );
    $stmt->execute([$user['role_id']]);
    $_SESSION['permissions'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Log successful login
    $logStmt = $pdo->prepare(
        "INSERT INTO system_logs (user_id, action_type, description, status) VALUES (?, 'LOGIN', 'Successful login', 'Success')"
    );
    $logStmt->execute([$user['user_id']]);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login |
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
            overflow: hidden;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 3rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.1);
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

        .login-brand {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-brand .gem-icon {
            font-size: 2.5rem;
            background: linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.75rem;
        }

        .login-brand h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1a1a2e;
            margin: 0;
        }

        .login-brand p {
            color: #6b7280;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.9rem;
            transition: color 0.2s;
        }

        .input-wrapper input {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.75rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            color: #1f2937;
            background: #fafafa;
            transition: all 0.2s;
            box-sizing: border-box;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: #6366f1;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .input-wrapper input:focus+i,
        .input-wrapper input:focus~i {
            color: #6366f1;
        }

        .btn-login {
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
            margin-top: 0.5rem;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.35);
        }

        .btn-login:active {
            transform: translateY(0);
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

        .flash-success {
            background: rgba(34, 197, 94, 0.08);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #16a34a;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
                <div class="login-brand">
                    <div class="gem-icon"><i class="fa-solid fa-gem"></i></div>
                    <h1>MiskStone</h1>
                    <p>مسك للحجر الصناعي والديكور</p>
                </div>

                <?php if ($error): ?>
                    <div class="flash-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="flash-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="on">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" placeholder="name@company.com"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                            <i class="fa-regular fa-envelope"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" placeholder="Enter your password"
                                required>
                            <i class="fa-solid fa-lock"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fa-solid fa-right-to-bracket"></i> Sign In
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>