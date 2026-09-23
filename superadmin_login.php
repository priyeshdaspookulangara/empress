<?php
$pageTitle = "Super Admin Login - Empress Two Way 3.0";
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['superadmin_id'])) {
    header("Location: /superadmin/index.php");
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
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND role = 'superadmin'");
        $stmt->execute([$username]);
        $superadmin = $stmt->fetch();

        if ($superadmin && password_verify($password, $superadmin['password'])) {
            $_SESSION['superadmin_id'] = $superadmin['id'];
            $_SESSION['superadmin_username'] = $superadmin['username'];
            header("Location: /superadmin/index.php");
            exit();
        } else {
            $error = "Invalid Super Admin username or password.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto py-12">
    <div class="glass-card p-8 rounded-3xl border border-neon-cyan/40 shadow-2xl relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-neon-cyan/40 via-neon-cyan to-neon-cyan/40"></div>
        <div class="text-center mb-8">
            <span class="text-xs uppercase font-mono tracking-widest text-neon-cyan bg-neon-cyan/10 px-3 py-1 rounded-full border border-neon-cyan/30">Super Admin Console</span>
            <h1 class="text-3xl font-extrabold text-ice mt-3">Super Admin Portal</h1>
            <p class="text-xs text-champagne/70 mt-1">Master System Governance & Financial Oversight</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-500/10 border border-red-500/40 text-red-300 p-4 rounded-xl text-xs mb-6 flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-base text-red-400"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="/superadmin_login.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-neon-cyan mb-1">Super Admin Username</label>
                <input type="text" name="username" required placeholder="superadmin" class="w-full bg-obsidian/80 border border-neon-cyan/30 rounded-xl px-4 py-3 text-sm text-ice focus:outline-none focus:border-neon-cyan">
            </div>

            <div>
                <label class="block text-xs font-semibold text-neon-cyan mb-1">Password</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full bg-obsidian/80 border border-neon-cyan/30 rounded-xl px-4 py-3 text-sm text-ice focus:outline-none focus:border-neon-cyan">
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full py-3.5 rounded-xl text-base font-bold bg-gradient-to-r from-neon-cyan to-blue-600 text-obsidian shadow-lg shadow-neon-cyan/20 hover:brightness-110 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-crown"></i> Access Super Admin Portal
                </button>
            </div>
        </form>

        <div class="mt-6 pt-6 border-t border-neon-cyan/20 text-center text-xs text-champagne/60 flex justify-between">
            <a href="/login.php" class="hover:text-neon-cyan"><i class="fa-solid fa-arrow-left"></i> Member Login</a>
            <a href="/admin_login.php" class="hover:text-neon-cyan">Standard Admin <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
