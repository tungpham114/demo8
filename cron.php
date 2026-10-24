<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$processed = run_automated_tasks();
if (PHP_SAPI === 'cli') {
    echo "Processed $processed automated booking/departure transitions.\n";
} else {
    flash("Đã chạy tác vụ tự động: xử lý $processed mốc hạn / trạng thái.");
    $back = $_SERVER['HTTP_REFERER'] ?? url('home');
    header('Location: ' . $back);
    exit;
}
