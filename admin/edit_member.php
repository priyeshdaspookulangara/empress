<?php
$pageTitle = "Edit Member Profile";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin_login.php");
    exit();
}

$memberId = trim($_GET['member_id'] ?? '');
if (empty($memberId)) {
    header("Location: /admin/members.php?err=" . urlencode("Member ID required."));
    exit();
}

$pdo = getDBConnection();
$msg = '';
$err = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = $_POST['status'] ?? 'Active';
    $kyc_status = $_POST['kyc_status'] ?? 'Pending';
    $package_type = $_POST['package_type'] ?? 'Starter_1000';
    $address_line = trim($_POST['address_line'] ?? '');
    $place = trim($_POST['place'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pan_number = trim($_POST['pan_number'] ?? '');
    $aadhaar_number = trim($_POST['aadhaar_number'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $bank_account_number = trim($_POST['bank_account_number'] ?? '');
    $ifsc_code = trim($_POST['ifsc_code'] ?? '');
    $crypto_wallet_address = trim($_POST['crypto_wallet_address'] ?? '');
    $wallet_network = trim($_POST['wallet_network'] ?? 'USDT_TRC20');

    if (empty($name) || empty($email) || empty($phone)) {
        $err = "Name, Email, and Phone number are required.";
    } else {
        try {
            $stmtUp = $pdo->prepare("
                UPDATE members SET
                    name = ?, email = ?, phone = ?, status = ?, kyc_status = ?, package_type = ?,
                    address_line = ?, place = ?, city = ?, pincode = ?, state = ?,
                    pan_number = ?, aadhaar_number = ?, bank_name = ?, bank_account_number = ?,
                    ifsc_code = ?, crypto_wallet_address = ?, wallet_network = ?
                WHERE member_id = ?
            ");
            $stmtUp->execute([
                $name, $email, $phone, $status, $kyc_status, $package_type,
                $address_line, $place, $city, $pincode, $state,
                $pan_number, $aadhaar_number, $bank_name, $bank_account_number,
                $ifsc_code, $crypto_wallet_address, $wallet_network,
                $memberId
            ]);
            $msg = "Member profile for {$memberId} successfully updated!";
        } catch (Exception $e) {
            $err = "Failed to update member: " . $e->getMessage();
        }
    }
}

// Fetch member details
$stmt = $pdo->prepare("SELECT m.*, w.balance, w.user_wallet_60, w.company_wallet_40 FROM members m LEFT JOIN wallets w ON m.member_id = w.member_id WHERE m.member_id = ?");
$stmt->execute([$memberId]);
$m = $stmt->fetch();

if (!$m) {
    header("Location: /admin/members.php?err=" . urlencode("Member not found."));
    exit();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6 max-w-4xl mx-auto">
    <div class="glass-card p-6 rounded-3xl border border-gold/30 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-extrabold gold-gradient-text">Edit Member Profile: <?php echo htmlspecialchars($m['member_id']); ?></h1>
            <p class="text-xs text-champagne/70 mt-1">Super Admin full view and modification control panel</p>
        </div>
        <a href="/admin/members.php" class="glass-card border border-gold/30 hover:bg-gold/10 text-gold px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1">
            <i class="fa-solid fa-arrow-left"></i> Back to Members
        </a>
    </div>

    <?php if ($msg): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-300 p-4 rounded-xl text-xs">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div class="bg-red-500/10 border border-red-500/40 text-red-300 p-4 rounded-xl text-xs">
            <?php echo htmlspecialchars($err); ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <!-- Account & Matrix Overview -->
        <div class="glass-card p-6 rounded-3xl border border-gold/20 space-y-4">
            <h3 class="text-base font-bold text-gold border-b border-gold/20 pb-2 flex items-center gap-2">
                <i class="fa-solid fa-id-card"></i> Account Settings & Status
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div>
                    <label class="block font-semibold text-gold mb-1">Full Name *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($m['name']); ?>" required class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-3 py-2 text-champagne focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block font-semibold text-gold mb-1">Email Address *</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($m['email']); ?>" required class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-3 py-2 text-champagne focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block font-semibold text-gold mb-1">Phone Number *</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($m['phone']); ?>" required class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-3 py-2 text-champagne focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block font-semibold text-gold mb-1">Account Status</label>
                    <select name="status" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-3 py-2 text-champagne focus:outline-none focus:border-gold">
                        <option value="Active" <?php echo $m['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo $m['status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive / Suspended</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-gold mb-1">KYC Verification Status</label>
                    <select name="kyc_status" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-3 py-2 text-champagne focus:outline-none focus:border-gold">
                        <option value="Pending" <?php echo $m['kyc_status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Submitted" <?php echo $m['kyc_status'] === 'Submitted' ? 'selected' : ''; ?>>Submitted</option>
                        <option value="Approved" <?php echo $m['kyc_status'] === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="Rejected" <?php echo $m['kyc_status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-gold mb-1">Package Type</label>
                    <select name="package_type" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-3 py-2 text-champagne focus:outline-none focus:border-gold">
                        <option value="Starter_1000" <?php echo $m['package_type'] === 'Starter_1000' ? 'selected' : ''; ?>>Starter 1000 ($10 USD)</option>
                        <option value="Starter_5000" <?php echo $m['package_type'] === 'Starter_5000' ? 'selected' : ''; ?>>Starter 5000 ($50 USD)</option>
                        <option value="Empress_15000" <?php echo $m['package_type'] === 'Empress_15000' ? 'selected' : ''; ?>>Empress 15000 ($150 USD)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Crypto & Financial Payout Info -->
        <div class="glass-card p-6 rounded-3xl border border-gold/20 space-y-4">
            <h3 class="text-base font-bold text-gold border-b border-gold/20 pb-2 flex items-center gap-2">
                <i class="fa-solid fa-wallet"></i> Crypto Wallet Address & Payout Details
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block font-semibold text-gold mb-1">USD Crypto Receiving Wallet Address (USDT)</label>
                    <input type="text" name="crypto_wallet_address" value="<?php echo htmlspecialchars($m['crypto_wallet_address'] ?? ''); ?>" placeholder="Enter TRC20/BEP20 Address" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-3 py-2 text-champagne font-mono focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block font-semibold text-gold mb-1">Network Type</label>
                    <select name="wallet_network" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-3 py-2 text-champagne focus:outline-none focus:border-gold">
                        <option value="USDT_TRC20" <?php echo ($m['wallet_network'] ?? '') === 'USDT_TRC20' ? 'selected' : ''; ?>>USDT (TRC20)</option>
                        <option value="USDT_BEP20" <?php echo ($m['wallet_network'] ?? '') === 'USDT_BEP20' ? 'selected' : ''; ?>>USDT (BEP20)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Full Address & Verification Documents -->
        <div class="glass-card p-6 rounded-3xl border border-gold/20 space-y-4">
            <h3 class="text-base font-bold text-gold border-b border-gold/20 pb-2 flex items-center gap-2">
                <i class="fa-solid fa-location-dot"></i> Full Address & Secondary Banking Details
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="md:col-span-3">
                    <label class="block font-semibold text-gold mb-1">Address Line</label>
                    <input type="text" name="address_line" value="<?php echo htmlspecialchars($m['address_line'] ?? ''); ?>" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-3 py-2 text-champagne focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block font-semibold text-gold mb-1">City / Place</label>
                    <input type="text" name="city" value="<?php echo htmlspecialchars($m['city'] ?? ''); ?>" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-3 py-2 text-champagne focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block font-semibold text-gold mb-1">State</label>
                    <input type="text" name="state" value="<?php echo htmlspecialchars($m['state'] ?? ''); ?>" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-3 py-2 text-champagne focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block font-semibold text-gold mb-1">Pincode</label>
                    <input type="text" name="pincode" value="<?php echo htmlspecialchars($m['pincode'] ?? ''); ?>" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-3 py-2 text-champagne focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block font-semibold text-gold mb-1">PAN Number</label>
                    <input type="text" name="pan_number" value="<?php echo htmlspecialchars($m['pan_number'] ?? ''); ?>" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-3 py-2 text-champagne focus:outline-none focus:border-gold uppercase">
                </div>

                <div>
                    <label class="block font-semibold text-gold mb-1">Aadhaar Number</label>
                    <input type="text" name="aadhaar_number" value="<?php echo htmlspecialchars($m['aadhaar_number'] ?? ''); ?>" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-3 py-2 text-champagne focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block font-semibold text-gold mb-1">Bank Name</label>
                    <input type="text" name="bank_name" value="<?php echo htmlspecialchars($m['bank_name'] ?? ''); ?>" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-3 py-2 text-champagne focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block font-semibold text-gold mb-1">Bank Account Number</label>
                    <input type="text" name="bank_account_number" value="<?php echo htmlspecialchars($m['bank_account_number'] ?? ''); ?>" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-3 py-2 text-champagne focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block font-semibold text-gold mb-1">IFSC Code</label>
                    <input type="text" name="ifsc_code" value="<?php echo htmlspecialchars($m['ifsc_code'] ?? ''); ?>" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-3 py-2 text-champagne focus:outline-none focus:border-gold uppercase">
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="/admin/members.php" class="glass-card border border-gold/30 hover:bg-gold/10 text-gold px-6 py-3 rounded-xl text-xs font-bold">Cancel</a>
            <button type="submit" class="gold-button px-8 py-3 rounded-xl text-xs font-bold">Save Changes</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
