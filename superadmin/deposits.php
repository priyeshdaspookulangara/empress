<?php
$pageTitle = "USDT Fund Deposits Directory & Blockchain Verification";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['superadmin_id'])) {
    header("Location: /superadmin_login.php");
    exit();
}

$pdo = getDBConnection();
$msg = '';
$err = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $depositId = (int)($_POST['deposit_id'] ?? 0);

    if ($action === 'verify_single' && $depositId > 0) {
        $stmtD = $pdo->prepare("SELECT * FROM deposits WHERE id = ?");
        $stmtD->execute([$depositId]);
        $dep = $stmtD->fetch();

        if ($dep) {
            $verResult = verifyBscTransactionOnChain($dep['tx_hash']);
            if ($verResult['verified']) {
                $appRes = approveDepositAndCreditWallet($pdo, $depositId, "Auto-verified via BSC Blockchain (Amount: \${$verResult['amount']} USDT)");
                if ($appRes['success']) {
                    $msg = $appRes['message'];
                } else {
                    $err = $appRes['message'];
                }
            } else {
                $err = "Blockchain verification failed: " . $verResult['message'];
            }
        }
    } elseif ($action === 'manual_approve' && $depositId > 0) {
        $appRes = approveDepositAndCreditWallet($pdo, $depositId, "Manual Super Admin Approval");
        if ($appRes['success']) {
            $msg = $appRes['message'];
        } else {
            $err = $appRes['message'];
        }
    } elseif ($action === 'manual_reject' && $depositId > 0) {
        $stmtR = $pdo->prepare("UPDATE deposits SET status = 'Rejected' WHERE id = ? AND status = 'Pending'");
        $stmtR->execute([$depositId]);
        if ($stmtR->rowCount() > 0) {
            $msg = "Deposit #{$depositId} has been manually rejected.";
        } else {
            $err = "Unable to reject deposit #{$depositId} (or already processed).";
        }
    } elseif ($action === 'manual_create_deposit') {
        $targetMemberId = trim($_POST['member_id'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $txHash = trim($_POST['tx_hash'] ?? '');
        $notes = trim($_POST['admin_notes'] ?? 'Manual Super Admin Direct Entry');

        if (empty($targetMemberId) || $amount <= 0) {
            $err = "Please enter a valid Member ID and positive amount.";
        } else {
            // Check member exists
            $stmtM = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ?");
            $stmtM->execute([$targetMemberId]);
            if (!$stmtM->fetch()) {
                $err = "Member ID '{$targetMemberId}' does not exist.";
            } else {
                if (empty($txHash)) {
                    $txHash = '0xMANUAL_' . strtoupper(bin2hex(random_bytes(16)));
                }
                $stmtIns = $pdo->prepare("INSERT INTO deposits (user_id, tx_hash, amount, network, status) VALUES (?, ?, ?, 'BEP20', 'Approved')");
                $stmtIns->execute([$targetMemberId, $txHash, $amount]);
                $newDepId = $pdo->lastInsertId();

                // Credit User Wallet
                $stmtW = $pdo->prepare("UPDATE wallets SET balance = balance + ?, user_wallet_60 = user_wallet_60 + ? WHERE member_id = ?");
                $stmtW->execute([$amount, $amount, $targetMemberId]);

                // Record transaction
                $stmtTx = $pdo->prepare("INSERT INTO transactions (member_id, type, amount, wallet_type, status, description) VALUES (?, 'Admin_Adjustment', ?, 'User_Wallet', 'Credit', ?)");
                $stmtTx->execute([$targetMemberId, $amount, "Manual Deposit Entry (#{$newDepId}): {$notes}"]);

                $msg = "Successfully created and credited manual deposit of \${$amount} USDT for member {$targetMemberId}.";
            }
        }
    } elseif ($action === 'batch_verify_pending') {
        $stmtPend = $pdo->query("SELECT id, tx_hash, user_id FROM deposits WHERE status = 'Pending'");
        $pendingList = $stmtPend->fetchAll();

        $approvedCount = 0;
        $failedCount = 0;

        foreach ($pendingList as $pDep) {
            $ver = verifyBscTransactionOnChain($pDep['tx_hash']);
            if ($ver['verified']) {
                $res = approveDepositAndCreditWallet($pdo, $pDep['id'], "Batch Blockchain Verification");
                if ($res['success']) $approvedCount++;
                else $failedCount++;
            } else {
                $failedCount++;
            }
        }

        $msg = "Batch Verification Completed: {$approvedCount} deposits approved and credited on blockchain confirmation. ({$failedCount} pending/unverified).";
    }
}

$filter = $_GET['status'] ?? 'All';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT d.*, m.name, m.email, m.phone
        FROM deposits d
        LEFT JOIN members m ON d.user_id = m.member_id";
$params = [];

$whereClause = [];
if ($filter !== 'All') {
    $whereClause[] = "d.status = ?";
    $params[] = $filter;
}
if (!empty($search)) {
    $whereClause[] = "(d.user_id LIKE ? OR d.tx_hash LIKE ? OR m.name LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if (!empty($whereClause)) {
    $sql .= " WHERE " . implode(' AND ', $whereClause);
}

$sql .= " ORDER BY d.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$deposits = $stmt->fetchAll();

// Stats
$stmtTotal = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM deposits WHERE status = 'Approved'");
$totalApprovedAmount = $stmtTotal->fetchColumn();

$stmtPendCount = $pdo->query("SELECT COUNT(*) FROM deposits WHERE status = 'Pending'");
$pendingCount = $stmtPendCount->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <!-- Top Header -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-extrabold neon-gradient-text flex items-center gap-3">
                <i class="fa-brands fa-ethereum text-neon-cyan"></i> USDT Deposit Blockchain Directory
            </h1>
            <p class="text-xs text-ice/70 mt-1">Super Admin live verification of USDT (BEP-20) transaction hashes directly on BSC Smart Chain</p>
        </div>

        <div class="flex items-center gap-3">
            <form method="POST">
                <input type="hidden" name="action" value="batch_verify_pending">
                <button type="submit" onclick="return confirm('Run live blockchain verification for all pending deposits?');" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-xl text-xs font-bold transition shadow-lg flex items-center gap-2">
                    <i class="fa-solid fa-cloud-bolt"></i> Auto-Verify All Pending (<?php echo $pendingCount; ?>)
                </button>
            </form>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-300 p-4 rounded-xl text-xs flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-base"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div class="bg-red-500/10 border border-red-500/40 text-red-300 p-4 rounded-xl text-xs flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-base"></i> <?php echo htmlspecialchars($err); ?>
        </div>
    <?php endif; ?>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="glass-card p-5 rounded-2xl border border-neon-cyan/20">
            <span class="text-xs text-ice/60 block font-semibold">Total Approved Deposits</span>
            <span class="text-xl font-bold font-mono text-emerald-400 mt-1 block">$<?php echo number_format($totalApprovedAmount, 2); ?> USDT</span>
        </div>

        <div class="glass-card p-5 rounded-2xl border border-neon-cyan/20">
            <span class="text-xs text-ice/60 block font-semibold">Pending Approvals</span>
            <span class="text-xl font-bold font-mono text-amber-400 mt-1 block"><?php echo $pendingCount; ?> Requests</span>
        </div>

        <div class="glass-card p-5 rounded-2xl border border-neon-cyan/20">
            <span class="text-xs text-ice/60 block font-semibold">Receiving Smart Wallet</span>
            <span class="text-xs font-bold font-mono text-neon-cyan mt-1 block truncate">0x9811cCf1E9dcc6451357D9f983E6E9bA615920B5</span>
        </div>
    </div>

    <!-- Manual Deposit Direct Entry Console -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/30">
        <h2 class="text-sm font-bold text-neon-cyan uppercase tracking-wider mb-3 flex items-center gap-2">
            <i class="fa-solid fa-plus-circle text-neon-cyan"></i> Manual Direct Deposit Entry
        </h2>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end text-xs">
            <input type="hidden" name="action" value="manual_create_deposit">

            <div>
                <label class="block text-[11px] text-ice/70 font-semibold mb-1">Member ID <span class="text-red-400">*</span></label>
                <input type="text" name="member_id" placeholder="e.g. EMP100001" required class="w-full bg-obsidian border border-neon-cyan/30 rounded-xl px-3 py-2 text-ice focus:outline-none focus:border-neon-cyan font-mono">
            </div>

            <div>
                <label class="block text-[11px] text-ice/70 font-semibold mb-1">Amount ($ USD) <span class="text-red-400">*</span></label>
                <input type="number" step="0.01" min="1" name="amount" placeholder="e.g. 10.00" required class="w-full bg-obsidian border border-neon-cyan/30 rounded-xl px-3 py-2 text-ice focus:outline-none focus:border-neon-cyan font-mono">
            </div>

            <div>
                <label class="block text-[11px] text-ice/70 font-semibold mb-1">Transaction Hash / Notes (Optional)</label>
                <input type="text" name="tx_hash" placeholder="0x... or leave blank for auto" class="w-full bg-obsidian border border-neon-cyan/30 rounded-xl px-3 py-2 text-ice focus:outline-none focus:border-neon-cyan font-mono">
            </div>

            <div>
                <button type="submit" onclick="return confirm('Manually credit funds to this member wallet?');" class="w-full bg-neon-cyan hover:bg-cyan-300 text-obsidian py-2 rounded-xl font-extrabold text-xs shadow-lg flex items-center justify-center gap-2">
                    <i class="fa-solid fa-wallet"></i> Credit Member Wallet
                </button>
            </div>
        </form>
    </div>

    <!-- Filters & Search -->
    <div class="glass-card p-4 rounded-2xl border border-neon-cyan/20 flex flex-col md:flex-row justify-between items-center gap-4 text-xs">
        <div class="flex gap-2">
            <a href="/superadmin/deposits.php?status=All" class="px-3 py-1.5 rounded-xl border font-bold <?php echo $filter === 'All' ? 'bg-neon-cyan text-obsidian border-neon-cyan' : 'border-neon-cyan/30 text-neon-cyan hover:bg-neon-cyan/10'; ?>">All Deposits</a>
            <a href="/superadmin/deposits.php?status=Pending" class="px-3 py-1.5 rounded-xl border font-bold <?php echo $filter === 'Pending' ? 'bg-amber-500 text-obsidian border-amber-500' : 'border-amber-500/30 text-amber-400 hover:bg-amber-500/10'; ?>">Pending (<?php echo $pendingCount; ?>)</a>
            <a href="/superadmin/deposits.php?status=Approved" class="px-3 py-1.5 rounded-xl border font-bold <?php echo $filter === 'Approved' ? 'bg-emerald-500 text-obsidian border-emerald-500' : 'border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10'; ?>">Approved</a>
        </div>

        <form method="GET" action="/superadmin/deposits.php" class="flex gap-2 w-full md:w-auto">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter); ?>">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search ID, Name, TxHash..." class="bg-obsidian border border-neon-cyan/30 rounded-xl px-3 py-1.5 text-ice focus:outline-none focus:border-neon-cyan text-xs w-64">
            <button type="submit" class="bg-neon-cyan/20 hover:bg-neon-cyan/30 text-neon-cyan border border-neon-cyan/40 px-4 py-1.5 rounded-xl font-bold">Search</button>
        </form>
    </div>

    <!-- Table -->
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-neon-cyan/10 border-b border-neon-cyan/20 text-neon-cyan font-semibold uppercase">
                        <th class="p-3">ID & Date</th>
                        <th class="p-3">Member Details</th>
                        <th class="p-3">Amount</th>
                        <th class="p-3">Network & Tx Hash</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Verification Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neon-cyan/10 text-ice">
                    <?php if (empty($deposits)): ?>
                        <tr>
                            <td colspan="6" class="p-4 text-center text-ice/50">No deposit records match search filter.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($deposits as $dep): ?>
                            <?php $nameFmt = formatMemberName($dep['name'] ?? $dep['user_id']); ?>
                            <tr>
                                <td class="p-3 font-mono">
                                    <span class="text-neon-cyan font-bold">#<?php echo $dep['id']; ?></span>
                                    <span class="block text-[10px] text-ice/50"><?php echo $dep['created_at']; ?></span>
                                </td>
                                <td class="p-3">
                                    <span class="font-bold text-ice block"><?php echo htmlspecialchars($nameFmt['clean_name']); ?></span>
                                    <span class="font-mono text-neon-cyan text-[11px]"><?php echo htmlspecialchars($dep['user_id']); ?></span>
                                </td>
                                <td class="p-3 font-mono text-emerald-400 font-extrabold text-sm">
                                    $<?php echo number_format($dep['amount'], 2); ?> <span class="text-[10px] text-neon-cyan font-normal">USDT</span>
                                </td>
                                <td class="p-3 font-mono">
                                    <span class="bg-neon-cyan/10 text-neon-cyan border border-neon-cyan/30 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase"><?php echo htmlspecialchars($dep['network']); ?></span>
                                    <a href="https://bscscan.com/tx/<?php echo htmlspecialchars($dep['tx_hash']); ?>" target="_blank" class="block text-neon-cyan hover:underline text-[11px] mt-1 truncate max-w-xs" title="<?php echo htmlspecialchars($dep['tx_hash']); ?>">
                                        <?php echo substr($dep['tx_hash'], 0, 16) . '...' . substr($dep['tx_hash'], -8); ?> <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                    </a>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border <?php
                                        echo $dep['status'] === 'Approved' ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40' : ($dep['status'] === 'Rejected' ? 'bg-red-500/20 text-red-400 border-red-500/40' : 'bg-amber-500/20 text-amber-400 border-amber-500/40');
                                    ?>">
                                        <?php echo $dep['status']; ?>
                                    </span>
                                </td>
                                <td class="p-3 text-right">
                                    <?php if ($dep['status'] === 'Pending'): ?>
                                        <div class="flex items-center justify-end gap-2">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="verify_single">
                                                <input type="hidden" name="deposit_id" value="<?php echo $dep['id']; ?>">
                                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-3 py-1.5 rounded-xl text-xs flex items-center gap-1">
                                                    <i class="fa-solid fa-circle-check"></i> Verify Blockchain
                                                </button>
                                            </form>

                                            <form method="POST">
                                                <input type="hidden" name="action" value="manual_approve">
                                                <input type="hidden" name="deposit_id" value="<?php echo $dep['id']; ?>">
                                                <button type="submit" onclick="return confirm('Manually approve and credit \$<?php echo number_format($dep['amount'], 2); ?> USDT to member <?php echo htmlspecialchars($dep['user_id']); ?>?');" class="bg-neon-cyan/10 hover:bg-neon-cyan/20 text-neon-cyan border border-neon-cyan/30 px-2.5 py-1.5 rounded-xl text-xs font-bold">
                                                    Manual Approve
                                                </button>
                                            </form>

                                            <form method="POST">
                                                <input type="hidden" name="action" value="manual_reject">
                                                <input type="hidden" name="deposit_id" value="<?php echo $dep['id']; ?>">
                                                <button type="submit" onclick="return confirm('Manually reject deposit #<?php echo $dep['id']; ?>?');" class="bg-red-500/20 hover:bg-red-500/30 text-red-400 border border-red-500/40 px-2.5 py-1.5 rounded-xl text-xs font-bold">
                                                    Reject
                                                </button>
                                            </form>
                                        </div>
                                    <?php elseif ($dep['status'] === 'Approved'): ?>
                                        <span class="text-emerald-400 text-[11px] font-mono"><i class="fa-solid fa-check-double mr-1"></i>Credited to Wallet</span>
                                    <?php else: ?>
                                        <span class="text-red-400 text-[11px] font-mono"><i class="fa-solid fa-xmark mr-1"></i>Rejected</span>
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
