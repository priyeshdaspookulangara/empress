<?php
$pageTitle = "ePIN Code Management Engine";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['superadmin_id'])) {
    header("Location: /superadmin_login.php");
    exit();
}

$pdo = getDBConnection();
$adminId = $_SESSION['superadmin_id'];
$msg = '';
$err = '';

// Handle ePIN Batch Generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $packageType = $_POST['package_type'] ?? 'Starter_1000';
    $quantity = (int)($_POST['quantity'] ?? 1);
    $quantity = max(1, min(100, $quantity));

    if (!in_array($packageType, ['Starter_1000', 'Starter_5000', 'Empress_15000'])) {
        $packageType = 'Starter_1000';
    }

    $pdo->beginTransaction();
    try {
        $stmtIns = $pdo->prepare("INSERT INTO epins (epin_code, package_type, status, generated_by_admin_id) VALUES (?, ?, 'Unused', ?)");
        $generatedCodes = [];
        for ($i = 0; $i < $quantity; $i++) {
            $code = generateEpinCode();
            $stmtIns->execute([$code, $packageType, $adminId]);
            $generatedCodes[] = $code;
        }
        $pdo->commit();
        $msg = "Successfully generated batch of {$quantity} x {$packageType} ePINs.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $err = "ePIN Generation failed: " . $e->getMessage();
    }
}

// Fetch ePIN Inventory
$stmtList = $pdo->query("
    SELECT e.*, m.name as used_by_name
    FROM epins e
    LEFT JOIN members m ON e.used_by_member_id = m.member_id
    ORDER BY e.id DESC LIMIT 100
");
$epins = $stmtList->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div class="glass-card p-6 rounded-3xl border border-gold/30 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-extrabold gold-gradient-text">ePIN Generation & Inventory</h1>
            <p class="text-xs text-champagne/70 mt-1">Batch generate unique security keys for member registration and package activation</p>
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

    <!-- Batch Generation Form -->
    <div class="glass-card p-6 md:p-8 rounded-3xl border border-gold/30 max-w-2xl">
        <h3 class="text-lg font-bold text-gold mb-4 flex items-center gap-2">
            <i class="fa-solid fa-plus-circle"></i> Generate New ePIN Key Batch
        </h3>

        <form action="/superadmin/epins.php" method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gold mb-1">Package Type *</label>
                    <select name="package_type" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-3 text-xs text-champagne focus:outline-none focus:border-gold">
                        <option value="Starter_1000" selected>Starter 1000 ($10 USD)</option>
                        <option value="Starter_5000">Starter 5000 ($50 USD)</option>
                        <option value="Empress_15000">Empress 15000 ($150 USD)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gold mb-1">Batch Quantity (1 to 100) *</label>
                    <input type="number" name="quantity" min="1" max="100" value="10" required class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-3 text-xs text-champagne focus:outline-none focus:border-gold">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" name="generate" value="1" class="gold-button px-6 py-3 rounded-xl text-xs font-bold shadow-lg shadow-gold/20 flex items-center gap-2">
                    <i class="fa-solid fa-key"></i> Batch Generate ePINs
                </button>
            </div>
        </form>
    </div>

    <!-- Inventory Table -->
    <div class="glass-card p-6 rounded-3xl border border-gold/20">
        <h3 class="text-lg font-bold text-gold mb-4 flex items-center gap-2">
            <i class="fa-solid fa-vault"></i> Recent ePIN Keys Inventory
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gold/10 border-b border-gold/20 text-gold font-semibold uppercase">
                        <th class="p-3">ePIN Code</th>
                        <th class="p-3">Package</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Used By Member</th>
                        <th class="p-3">Created Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10 text-champagne">
                    <?php if (empty($epins)): ?>
                        <tr>
                            <td colspan="5" class="p-4 text-center text-champagne/50">No ePIN keys generated yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($epins as $ep): ?>
                            <tr>
                                <td class="p-3 font-mono font-bold text-gold tracking-widest"><?php echo htmlspecialchars($ep['epin_code']); ?></td>
                                <td class="p-3 font-semibold"><?php echo str_replace('_', ' ', $ep['package_type']); ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border <?php
                                        echo $ep['status'] === 'Used' ? 'bg-red-500/20 text-red-400 border-red-500/40' : 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40';
                                    ?>">
                                        <?php echo $ep['status']; ?>
                                    </span>
                                </td>
                                <td class="p-3 font-mono">
                                    <?php if ($ep['used_by_member_id']): ?>
                                        <span class="text-gold"><?php echo htmlspecialchars($ep['used_by_member_id']); ?></span>
                                        <span class="text-champagne/60 text-[11px] block"><?php echo htmlspecialchars($ep['used_by_name'] ?? ''); ?></span>
                                    <?php else: ?>
                                        <span class="text-champagne/40 italic">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 font-mono text-champagne/50"><?php echo $ep['created_at']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
