<?php
$pageTitle = "Customer P2P Wallet Settings & QR Codes";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin_login.php");
    exit();
}

$pdo = getDBConnection();
$search = trim($_GET['search'] ?? '');

$sql = "SELECT m.*, w.balance, w.user_wallet_50 FROM members m LEFT JOIN wallets w ON m.member_id = w.member_id WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (m.member_id LIKE ? OR m.name LIKE ? OR m.email LIKE ? OR m.bep20_address LIKE ?)";
    $term = "%$search%";
    $params = [$term, $term, $term, $term];
}

$sql .= " ORDER BY CASE WHEN m.qr_code_url IS NOT NULL AND m.qr_code_url != '' THEN 0 ELSE 1 END, m.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-extrabold gold-gradient-text">Customer P2P Crypto Wallet Directory & QR Codes</h1>
            <p class="text-xs text-champagne/70 mt-1">Inspect members' USDT (BEP-20) addresses and personal receiving QR code images</p>
        </div>

        <!-- Search Bar -->
        <form action="/admin/p2p_wallets.php" method="GET" class="flex gap-2 w-full md:w-auto">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search ID, Name, BEP-20 Address..." class="bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-2 text-xs text-champagne focus:outline-none focus:border-gold w-64">
            <button type="submit" class="gold-button px-4 py-2 rounded-xl text-xs font-bold">Search</button>
            <?php if (!empty($search)): ?>
                <a href="/admin/p2p_wallets.php" class="glass-card border border-gold/30 hover:bg-gold/10 text-gold px-3 py-2 rounded-xl text-xs flex items-center">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- P2P Wallet Table -->
    <div class="glass-card p-6 rounded-3xl border border-gold/20">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gold/10 border-b border-gold/20 text-gold font-semibold uppercase">
                        <th class="p-3">Member ID</th>
                        <th class="p-3">Name & Contact</th>
                        <th class="p-3">USDT (BEP-20) Address</th>
                        <th class="p-3 text-center">Receiving QR Code</th>
                        <th class="p-3">KYC Status</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10 text-champagne">
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="6" class="p-4 text-center text-champagne/50">No customer P2P wallet records found matching search.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td class="p-3 font-mono text-gold font-bold">
                                    <a href="/admin/matrix_tree.php?member_id=<?php echo $m['member_id']; ?>" class="hover:underline">
                                        <?php echo htmlspecialchars($m['member_id']); ?>
                                    </a>
                                </td>
                                <td class="p-3">
                                    <span class="font-bold text-champagne block"><?php echo htmlspecialchars($m['name']); ?></span>
                                    <span class="text-champagne/60 block text-[11px]"><?php echo htmlspecialchars($m['email']); ?></span>
                                    <span class="font-mono text-gold/70 text-[11px]"><?php echo htmlspecialchars($m['phone']); ?></span>
                                </td>
                                <td class="p-3 font-mono">
                                    <?php if (!empty($m['bep20_address'])): ?>
                                        <div class="bg-obsidian/80 border border-gold/20 p-2 rounded-lg text-emerald-400 font-bold break-all text-[11px] flex items-center justify-between gap-2">
                                            <span><?php echo htmlspecialchars($m['bep20_address']); ?></span>
                                            <button onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($m['bep20_address']); ?>'); alert('BEP-20 Address copied!');" class="hover:text-gold text-xs shrink-0" title="Copy Address">
                                                <i class="fa-regular fa-copy"></i>
                                            </button>
                                        </div>
                                    <?php elseif (!empty($m['crypto_wallet_address'])): ?>
                                        <div class="text-champagne/80 break-all text-[11px]">
                                            <?php echo htmlspecialchars($m['crypto_wallet_address']); ?>
                                            <span class="text-[10px] text-champagne/50 block">(<?php echo htmlspecialchars($m['wallet_network'] ?? 'TRC20'); ?>)</span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-amber-400/60 italic text-[11px]">Not Configured</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-center">
                                    <?php if (!empty($m['qr_code_url'])): ?>
                                        <div class="flex flex-col items-center gap-1">
                                            <a href="<?php echo htmlspecialchars($m['qr_code_url']); ?>" target="_blank" class="block group relative">
                                                <img src="<?php echo htmlspecialchars($m['qr_code_url']); ?>" alt="P2P QR Code" class="w-16 h-16 object-cover rounded-xl border border-gold/40 group-hover:scale-105 transition shadow-md">
                                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 rounded-xl flex items-center justify-center text-white text-[10px] font-bold transition">
                                                    <i class="fa-solid fa-magnifying-glass-plus text-sm"></i>
                                                </div>
                                            </a>
                                            <span class="text-[10px] text-emerald-400 font-semibold flex items-center gap-1">
                                                <i class="fa-solid fa-circle-check"></i> Uploaded
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-champagne/40 italic text-[11px]">No QR Code</span>
                                    <?php endif; ?>
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
                                <td class="p-3">
                                    <a href="/admin/edit_member.php?member_id=<?php echo $m['member_id']; ?>" class="bg-gold/10 hover:bg-gold/20 text-gold border border-gold/30 px-2.5 py-1.5 rounded-lg text-[11px] font-bold inline-flex items-center gap-1">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit Profile
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
