<?php
$pageTitle = "New Member WhatsApp Welcome & Greeting Portal";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin_login.php");
    exit();
}

$pdo = getDBConnection();
$search = trim($_GET['search'] ?? '');
$selectedMemberId = trim($_GET['member_id'] ?? '');

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

// Get target member for preview generator (default to newest member or selected)
$targetMember = null;
if (!empty($selectedMemberId)) {
    foreach ($members as $m) {
        if ($m['member_id'] === $selectedMemberId) {
            $targetMember = $m;
            break;
        }
    }
}
if (!$targetMember && !empty($members)) {
    $targetMember = $members[0];
}

// Preset Motivational Quotes
$motivationalQuotes = [
    "1" => "Success is not final, failure is not fatal: it is the courage to continue that counts. Your journey to financial freedom starts today! 💎🔥",
    "2" => "The future belongs to those who believe in the beauty of their dreams. Double your path and build your empire! 🚀✨",
    "3" => "Small daily steps lead to massive lifetime achievements. Welcome to a platform built for your ultimate empowerment! 🌟🏆",
    "4" => "Opportunities don't happen, you create them. Together with Empress Two Way 3.0, your growth knows no boundaries! 💎👑"
];

$selectedQuoteKey = $_GET['quote_key'] ?? '1';
$customQuote = trim($_GET['custom_quote'] ?? $motivationalQuotes[$selectedQuoteKey] ?? $motivationalQuotes['1']);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <!-- Header Banner -->
    <div class="glass-card p-6 rounded-3xl border border-gold/30 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-extrabold gold-gradient-text flex items-center gap-3">
                <i class="fa-brands fa-whatsapp text-emerald-400"></i> New Member WhatsApp Welcome Portal
            </h1>
            <p class="text-xs text-champagne/70 mt-1">Generate beautifully formatted WhatsApp credentials, motivational quotes, and portal access links for new members</p>
        </div>

        <!-- Search Bar -->
        <form action="/admin/welcome.php" method="GET" class="flex gap-2 w-full md:w-auto">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search Member ID, Name, Phone..." class="bg-obsidian/80 border border-gold/30 rounded-xl px-4 py-2 text-xs text-champagne focus:outline-none focus:border-gold w-64">
            <button type="submit" class="gold-button px-4 py-2 rounded-xl text-xs font-bold">Search</button>
            <?php if (!empty($search)): ?>
                <a href="/admin/welcome.php" class="glass-card border border-gold/30 hover:bg-gold/10 text-gold px-3 py-2 rounded-xl text-xs flex items-center">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Featured WhatsApp Composer Card -->
    <?php if ($targetMember): ?>
        <?php
        $cleanPhone = preg_replace('/[^0-9]/', '', $targetMember['phone']);
        if (strlen($cleanPhone) === 10) {
            $cleanPhone = '91' . $cleanPhone;
        }

        $pkgName = str_replace('_', ' ', $targetMember['package_type']);
        $loginUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/login.php";
        $cleanTargetName = str_replace(["\u{FFFD}", "\ufffd"], "", trim(preg_replace('/\s*\(Rebirth\s*#.*$/i', '', $targetMember['name'])));
        $cleanCustomQuote = str_replace(["\u{FFFD}", "\ufffd"], "", $customQuote);

        $waText = "WELCOME TO EMPRESS 2 WAY 4.0!\n\n" .
                  "Dear {$cleanTargetName},\n" .
                  "Congratulations and welcome aboard!\n\n" .
                  "YOUR ACCOUNT DETAILS:\n" .
                  "Member ID: {$targetMember['member_id']}\n" .
                  "Full Name: {$cleanTargetName}\n" .
                  "Mobile: {$targetMember['phone']}\n" .
                  "Email: {$targetMember['email']}\n" .
                  "Package: {$pkgName}\n" .
                  "Sponsor ID: " . ($targetMember['sponsor_id'] ?: 'EMP100000') . "\n" .
                  "Placement Parent: " . ($targetMember['placement_parent_id'] ?: 'EMP100000') . "\n\n" .
                  "Portal Login: {$loginUrl}\n\n" .
                  "\"" . $cleanCustomQuote . "\"\n\n" .
                  "Empress 2 Way 4.0 Management Team";

        $waUrl = "https://wa.me/" . $cleanPhone . "?text=" . rawurlencode($waText);
        ?>

        <div class="glass-card p-6 rounded-3xl border border-emerald-500/40 bg-emerald-950/10 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Configurator -->
            <div class="space-y-4">
                <div class="flex items-center gap-2 text-emerald-400 font-bold text-sm border-b border-emerald-500/20 pb-2">
                    <i class="fa-solid fa-sliders"></i> WhatsApp Message Customizer
                </div>

                <form method="GET" action="/admin/welcome.php" class="space-y-3 text-xs">
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>

                    <div>
                        <label class="block font-semibold text-gold mb-1">Select Member to Greet</label>
                        <select name="member_id" onchange="this.form.submit()" class="w-full bg-obsidian border border-gold/30 rounded-xl px-3 py-2 text-champagne focus:outline-none focus:border-gold font-mono">
                            <?php foreach ($members as $m): ?>
                                <?php $nf = formatMemberName($m['name']); ?>
                                <option value="<?php echo $m['member_id']; ?>" <?php echo $m['member_id'] === $targetMember['member_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($m['member_id'] . ' - ' . $nf['clean_name'] . ($nf['is_rebirth'] ? ' [' . $nf['rebirth_label'] . ']' : '') . ' (' . $m['phone'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-gold mb-1">Select Motivational Quote</label>
                        <select name="quote_key" onchange="this.form.submit()" class="w-full bg-obsidian border border-gold/30 rounded-xl px-3 py-2 text-champagne focus:outline-none focus:border-gold">
                            <?php foreach ($motivationalQuotes as $k => $q): ?>
                                <option value="<?php echo $k; ?>" <?php echo $k == $selectedQuoteKey ? 'selected' : ''; ?>>
                                    Quote #<?php echo $k; ?>: <?php echo htmlspecialchars(substr($q, 0, 60)) . '...'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-gold mb-1">Customize Quote / Personal Message</label>
                        <textarea name="custom_quote" rows="3" onblur="this.form.submit()" class="w-full bg-obsidian border border-gold/30 rounded-xl p-3 text-champagne focus:outline-none focus:border-gold text-xs"><?php echo htmlspecialchars($customQuote); ?></textarea>
                    </div>

                    <div class="pt-2">
                        <a href="<?php echo $waUrl; ?>" target="_blank" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold py-3 rounded-xl transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-950/50 text-sm">
                            <i class="fa-brands fa-whatsapp text-lg"></i> Send WhatsApp Welcome Message
                        </a>
                    </div>
                </form>
            </div>

            <!-- Right Live Preview Bubble -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-xs font-bold text-champagne border-b border-gold/20 pb-2">
                    <span class="flex items-center gap-2 text-gold"><i class="fa-solid fa-mobile-screen"></i> WhatsApp Live Formatting Preview</span>
                    <span class="text-emerald-400 font-mono"><i class="fa-solid fa-circle text-[8px]"></i> Ready to Send</span>
                </div>

                <div class="bg-[#0b141a] border border-[#222d34] rounded-2xl p-4 font-sans text-xs text-[#e9edef] whitespace-pre-wrap leading-relaxed max-h-80 overflow-y-auto custom-scrollbar shadow-inner relative">
                    <div class="bg-[#005c4b] text-[#e9edef] p-3.5 rounded-2xl rounded-tl-none shadow-md border border-[#007a63]/40">
<?php echo htmlspecialchars($waText); ?>
                        <div class="text-[10px] text-emerald-200/60 text-right mt-2 font-mono">
                            Just now <i class="fa-solid fa-check-double text-emerald-300 ml-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- All Registered Members Directory -->
    <div class="glass-card p-6 rounded-3xl border border-gold/20 space-y-4">
        <h3 class="text-base font-bold text-gold border-b border-gold/20 pb-2 flex items-center gap-2">
            <i class="fa-solid fa-users"></i> Member Directory - One-Click WhatsApp Greet
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gold/10 border-b border-gold/20 text-gold font-semibold uppercase">
                        <th class="p-3">Member ID</th>
                        <th class="p-3">Member Details</th>
                        <th class="p-3">Sponsor / Parent</th>
                        <th class="p-3">Package</th>
                        <th class="p-3">Joined Date</th>
                        <th class="p-3 text-right">Greet Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gold/10 text-champagne">
                    <?php if (empty($members)): ?>
                        <tr>
                            <td colspan="6" class="p-4 text-center text-champagne/50">No registered members found in directory.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($members as $m): ?>
                            <?php
                            $cleanP = preg_replace('/[^0-9]/', '', $m['phone']);
                            if (strlen($cleanP) === 10) $cleanP = '91' . $cleanP;

                            $pkgN = str_replace('_', ' ', $m['package_type']);
                            $lUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/login.php";
                            $cleanMName = str_replace(["\u{FFFD}", "\ufffd"], "", trim(preg_replace('/\s*\(Rebirth\s*#.*$/i', '', $m['name'])));
                            $cleanCQ = str_replace(["\u{FFFD}", "\ufffd"], "", $customQuote);

                            $mText = "WELCOME TO EMPRESS 2 WAY 4.0!\n\n" .
                                      "Dear {$cleanMName},\n" .
                                      "Congratulations and welcome aboard!\n\n" .
                                      "YOUR ACCOUNT DETAILS:\n" .
                                      "Member ID: {$m['member_id']}\n" .
                                      "Full Name: {$cleanMName}\n" .
                                      "Mobile: {$m['phone']}\n" .
                                      "Email: {$m['email']}\n" .
                                      "Package: {$pkgN}\n" .
                                      "Sponsor ID: " . ($m['sponsor_id'] ?: 'EMP100000') . "\n" .
                                      "Placement Parent: " . ($m['placement_parent_id'] ?: 'EMP100000') . "\n\n" .
                                      "Portal Login: {$lUrl}\n\n" .
                                      "\"" . $cleanCQ . "\"\n\n" .
                                      "Empress 2 Way 4.0 Management Team";

                            $mUrl = "https://wa.me/" . $cleanP . "?text=" . rawurlencode($mText);
                            $isSelected = $targetMember && $targetMember['member_id'] === $m['member_id'];
                            ?>
                            <?php $nameFmt = formatMemberName($m['name']); ?>
                            <tr class="<?php echo $isSelected ? 'bg-gold/10 border-l-4 border-gold' : 'hover:bg-gold/5'; ?> transition">
                                <td class="p-3 font-mono text-gold font-bold text-sm">
                                    <?php echo htmlspecialchars($m['member_id']); ?>
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-bold text-champagne block text-sm"><?php echo htmlspecialchars($nameFmt['clean_name']); ?></span>
                                        <?php if ($nameFmt['is_rebirth']): ?>
                                            <span class="bg-purple-500/20 text-purple-300 border border-purple-500/40 text-[10px] px-1.5 py-0.5 rounded font-mono font-semibold">
                                                <i class="fa-solid fa-rotate-right text-[8px] mr-1"></i><?php echo htmlspecialchars($nameFmt['rebirth_label']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-champagne/60 block text-[11px]"><?php echo htmlspecialchars($m['email']); ?></span>
                                    <span class="font-mono text-gold/80 text-[11px]"><i class="fa-solid fa-phone text-[9px] mr-1"></i><?php echo htmlspecialchars($m['phone']); ?></span>
                                </td>
                                <td class="p-3 font-mono text-champagne/80">
                                    <div>Sp: <span class="text-gold font-semibold"><?php echo htmlspecialchars($m['sponsor_id'] ?: 'ROOT'); ?></span></div>
                                    <div>Par: <span class="text-emerald-400 font-semibold"><?php echo htmlspecialchars($m['placement_parent_id'] ?: 'ROOT'); ?></span> (Pos <?php echo $m['matrix_position'] ?: '1'; ?>)</div>
                                </td>
                                <td class="p-3 font-semibold text-gold"><?php echo $pkgN; ?></td>
                                <td class="p-3 font-mono text-champagne/50"><?php echo $m['created_at']; ?></td>
                                <td class="p-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="/admin/welcome.php?member_id=<?php echo $m['member_id']; ?>" class="bg-gold/10 hover:bg-gold/20 text-gold border border-gold/30 px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1" title="Customize Message">
                                            <i class="fa-solid fa-sliders"></i> Customize
                                        </a>

                                        <a href="<?php echo $mUrl; ?>" target="_blank" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold px-4 py-1.5 rounded-xl text-xs transition shadow-md shadow-emerald-900/30">
                                            <i class="fa-brands fa-whatsapp text-sm"></i> Greet
                                        </a>
                                    </div>
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
