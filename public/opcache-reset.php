<?php
if (($_GET['token'] ?? '') !== 'cargo2026secret') { http_response_code(403); die('403'); }
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo json_encode(['success' => true, 'message' => 'OPcache reset successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'OPcache not available.']);
}
