<?php
// Imports ALL posts from the old Blogger site (tvtntj.blogspot.com) into the `news` table.
// Safe to re-run: posts are matched on source_id and updated, never duplicated.
// Usage: php scripts/import_blogger.php [--blog=https://tvtntj.blogspot.com] [--max=0]
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit("CLI only\n");
require __DIR__ . '/../app/bootstrap.php';
use App\Core\DB;

$opts = getopt('', ['blog::', 'max::']);
$blog = rtrim($opts['blog'] ?? 'https://tvtntj.blogspot.com', '/');
$max = (int)($opts['max'] ?? 0);

$branches = DB::pairs('SELECT id, name FROM branches');
$norm = fn(string $s) => preg_replace('/[\s.]+/u', '', preg_replace('/\s*கிளை.*$/u', '', trim($s)));
$branchFor = function (array $labels) use ($branches, $norm): ?int {
    foreach ($labels as $l) foreach ($branches as $id => $name) {
        $a = $norm($l); $b = $norm($name);
        if ($a !== '' && (str_starts_with($a, $b) || str_starts_with($b, $a))) return (int)$id;
    }
    return null;
};

$start = 1; $done = 0; $per = 150;
$ctx = stream_context_create(['http' => ['timeout' => 60, 'user_agent' => 'tvtntj-importer/1.0']]);
while (true) {
    $url = "$blog/feeds/posts/default?alt=json&max-results=$per&start-index=$start";
    $json = @file_get_contents($url, false, $ctx);
    if ($json === false) { fwrite(STDERR, "Fetch failed: $url\n"); exit(1); }
    $entries = json_decode($json, true)['feed']['entry'] ?? [];
    if (!$entries) break;
    foreach ($entries as $p) {
        $sid = substr(strrchr($p['id']['$t'], '-'), 1);
        $title = trim(html_entity_decode(strip_tags($p['title']['$t'] ?? ''), ENT_QUOTES, 'UTF-8')) ?: 'பதிவு';
        $body = $p['content']['$t'] ?? ($p['summary']['$t'] ?? '');
        preg_match('/<img[^>]+src="([^"]+)"/i', $body, $m);
        $labels = array_map(fn($c) => $c['term'], $p['category'] ?? []);
        $branchId = $branchFor($labels);
        $cat = null;
        foreach ($labels as $l) if (!$branchFor([$l])) { $cat = $l; break; }
        $cat = $cat === 'Monthly points' ? 'மாதாந்திர புள்ளிப்பட்டியல்' : ($cat ?? ($branchId ? 'கிளை செய்திகள்' : 'செய்திகள்'));
        // Titles like "கரும்பலகை தாவா - வழுத்தூர் கிளை" are more reliable than Blogger labels.
        $t = \App\NewsTagger::tag($title, $cat);
        $cat = $t['category'] ?? $cat;
        $branchId = $t['branch_id'] ?? $branchId;
        $published = date('Y-m-d H:i:s', strtotime($p['published']['$t']));
        DB::run('INSERT INTO news (title, slug, category, branch_id, image, body, source_id, published_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE title = VALUES(title), category = VALUES(category), branch_id = VALUES(branch_id),
                                         image = VALUES(image), body = VALUES(body), published_at = VALUES(published_at)',
            [mb_substr($title, 0, 500), 'post-' . $sid, $cat, $branchId, $m[1] ?? null, safe_html($body), $sid, $published]);
        $done++;
        if ($max && $done >= $max) break 2;
    }
    echo "Imported $done posts…\n";
    $start += $per;
}
echo "Done: $done posts imported/updated.\n";
