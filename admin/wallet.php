<?php
$pageTitle = "Crypto Payout Requests Console";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin_login.php");
    exit();
}

$pdo = getDBConnection();
$msg = '';
$err = '';

// Handle Payout Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $withdrawalId = (int)($_POST['withdrawal_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($withdrawalId > 0 && in_array($action, ['Approve', 'Reject'])) {
        $stmtW = $pdo->prepare("SELECT w.*, m.kyc_status, m.name, m.crypto_wallet_address, m.wallet_network FROM withdrawals w JOIN members m ON w.member_id = m.member_id WHERE w.id = ?");
        $stmtW->execute([$withdrawalId]);
        $w = $stmtW->fetch();

        if ($w && $w['status'] === 'Pending') {
            $pdo->beginTransaction();
            try {
                if ($action === 'Approve') {
                    if ($w['kyc_status'] !== 'Approved') {
                        throw new Exception("Cannot approve payout for member {$w['member_id']} whose KYC is not Approved.");
                    }

                    $stmtApprove = $pdo->prepare("UPDATE withdrawals SET status = 'Approved', processed_date = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmtApprove->execute([$withdrawalId]);

                    $msg = "Payout request #WD-{$withdrawalId} of $" . number_format($w['amount'], 2) . " USD for member {$w['member_id']} has been APPROVED.";
                } else {
                    $stmtReject = $pdo->prepare("UPDATE withdrawals SET status = 'Rejected', processed_date = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmtReject->execute([$withdrawalId]);

                    $stmtRefund = $pdo->prepare("UPDATE wallets SET user_wallet_60 = user_wallet_60 + ? WHERE member_id = ?");
                    $stmtRefund->execute([$w['amount'], $w['member_id']]);

                    $stmtTx = $pdo->prepare("
                        INSERT INTO transactions (member_id, type, amount, wallet_type, status, description)
                        VALUES (?, 'Admin_Adjustment', ?, 'User_Wallet', 'Credit', ?)
                    ");
                    $stmtTx->execute([$w['member_id'], $w['amount'], "Refund for rejected withdrawal request #WD-{$withdrawalId}."]);

                    $msg = "Payout request #WD-{$withdrawalId} REJECTED and $" . number_format($w['amount'], 2) . " USD refunded to member's User Wallet.";
                }
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $err = $e->getMessage();
            }
        }
    }
}

// Fetch Withdrawal Requests Joined with Crypto Wallet Details
$stmtList = $pdo->query("
    SELECT w.*, m.name, m.email, m.phone, m.kyc_status, m.crypto_wallet_address, m.wallet_network
    FROM withdrawals w
    JOIN members m ON w.member_id = m.member_id
    ORDER BY FIELD(w.status, 'Pending', 'Approved', 'Rejected'), w.id DESC
");
$withdrawals = $stmtList->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/30 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-extrabold neon-gradient-text">USD Crypto Payout Console</h1>
            <p class="text-xs text-ice/70 mt-1">Review pending payout requests, inspect member crypto wallets, and approve transfers</p>
        </div>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-300 p-4 rounded-xl text-xs">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($err)): ?>
        <div class="bg-red-500/10 border border-red-500/40 text-red-300 p-4 rounded-xl text-xs">
            <?php echo htmlspecialchars($err); ?>
        </div>
    <?php endif; ?>

    <!-- Withdrawals Processing Table -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-neon-cyan/10 border-b border-neon-cyan/20 text-neon-cyan font-semibold uppercase">
                        <th class="p-3">Req ID</th>
                        <th class="p-3">Member Details</th>
                        <th class="p-3">Amount ($ USD)</th>
                        <th class="p-3">Destination Crypto Wallet</th>
                        <th class="p-3">KYC & Status</th>
                        <th class="p-3">Request Date</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neon-cyan/10 text-ice">
                    <?php if (empty($withdrawals)): ?>
                        <tr>
                            <td colspan="7" class="p-4 text-center text-ice/50">No withdrawal requests found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($withdrawals as $w): ?>
                            <tr>
                                <td class="p-3 font-mono text-neon-cyan font-bold">#WD-<?php echo $w['id']; ?></td>
                                <td class="p-3">
                                    <div class="font-bold text-ice"><?php echo htmlspecialchars($w['name']); ?></div>
                                    <div class="text-neon-cyan font-mono text-[11px]"><?php echo htmlspecialchars($w['member_id']); ?></div>
                                </td>
                                <td class="p-3 font-bold text-emerald-400 text-sm">$<?php echo number_format($w['amount'], 2); ?></td>
                                <td class="p-3 font-mono max-w-xs">
                                    <div>Network: <strong class="text-ice"><?php echo htmlspecialchars($w['wallet_network'] ?: 'USDT (TRC20)'); ?></strong></div>
                                    <div>Address: <strong class="text-neon-cyan break-all text-[11px]"><?php echo htmlspecialchars($w['crypto_wallet_address'] ?: 'N/A'); ?></strong></div>
                                </td>
                                <td class="p-3">
                                    <div class="mb-1">
                                        <span class="text-[9px] uppercase font-bold px-2 py-0.5 rounded border <?php
                                            echo $w['kyc_status'] === 'Approved' ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40' : 'bg-red-500/20 text-red-400 border-red-500/40';
                                        ?>">KYC: <?php echo $w['kyc_status']; ?></span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded border <?php
                                            echo match($w['status']) {
                                                'Approved' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40',
                                                'Rejected' => 'bg-red-500/20 text-red-400 border-red-500/40',
                                                default => 'bg-amber-500/20 text-amber-400 border-amber-500/40'
                                            };
                                        ?>"><?php echo $w['status']; ?></span>
                                    </div>
                                </td>
                                <td class="p-3 font-mono text-ice/60"><?php echo $w['request_date']; ?></td>
                                <td class="p-3">
                                    <?php if ($w['status'] === 'Pending'): ?>
                                        <form action="/admin/wallet.php" method="POST" class="flex gap-2">
                                            <input type="hidden" name="withdrawal_id" value="<?php echo $w['id']; ?>">
                                            <button type="submit" name="action" value="Approve" <?php echo $w['kyc_status'] !== 'Approved' ? 'disabled title="Blocked: Member KYC not approved"' : ''; ?> class="bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-bold px-2.5 py-1 rounded transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1">
                                                <i class="fa-solid fa-check"></i> Approve
                                            </button>
                                            <button type="submit" name="action" value="Reject" class="bg-red-600 hover:bg-red-500 text-white text-[10px] font-bold px-2.5 py-1 rounded transition flex items-center gap-1">
                                                <i class="fa-solid fa-xmark"></i> Reject
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-ice/50 text-[11px] font-mono">Processed <?php echo $w['processed_date']; ?></span>
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
