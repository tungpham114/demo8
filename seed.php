<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$sqlFile = __DIR__ . '/TOURBUSINESSPROPOSAL8.sql';
if (!is_file($sqlFile)) {
    exit("Không tìm thấy file TOURBUSINESSPROPOSAL8.sql\n");
}
$sql = (string)file_get_contents($sqlFile);
db()->exec($sql);

if (PHP_SAPI === 'cli') {
    echo "Seed complete. Database TOURBUSINESSPROPOSAL8 has been initialized.\n";
} else {
    flash('Đã khôi phục dữ liệu mặc định thành công!');
    go('home');
}
