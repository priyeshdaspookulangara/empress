<?php
$pageTitle = "Member Registration - Empress Two Way 3.0";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();
$error = '';
$success = '';

// Pre-fill sponsor if passed in GET
$sponsorId = isset($_GET['sponsor']) ? trim($_GET['sponsor']) : 'EMP100000';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sponsorId = !empty($_POST['sponsor_id']) ? trim($_POST['sponsor_id']) : 'EMP100000';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $epinCode = trim($_POST['epin_code'] ?? '');

    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($epinCode)) {
        $error = "Please fill in all required fields including a valid ePIN code.";
    } else {
        $res = registerMember($pdo, [
            'sponsor_id' => $sponsorId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'epin_code' => $epinCode
        ]);

        if ($res['success']) {
            $success = $res['message'];
        } else {
            $error = $res['message'];
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-xl mx-auto py-8">
    <div class="glass-card p-8 rounded-3xl border border-gold/30 shadow-2xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold gold-gradient-text">Member Registration</h1>
            <p class="text-xs text-champagne/70 mt-2">Join Empress Two Way 3.0 via Global BFS Auto-Spillover Matrix</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-500/10 border border-red-500/40 text-red-300 p-4 rounded-xl text-xs mb-6 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-base"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-300 p-6 rounded-2xl text-center mb-6">
                <i class="fa-solid fa-circle-check text-4xl mb-3 text-emerald-400"></i>
                <h3 class="text-lg font-bold">Registration Completed!</h3>
                <p class="text-xs text-champagne/90 mt-2"><?php echo htmlspecialchars($success); ?></p>
                <div class="mt-6">
                    <a href="/login.php" class="gold-button px-6 py-2.5 rounded-xl text-sm inline-block">Proceed to Login</a>
                </div>
            </div>
        <?php else: ?>

            <form action="/register.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gold mb-1">Sponsor Member ID (Optional)</label>
                    <input type="text" name="sponsor_id" value="<?php echo htmlspecialchars($sponsorId); ?>" placeholder="EMP100000" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-3 text-sm text-champagne focus:outline-none focus:border-gold">
                    <span class="text-[10px] text-champagne/50">If left blank or invalid, default root EMP100000 sponsor is assigned.</span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gold mb-1">Full Name *</label>
                    <input type="text" name="name" required placeholder="John Doe" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-3 text-sm text-champagne focus:outline-none focus:border-gold">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gold mb-1">Email Address *</label>
                        <input type="email" name="email" required placeholder="member@example.com" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-3 text-sm text-champagne focus:outline-none focus:border-gold">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gold mb-1">Mobile Phone Number *</label>
                        <input type="text" name="phone" required placeholder="9876543210" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-3 text-sm text-champagne focus:outline-none focus:border-gold">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gold mb-1">Account Password *</label>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-3 text-sm text-champagne focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gold mb-1">ePIN Key Code *</label>
                    <input type="text" name="epin_code" required placeholder="Enter Unused Activation ePIN Code" class="w-full bg-obsidian/80 border border-gold/40 rounded-xl px-4 py-3 text-sm text-gold font-mono focus:outline-none focus:border-gold uppercase">
                    <span class="text-[10px] text-champagne/50">Required for package activation (Starter 5000 / Empress 15000). Contact admin/sponsor if you don't have one.</span>
                </div>

                <div class="pt-4">
                    <button type="submit" class="gold-button w-full py-3.5 rounded-xl text-base font-bold shadow-lg shadow-gold/20 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-user-plus"></i> Activate & Register Account
                    </button>
                </div>
            </form>

            <div class="text-center mt-6 pt-6 border-t border-gold/20 text-xs text-champagne/70">
                Already a registered member? <a href="/login.php" class="text-gold font-bold hover:underline">Log in here</a>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
