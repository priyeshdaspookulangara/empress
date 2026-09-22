<?php
$pageTitle = "Member Management";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin_login.php");
    exit();
}

$pdo = getDBConnection();
$search = trim($_GET['search'] ?? '');

$sql = "SELECT m.*, w.balance, w.user_wallet_60, w.company_wallet_40
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
    <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-extrabold gold-gradient-text">Member Management Directory</h1>
            <p class="text-xs text-champagne/70 mt-1">Search, inspect downline trees, send WhatsApp welcomes, or safely re-parent members</p>
        </div>

        <!-- Search Bar -->
        <form action="/admin/members.php" method="GET" class="flex gap-2 w-full md:w-auto">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search ID, Name, Phone..." class="bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-2 text-xs text-champagne focus:outline-none focus:border-gold w-64">
            <button type="submit" class="gold-button px-4 py-2 rounded-xl text-xs font-bold">Search</button>
            <?php if (!empty($search)): ?>
                <a href="/admin/members.php" class="glass-card border border-gold/30 hover:bg-gold/10 text-gold px-3 py-2 rounded-xl text-xs flex items-center">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-300 p-4 rounded-xl text-xs">
            <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['err'])): ?>
        <div class="bg-red-500/10 border border-red-500/40 text-red-300 p-4 rounded-xl text-xs">
            <?php echo htmlspecialchars($_GET['err']); ?>
        </div>
    <?php endif; ?>

    <!-- Member Table -->
    <div class="glass-card p-6 rounded-3xl border border-gold/20">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gold/10 border-b border-gold/20 text-gold font-semibold uppercase">
                        <th class="p-3">Member ID</th>
                        <th class="p-3">Name & Contact</th>
                        <th class="p-3">Package</th>
                        <th class="p-3">Sponsor / Parent</th>
                        <th class="p-3">Wallet (60 / 40)</th>
                        <th class="p-3">KYC</th>
                        <th class="p-3">Joined Date</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10 text-champagne">
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="8" class="p-4 text-center text-champagne/50">No member accounts matching search criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($members as $m): ?>
                            <?php
                            $waMsg = rawurlencode("Hello {$m['name']}, Welcome to Empress Two Way 3.0! Your Member ID is {$m['member_id']}. Access your dashboard here: " . $_SERVER['HTTP_HOST'] . "/login.php");
                            $waUrl = "https://wa.me/91" . preg_replace('/[^0-9]/', '', $m['phone']) . "?text=" . $waMsg;
                            ?>
                            <tr>
                                <td class="p-3 font-mono text-gold font-bold">
                                    <a href="/admin/matrix_tree.php?member_id=<?php echo $m['member_id']; ?>" class="hover:underline" title="Inspect Tree">
                                        <?php echo htmlspecialchars($m['member_id']); ?> <i class="fa-solid fa-network-wired text-[10px] ml-1"></i>
                                    </a>
                                </td>
                                <td class="p-3">
                                    <span class="font-bold text-champagne block"><?php echo htmlspecialchars($m['name']); ?></span>
                                    <span class="text-champagne/60 block text-[11px]"><?php echo htmlspecialchars($m['email']); ?></span>
                                    <span class="font-mono text-gold/70 text-[11px]"><?php echo htmlspecialchars($m['phone']); ?></span>
                                </td>
                                <td class="p-3 font-semibold text-gold"><?php echo str_replace('_', ' ', $m['package_type']); ?></td>
                                <td class="p-3 font-mono text-champagne/70">
                                    <div>Sp: <?php echo htmlspecialchars($m['sponsor_id'] ?: 'ROOT'); ?></div>
                                    <div>Par: <?php echo htmlspecialchars($m['placement_parent_id'] ?: 'ROOT'); ?> (Pos <?php echo $m['matrix_position'] ?: '-'; ?>)</div>
                                </td>
                                <td class="p-3 font-mono">
                                    <div class="text-emerald-400 font-bold">U: $<?php echo number_format($m['user_wallet_60'] ?? 0, 2); ?></div>
                                    <div class="text-gold">C: $<?php echo number_format($m['company_wallet_40'] ?? 0, 2); ?></div>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border <?php
                                        echo match($m['kyc_status']) {
                                            'Approved' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40',
                                            'Submitted' => 'bg-blue-500/20 text-blue-400 border-blue-500/40',
                                            'Rejected' => 'bg-red-500/20 text-red-400 border-red-500/40',
                                            default => 'bg-amber-500/20 text-amber-400 border-amber-500/40'
                                        };
                                    ?>">
                                        <?php echo $m['kyc_status']; ?>
                                    </span>
                                </td>
                                <td class="p-3 font-mono text-champagne/50"><?php echo $m['created_at']; ?></td>
                                <td class="p-3 space-y-1">
                                    <a href="<?php echo $waUrl; ?>" target="_blank" class="text-emerald-400 hover:text-emerald-300 font-bold flex items-center gap-1">
                                        <i class="fa-brands fa-whatsapp text-sm"></i> WhatsApp
                                    </a>
                                    <?php if ($m['member_id'] !== 'EMP100000'): ?>
                                        <a href="/admin/delete_member.php?member_id=<?php echo $m['member_id']; ?>" onclick="return confirm('Are you sure you want to delete member <?php echo $m['member_id']; ?>? Direct matrix children will be safely re-parented to EMP100000.');" class="text-red-400 hover:text-red-300 font-bold flex items-center gap-1">
                                            <i class="fa-solid fa-trash-can text-xs"></i> Delete
                                        </a>
                                    <?php endif; ?>
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
