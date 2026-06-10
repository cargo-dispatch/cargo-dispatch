<?php
if (($_GET['token'] ?? '') !== 'cargo2026secret') { http_response_code(403); die('403'); }
$log = __DIR__ . '/../storage/logs/laravel.log';
if (!file_exists($log)) { die('No log file found.'); }
$lines = array_slice(file($log), -100);
header('Content-Type: text/plain');
echo implode('', $lines);
