<?php
$pageTitle = "My Profile & P2P Wallet Settings";
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

// Handle P2P Wallet Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_p2p_settings'])) {
    $bep20Address = trim($_POST['bep20_address'] ?? '');
    $qrFile = $_FILES['qr_code_file'] ?? null;

    $res = updateP2PWalletSettings($pdo, $memberId, $bep20Address, $qrFile);
    if ($res['success']) {
        $success = $res['message'];
    } else {
        $error = $res['message'];
    }
}

// Handle Profile & KYC Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $addressLine = trim($_POST['address_line'] ?? '');
    $place = trim($_POST['place'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $panNumber = strtoupper(trim($_POST['pan_number'] ?? ''));
    $aadhaarNumber = trim($_POST['aadhaar_number'] ?? '');
    $cryptoWallet = trim($_POST['crypto_wallet_address'] ?? '');
    $walletNetwork = trim($_POST['wallet_network'] ?? 'USDT (TRC20)');

    if (empty($addressLine) || empty($city) || empty($state) || empty($pincode) || empty($cryptoWallet)) {
        $error = "Please fill in all mandatory address and USD Crypto Wallet Address details.";
    } else {
        $stmt = $pdo->prepare("
            UPDATE members
            SET address_line = ?, place = ?, city = ?, pincode = ?, state = ?,
                pan_number = ?, aadhaar_number = ?, crypto_wallet_address = ?,
                wallet_network = ?, kyc_status = 'Submitted'
            WHERE member_id = ?
        ");
        $stmt->execute([
            $addressLine, $place, $city, $pincode, $state,
            $panNumber, $aadhaarNumber, $cryptoWallet,
            $walletNetwork, $memberId
        ]);

        $success = "Profile and USD Crypto Wallet details submitted successfully! Awaiting admin review.";
    }
}

// Fetch Latest Member Data
$stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">
    <div class="glass-card p-6 md:p-8 rounded-3xl border border-neon-cyan/30">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 pb-6 border-b border-neon-cyan/20">
            <div>
                <h1 class="text-2xl font-extrabold neon-gradient-text">Member Profile & Wallet Console</h1>
                <p class="text-xs text-ice/70 mt-1">Manage address, KYC, and P2P USDT (BEP-20) Crypto Wallet Settings</p>
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
        <div class="bg-navy/60 p-5 rounded-2xl border border-neon-cyan/20 mb-8 grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
            <div>
                <span class="text-neon-cyan/70 block uppercase font-mono text-[10px]">Member ID</span>
                <span class="text-ice font-bold font-mono text-sm"><?php echo htmlspecialchars($member['member_id']); ?></span>
            </div>
            <div>
                <span class="text-neon-cyan/70 block uppercase font-mono text-[10px]">Full Name</span>
                <span class="text-ice font-bold text-sm"><?php echo htmlspecialchars($member['name']); ?></span>
            </div>
            <div>
                <span class="text-neon-cyan/70 block uppercase font-mono text-[10px]">Email & Phone</span>
                <span class="text-ice block"><?php echo htmlspecialchars($member['email']); ?></span>
                <span class="text-ice/70 block font-mono"><?php echo htmlspecialchars($member['phone']); ?></span>
            </div>
            <div>
                <span class="text-neon-cyan/70 block uppercase font-mono text-[10px]">Activated Package</span>
                <span class="text-neon-cyan font-bold"><?php echo str_replace('_', ' ', $member['package_type']); ?></span>
            </div>
            <div>
                <span class="text-neon-cyan/70 block uppercase font-mono text-[10px]">Matrix Placement Parent</span>
                <span class="text-ice font-mono"><?php echo htmlspecialchars($member['placement_parent_id'] ?: 'ROOT'); ?></span>
            </div>
            <div>
                <span class="text-neon-cyan/70 block uppercase font-mono text-[10px]">Sponsor Member</span>
                <span class="text-ice font-mono"><?php echo htmlspecialchars($member['sponsor_id'] ?: 'ROOT'); ?></span>
            </div>
        </div>

        <!-- 1. P2P WALLET SETTINGS SECTION -->
        <div class="bg-navy/80 p-6 rounded-3xl border border-gold/40 mb-8 space-y-4">
            <div class="flex items-center gap-3 border-b border-gold/20 pb-3">
                <div class="w-10 h-10 rounded-xl bg-gold/10 text-gold flex items-center justify-center border border-gold/30 text-xl">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-gold uppercase tracking-wider">P2P Crypto Wallet Settings (USDT BEP-20)</h3>
                    <p class="text-xs text-champagne/70">Configure your personal BNB Smart Chain wallet address and QR code for peer-to-peer transfers</p>
                </div>
            </div>

            <!-- Network Guidelines Banner -->
            <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-xs text-champagne/90 flex items-start gap-2.5">
                <i class="fa-solid fa-triangle-exclamation text-amber-400 text-sm mt-0.5"></i>
                <div>
                    <strong class="text-amber-300">P2P Network Requirement:</strong> Ensure your receiving wallet address and QR code belong strictly to the <strong>BNB Smart Chain (BEP-20)</strong> network. Transacting via incompatible networks will result in unrecoverable asset loss.
                </div>
            </div>

            <form action="/customer/profile.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
                    <!-- BEP-20 Wallet Address Input -->
                    <div class="md:col-span-2 space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-gold uppercase tracking-wider mb-1">USDT BEP-20 Wallet Address *</label>
                            <input type="text" name="bep20_address" pattern="^0x[a-fA-F0-9]{40}$" title="Must be a valid 42-character hex address starting with 0x" value="<?php echo htmlspecialchars($member['bep20_address'] ?? ''); ?>" placeholder="e.g. 0x9811cCf1E9dcc6451357D9f983E6E9bA615920B5" class="w-full bg-obsidian border border-gold/30 rounded-xl px-4 py-2.5 text-xs text-emerald-400 font-mono focus:outline-none focus:border-gold">
                            <p class="text-[10px] text-champagne/50 mt-1">Standard 42-character BSC/Ethereum hex address starting with <span class="font-mono text-gold">0x</span></p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gold uppercase tracking-wider mb-1">Personal Receiving QR Code (Optional)</label>
                            <input type="file" name="qr_code_file" accept="image/jpeg,image/png,image/webp" class="w-full bg-obsidian border border-gold/30 rounded-xl px-3 py-2 text-xs text-champagne file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-gold/20 file:text-gold hover:file:bg-gold/30">
                            <p class="text-[10px] text-champagne/50 mt-1">Supported file formats: JPG, JPEG, PNG, WEBP (Max 5MB)</p>
                        </div>
                    </div>

                    <!-- QR Code Preview Area -->
                    <div class="flex flex-col items-center justify-center p-4 bg-obsidian/90 rounded-2xl border border-gold/20 text-center">
                        <span class="text-[10px] font-bold text-gold uppercase tracking-wider mb-2">QR Code Preview</span>
                        <?php if (!empty($member['qr_code_url'])): ?>
                            <div class="p-2 bg-white rounded-xl border border-gold/40 shadow-md">
                                <img src="<?php echo htmlspecialchars($member['qr_code_url']); ?>" alt="Personal BEP-20 QR Code" class="w-28 h-28 object-contain">
                            </div>
                            <span class="text-[10px] text-emerald-400 font-mono font-bold mt-2"><i class="fa-solid fa-circle-check"></i> QR Uploaded</span>
                        <?php else: ?>
                            <div class="w-28 h-28 rounded-xl border border-dashed border-gold/30 flex flex-col items-center justify-center text-champagne/40 text-xs">
                                <i class="fa-solid fa-qrcode text-2xl mb-1 text-gold/40"></i>
                                <span>No QR Uploaded</span>
                            </div>
                            <span class="text-[10px] text-champagne/40 mt-2">Upload image to display</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" name="save_p2p_settings" class="gold-button px-6 py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-gold/20 flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Update P2P Wallet Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- 2. EDITABLE ADDRESS & KYC FORM -->
        <form action="/customer/profile.php" method="POST" class="space-y-6">
            <h3 class="text-sm font-bold text-neon-cyan uppercase tracking-wider border-b border-neon-cyan/20 pb-2 flex items-center gap-2">
                <i class="fa-solid fa-location-dot"></i> Address & Identification Details
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-neon-cyan mb-1">Address Line *</label>
                    <input type="text" name="address_line" value="<?php echo htmlspecialchars($member['address_line'] ?? ''); ?>" required placeholder="Building / Street Address" class="w-full bg-navy/80 border border-neon-cyan/30 rounded-xl px-4 py-2.5 text-xs text-ice focus:outline-none focus:border-neon-cyan">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-neon-cyan mb-1">City *</label>
                    <input type="text" name="city" value="<?php echo htmlspecialchars($member['city'] ?? ''); ?>" required placeholder="New York / London" class="w-full bg-navy/80 border border-neon-cyan/30 rounded-xl px-4 py-2.5 text-xs text-ice focus:outline-none focus:border-neon-cyan">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-neon-cyan mb-1">State / Region *</label>
                    <input type="text" name="state" value="<?php echo htmlspecialchars($member['state'] ?? ''); ?>" required placeholder="California / State" class="w-full bg-navy/80 border border-neon-cyan/30 rounded-xl px-4 py-2.5 text-xs text-ice focus:outline-none focus:border-neon-cyan">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-neon-cyan mb-1">Postal Code *</label>
                    <input type="text" name="pincode" value="<?php echo htmlspecialchars($member['pincode'] ?? ''); ?>" required placeholder="10001" class="w-full bg-navy/80 border border-neon-cyan/30 rounded-xl px-4 py-2.5 text-xs text-ice font-mono focus:outline-none focus:border-neon-cyan">
                </div>
            </div>

            <h3 class="text-sm font-bold text-neon-cyan uppercase tracking-wider border-b border-neon-cyan/20 pb-2 flex items-center gap-2 pt-4">
                <i class="fa-solid fa-wallet"></i> USD Crypto Payout Wallet (USDT / Crypto Address)
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-neon-cyan mb-1">Wallet Network *</label>
                    <select name="wallet_network" class="w-full bg-navy/80 border border-neon-cyan/30 rounded-xl px-4 py-2.5 text-xs text-ice focus:outline-none focus:border-neon-cyan">
                        <option value="USDT (BEP20)" <?php echo ($member['wallet_network'] ?? '') === 'USDT (BEP20)' ? 'selected' : ''; ?>>USDT (BEP20)</option>
                        <option value="USDT (TRC20)" <?php echo ($member['wallet_network'] ?? '') === 'USDT (TRC20)' ? 'selected' : ''; ?>>USDT (TRC20)</option>
                        <option value="USDC (ERC20)" <?php echo ($member['wallet_network'] ?? '') === 'USDC (ERC20)' ? 'selected' : ''; ?>>USDC (ERC20)</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-neon-cyan mb-1">Crypto Wallet Receiving Address (USD) *</label>
                    <input type="text" name="crypto_wallet_address" value="<?php echo htmlspecialchars($member['crypto_wallet_address'] ?? ''); ?>" required placeholder="0x..." class="w-full bg-navy/80 border border-neon-cyan/30 rounded-xl px-4 py-2.5 text-xs text-neon-cyan font-mono focus:outline-none focus:border-neon-cyan">
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" name="save_profile" class="neon-button px-8 py-3 rounded-xl text-sm font-bold shadow-lg shadow-neon-cyan/20 flex items-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Save USD Crypto Wallet Details
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
