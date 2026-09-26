<?php
$pageTitle = "SQL Member Import & Bulk Registration Console";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['superadmin_id'])) {
    header("Location: /superadmin_login.php");
    exit();
}

$pdo = getDBConnection();
$results = [];
$errorMsg = '';
$countSuccess = 0;
$countFail = 0;

/**
 * Parse SQL INSERT statement into associative arrays of member attributes
 */
function parseMemberInsertSQL($sql) {
    $sql = trim($sql);
    if (empty($sql)) return [];

    // Match column names if present
    $columns = [];
    if (preg_match('/INSERT\s+INTO\s+`?members`?\s*\(([^)]+)\)\s*VALUES/i', $sql, $colMatch)) {
        $rawCols = explode(',', $colMatch[1]);
        foreach ($rawCols as $c) {
            $columns[] = strtolower(trim($c, " `'\t\n\r\0\x0B"));
        }
    }

    // Extract VALUES tuples
    if (!preg_match('/VALUES\s*(.+)/is', $sql, $valMatch)) {
        return [];
    }

    $valuesPart = trim($valMatch[1]);
    preg_match_all('/\(([^)]+)\)/s', $valuesPart, $tuples);

    $parsedRows = [];

    if (!empty($tuples[1])) {
        foreach ($tuples[1] as $rawTuple) {
            $rowValues = str_getcsv($rawTuple, ',', "'");

            $item = [];
            if (!empty($columns) && count($columns) === count($rowValues)) {
                for ($i = 0; $i < count($columns); $i++) {
                    $item[$columns[$i]] = trim($rowValues[$i]);
                }
            } else {
                $item['name'] = trim($rowValues[0] ?? 'Burfee Cart');
                $item['email'] = trim($rowValues[1] ?? '');
                $item['phone'] = trim($rowValues[2] ?? '');
                $item['sponsor_id'] = trim($rowValues[3] ?? 'EMP100000');
            }
            $parsedRows[] = $item;
        }
    }

    return $parsedRows;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sqlInput = trim($_POST['sql_statement'] ?? '');
    $singleCount = (int)($_POST['single_count'] ?? 1);
    $actionType = $_POST['action_type'] ?? 'sql';

    if ($actionType === 'sql' && !empty($sqlInput)) {
        $parsed = parseMemberInsertSQL($sqlInput);

        if (empty($parsed)) {
            $lines = explode("\n", $sqlInput);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line) && strpos($line, '--') !== 0 && strpos($line, '/*') !== 0) {
                    $regRes = registerMemberWithoutEpin($pdo, [
                        'sponsor_id' => 'EMP100000',
                        'name' => 'Burfee Cart'
                    ]);
                    if ($regRes['success']) {
                        $countSuccess++;
                        $results[] = $regRes;
                    } else {
                        $countFail++;
                        $results[] = $regRes;
                    }
                }
            }
        } else {
            foreach ($parsed as $data) {
                $regRes = registerMemberWithoutEpin($pdo, $data);
                if ($regRes['success']) {
                    $countSuccess++;
                    $results[] = $regRes;
                } else {
                    $countFail++;
                    $results[] = $regRes;
                }
            }
        }
    } elseif ($actionType === 'bulk_generator' && $singleCount > 0) {
        $sponsorId = trim($_POST['bulk_sponsor_id'] ?? 'EMP100000');
        $bulkName = trim($_POST['bulk_name'] ?? 'Burfee Cart');

        for ($i = 1; $i <= $singleCount; $i++) {
            $regRes = registerMemberWithoutEpin($pdo, [
                'sponsor_id' => $sponsorId,
                'name' => $bulkName . ($singleCount > 1 ? " #{$i}" : '')
            ]);
            if ($regRes['success']) {
                $countSuccess++;
                $results[] = $regRes;
            } else {
                $countFail++;
                $results[] = $regRes;
            }
        }
    } else {
        $errorMsg = "Please enter a valid SQL INSERT statement or count.";
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="space-y-6">
    <div class="glass-card p-6 rounded-3xl border border-neon-cyan/30 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-extrabold neon-gradient-text">Super Admin SQL Member Import & Direct Registration Engine</h1>
            <p class="text-xs text-ice/70 mt-1">Paste SQL INSERT statements or bulk execute direct registrations without ePINs. Triggers global BFS spillover, wallet setup, and 50:50 payouts automatically.</p>
        </div>
    </div>

    <?php if ($errorMsg): ?>
        <div class="bg-red-500/10 border border-red-500/40 text-red-300 p-4 rounded-xl text-xs">
            <?php echo htmlspecialchars($errorMsg); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($results)): ?>
        <div class="glass-card p-6 rounded-3xl border border-emerald-500/40 space-y-4">
            <div class="flex justify-between items-center border-b border-neon-cyan/20 pb-3">
                <h3 class="text-base font-bold text-emerald-400 flex items-center gap-2">
                    <i class="fa-solid fa-circle-check"></i> Execution Results Summary
                </h3>
                <div class="text-xs font-mono">
                    <span class="text-emerald-400 font-bold"><?php echo $countSuccess; ?> Success</span> |
                    <span class="text-red-400 font-bold"><?php echo $countFail; ?> Failed</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-neon-cyan/10 border-b border-neon-cyan/20 text-neon-cyan font-semibold uppercase">
                            <th class="p-2.5">Generated ID</th>
                            <th class="p-2.5">Member Name</th>
                            <th class="p-2.5">Placement Parent</th>
                            <th class="p-2.5">Position</th>
                            <th class="p-2.5">Sponsor</th>
                            <th class="p-2.5">Status Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neon-cyan/10 text-ice font-mono text-[11px]">
                        <?php foreach ($results as $res): ?>
                            <tr>
                                <td class="p-2.5 font-bold text-neon-cyan"><?php echo htmlspecialchars($res['member_id'] ?? 'N/A'); ?></td>
                                <td class="p-2.5 font-sans font-semibold"><?php echo htmlspecialchars($res['name'] ?? 'Burfee Cart'); ?></td>
                                <td class="p-2.5 text-emerald-400"><?php echo htmlspecialchars($res['placement_parent_id'] ?? 'N/A'); ?></td>
                                <td class="p-2.5"><?php echo htmlspecialchars($res['matrix_position'] ?? '-'); ?></td>
                                <td class="p-2.5 text-ice/70"><?php echo htmlspecialchars($res['sponsor_id'] ?? 'ROOT'); ?></td>
                                <td class="p-2.5 font-sans <?php echo $res['success'] ? 'text-emerald-300' : 'text-red-300'; ?>">
                                    <?php echo htmlspecialchars($res['message']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- SQL Import Form -->
        <div class="lg:col-span-2 glass-card p-6 rounded-3xl border border-neon-cyan/20 space-y-4">
            <h3 class="text-base font-bold text-neon-cyan border-b border-neon-cyan/20 pb-2 flex items-center gap-2">
                <i class="fa-solid fa-code"></i> Paste SQL INSERT Statement
            </h3>
            <p class="text-xs text-ice/70">
                Copy and paste your SQL `INSERT INTO members` statements below. The system will parse each row, execute a direct registration without ePINs, auto-spillover into the 3-matrix tree, initialize wallets, and credit matrix level commissions up 6 levels.
            </p>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="action_type" value="sql">
                <div>
                    <label class="block text-xs font-semibold text-neon-cyan mb-1">SQL Query Input</label>
                    <textarea name="sql_statement" rows="8" placeholder="INSERT INTO members (name, email, phone, sponsor_id) VALUES
('Burfee Cart 1', 'member1@example.com', '9876543210', 'EMP100000'),
('Burfee Cart 2', 'member2@example.com', '9876543211', 'EMP100000');" class="w-full bg-obsidian/80 border border-neon-cyan/30 rounded-xl p-3 text-xs text-ice font-mono focus:outline-none focus:border-neon-cyan"></textarea>
                </div>

                <div class="flex justify-between items-center pt-2">
                    <span class="text-[11px] text-ice/50">Supports bulk INSERT statements with multiple tuple values.</span>
                    <button type="submit" class="bg-neon-cyan/20 hover:bg-neon-cyan/30 text-neon-cyan border border-neon-cyan/40 px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2">
                        <i class="fa-solid fa-play"></i> Execute SQL Member Imports
                    </button>
                </div>
            </form>
        </div>

        <!-- Quick Bulk Generator -->
        <div class="glass-card p-6 rounded-3xl border border-neon-cyan/20 space-y-4">
            <h3 class="text-base font-bold text-neon-cyan border-b border-neon-cyan/20 pb-2 flex items-center gap-2">
                <i class="fa-solid fa-bolt"></i> Quick Bulk Node Generator
            </h3>
            <p class="text-xs text-ice/70">
                Generate multiple active member nodes directly without pasting SQL.
            </p>

            <form method="POST" class="space-y-4 text-xs">
                <input type="hidden" name="action_type" value="bulk_generator">

                <div>
                    <label class="block font-semibold text-neon-cyan mb-1">Sponsor ID</label>
                    <input type="text" name="bulk_sponsor_id" value="EMP100000" class="w-full bg-obsidian/80 border border-neon-cyan/30 rounded-xl px-3 py-2 text-ice focus:outline-none focus:border-neon-cyan font-mono">
                </div>

                <div>
                    <label class="block font-semibold text-neon-cyan mb-1">Member Name Prefix</label>
                    <input type="text" name="bulk_name" value="Burfee Cart" class="w-full bg-obsidian/80 border border-neon-cyan/30 rounded-xl px-3 py-2 text-ice focus:outline-none focus:border-neon-cyan">
                </div>

                <div>
                    <label class="block font-semibold text-neon-cyan mb-1">Number of Members to Create</label>
                    <input type="number" name="single_count" value="3" min="1" max="100" class="w-full bg-obsidian/80 border border-neon-cyan/30 rounded-xl px-3 py-2 text-ice focus:outline-none focus:border-neon-cyan font-mono">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3 rounded-xl transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-user-plus"></i> Generate Member Nodes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
