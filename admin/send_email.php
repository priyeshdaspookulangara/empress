<?php
$pageTitle = "HTML Email Dispatcher Console";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin_login.php");
    exit();
}

$pdo = getDBConnection();
$msg = '';
$err = '';

// Handle Email Dispatch
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipientType = $_POST['recipient_type'] ?? 'all';
    $specificMemberId = trim($_POST['specific_member_id'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $emailBody = $_POST['email_body'] ?? '';

    if (empty($subject) || empty($emailBody)) {
        $err = "Please provide both Subject and Email Body content.";
    } else {
        $targetMembers = [];

        if ($recipientType === 'single' && !empty($specificMemberId)) {
            $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ? OR email = ?");
            $stmt->execute([$specificMemberId, $specificMemberId]);
            $targetMembers = $stmt->fetchAll();
        } elseif ($recipientType === 'active') {
            $stmt = $pdo->query("SELECT * FROM members WHERE status = 'Active'");
            $targetMembers = $stmt->fetchAll();
        } else {
            $stmt = $pdo->query("SELECT * FROM members");
            $targetMembers = $stmt->fetchAll();
        }

        if (empty($targetMembers)) {
            $err = "No matching member accounts found for recipient selection.";
        } else {
            $sentCount = 0;
            foreach ($targetMembers as $m) {
                // Parse dynamic {{tags}} in subject and body
                $parsedSubject = renderEmailTemplate($subject, $m);
                $parsedBody = renderEmailTemplate($emailBody, $m);
                if (sendEmpressHtmlEmail($m['email'], $parsedSubject, $parsedBody)) {
                    $sentCount++;
                }
            }
            $msg = "HTML Email successfully dispatched to {$sentCount} recipient(s) from info@empress2way.com!";
        }
    }
}

// Fetch member list for dropdown selection
$allMembersList = $pdo->query("SELECT member_id, name, email FROM members ORDER BY id DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-8 max-w-5xl mx-auto">
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/30 flex justify-between items-center flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-extrabold neon-gradient-text flex items-center gap-2">
                <i class="fa-solid fa-paper-plane"></i> HTML Email Dispatcher Console
            </h1>
            <p class="text-xs text-ice/70 mt-1">Send custom formatted HTML emails to network members from <code class="text-gold font-mono">info@empress2way.com</code> with dynamic <code class="text-neon-cyan font-mono">{{tags}}</code>.</p>
        </div>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/40 text-emerald-300 p-4 rounded-xl text-xs flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-base text-emerald-400"></i>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($err)): ?>
        <div class="bg-red-500/10 border border-red-500/40 text-red-300 p-4 rounded-xl text-xs flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation text-base text-red-400"></i>
            <span><?php echo htmlspecialchars($err); ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Email Form Column -->
        <div class="lg:col-span-2 space-y-6">
            <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20">
                <form action="/admin/send_email.php" method="POST" class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-neon-cyan uppercase mb-2">Recipient Group</label>
                        <select name="recipient_type" id="recipientType" onchange="toggleRecipientField()" class="w-full bg-obsidian border border-neon-cyan/30 rounded-xl px-4 py-3 text-xs text-ice focus:outline-none focus:border-neon-cyan">
                            <option value="all">All Registered Network Members</option>
                            <option value="active">Active Members Only</option>
                            <option value="single">Single Specific Member Account</option>
                        </select>
                    </div>

                    <div id="singleMemberContainer" class="hidden">
                        <label class="block text-xs font-bold text-neon-cyan uppercase mb-2">Select Member / Enter ID</label>
                        <select name="specific_member_id" class="w-full bg-obsidian border border-neon-cyan/30 rounded-xl px-4 py-3 text-xs text-ice focus:outline-none focus:border-neon-cyan">
                            <option value="">-- Choose Member --</option>
                            <?php foreach ($allMembersList as $sm): ?>
                                <option value="<?php echo htmlspecialchars($sm['member_id']); ?>">
                                    <?php echo htmlspecialchars($sm['member_id'] . ' - ' . $sm['name'] . ' (' . $sm['email'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neon-cyan uppercase mb-2">Email Subject Line</label>
                        <input type="text" name="subject" required placeholder="Important Network Announcement for {{name}}" class="w-full bg-obsidian border border-neon-cyan/30 rounded-xl px-4 py-3 text-xs text-ice focus:outline-none focus:border-neon-cyan">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neon-cyan uppercase mb-2">HTML Email Body Content</label>
                        <textarea name="email_body" id="emailBody" rows="10" required class="w-full bg-obsidian border border-neon-cyan/30 rounded-xl p-4 text-xs text-ice font-mono focus:outline-none focus:border-neon-cyan leading-relaxed"><h2 style="color: #c5a059; margin-top:0;">Hello {{name}},</h2>
<p>We are excited to share an important platform update regarding your 3-matrix account (<strong>{{member_id}}</strong>).</p>

<div style="background: rgba(197, 160, 89, 0.1); border: 1px solid rgba(197, 160, 89, 0.3); padding: 15px; border-radius: 10px; margin: 20px 0;">
    <p style="margin: 5px 0;"><strong>Member ID:</strong> {{member_id}}</p>
    <p style="margin: 5px 0;"><strong>Registered Email:</strong> {{email}}</p>
    <p style="margin: 5px 0;"><strong>Phone:</strong> {{phone}}</p>
    <p style="margin: 5px 0;"><strong>Package:</strong> {{package_type}}</p>
</div>

<p>Double your path and empower your future with Empress Two Way 4.0!</p>
<a href="{{site_url}}/login.php" class="btn">Login to Dashboard</a></textarea>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full bg-neon-cyan/20 hover:bg-neon-cyan/30 text-neon-cyan border border-neon-cyan/50 py-3.5 rounded-xl font-bold text-sm flex items-center justify-center gap-2 shadow-lg shadow-neon-cyan/10">
                            <i class="fa-solid fa-paper-plane"></i> Dispatch HTML Email
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Dynamic Tags Reference Guide Column -->
        <div class="space-y-6">
            <div class="glass-card p-6 rounded-3xl border border-gold/30">
                <h3 class="text-sm font-bold text-gold uppercase mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-code"></i> Dynamic Tag Guide
                </h3>
                <p class="text-xs text-champagne/70 mb-4 leading-relaxed">
                    Insert any field tag enclosed in <code class="text-gold font-mono">{{tag_name}}</code>. During dispatch, the engine replaces tags with recipient values automatically:
                </p>

                <div class="space-y-2 text-xs font-mono">
                    <div class="p-2.5 rounded bg-obsidian/80 border border-gold/20 flex justify-between">
                        <span class="text-gold">{{member_id}}</span>
                        <span class="text-champagne/60 font-sans">e.g. EMP100001</span>
                    </div>
                    <div class="p-2.5 rounded bg-obsidian/80 border border-gold/20 flex justify-between">
                        <span class="text-gold">{{name}}</span>
                        <span class="text-champagne/60 font-sans">Member Name</span>
                    </div>
                    <div class="p-2.5 rounded bg-obsidian/80 border border-gold/20 flex justify-between">
                        <span class="text-gold">{{email}}</span>
                        <span class="text-champagne/60 font-sans">Customer Email</span>
                    </div>
                    <div class="p-2.5 rounded bg-obsidian/80 border border-gold/20 flex justify-between">
                        <span class="text-gold">{{phone}}</span>
                        <span class="text-champagne/60 font-sans">Phone Number</span>
                    </div>
                    <div class="p-2.5 rounded bg-obsidian/80 border border-gold/20 flex justify-between">
                        <span class="text-gold">{{sponsor_id}}</span>
                        <span class="text-champagne/60 font-sans">Direct Sponsor ID</span>
                    </div>
                    <div class="p-2.5 rounded bg-obsidian/80 border border-gold/20 flex justify-between">
                        <span class="text-gold">{{package_type}}</span>
                        <span class="text-champagne/60 font-sans">Royal_Starter</span>
                    </div>
                    <div class="p-2.5 rounded bg-obsidian/80 border border-gold/20 flex justify-between">
                        <span class="text-gold">{{site_url}}</span>
                        <span class="text-champagne/60 font-sans">https://...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleRecipientField() {
    const val = document.getElementById('recipientType').value;
    const container = document.getElementById('singleMemberContainer');
    if (val === 'single') {
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
