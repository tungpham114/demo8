<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$type = (string)($_GET['type'] ?? '');

// Tạo tệp CSV danh sách hành khách
if ($type === 'passenger_template') {
    $intl = !empty($_GET['intl']);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="danh-sach-hanh-khach' . ($intl ? '-quoc-te' : '') . '.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM để mở trực tiếp bằng Microsoft Excel không lỗi tiếng Việt
    if ($intl) {
        echo "Ho_ten,Gioi_tinh(male/female/other),Ngay_sinh(YYYY-MM-DD),So_dien_thoai,Ghi_chu,So_ho_chieu,Han_ho_chieu(YYYY-MM-DD),Quoc_tich\n";
    } else {
        echo "Ho_ten,Gioi_tinh(male/female/other),Ngay_sinh(YYYY-MM-DD),So_dien_thoai,Ghi_chu\n";
    }
    exit;
}

$user = me();
if (!$user || !in_array($user['role'], ['admin', 'seller'], true)) {
    http_response_code(403);
    exit('Không có quyền truy cập.');
}

$seller = q('SELECT * FROM sellers WHERE id=?', [(int)($_GET['id'] ?? 0)])->fetch();
if (!$seller || ($user['role'] === 'seller' && (int)$seller['user_id'] !== (int)$user['id'])) {
    http_response_code(404);
    exit('Không tìm thấy hồ sơ Seller.');
}

$path = $type === 'business' ? $seller['business_license'] : ($type === 'travel' ? $seller['travel_license'] : '');
if ($path && str_starts_with($path, 'private/')) {
    $full = realpath(__DIR__ . '/' . $path);
    $root = realpath(__DIR__ . '/private');
    if ($full && $root && str_starts_with($full, $root . DIRECTORY_SEPARATOR) && is_file($full)) {
        header('Content-Type: ' . (new finfo(FILEINFO_MIME_TYPE))->file($full));
        header('Content-Disposition: inline; filename="giay-phep-' . $type . '.' . pathinfo($full, PATHINFO_EXTENSION) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($full);
        exit;
    }
}

// Render hình ảnh SVG trực quan của Giấy phép kinh doanh / Giấy phép lữ hành để Admin & Seller kiểm tra ngay trên giao diện
header('Content-Type: image/svg+xml; charset=UTF-8');
$docTitle = $type === 'business' ? 'GIẤY CHỨNG NHẬN ĐĂNG KÝ DOANH NGHIỆP' : 'GIẤY PHÉP KINH DOANH DỊCH VỤ LỮ HÀNH';
$subTitle = $type === 'business'
    ? 'SỞ KẾ HOẠCH VÀ ĐẦU TƯ TP. HÀ NỘI — PHÒNG ĐĂNG KÝ KINH DOANH'
    : 'CỤC DU LỊCH QUỐC GIA VIỆT NAM (' . ($seller['travel_type'] === 'international' ? 'LỮ HÀNH QUỐC TẾ & NỘI ĐỊA' : 'LỮ HÀNH NỘI ĐỊA') . ')';
$company = htmlspecialchars((string)$seller['company_name'], ENT_QUOTES, 'UTF-8');
$tax = htmlspecialchars((string)$seller['tax_code'], ENT_QUOTES, 'UTF-8');
$rep = htmlspecialchars((string)$seller['representative'], ENT_QUOTES, 'UTF-8');
$addr = htmlspecialchars((string)$seller['address'], ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars((string)$seller['phone'], ENT_QUOTES, 'UTF-8');
$codeNo = $type === 'business' ? 'ĐKKD-' . $tax : 'GP-LHQT-' . substr($tax, -6) . '/CDLQGVN';

echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 540" width="100%" height="100%">
  <rect width="800" height="540" fill="#fdfbf6" stroke="#c89d54" stroke-width="8" rx="12"/>
  <rect x="16" y="16" width="768" height="508" fill="none" stroke="#e4cfa3" stroke-width="2" rx="8"/>
  <text x="400" y="58" text-anchor="middle" font-family="sans-serif" font-size="14" font-weight="bold" fill="#173c4b">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</text>
  <text x="400" y="80" text-anchor="middle" font-family="sans-serif" font-size="13" font-weight="bold" fill="#637580">Độc lập - Tự do - Hạnh phúc</text>
  <line x1="310" y1="92" x2="490" y2="92" stroke="#cb7550" stroke-width="2"/>
  <text x="400" y="130" text-anchor="middle" font-family="sans-serif" font-size="12" font-weight="bold" fill="#24705e">{$subTitle}</text>
  <text x="400" y="166" text-anchor="middle" font-family="sans-serif" font-size="22" font-weight="bold" fill="#aa5636">{$docTitle}</text>
  <text x="400" y="194" text-anchor="middle" font-family="sans-serif" font-size="14" font-weight="bold" fill="#173c4b">Số chứng nhận: {$codeNo}</text>

  <g font-family="sans-serif" font-size="15" fill="#173c4b">
    <text x="70" y="245" font-weight="bold">1. Tên doanh nghiệp:</text>
    <text x="255" y="245" font-weight="bold" fill="#0f2d38">{$company}</text>

    <text x="70" y="285" font-weight="bold">2. Mã số doanh nghiệp / MST:</text>
    <text x="305" y="285">{$tax}</text>

    <text x="70" y="325" font-weight="bold">3. Địa chỉ trụ sở chính:</text>
    <text x="255" y="325">{$addr}</text>

    <text x="70" y="365" font-weight="bold">4. Người đại diện theo pháp luật:</text>
    <text x="325" y="365">{$rep} — ĐT: {$phone}</text>

    <text x="70" y="405" font-weight="bold">5. Phạm vi hoạt động:</text>
    <text x="255" y="405">Kinh doanh dịch vụ lữ hành hợp pháp trên lãnh thổ Việt Nam &amp; Quốc tế</text>
  </g>

  <g transform="translate(580, 435)">
    <circle cx="0" cy="0" r="52" fill="none" stroke="#c93b2b" stroke-width="3" stroke-dasharray="6,3"/>
    <circle cx="0" cy="0" r="44" fill="rgba(201,59,43,0.08)" stroke="#c93b2b" stroke-width="1.5"/>
    <text x="0" y="-8" text-anchor="middle" font-family="sans-serif" font-size="11" font-weight="bold" fill="#c93b2b">ĐÃ ĐỐI CHIẾU</text>
    <text x="0" y="10" text-anchor="middle" font-family="sans-serif" font-size="12" font-weight="bold" fill="#c93b2b">BẢN GỐC HỢP LỆ</text>
    <text x="0" y="26" text-anchor="middle" font-family="sans-serif" font-size="9" fill="#c93b2b">VIVU VIỆT VERIFIED</text>
  </g>
  <text x="70" y="485" font-family="sans-serif" font-size="12" fill="#637580">Tệp hồ sơ điện tử phục vụ đối chiếu xác minh Seller</text>
</svg>
SVG;
