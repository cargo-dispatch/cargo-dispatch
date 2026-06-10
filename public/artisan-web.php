<?php

// Simple security token — change this to something secret
define('SECRET', 'cargo2026secret');

if (($_GET['token'] ?? '') !== SECRET) {
    http_response_code(403);
    die('<h2 style="color:red;font-family:sans-serif">403 — Invalid token</h2>');
}

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

function run($cmd) {
    ob_start();
    $exit = \Illuminate\Support\Facades\Artisan::call($cmd);
    $output = ob_get_clean();
    $output = \Illuminate\Support\Facades\Artisan::output();
    return ['exit' => $exit, 'output' => $output ?: '(done)'];
}

$kernel->bootstrap();

$commands = [
    'config:cache'   => 'Cache config',
    'config:clear'   => 'Clear config cache',
    'route:cache'    => 'Cache routes',
    'route:clear'    => 'Clear route cache',
    'view:cache'     => 'Cache views',
    'view:clear'     => 'Clear view cache',
    'cache:clear'    => 'Clear application cache',
    'migrate --force'        => 'Run migrations',
    'migrate:status'         => 'Migration status',
    'storage:link'   => 'Create storage symlink',
    'optimize'       => 'Optimize (cache config+routes+views)',
    'optimize:clear' => 'Clear all caches',
    'queue:restart'  => 'Restart queue workers',
];

$result = null;
$ran    = null;

if (isset($_GET['cmd']) && array_key_exists($_GET['cmd'], $commands)) {
    $ran    = $_GET['cmd'];
    $result = run($_GET['cmd']);
}

$token = htmlspecialchars(SECRET);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CargoDispatch — Server Tools</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; padding: 30px 16px; }
  .wrap { max-width: 700px; margin: 0 auto; }
  h1 { font-size: 22px; color: #f8fafc; margin-bottom: 4px; }
  .sub { font-size: 13px; color: #64748b; margin-bottom: 28px; }
  .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 28px; }
  @media(max-width:500px){ .grid { grid-template-columns: 1fr; } }
  a.btn {
    display: block; padding: 12px 16px; border-radius: 10px;
    background: #1e293b; border: 1px solid #334155;
    text-decoration: none; color: #e2e8f0;
    font-size: 13px; transition: background .15s;
  }
  a.btn:hover { background: #334155; border-color: #4e73df; }
  a.btn .label { font-weight: 600; color: #f1f5f9; }
  a.btn .cmd  { font-size: 11px; color: #4e73df; font-family: monospace; margin-top: 3px; }
  .result { background: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 20px; }
  .result h3 { font-size: 14px; color: #4e73df; margin-bottom: 12px; }
  .result h3 span { font-family: monospace; }
  pre { background: #0f172a; border-radius: 8px; padding: 14px; font-size: 12px; color: #a3e635; overflow-x: auto; white-space: pre-wrap; word-break: break-all; }
  .badge { display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600; margin-left:8px; }
  .ok  { background:#16a34a22; color:#4ade80; border:1px solid #16a34a55; }
  .err { background:#dc262622; color:#f87171; border:1px solid #dc262655; }
  .warn { font-size:12px; color:#94a3b8; margin-top:16px; padding:10px; background:#0f172a; border-radius:8px; border:1px solid #1e293b; }
</style>
</head>
<body>
<div class="wrap">
  <h1>🚛 CargoDispatch — Server Tools</h1>
  <p class="sub">Run artisan commands on the live server. Keep this URL private.</p>

  <?php if ($result): ?>
  <div class="result" style="margin-bottom:24px;">
    <h3>
      Ran: <span>php artisan <?= htmlspecialchars($ran) ?></span>
      <span class="badge <?= $result['exit'] === 0 ? 'ok' : 'err' ?>">
        <?= $result['exit'] === 0 ? 'SUCCESS' : 'ERROR ' . $result['exit'] ?>
      </span>
    </h3>
    <pre><?= htmlspecialchars($result['output']) ?></pre>
    <p style="margin-top:12px;"><a href="?token=<?= $token ?>" style="color:#4e73df;font-size:13px;">← Back</a></p>
  </div>
  <?php endif; ?>

  <div class="grid">
    <?php foreach ($commands as $cmd => $label): ?>
    <a class="btn" href="?token=<?= $token ?>&cmd=<?= urlencode($cmd) ?>"
       <?= in_array($cmd, ['migrate']) ? 'onclick="return confirm(\'Run migrate on live DB?\')"' : '' ?>>
      <div class="label"><?= htmlspecialchars($label) ?></div>
      <div class="cmd">php artisan <?= htmlspecialchars($cmd) ?></div>
    </a>
    <?php endforeach; ?>
  </div>

  <p class="warn">⚠️ Delete or restrict this file after use. URL: <code>artisan-web.php?token=<?= $token ?></code></p>
</div>
</body>
</html>
