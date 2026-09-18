<?php
$pageTitle = "My Profile & KYC Verification";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: /login.php");
    exit();
}

$memberId = $_SESSION['member_id'];
$pdo = getDBConnection();
$error = '';
$success = '';

// Handle Profile & KYC Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $addressLine = trim($_POST['address_line'] ?? '');
    $place = trim($_POST['place'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $panNumber = strtoupper(trim($_POST['pan_number'] ?? ''));
    $aadhaarNumber = trim($_POST['aadhaar_number'] ?? '');
    $bankName = trim($_POST['bank_name'] ?? '');
    $bankAccountNumber = trim($_POST['bank_account_number'] ?? '');
    $ifscCode = strtoupper(trim($_POST['ifsc_code'] ?? ''));

    if (empty($addressLine) || empty($city) || empty($state) || empty($pincode) ||
        empty($panNumber) || empty($aadhaarNumber) || empty($bankName) ||
        empty($bankAccountNumber) || empty($ifscCode)) {
        $error = "Please fill in all mandatory KYC and Bank account details.";
    } else {
        $stmt = $pdo->prepare("
            UPDATE members
            SET address_line = ?, place = ?, city = ?, pincode = ?, state = ?,
                pan_number = ?, aadhaar_number = ?, bank_name = ?,
                bank_account_number = ?, ifsc_code = ?, kyc_status = 'Submitted'
            WHERE member_id = ?
        ");
        $stmt->execute([
            $addressLine, $place, $city, $pincode, $state,
            $panNumber, $aadhaarNumber, $bankName,
            $bankAccountNumber, $ifscCode, $memberId
        ]);

        $success = "KYC and Bank details submitted successfully! Awaiting admin review.";
    }
}

// Fetch Latest Member Data
$stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto py-6 space-y-6">
    <div class="glass-card p-6 md:p-8 rounded-3xl border border-gold/30">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 pb-6 border-b border-gold/20">
            <div>
                <h1 class="text-2xl font-extrabold gold-gradient-text">Member Profile & KYC Console</h1>
                <p class="text-xs text-champagne/70 mt-1">Manage address, identity documents (PAN / Aadhaar) and bank payout details</p>
            </div>
            <div>
                <span class="text-xs font-bold uppercase tracking-wider px-3.5 py-1.5 rounded-full border <?php
                    echo match($member['kyc_status']) {
                        'Approved' => 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40',
                        'Submitted' => 'bg-blue-500/20 text-blue-400 border-blue-500/40',
                        'Rejected' => 'bg-red-500/20 text-red-400 border-red-500/40',
                        default => 'bg-amber-500/20 text-amber-400 border-amber-500/40'
                    };
                ?>">
                    KYC Status: <?php echo htmlspecialchars($member['kyc_status']); ?>
                </span>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-500/10 border border-red-500/40 text-red-300 p-4 rounded-xl text-xs mb-6 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-base"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-300 p-4 rounded-xl text-xs mb-6 flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-base text-emerald-400"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <!-- Read-Only Basic Info -->
        <div class="bg-obsidian/60 p-5 rounded-2xl border border-gold/20 mb-8 grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
            <div>
                <span class="text-gold/70 block uppercase font-mono text-[10px]">Member ID</span>
                <span class="text-champagne font-bold font-mono text-sm"><?php echo htmlspecialchars($member['member_id']); ?></span>
            </div>
            <div>
                <span class="text-gold/70 block uppercase font-mono text-[10px]">Full Name</span>
                <span class="text-champagne font-bold text-sm"><?php echo htmlspecialchars($member['name']); ?></span>
            </div>
            <div>
                <span class="text-gold/70 block uppercase font-mono text-[10px]">Email & Phone</span>
                <span class="text-champagne block"><?php echo htmlspecialchars($member['email']); ?></span>
                <span class="text-champagne/70 block font-mono"><?php echo htmlspecialchars($member['phone']); ?></span>
            </div>
            <div>
                <span class="text-gold/70 block uppercase font-mono text-[10px]">Activated Package</span>
                <span class="text-gold font-bold"><?php echo str_replace('_', ' ', $member['package_type']); ?></span>
            </div>
            <div>
                <span class="text-gold/70 block uppercase font-mono text-[10px]">Matrix Placement Parent</span>
                <span class="text-champagne font-mono"><?php echo htmlspecialchars($member['placement_parent_id'] ?: 'ROOT'); ?></span>
            </div>
            <div>
                <span class="text-gold/70 block uppercase font-mono text-[10px]">Sponsor Member</span>
                <span class="text-champagne font-mono"><?php echo htmlspecialchars($member['sponsor_id'] ?: 'ROOT'); ?></span>
            </div>
        </div>

        <!-- Editable Address & KYC Form -->
        <form action="/customer/profile.php" method="POST" class="space-y-6">
            <h3 class="text-sm font-bold text-gold uppercase tracking-wider border-b border-gold/20 pb-2 flex items-center gap-2">
                <i class="fa-solid fa-location-dot"></i> Address Details
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gold mb-1">Address Line *</label>
                    <input type="text" name="address_line" value="<?php echo htmlspecialchars($member['address_line'] ?? ''); ?>" required placeholder="Flat / Building / Street Address" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-champagne focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gold mb-1">Place / Landmark</label>
                    <input type="text" name="place" value="<?php echo htmlspecialchars($member['place'] ?? ''); ?>" placeholder="Near Park / Locality" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-champagne focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gold mb-1">City *</label>
                    <input type="text" name="city" value="<?php echo htmlspecialchars($member['city'] ?? ''); ?>" required placeholder="Mumbai / Delhi" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-champagne focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gold mb-1">State *</label>
                    <input type="text" name="state" value="<?php echo htmlspecialchars($member['state'] ?? ''); ?>" required placeholder="Maharashtra" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-champagne focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gold mb-1">Pincode *</label>
                    <input type="text" name="pincode" value="<?php echo htmlspecialchars($member['pincode'] ?? ''); ?>" required placeholder="400001" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-champagne font-mono focus:outline-none focus:border-gold">
                </div>
            </div>

            <h3 class="text-sm font-bold text-gold uppercase tracking-wider border-b border-gold/20 pb-2 flex items-center gap-2 pt-4">
                <i class="fa-solid fa-id-card"></i> Identity Verification (PAN & Aadhaar)
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gold mb-1">PAN Card Number *</label>
                    <input type="text" name="pan_number" value="<?php echo htmlspecialchars($member['pan_number'] ?? ''); ?>" required placeholder="ABCDE1234F" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-gold uppercase font-mono focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gold mb-1">Aadhaar Card Number *</label>
                    <input type="text" name="aadhaar_number" value="<?php echo htmlspecialchars($member['aadhaar_number'] ?? ''); ?>" required placeholder="1234 5678 9012" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-gold font-mono focus:outline-none focus:border-gold">
                </div>
            </div>

            <h3 class="text-sm font-bold text-gold uppercase tracking-wider border-b border-gold/20 pb-2 flex items-center gap-2 pt-4">
                <i class="fa-solid fa-building-columns"></i> Bank Account Payout Information
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gold mb-1">Bank Name *</label>
                    <input type="text" name="bank_name" value="<?php echo htmlspecialchars($member['bank_name'] ?? ''); ?>" required placeholder="HDFC Bank" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-champagne focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gold mb-1">Bank Account Number *</label>
                    <input type="text" name="bank_account_number" value="<?php echo htmlspecialchars($member['bank_account_number'] ?? ''); ?>" required placeholder="50100012345678" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-champagne font-mono focus:outline-none focus:border-gold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gold mb-1">IFSC Code *</label>
                    <input type="text" name="ifsc_code" value="<?php echo htmlspecialchars($member['ifsc_code'] ?? ''); ?>" required placeholder="HDFC0000123" class="w-full bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-gold uppercase font-mono focus:outline-none focus:border-gold">
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="gold-button px-8 py-3 rounded-xl text-sm font-bold shadow-lg shadow-gold/20 flex items-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Save & Submit Profile for Verification
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
