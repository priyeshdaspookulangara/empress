<?php
$pageTitle = "Admin Login - Empress Two Way 3.0";
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: /admin/index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
            header("Location: /admin/index.php");
            exit();
        } else {
            $error = "Invalid admin username or password.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto py-12">
    <div class="glass-card p-8 rounded-3xl border border-gold/40 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-gold/40 via-gold to-gold/40"></div>
        <div class="text-center mb-8">
            <span class="text-xs uppercase font-mono tracking-widest text-gold bg-gold/10 px-3 py-1 rounded-full border border-gold/30">System Administration</span>
            <h1 class="text-3xl font-extrabold gold-gradient-text mt-3">Admin Console</h1>
            <p class="text-xs text-champagne/70 mt-1">Authorized Executive Access Only</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-500/10 border border-red-500/40 text-red-300 p-4 rounded-xl text-xs mb-6 flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-base text-red-400"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="/admin_login.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gold mb-1">Admin Username</label>
                <input type="text" name="username" required placeholder="admin" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-3 text-sm text-champagne focus:outline-none focus:border-gold">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gold mb-1">Password</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-3 text-sm text-champagne focus:outline-none focus:border-gold">
            </div>

            <div class="pt-2">
                <button type="submit" class="gold-button w-full py-3.5 rounded-xl text-base font-bold shadow-lg shadow-gold/20 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-user-shield"></i> Access Executive Panel
                </button>
            </div>
        </form>

        <div class="mt-6 pt-6 border-t border-gold/20 text-center text-xs text-champagne/60">
            <a href="/login.php" class="hover:text-gold"><i class="fa-solid fa-arrow-left"></i> Return to Member Login</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
