<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Ho_Chi_Minh');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

const APP_NAME = 'ViVu Việt';
const APP_DIR = 'TOURBUSINESSPROPOSAL8';

const CANCEL_POLICY_FRAME = [
    'Linh hoạt: hủy trước 7 ngày được hoàn 100%',
    'Tiêu chuẩn: hủy trước 7 ngày hoàn 80%, trước 3 ngày hoàn 50%',
    'Chặt chẽ: hủy trước 14 ngày hoàn 70%, dưới 7 ngày không hoàn',
];

function db(): PDO {
    static $connection = null;
    if ($connection) return $connection;
    $host = getenv('TOUR_DB_HOST') ?: '127.0.0.1';
    $port = getenv('TOUR_DB_PORT') ?: '3306';
    $name = 'TOURBUSINESSPROPOSAL8';
    $user = getenv('TOUR_DB_USER') ?: 'root';
    $password = getenv('TOUR_DB_PASSWORD') ?: '';
    try {
        $connection = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        // Tự động tạo database nếu trên localhost chưa khởi tạo
        $rootPdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $connection = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    $connection->exec("SET time_zone = '+07:00'");
    ensure_schema_and_seed($connection);
    return $connection;
}

function q(string $sql, array $args = []): PDOStatement {
    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    return $stmt;
}

function ensure_schema_and_seed(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        $hasUsers = (bool)$pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
        if (!$hasUsers && is_file(__DIR__ . '/TOURBUSINESSPROPOSAL8.sql')) {
            $sql = (string)file_get_contents(__DIR__ . '/TOURBUSINESSPROPOSAL8.sql');
            $pdo->exec($sql);
            return;
        }

        // Tự động nâng cấp bảng cũ nếu người dùng dùng CSDL phiên bản trước
        $cols = fn(string $table): array => array_column($pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC), 'Field');

        $uCols = $cols('users');
        if (!in_array('phone', $uCols, true)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(25) NULL DEFAULT NULL AFTER email");
        }

        $tCols = $cols('tours');
        if (!in_array('code', $tCols, true)) {
            $pdo->exec("ALTER TABLE tours ADD COLUMN code VARCHAR(32) NULL AFTER id");
        }
        if (!in_array('gallery_json', $tCols, true)) {
            $pdo->exec("ALTER TABLE tours ADD COLUMN gallery_json TEXT NULL AFTER image_path");
        }
        $pdo->exec("ALTER TABLE tours MODIFY COLUMN status ENUM('draft','pending','approved','selling','rejected','paused') NOT NULL DEFAULT 'draft'");
        $pdo->exec("ALTER TABLE tours MODIFY COLUMN cancellation_policy VARCHAR(160) NOT NULL");

        $dCols = $cols('departures');
        if (!in_array('code', $dCols, true)) {
            $pdo->exec("ALTER TABLE departures ADD COLUMN code VARCHAR(32) NULL AFTER id");
        }
        $pdo->exec("ALTER TABLE departures MODIFY COLUMN status ENUM('open','full','closed','cancelled') NOT NULL DEFAULT 'open'");

        $bCols = $cols('bookings');
        if (!in_array('passenger_status', $bCols, true)) {
            $pdo->exec("ALTER TABLE bookings ADD COLUMN passenger_status ENUM('incomplete','completed') NOT NULL DEFAULT 'incomplete' AFTER policy_snapshot");
        }
        foreach (['reminded_seller_4h', 'reminded_deposit_12h', 'reminded_deposit_2h', 'reminded_balance'] as $rCol) {
            if (!in_array($rCol, $bCols, true)) {
                $pdo->exec("ALTER TABLE bookings ADD COLUMN `$rCol` TINYINT(1) NOT NULL DEFAULT 0");
            }
        }
        $pdo->exec("ALTER TABLE bookings MODIFY COLUMN policy_snapshot VARCHAR(160) NOT NULL");

        $pCols = $cols('passengers');
        if (!in_array('note', $pCols, true)) {
            $pdo->exec("ALTER TABLE passengers ADD COLUMN note VARCHAR(255) NULL DEFAULT NULL AFTER phone");
        }

        $nCols = $cols('notifications');
        if (!in_array('link', $nCols, true)) {
            $pdo->exec("ALTER TABLE notifications ADD COLUMN link VARCHAR(255) NULL DEFAULT NULL AFTER message");
        }
        if (!in_array('is_read', $nCols, true)) {
            $pdo->exec("ALTER TABLE notifications ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER link");
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS saved_tours (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            tour_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_saved_user_tour (user_id, tour_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tour_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            booking_id BIGINT UNSIGNED NULL,
            rating TINYINT UNSIGNED NOT NULL,
            comment TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS system_config (
            config_key VARCHAR(64) PRIMARY KEY,
            config_value VARCHAR(255) NOT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Cập nhật mã tour và mã lịch khởi hành nếu còn trống
        $pdo->exec("UPDATE tours SET code = CONCAT('TOUR-', LPAD(id, 3, '0')) WHERE code IS NULL OR code = ''");
        $pdo->exec("UPDATE departures SET code = CONCAT('DEP-', LPAD(id, 3, '0')) WHERE code IS NULL OR code = ''");

        // Đảm bảo có ít nhất 1 tour dưới 1 triệu (< 1tr) để lọc khoảng giá
        $under1m = (int)$pdo->query("SELECT COUNT(*) FROM tours WHERE base_price < 1000000")->fetchColumn();
        if ($under1m === 0) {
            $pdo->exec("UPDATE tours SET base_price = 890000, surcharge_per_person = 90000, title = 'Ninh Bình – Tràng An Cổ Kính & Hang Múa (Giá < 1 Triệu)' WHERE destination = 'Ninh Bình' ORDER BY id LIMIT 1");
            $pdo->exec("UPDATE departures d JOIN tours t ON t.id = d.tour_id SET d.price_per_person = 890000 WHERE t.base_price = 890000");
        }

        // Đảm bảo có tour tạm ngừng để hệ thống duy trì đủ dữ liệu trạng thái
        $hasUnavailable = (int)$pdo->query("SELECT COUNT(*) FROM tours WHERE status = 'paused'")->fetchColumn();
        $sellerId = (int)$pdo->query("SELECT id FROM sellers ORDER BY id LIMIT 1")->fetchColumn();
        if ($hasUnavailable === 0 && $sellerId > 0) {
            $stmt = $pdo->prepare("INSERT INTO tours(code,seller_id,title,destination,origin,days,nights,description,itinerary,included,excluded,pickup,transport,accommodation,required_documents,cancellation_policy,is_international,base_price,surcharge_per_person,surcharge_label,image_path,gallery_json,status)
                VALUES ('TOUR-HUE10',?,'Huế – Cố Đô Trầm Mặc & Du Thuyền Sông Hương','Huế','Hà Nội',3,2,
                'Khám phá Đại Nội, lăng tẩm triều Nguyễn và vẻ đẹp thơ mộng của sông Hương trong hành trình trọn vẹn tại Huế.',
                'Ngày 1: Hà Nội – Huế, tham quan Đại Nội Kinh Thành Huế.\nNgày 2: Thăm lăng Minh Mạng – Khải Định, nghe ca Huế trên sông Hương.\nNgày 3: Mua sắm chợ Đông Ba, tiễn sân bay Phú Bài.',
                'Xe đưa đón, khách sạn 4 sao ven sông Hương, vé tham quan Đại Nội.','Vé máy bay khứ hồi, chi tiêu cá nhân.',
                'Sân bay Quốc tế Nội Bài','Máy bay & Xe du lịch','Khách sạn 4 sao ven sông Hương',
                'Căn cước công dân hợp lệ (chuẩn bị trước 3 ngày).','Tiêu chuẩn: hủy trước 7 ngày hoàn 80%, trước 3 ngày hoàn 50%',0,2750000,200000,'Phụ thu thuyền Rồng nghe ca Huế','assets/images/hoi-an.jpg','[\"assets/images/hoi-an.jpg\",\"assets/images/da-lat.jpg\"]','paused')");
            $stmt->execute([$sellerId]);
        }

        // Tạo đánh giá ban đầu khi cơ sở dữ liệu chưa có đánh giá
        $revCount = (int)$pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
        $custId = (int)$pdo->query("SELECT id FROM users WHERE role='customer' ORDER BY id LIMIT 1")->fetchColumn();
        if ($revCount === 0 && $custId > 0) {
            $tourIds = $pdo->query("SELECT id FROM tours ORDER BY id LIMIT 7")->fetchAll(PDO::FETCH_COLUMN);
            $sampleComments = [
                'Lịch trình chuẩn, hướng dẫn viên nhiệt tình và dịch vụ lưu trú rất sạch đẹp!',
                'Chuyến đi tuyệt vời, xe đưa đón đúng giờ, đồ ăn ngon miệng và cảnh đẹp.',
                'Rất hài lòng với cách tổ chức của Seller, chắc chắn sẽ tiếp tục đặt tour trên ViVu Việt.',
            ];
            foreach ($tourIds as $idx => $tid) {
                // Chừa lại một tour chưa có đánh giá
                if ($idx === 5) continue;
                $stmt = $pdo->prepare("INSERT INTO reviews(tour_id,user_id,rating,comment) VALUES (?,?,?,?)");
                $stmt->execute([(int)$tid, $custId, ($idx % 2 === 0 ? 5 : 4), $sampleComments[$idx % count($sampleComments)]]);
            }
        }

        // Đồng bộ trạng thái các tour 'approved' có lịch 'open' sang 'selling' (Đang bán)
        $pdo->exec("UPDATE tours t SET t.status = 'selling'
                    WHERE t.status = 'approved'
                      AND EXISTS (SELECT 1 FROM departures d WHERE d.tour_id = t.id AND d.status = 'open' AND d.cutoff_at > NOW())");
    } catch (Throwable $e) {
        error_log('Schema check warning: ' . $e->getMessage());
    }
}

function h(string|int|float|null $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(int|float $number): string {
    return number_format((float)$number, 0, ',', '.') . ' ₫';
}

function url(string $page = '', array $params = []): string {
    $query = ['page' => $page ?: 'home'] + $params;
    return 'index.php?' . http_build_query($query);
}

function go(string $page = '', array $params = []): never {
    header('Location: ' . url($page, $params));
    exit;
}

function csrf(): string {
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(24));
}

function hidden_csrf(): string {
    return '<input type="hidden" name="csrf" value="' . h(csrf()) . '">';
}

function me(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_role(string $role): array {
    $user = me();
    if (!$user) {
        $_SESSION['return_to'] = $_SERVER['REQUEST_URI'] ?? url();
        flash('Vui lòng đăng nhập để tiếp tục thao tác.', 'error');
        go('login');
    }
    if ($user['role'] !== $role) {
        throw new DomainException('Bạn đang đăng nhập với vai trò "' . strtoupper($user['role']) . '" nên không có quyền thực hiện thao tác dành cho "' . strtoupper($role) . '".');
    }
    return $user;
}

function flash(string $message, string $type = 'ok'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function set_field_errors(array $errors, array $old = []): void {
    $_SESSION['field_errors'] = $errors;
    $_SESSION['old_input'] = $old;
}

function clear_form_state(): void {
    unset($_SESSION['field_errors'], $_SESSION['old_input']);
}

function field_error(string $field): string {
    $err = $_SESSION['field_errors'][$field] ?? '';
    if ($err === '') return '';
    return '<span class="field-error" role="alert">⚠ ' . h($err) . '</span>';
}

function old_val(string $field, string $default = ''): string {
    if (isset($_SESSION['old_input']) && array_key_exists($field, $_SESSION['old_input'])) {
        $v = $_SESSION['old_input'][$field];
        return is_scalar($v) ? (string)$v : $default;
    }
    return $default;
}

function notice(int $userId, string $message, ?string $link = null): void {
    q('INSERT INTO notifications(user_id,message,link,is_read) VALUES (?,?,?,0)', [$userId, $message, $link]);
}

function unread_notifications_count(?int $userId): int {
    if (!$userId) return 0;
    return (int)q('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0', [$userId])->fetchColumn();
}

function audit(?int $actorId, string $type, int $id, string $action, string $detail = ''): void {
    q('INSERT INTO audit_log(actor_id,object_type,object_id,action,detail) VALUES (?,?,?,?,?)', [$actorId, $type, $id, $action, $detail]);
}

function must(bool $ok, string $message): void {
    if (!$ok) throw new DomainException($message);
}

function dt(?string $date): string {
    if (!$date) return '—';
    return date('d/m/Y H:i', strtotime($date));
}

function day(?string $date): string {
    if (!$date) return '—';
    return date('d/m/Y', strtotime($date));
}

function vn_normalize(string $str): string {
    $str = mb_strtolower(trim($str), 'UTF-8');
    $map = [
        'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
        'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
        'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
        'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
        'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
        'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y',
        'đ'=>'d',
    ];
    return strtr($str, $map);
}

function badge(string $status): string {
    $map = [
        'draft'          => 'Nháp',
        'pending'        => 'Chờ duyệt',
        'approved'       => 'Đã duyệt',
        'selling'        => 'Đang bán',
        'rejected'       => 'Bị từ chối',
        'paused'         => 'Tạm ngừng / Không mở bán',
        'verified'       => 'Đã xác minh',
        'open'           => 'Mở bán',
        'full'           => 'Hết chỗ',
        'closed'         => 'Đóng bán',
        'cancelled'      => 'Đã hủy',
        'pending_seller' => 'Chờ xác nhận',
        'confirmed'      => 'Đã xác nhận',
        'deposited'      => 'Đã đặt cọc (50%)',
        'paid'           => 'Đã thanh toán (100%)',
        'completed'      => 'Đã hoàn thành',
        'expired'        => 'Hết hạn',
        'incomplete'     => 'Chưa hoàn tất hành khách',
    ];
    return '<span class="badge status-' . h($status) . '">' . h($map[$status] ?? $status) . '</span>';
}

function balance_deadline(string $start, bool $international): string {
    $d = new DateTimeImmutable($start, new DateTimeZone('Asia/Ho_Chi_Minh'));
    $days = $international ? 10 : 3;
    if ($international) {
        while ($days > 0) {
            $d = $d->modify('-1 day');
            if ((int)$d->format('N') < 6) $days--;
        }
    } else {
        $d = $d->modify('-3 days');
    }
    return $d->format('Y-m-d') . ' 23:59:59';
}

/**
 * Đồng bộ trạng thái từng Lịch khởi hành (Mở bán <-> Hết chỗ <-> Đóng bán)
 * và trạng thái Tour (Đã duyệt <-> Đang bán) theo đúng AD-CN06 & AD-CN11
 */
function sync_departure_and_tour(int $tourId): void {
    $deps = q("SELECT d.*,
        COALESCE((SELECT SUM(b.people) FROM bookings b WHERE b.departure_id=d.id AND b.status IN ('pending_seller','confirmed')),0) AS held_seats,
        COALESCE((SELECT SUM(b.people) FROM bookings b WHERE b.departure_id=d.id AND b.status IN ('deposited','paid','completed')),0) AS sold_seats
        FROM departures d WHERE d.tour_id=?", [$tourId])->fetchAll();

    $now = time();
    $hasOpen = false;
    foreach ($deps as $d) {
        $rem = max(0, (int)$d['capacity'] - (int)$d['held_seats'] - (int)$d['sold_seats']);
        $st = $d['status'];
        if (in_array($st, ['open', 'full'], true) && strtotime($d['cutoff_at']) <= $now) {
            $st = 'closed';
            q("UPDATE departures SET status='closed' WHERE id=?", [$d['id']]);
        } elseif ($st === 'open' && $rem === 0) {
            $st = 'full';
            q("UPDATE departures SET status='full' WHERE id=?", [$d['id']]);
        } elseif ($st === 'full' && $rem > 0 && strtotime($d['cutoff_at']) > $now) {
            $st = 'open';
            q("UPDATE departures SET status='open' WHERE id=?", [$d['id']]);
        }
        if ($st === 'open' && $rem > 0 && strtotime($d['cutoff_at']) > $now) {
            $hasOpen = true;
        }
    }

    $tour = q("SELECT id, status FROM tours WHERE id=?", [$tourId])->fetch();
    if ($tour && in_array($tour['status'], ['approved', 'selling'], true)) {
        $target = $hasOpen ? 'selling' : 'approved';
        if ($tour['status'] !== $target) {
            q("UPDATE tours SET status=? WHERE id=?", [$target, $tourId]);
        }
    }
}

function departure_rows(int $tourId): array {
    sync_departure_and_tour($tourId);
    return q("SELECT d.*,
              COALESCE((SELECT SUM(b.people) FROM bookings b WHERE b.departure_id=d.id AND b.status IN ('pending_seller','confirmed')),0) AS held_seats,
              COALESCE((SELECT SUM(b.people) FROM bookings b WHERE b.departure_id=d.id AND b.status IN ('deposited','paid','completed')),0) AS sold_seats,
              GREATEST(0, CAST(d.capacity AS SIGNED) - COALESCE((SELECT SUM(b.people) FROM bookings b WHERE b.departure_id=d.id AND b.status IN ('pending_seller','confirmed','deposited','paid','completed')),0)) AS remaining
              FROM departures d WHERE d.tour_id=? ORDER BY d.start_date ASC", [$tourId])->fetchAll();
}

function tour_with_seller(int $id): ?array {
    sync_departure_and_tour($id);
    $tour = q("SELECT t.*, s.company_name, s.status AS seller_status, s.created_at AS seller_created_at, s.phone AS seller_phone, s.address AS seller_address
               FROM tours t JOIN sellers s ON s.id=t.seller_id WHERE t.id=?", [$id])->fetch();
    return $tour ?: null;
}

function public_tour(int $id): ?array {
    $tour = tour_with_seller($id);
    if (!$tour) return null;
    if ($tour['seller_status'] !== 'verified') return null;
    if (!in_array($tour['status'], ['selling', 'approved', 'paused'], true)) return null;
    return $tour;
}

function tour_rating_summary(int $tourId): array {
    $row = q("SELECT COUNT(*) AS cnt, AVG(rating) AS avg_rating FROM reviews WHERE tour_id=?", [$tourId])->fetch();
    $cnt = (int)($row['cnt'] ?? 0);
    $avg = $cnt > 0 ? round((float)$row['avg_rating'], 1) : null;
    $list = q("SELECT r.*, u.name AS user_name FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.tour_id=? ORDER BY r.created_at DESC", [$tourId])->fetchAll();
    return ['count' => $cnt, 'avg' => $avg, 'reviews' => $list];
}

function seller_stats(int $sellerId): array {
    $bookingCount = (int)q("SELECT COUNT(*) FROM bookings b JOIN departures d ON d.id=b.departure_id JOIN tours t ON t.id=d.tour_id WHERE t.seller_id=? AND b.status IN ('confirmed','deposited','paid','completed')", [$sellerId])->fetchColumn();
    $rev = q("SELECT COUNT(*) AS cnt, AVG(r.rating) AS avg_rating FROM reviews r JOIN tours t ON t.id=r.tour_id WHERE t.seller_id=?", [$sellerId])->fetch();
    $cnt = (int)($rev['cnt'] ?? 0);
    $avg = $cnt > 0 ? round((float)$rev['avg_rating'], 1) : null;
    return ['booking_count' => $bookingCount, 'review_count' => $cnt, 'avg_rating' => $avg];
}

function is_tour_saved(?int $userId, int $tourId): bool {
    if (!$userId) return false;
    return (bool)q("SELECT id FROM saved_tours WHERE user_id=? AND tour_id=?", [$userId, $tourId])->fetchColumn();
}

function saved_tour_ids(?int $userId): array {
    if (!$userId) return [];
    return array_map('intval', q("SELECT tour_id FROM saved_tours WHERE user_id=?", [$userId])->fetchAll(PDO::FETCH_COLUMN));
}

function booking_for(int $id, bool $lock = false): ?array {
    return q("SELECT b.*, d.code AS departure_code, d.tour_id, d.start_date, d.end_date, d.cutoff_at, d.meeting_time, d.status AS departure_status,
                     t.code AS tour_code, t.title, t.destination, t.origin, t.days, t.nights, t.image_path, t.is_international, t.pickup, t.required_documents, t.seller_id,
                     s.company_name, s.user_id AS seller_user_id
              FROM bookings b
              JOIN departures d ON d.id=b.departure_id
              JOIN tours t ON t.id=d.tour_id
              JOIN sellers s ON s.id=t.seller_id
              WHERE b.id=?" . ($lock ? ' FOR UPDATE' : ''), [$id])->fetch() ?: null;
}

/**
 * Thực thi toàn bộ luồng AD-CN11: Tự động nhắc hạn, chuyển trạng thái booking và lịch khởi hành
 */
function run_automated_tasks(): int {
    $processed = 0;

    // Bước 1 & E1: Kiểm tra lần chạy cuối cùng để quét bù các mốc đã qua
    q("INSERT INTO system_config(config_key, config_value) VALUES ('last_cron_run', NOW()) ON DUPLICATE KEY UPDATE config_value = NOW()");

    // Bước 3: Booking 'Chờ xác nhận', còn <= 4h tới hạn xác nhận -> Nhắc Seller xác nhận (1 lần)
    $remind4h = q("SELECT b.id, b.code, b.seller_deadline, s.user_id AS seller_user_id
                   FROM bookings b JOIN departures d ON d.id=b.departure_id JOIN tours t ON t.id=d.tour_id JOIN sellers s ON s.id=t.seller_id
                   WHERE b.status='pending_seller' AND b.reminded_seller_4h=0
                     AND b.seller_deadline > NOW() AND b.seller_deadline <= DATE_ADD(NOW(), INTERVAL 4 HOUR)")->fetchAll();
    foreach ($remind4h as $r) {
        q("UPDATE bookings SET reminded_seller_4h=1 WHERE id=?", [$r['id']]);
        notice((int)$r['seller_user_id'], 'Nhắc hạn (còn ≤ 4h): Booking ' . $r['code'] . ' sắp hết hạn xác nhận lúc ' . dt($r['seller_deadline']) . '.', url('seller_bookings'));
        audit(null, 'booking', (int)$r['id'], 'remind_seller_4h', 'Nhắc Seller xác nhận mốc 4 giờ');
        $processed++;
    }

    // Bước 4: Booking 'Chờ xác nhận' quá hạn -> Chuyển 'Bị từ chối', trả chỗ về lịch (mở lại lịch nếu đang Hết chỗ)
    $expiredSeller = q("SELECT b.id, b.code, b.customer_id, d.tour_id, s.user_id AS seller_user_id
                        FROM bookings b JOIN departures d ON d.id=b.departure_id JOIN tours t ON t.id=d.tour_id JOIN sellers s ON s.id=t.seller_id
                        WHERE b.status='pending_seller' AND b.seller_deadline <= NOW()")->fetchAll();
    foreach ($expiredSeller as $r) {
        db()->beginTransaction();
        try {
            $b = q("SELECT * FROM bookings WHERE id=? FOR UPDATE", [$r['id']])->fetch();
            if ($b && $b['status'] === 'pending_seller') {
                q("UPDATE bookings SET status='rejected', cancellation_reason='Quá 24 giờ Seller chưa xác nhận' WHERE id=?", [$r['id']]);
                sync_departure_and_tour((int)$r['tour_id']);
                audit(null, 'booking', (int)$r['id'], 'auto_rejected_timeout', 'Quá 24h Seller chưa xác nhận, đã trả chỗ');
                notice((int)$r['customer_id'], 'Booking ' . $r['code'] . ' đã chuyển sang Bị từ chối do quá 24 giờ Seller chưa phản hồi. Số chỗ đã được hoàn trả.', url('booking', ['id' => $r['id']]));
                notice((int)$r['seller_user_id'], 'Booking ' . $r['code'] . ' đã tự động chuyển Bị từ chối do quá hạn xác nhận 24 giờ.', url('seller_bookings'));
                $processed++;
            }
            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
        }
    }

    // Bước 5: Booking 'Đã xác nhận', còn <= 12h hoặc <= 2h tới hạn đặt cọc -> Nhắc khách đặt cọc (mỗi mốc 1 lần)
    $remind12h = q("SELECT id, code, customer_id, deposit_deadline FROM bookings
                    WHERE status='confirmed' AND reminded_deposit_12h=0
                      AND deposit_deadline > NOW() AND deposit_deadline <= DATE_ADD(NOW(), INTERVAL 12 HOUR)")->fetchAll();
    foreach ($remind12h as $r) {
        q("UPDATE bookings SET reminded_deposit_12h=1 WHERE id=?", [$r['id']]);
        notice((int)$r['customer_id'], 'Nhắc đặt cọc (mốc 12h): Booking ' . $r['code'] . ' cần đặt cọc/thanh toán trước ' . dt($r['deposit_deadline']) . '.', url('booking', ['id' => $r['id']]));
        audit(null, 'booking', (int)$r['id'], 'remind_deposit_12h', 'Nhắc khách đặt cọc mốc 12 giờ');
        $processed++;
    }

    $remind2h = q("SELECT id, code, customer_id, deposit_deadline FROM bookings
                   WHERE status='confirmed' AND reminded_deposit_2h=0
                     AND deposit_deadline > NOW() AND deposit_deadline <= DATE_ADD(NOW(), INTERVAL 2 HOUR)")->fetchAll();
    foreach ($remind2h as $r) {
        q("UPDATE bookings SET reminded_deposit_2h=1 WHERE id=?", [$r['id']]);
        notice((int)$r['customer_id'], 'Nhắc khẩn (còn ≤ 2h): Booking ' . $r['code'] . ' sắp hết hạn đặt cọc lúc ' . dt($r['deposit_deadline']) . '.', url('booking', ['id' => $r['id']]));
        audit(null, 'booking', (int)$r['id'], 'remind_deposit_2h', 'Nhắc khách đặt cọc mốc 2 giờ');
        $processed++;
    }

    // Bước 6: Booking 'Đã xác nhận', quá hạn đặt cọc -> Chuyển 'Hết hạn', trả chỗ về lịch
    $expiredDeposit = q("SELECT b.id, b.code, b.customer_id, d.tour_id, s.user_id AS seller_user_id
                         FROM bookings b JOIN departures d ON d.id=b.departure_id JOIN tours t ON t.id=d.tour_id JOIN sellers s ON s.id=t.seller_id
                         WHERE b.status='confirmed' AND b.deposit_deadline <= NOW()")->fetchAll();
    foreach ($expiredDeposit as $r) {
        db()->beginTransaction();
        try {
            $b = q("SELECT * FROM bookings WHERE id=? FOR UPDATE", [$r['id']])->fetch();
            if ($b && $b['status'] === 'confirmed') {
                q("UPDATE bookings SET status='expired', cancellation_reason='Quá hạn đặt cọc 24 giờ' WHERE id=?", [$r['id']]);
                sync_departure_and_tour((int)$r['tour_id']);
                audit(null, 'booking', (int)$r['id'], 'auto_expired_deposit', 'Quá hạn đặt cọc, trả chỗ về lịch');
                notice((int)$r['customer_id'], 'Booking ' . $r['code'] . ' đã chuyển sang Hết hạn do quá thời hạn đặt cọc.', url('booking', ['id' => $r['id']]));
                notice((int)$r['seller_user_id'], 'Booking ' . $r['code'] . ' đã Hết hạn đặt cọc, số chỗ đã được trả về lịch khởi hành.', url('seller_bookings'));
                $processed++;
            }
            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
        }
    }

    // Bước 7: Booking 'Đã đặt cọc', tới mốc nhắc trả phần còn lại (trước 1 ngày nội địa / 3 ngày quốc tế)
    $remindBal = q("SELECT b.id, b.code, b.customer_id, b.balance_deadline, t.is_international
                    FROM bookings b JOIN departures d ON d.id=b.departure_id JOIN tours t ON t.id=d.tour_id
                    WHERE b.status='deposited' AND b.reminded_balance=0 AND b.balance_deadline > NOW()")->fetchAll();
    foreach ($remindBal as $r) {
        $daysBefore = (int)$r['is_international'] ? 3 : 1;
        if (strtotime($r['balance_deadline']) <= strtotime("+$daysBefore days")) {
            q("UPDATE bookings SET reminded_balance=1 WHERE id=?", [$r['id']]);
            notice((int)$r['customer_id'], 'Nhắc thanh toán phần còn lại: Booking ' . $r['code'] . ' cần thanh toán đủ 100% trước ' . dt($r['balance_deadline']) . '.', url('booking', ['id' => $r['id']]));
            audit(null, 'booking', (int)$r['id'], 'remind_balance', 'Nhắc thanh toán 50% còn lại');
            $processed++;
        }
    }

    // Bước 8: Booking 'Đã đặt cọc', quá hạn trả phần còn lại -> Chuyển 'Đã hủy', trả chỗ, xử lý cọc theo chính sách
    $expiredBal = q("SELECT b.id, b.code, b.customer_id, b.policy_snapshot, d.tour_id, s.user_id AS seller_user_id
                     FROM bookings b JOIN departures d ON d.id=b.departure_id JOIN tours t ON t.id=d.tour_id JOIN sellers s ON s.id=t.seller_id
                     WHERE b.status='deposited' AND b.balance_deadline <= NOW()")->fetchAll();
    foreach ($expiredBal as $r) {
        db()->beginTransaction();
        try {
            $b = q("SELECT * FROM bookings WHERE id=? FOR UPDATE", [$r['id']])->fetch();
            if ($b && $b['status'] === 'deposited') {
                $reason = 'Quá hạn thanh toán phần còn lại. Tiền cọc xử lý theo chính sách: ' . $r['policy_snapshot'];
                q("UPDATE bookings SET status='cancelled', cancellation_reason=? WHERE id=?", [$reason, $r['id']]);
                sync_departure_and_tour((int)$r['tour_id']);
                audit(null, 'booking', (int)$r['id'], 'auto_cancelled_balance', $reason);
                notice((int)$r['customer_id'], 'Booking ' . $r['code'] . ' đã bị hủy do quá hạn thanh toán phần còn lại. ' . $reason, url('booking', ['id' => $r['id']]));
                notice((int)$r['seller_user_id'], 'Booking ' . $r['code'] . ' đã chuyển sang Đã hủy do quá hạn đóng phần còn lại; chỗ đã mở lại.', url('seller_bookings'));
                $processed++;
            }
            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
        }
    }

    // Bước 9: Lịch đến hạn chốt bán -> Chuyển lịch 'Đóng bán', chuyển tour về 'Đã duyệt' nếu hết lịch mở
    $dueDeps = q("SELECT id, tour_id, code FROM departures WHERE status IN ('open','full') AND cutoff_at <= NOW()")->fetchAll();
    foreach ($dueDeps as $d) {
        q("UPDATE departures SET status='closed' WHERE id=?", [$d['id']]);
        sync_departure_and_tour((int)$d['tour_id']);
        audit(null, 'departure', (int)$d['id'], 'auto_closed_cutoff', 'Lịch đến hạn chốt bán tự động Đóng bán');
        $processed++;
    }

    // Bước 10: Booking 'Đã thanh toán', tour đã kết thúc -> Chuyển 'Đã hoàn thành', tạo lời mời khách đánh giá tour
    $finished = q("SELECT b.id, b.code, b.customer_id, d.tour_id, t.title, s.user_id AS seller_user_id
                   FROM bookings b JOIN departures d ON d.id=b.departure_id JOIN tours t ON t.id=d.tour_id JOIN sellers s ON s.id=t.seller_id
                   WHERE b.status='paid' AND d.end_date < CURDATE()")->fetchAll();
    foreach ($finished as $row) {
        db()->beginTransaction();
        try {
            $changed = q("UPDATE bookings SET status='completed' WHERE id=? AND status='paid'", [$row['id']])->rowCount();
            if ($changed) {
                audit(null, 'booking', (int)$row['id'], 'completed', 'Hoàn thành tour, mời khách đánh giá');
                notice((int)$row['customer_id'], 'Chúc mừng bạn đã hoàn thành tour "' . $row['title'] . '" (Booking ' . $row['code'] . '). Hãy để lại đánh giá trải nghiệm của bạn!', url('tour', ['id' => $row['tour_id'], 'review_booking' => $row['id']]));
                notice((int)$row['seller_user_id'], 'Booking ' . $row['code'] . ' đã chuyển sang trạng thái Đã hoàn thành.', url('seller_bookings'));
                $processed++;
            }
            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
        }
    }

    return $processed;
}
