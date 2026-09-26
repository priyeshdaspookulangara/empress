<?php
$pageTitle = "Welcome & WhatsApp Greeting Center";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['superadmin_id'])) {
    header("Location: /superadmin_login.php");
    exit();
}

$pdo = getDBConnection();
$search = trim($_GET['search'] ?? '');

$sql = "SELECT m.*, w.balance, w.user_wallet_50, w.burfee_cart_wallet, w.charity_wallet
        FROM members m
        LEFT JOIN wallets w ON m.member_id = w.member_id";
$params = [];

if (!empty($search)) {
    $sql .= " WHERE m.member_id LIKE ? OR m.name LIKE ? OR m.email LIKE ? OR m.phone LIKE ?";
    $term = "%$search%";
    $params = [$term, $term, $term, $term];
}

$sql .= " ORDER BY m.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-extrabold neon-gradient-text">Welcome & WhatsApp Greeting Portal</h1>
            <p class="text-xs text-ice/70 mt-1">Send formatted account welcome details & motivational greetings to members directly via WhatsApp</p>
        </div>

        <!-- Search Bar -->
        <form action="/superadmin/welcome.php" method="GET" class="flex gap-2 w-full md:w-auto">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search ID, Name, Phone..." class="bg-obsidian/80 border border-neon-cyan/30 rounded-xl px-4 py-2 text-xs text-ice focus:outline-none focus:border-neon-cyan w-64">
            <button type="submit" class="bg-neon-cyan/20 hover:bg-neon-cyan/30 text-neon-cyan border border-neon-cyan/40 px-4 py-2 rounded-xl text-xs font-bold">Search</button>
            <?php if (!empty($search)): ?>
                <a href="/superadmin/welcome.php" class="glass-card border border-neon-cyan/30 hover:bg-neon-cyan/10 text-neon-cyan px-3 py-2 rounded-xl text-xs flex items-center">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Members Table with Greet Button -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-neon-cyan/10 border-b border-neon-cyan/20 text-neon-cyan font-semibold uppercase">
                        <th class="p-3">Member ID</th>
                        <th class="p-3">Member Details</th>
                        <th class="p-3">Sponsor / Parent</th>
                        <th class="p-3">Package</th>
                        <th class="p-3">Joined Date</th>
                        <th class="p-3 text-right">Greet Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neon-cyan/10 text-ice">
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="6" class="p-4 text-center text-ice/50">No registered members found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($members as $m): ?>
                            <?php
                            $cleanPhone = preg_replace('/[^0-9]/', '', $m['phone']);
                            if (strlen($cleanPhone) === 10) {
                                $cleanPhone = '91' . $cleanPhone;
                            }

                            $pkgName = str_replace('_', ' ', $m['package_type']);
                            $loginUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/login.php";

                            $waText = "🌟 *WELCOME TO EMPRESS TWO WAY 3.0!* 🌟\n\n" .
                                      "Dear *{$m['name']}*,\n" .
                                      "Congratulations on taking a powerful step toward doubling your path and empowering your future! 🚀✨\n\n" .
                                      "📋 *YOUR ACCOUNT DETAILS:*\n" .
                                      "▫️ *Member ID:* {$m['member_id']}\n" .
                                      "▫️ *Full Name:* {$m['name']}\n" .
                                      "▫️ *Mobile:* {$m['phone']}\n" .
                                      "▫️ *Email:* {$m['email']}\n" .
                                      "▫️ *Activation Package:* {$pkgName}\n" .
                                      "▫️ *Sponsor ID:* " . ($m['sponsor_id'] ?: 'EMP100000') . "\n" .
                                      "▫️ *Placement Parent:* " . ($m['placement_parent_id'] ?: 'EMP100000') . " (Position " . ($m['matrix_position'] ?: '1') . ")\n\n" .
                                      "🔑 *Portal Login URL:*\n{$loginUrl}\n\n" .
                                      "💡 *MOTIVATIONAL THOUGHT OF THE DAY:*\n" .
                                      "_\"Success is not final, failure is not fatal: it is the courage to continue that counts. Your journey to financial freedom starts today!\"_ 💎🔥\n\n" .
                                      "Empress Two Way 3.0 Management Team\n" .
                                      "Tagline: _Double Your Path, Empower Your Future._";

                            $waUrl = "https://wa.me/" . $cleanPhone . "?text=" . rawurlencode($waText);
                            ?>
                            <tr class="hover:bg-neon-cyan/5 transition">
                                <td class="p-3 font-mono text-neon-cyan font-bold text-sm">
                                    <?php echo htmlspecialchars($m['member_id']); ?>
                                </td>
                                <td class="p-3">
                                    <span class="font-bold text-ice block text-sm"><?php echo htmlspecialchars($m['name']); ?></span>
                                    <span class="text-ice/60 block text-[11px]"><?php echo htmlspecialchars($m['email']); ?></span>
                                    <span class="font-mono text-neon-cyan/80 text-[11px]"><i class="fa-solid fa-phone text-[9px] mr-1"></i><?php echo htmlspecialchars($m['phone']); ?></span>
                                </td>
                                <td class="p-3 font-mono text-ice/80">
                                    <div>Sp: <span class="text-neon-cyan font-semibold"><?php echo htmlspecialchars($m['sponsor_id'] ?: 'ROOT'); ?></span></div>
                                    <div>Par: <span class="text-emerald-400 font-semibold"><?php echo htmlspecialchars($m['placement_parent_id'] ?: 'ROOT'); ?></span> (Pos <?php echo $m['matrix_position'] ?: '1'; ?>)</div>
                                </td>
                                <td class="p-3 font-semibold text-neon-cyan"><?php echo $pkgName; ?></td>
                                <td class="p-3 font-mono text-ice/50"><?php echo $m['created_at']; ?></td>
                                <td class="p-3 text-right">
                                    <a href="<?php echo $waUrl; ?>" target="_blank" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold px-4 py-2 rounded-xl text-xs transition shadow-lg shadow-emerald-900/30">
                                        <i class="fa-brands fa-whatsapp text-sm"></i> Greet
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
