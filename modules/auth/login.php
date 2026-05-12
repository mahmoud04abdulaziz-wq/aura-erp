<?php
/**
 * AURA ERP — Customer Login & Registration
 * Front-facing authentication for Storefront users.
 */
require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/config/db_connect.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role_name'] === 'Customer') {
        header('Location: ' . BASE_URL . '/shop.php');
    } else {
        header('Location: ' . BASE_URL . '/app.php');
    }
    exit;
}

$error = '';
$success = '';
$activeTab = $_POST['action'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            $stmt = $pdo->prepare(
                "SELECT u.user_id, u.password_hash, u.account_status, u.mfa_enabled, u.role_id,
                        r.role_name, c.company_name, c.customer_id
                 FROM users u
                 JOIN roles r ON u.role_id = r.role_id
                 JOIN customers c ON u.customer_id = c.customer_id
                 WHERE u.email = ? AND u.role_id = 9"
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $error = 'Invalid email or password.';
            } elseif ($user['account_status'] !== 'Active') {
                $error = 'Your account has been suspended.';
            } else {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['customer_id'] = $user['customer_id'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['customer_name'] = $user['company_name'];
                $_SESSION['user_email'] = $email;
                header('Location: ' . BASE_URL . '/shop.php');
                exit;
            }
        }
    } elseif ($action === 'signup') {
        $company = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass1 = $_POST['password'] ?? '';
        $pass2 = $_POST['confirm_password'] ?? '';
        $terms = isset($_POST['terms']);

        if (empty($company) || empty($email) || empty($pass1)) {
            $error = 'All fields are required.';
        } elseif (strlen($company) < 3) {
            $error = 'Username must be at least 3 characters.';
        } elseif (strlen($pass1) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($pass1 !== $pass2) {
            $error = 'Passwords do not match.';
        } elseif (!$terms) {
            $error = 'You must agree to the Terms of Service.';
        } else {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'An account with this email already exists.';
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    // Insert into customers
                    $stmt = $pdo->prepare("INSERT INTO customers (company_name, email, lead_status) VALUES (?, ?, 'New')");
                    $stmt->execute([$company, $email]);
                    $customerId = $pdo->lastInsertId();
                    
                    // Insert into users
                    $hash = password_hash($pass1, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (customer_id, role_id, email, password_hash, account_status, mfa_enabled) VALUES (?, 9, ?, ?, 'Active', 0)");
                    $stmt->execute([$customerId, $email, $hash]);
                    
                    $pdo->commit();
                    $success = 'Account created successfully! You can now log in.';
                    $activeTab = 'login';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = 'Database error: Could not create account.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login | <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-page {
            width: 100%; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 50%, #c7d2fe 100%);
            padding: 2rem;
            box-sizing: border-box;
            position: relative;
        }
        .login-page::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background-image: radial-gradient(circle at 15% 50%, rgba(99, 102, 241, 0.1), transparent 25%),
                              radial-gradient(circle at 85% 30%, rgba(139, 92, 246, 0.1), transparent 25%);
            pointer-events: none;
        }
        .btn-back {
            position: absolute; top: 2rem; left: 2rem;
            display: inline-flex; align-items: center; gap: 0.5rem;
            color: #4f46e5; font-weight: 600; text-decoration: none;
            padding: 0.5rem 1rem; border-radius: 8px; background: rgba(255,255,255,0.7);
            backdrop-filter: blur(4px); transition: all 0.2s; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .btn-back:hover { background: #fff; transform: translateY(-1px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        
        .login-card {
            background: #fff;
            border-radius: 16px;
            padding: 2.5rem 3rem;
            width: 100%; max-width: 450px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            position: relative;
            z-index: 1;
            border: 1px solid rgba(255,255,255,0.5);
        }
        .brand { text-align: center; margin-bottom: 2rem; font-size: 1.75rem; font-weight: 700; color: #1e1b4b; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
        .brand i { color: #6366f1; }
        
        .tabs { display: flex; border-bottom: 2px solid #e2e8f0; margin-bottom: 1.5rem; }
        .tab { flex: 1; text-align: center; padding: 0.75rem; cursor: pointer; color: #64748b; font-weight: 600; transition: 0.2s; border-bottom: 2px solid transparent; margin-bottom: -2px; }
        .tab.active { color: #4f46e5; border-bottom-color: #4f46e5; }
        .tab:hover:not(.active) { color: #1e1b4b; }
        
        .form-section { display: none; }
        .form-section.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 0.4rem; }
        .form-group input, .form-group select {
            width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e2e8f0; border-radius: 8px; font-family: 'Inter', sans-serif;
            box-sizing: border-box; transition: 0.2s; background: #fafafa;
        }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #6366f1; background: #fff; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
        .form-group .hint { font-size: 0.75rem; color: #64748b; margin-top: 0.25rem; }
        
        .checkbox-group { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem; }
        .checkbox-group input { width: auto; }
        .checkbox-group label { margin: 0; font-size: 0.85rem; color: #475569; }
        
        .btn-submit { width: 100%; padding: 0.9rem; border: none; border-radius: 8px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; font-weight: 600; font-size: 1rem; cursor: pointer; transition: 0.2s; }
        .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(99,102,241,0.3); }
        
        .links { text-align: center; margin-top: 1.5rem; font-size: 0.85rem; color: #64748b; padding-top: 1.5rem; border-top: 1px solid #e2e8f0; }
        .links a { color: #4f46e5; text-decoration: none; font-weight: 600; }
        .links a:hover { text-decoration: underline; }
        
        .alert { padding: 0.75rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.85rem; font-weight: 500; }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>
    <div class="login-page">
        <a href="<?= BASE_URL ?>/" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Back to Store
        </a>
        <div class="login-card">
            <div class="brand">
                <i class="fa-solid fa-gem"></i> MiskStone
            </div>
            
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

            <div class="tabs">
                <div class="tab <?= $activeTab === 'login' ? 'active' : '' ?>" onclick="switchTab('login')">Login</div>
                <div class="tab <?= $activeTab === 'signup' ? 'active' : '' ?>" onclick="switchTab('signup')">Sign Up</div>
            </div>

            <!-- LOGIN FORM -->
            <div id="login-form" class="form-section <?= $activeTab === 'login' ? 'active' : '' ?>">
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Login As</label>
                        <select disabled>
                            <option>Customer</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit">Login</button>
                    <div style="text-align: center; margin-top: 1rem; font-size: 0.85rem;">
                        <a href="#" style="color: #2563eb; text-decoration: none;">Forgot Password?</a>
                    </div>
                </form>
                <div class="links">
                    Don't have an account? <a href="#" onclick="switchTab('signup')">Sign Up</a><br><br>
                    <i class="fa-solid fa-shield-halved" style="color:#64748b; margin-right:4px;"></i> <a href="employee_login.php" style="color: #64748b;">Employee Login</a>
                </div>
            </div>

            <!-- SIGN UP FORM -->
            <div id="signup-form" class="form-section <?= $activeTab === 'signup' ? 'active' : '' ?>">
                <form method="POST">
                    <input type="hidden" name="action" value="signup">
                    <div class="form-group">
                        <label>Username / Company Name</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                        <div class="hint">At least 3 characters</div>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                        <div class="hint">At least 8 characters</div>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <div class="form-group">
                        <label>Register As</label>
                        <select disabled>
                            <option>Customer</option>
                        </select>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" name="terms" id="terms" required>
                        <label for="terms">I agree to the Terms of Service and Privacy Policy</label>
                    </div>
                    <button type="submit" class="btn-submit">Create Account</button>
                </form>
                <div class="links">
                    Already have an account? <a href="#" onclick="switchTab('login')">Login</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.form-section').forEach(f => f.classList.remove('active'));
            
            if(tab === 'login') {
                document.querySelectorAll('.tab')[0].classList.add('active');
                document.getElementById('login-form').classList.add('active');
            } else {
                document.querySelectorAll('.tab')[1].classList.add('active');
                document.getElementById('signup-form').classList.add('active');
            }
        }
    </script>
</body>
</html>
