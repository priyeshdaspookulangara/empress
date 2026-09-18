<?php
$pageTitle = "Member Login - Empress Two Way 3.0";
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['member_id'])) {
    header("Location: /customer/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberId = strtoupper(trim($_POST['member_id'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($memberId) || empty($password)) {
        $error = "Please enter both Member ID and password.";
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch();

        if ($member && password_verify($password, $member['password'])) {
            if ($member['status'] !== 'Active') {
                $error = "Your account is currently inactive. Please contact support.";
            } else {
                $_SESSION['member_id'] = $member['member_id'];
                $_SESSION['member_name'] = $member['name'];
                header("Location: /customer/dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid Member ID or password.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto py-12">
    <div class="glass-card p-8 rounded-3xl border border-gold/30 shadow-2xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold gold-gradient-text">Member Portal</h1>
            <p class="text-xs text-champagne/70 mt-2">Log in to manage your 3-matrix wallet and network</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-500/10 border border-red-500/40 text-red-300 p-4 rounded-xl text-xs mb-6 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-base"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="/login.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gold mb-1">Member ID</label>
                <input type="text" name="member_id" required placeholder="e.g., EMP100001" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-3 text-sm text-champagne uppercase font-mono focus:outline-none focus:border-gold">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gold mb-1">Password</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-3 text-sm text-champagne focus:outline-none focus:border-gold">
            </div>

            <div class="pt-2">
                <button type="submit" class="gold-button w-full py-3.5 rounded-xl text-base font-bold shadow-lg shadow-gold/20 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign In to Account
                </button>
            </div>
        </form>

        <div class="mt-6 pt-6 border-t border-gold/20 text-center flex flex-col gap-2 text-xs text-champagne/70">
            <div>New member? <a href="/register.php" class="text-gold font-bold hover:underline">Register account</a></div>
            <div>Admin access? <a href="/admin_login.php" class="text-gold/60 hover:text-gold">Admin Portal</a></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
