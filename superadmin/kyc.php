<?php
$pageTitle = "KYC & Crypto Wallet Verification Console";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['superadmin_id'])) {
    header("Location: /superadmin_login.php");
    exit();
}

$pdo = getDBConnection();
$msg = '';
$err = '';

// Handle KYC Approval / Rejection Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberId = trim($_POST['member_id'] ?? '');
    $action = $_POST['action'] ?? '';

    if (!empty($memberId) && in_array($action, ['Approve', 'Reject'])) {
        $newStatus = ($action === 'Approve') ? 'Approved' : 'Rejected';
        $stmt = $pdo->prepare("UPDATE members SET kyc_status = ? WHERE member_id = ?");
        $stmt->execute([$newStatus, $memberId]);
        $msg = "KYC application for member {$memberId} has been marked as {$newStatus}.";
    }
}

// Fetch Pending and Submitted KYC Applications
$stmt = $pdo->query("SELECT * FROM members WHERE kyc_status IN ('Submitted', 'Pending', 'Rejected', 'Approved') ORDER BY FIELD(kyc_status, 'Submitted', 'Pending', 'Rejected', 'Approved'), id DESC");
$kycList = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/30 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-extrabold neon-gradient-text">KYC & Crypto Wallet Verification Console</h1>
            <p class="text-xs text-ice/70 mt-1">Review full address, identity, and USD Crypto Wallet Address details to approve/reject payout eligibility</p>
        </div>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-300 p-4 rounded-xl text-xs">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <!-- Applications Table -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-neon-cyan/10 border-b border-neon-cyan/20 text-neon-cyan font-semibold uppercase">
                        <th class="p-3">Member ID</th>
                        <th class="p-3">Full Address Details</th>
                        <th class="p-3">USD Crypto Wallet Address</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neon-cyan/10 text-ice">
                    <?php if (empty($kycList)): ?>
                        <tr>
                            <td colspan="5" class="p-4 text-center text-ice/50">No KYC submissions on record.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($kycList as $m): ?>
                            <tr>
                                <td class="p-3 font-mono text-neon-cyan font-bold">
                                    <div><?php echo htmlspecialchars($m['member_id']); ?></div>
                                    <div class="text-[11px] text-ice/80 font-sans font-normal"><?php echo htmlspecialchars($m['name']); ?></div>
                                </td>
                                <td class="p-3 max-w-xs">
                                    <?php if (!empty($m['address_line'])): ?>
                                        <div><?php echo htmlspecialchars($m['address_line']); ?>, <?php echo htmlspecialchars($m['place']); ?></div>
                                        <div class="text-ice/70"><?php echo htmlspecialchars($m['city']); ?>, <?php echo htmlspecialchars($m['state']); ?> - <?php echo htmlspecialchars($m['pincode']); ?></div>
                                    <?php else: ?>
                                        <span class="text-ice/40 italic">Not Provided</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 font-mono max-w-xs">
                                    <div>Network: <strong class="text-ice"><?php echo htmlspecialchars($m['wallet_network'] ?: 'USDT (TRC20)'); ?></strong></div>
                                    <div>Address: <strong class="text-neon-cyan break-all text-[11px]"><?php echo htmlspecialchars($m['crypto_wallet_address'] ?: 'N/A'); ?></strong></div>
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
                                    <form action="/superadmin/kyc.php" method="POST" class="flex gap-2">
                                        <input type="hidden" name="member_id" value="<?php echo $m['member_id']; ?>">
                                        <button type="submit" name="action" value="Approve" class="bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-bold px-2.5 py-1 rounded transition flex items-center gap-1">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </button>
                                        <button type="submit" name="action" value="Reject" class="bg-red-600 hover:bg-red-500 text-white text-[10px] font-bold px-2.5 py-1 rounded transition flex items-center gap-1">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </button>
                                    </form>
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
