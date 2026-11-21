<?php
// Re-derive category (activity) and branch for every news post from its title.
// Usage: php scripts/retag_news.php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit("CLI only\n");
require __DIR__ . '/../app/bootstrap.php';
use App\Core\DB;
use App\NewsTagger;

$changed = 0;
foreach (DB::all('SELECT id, title, category, branch_id FROM news') as $n) {
    $t = NewsTagger::tag($n['title'], $n['category']);
    $branch = $t['branch_id'] ?? null;
    // keep an existing branch only when the title gives us nothing better
    if ($branch === null && $t['category'] !== $n['category']) $branch = null;
    elseif ($branch === null) $branch = $n['branch_id'] ? (int)$n['branch_id'] : null;
    if ($t['category'] !== $n['category'] || $branch !== ($n['branch_id'] ? (int)$n['branch_id'] : null)) {
        DB::run('UPDATE news SET category = ?, branch_id = ? WHERE id = ?', [$t['category'], $branch, $n['id']]);
        $changed++;
    }
}
echo "Retagged $changed posts.\n";
