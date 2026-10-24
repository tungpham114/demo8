<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function render_header(string $activePage = 'home'): void {
    $user = me();
    $unread = $user ? unread_notifications_count((int)$user['id']) : 0;
    $savedCount = $user && $user['role'] === 'customer' ? count(saved_tour_ids((int)$user['id'])) : 0;
    ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h(APP_NAME) ?> — Sàn Thương Mại Điện Tử Tour Du Lịch</title>
  <link rel="stylesheet" href="assets/fonts.css">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="utility">
    <div class="container">
      <strong><?= h(APP_NAME) ?></strong> · Sàn thương mại điện tử tour du lịch
      <span>Hotline hỗ trợ: 1900 6888 · Giữ chỗ thời gian thực · Thanh toán cọc 50% hoặc 100%</span>
    </div>
  </div>
  <header class="site-header">
    <div class="container nav">
      <a class="brand" href="<?= h(url('home')) ?>">
        <img src="assets/logo.svg" alt="<?= h(APP_NAME) ?>">
        <div>
          <?= h(APP_NAME) ?> <b>Tour</b>
          <small>SÀN DU LỊCH TRỰC TUYẾN</small>
        </div>
      </a>
      <button class="menu-toggle" type="button" aria-label="Menu" aria-expanded="false">☰</button>
      <nav>
        <a href="<?= h(url('home')) ?>" class="<?= $activePage === 'home' ? 'active' : '' ?>">Trang chủ</a>
        <a href="<?= h(url('tours')) ?>" class="<?= $activePage === 'tours' ? 'active' : '' ?>">Tìm kiếm Tour</a>
        <a href="<?= h(url('compare')) ?>" class="<?= $activePage === 'compare' ? 'active' : '' ?>">So sánh <span id="nav-compare-badge" class="badge-mini">0</span></a>
        <a href="<?= h(url('saved_tours')) ?>" class="<?= $activePage === 'saved_tours' ? 'active' : '' ?>">Tour đã lưu <?= $savedCount > 0 ? '<span class="badge-mini">' . $savedCount . '</span>' : '' ?></a>
        <?php if ($user && $user['role'] === 'customer'): ?>
          <a href="<?= h(url('bookings')) ?>" class="<?= in_array($activePage, ['bookings', 'booking'], true) ? 'active' : '' ?>">Booking của tôi</a>
        <?php elseif ($user && $user['role'] === 'seller'): ?>
          <a href="<?= h(url('seller')) ?>" class="<?= $activePage === 'seller' ? 'active' : '' ?>">Cổng Seller</a>
          <a href="<?= h(url('seller_tours')) ?>" class="<?= in_array($activePage, ['seller_tours', 'seller_tour_form', 'seller_tour_view'], true) ? 'active' : '' ?>">Quản lý Tour</a>
          <a href="<?= h(url('seller_departures')) ?>" class="<?= $activePage === 'seller_departures' ? 'active' : '' ?>">Lịch & Quỹ chỗ</a>
          <a href="<?= h(url('seller_bookings')) ?>" class="<?= $activePage === 'seller_bookings' ? 'active' : '' ?>">Xác nhận Booking</a>
        <?php elseif ($user && $user['role'] === 'admin'): ?>
          <a href="<?= h(url('admin')) ?>" class="<?= $activePage === 'admin' ? 'active' : '' ?>">Quản trị & Duyệt</a>
        <?php else: ?>
          <a href="<?= h(url('seller_register')) ?>" class="<?= $activePage === 'seller_register' ? 'active' : '' ?>">Đăng ký bán tour</a>
        <?php endif; ?>
      </nav>
      <div class="nav-user">
        <?php if ($user): ?>
          <a href="<?= h(url('notifications')) ?>" class="notif-pill <?= $activePage === 'notifications' ? 'active' : '' ?>" title="Trung tâm thông báo">
            🔔 Thông báo <?php if ($unread > 0): ?><b class="notif-count"><?= $unread ?></b><?php endif; ?>
          </a>
          <span class="hello" title="<?= h($user['email']) ?>"><?= h($user['name']) ?> (<?= h(strtoupper($user['role'])) ?>)</span>
          <form method="post" action="index.php">
            <?= hidden_csrf() ?>
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="link-button">Đăng xuất</button>
          </form>
        <?php else: ?>
          <a href="<?= h(url('login')) ?>" class="login-link">Đăng nhập</a>
          <a href="<?= h(url('register')) ?>" class="btn btn-small">Đăng ký</a>
        <?php endif; ?>
      </div>
    </div>
  </header>
  <?php if (!empty($_SESSION['flash'])):
      $fl = $_SESSION['flash'];
      unset($_SESSION['flash']);
  ?>
    <div class="container">
      <div class="flash <?= ($fl['type'] ?? 'ok') === 'error' ? 'error' : '' ?>">
        <?= h($fl['message']) ?>
      </div>
    </div>
  <?php endif; ?>
<?php
}

function render_footer(): void {
    clear_form_state();
    ?>
  <div id="compare-tray" class="compare-tray" hidden>
    <div>
      <strong>Danh sách so sánh (<span id="compare-count">0</span>/3 Tour)</strong>
      <small id="compare-hint" style="display:block;color:#c8d8d7">Chọn ít nhất 2 tour để mở bảng so sánh</small>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
      <button type="button" id="compare-clear" class="btn btn-outline btn-small">Xóa</button>
      <button type="button" id="compare-go" class="btn btn-small">Mở So sánh →</button>
    </div>
  </div>
  <footer>
    <div class="container foot-grid">
      <div>
        <div class="brand foot-brand">
          <img src="assets/logo.svg" alt="<?= h(APP_NAME) ?>">
          <div>
            <?= h(APP_NAME) ?> <b>Tour</b>
            <small>HỆ THỐNG SÀN TMĐT BÁN TOUR DU LỊCH — <?= h(APP_DIR) ?></small>
          </div>
        </div>
        <p>Nền tảng kết nối trực tiếp du khách Việt với các đơn vị lữ hành đã xác minh giấy phép. Quỹ chỗ chuẩn hóa theo thời gian thực, giữ chỗ chờ Seller xác nhận trong 24 giờ, hỗ trợ đặt cọc 50% hoặc thanh toán 100% an toàn.</p>
      </div>
      <div>
        <b>Liên kết nhanh</b>
        <a href="<?= h(url('tours')) ?>">Tìm kiếm Tour</a>
        <a href="<?= h(url('compare')) ?>">So sánh Tour</a>
        <a href="<?= h(url('saved_tours')) ?>">Tour đã lưu</a>
        <a href="<?= h(url('seller_register')) ?>">Đăng ký Seller</a>
      </div>
    </div>
    <div class="container copyright">
      © <?= date('Y') ?> <?= h(APP_NAME) ?>. Bảo lưu mọi quyền.
    </div>
  </footer>
  <script src="assets/app.js"></script>
</body>
</html>
<?php
}

function render_error(string $message): void {
    render_header();
    ?>
    <main class="container section">
      <div class="panel error-panel">
        <span class="eyebrow">Thông báo hệ thống</span>
        <h2>Không thể hoàn tất thao tác</h2>
        <p><?= h($message) ?></p>
        <div style="margin-top:20px;display:flex;gap:12px">
          <a href="javascript:history.back()" class="btn btn-outline">← Quay lại trang trước</a>
          <a href="<?= h(url('home')) ?>" class="btn">Về trang chủ</a>
        </div>
      </div>
    </main>
    <?php
    render_footer();
}

function render_tour_card(array $t, array $savedIds = []): void {
    $tourId = (int)$t['id'];
    $rating = tour_rating_summary($tourId);
    $isSaved = in_array($tourId, $savedIds, true);
    $isSelling = $t['status'] === 'selling';
    $nearDate = $t['nearest_date'] ?? null;
    $nearPrice = (int)($t['nearest_price'] ?? $t['base_price']);
    $nearRem = (int)($t['nearest_remaining'] ?? 0);
    $currentUrl = $_SERVER['REQUEST_URI'] ?? url('tours');
    ?>
    <article class="tour-card <?= !$isSelling ? 'tour-card-unavailable' : '' ?>">
      <div class="photo">
        <a href="<?= h(url('tour', ['id' => $tourId])) ?>">
          <img src="<?= h($t['image_path']) ?>" alt="<?= h($t['title']) ?>">
        </a>
        <span class="photo-tag"><?= h($t['days']) ?>N<?= h($t['nights']) ?>Đ · <?= $t['is_international'] ? 'Quốc tế' : 'Trong nước' ?></span>
        <div class="card-status-badge"><?= badge($t['status']) ?></div>
        <form method="post" action="index.php" class="save-tour-form">
          <?= hidden_csrf() ?>
          <input type="hidden" name="action" value="toggle_save_tour">
          <input type="hidden" name="tour_id" value="<?= $tourId ?>">
          <input type="hidden" name="return_url" value="<?= h($currentUrl) ?>">
          <button type="submit" class="save-heart-btn <?= $isSaved ? 'saved' : '' ?>" title="<?= $isSaved ? 'Bỏ lưu tour' : 'Lưu tour quan tâm' ?>">
            <?= $isSaved ? '♥ Đã lưu' : '♡ Lưu tour' ?>
          </button>
        </form>
      </div>
      <div class="card-body">
        <div class="card-meta">
          📍 <?= h($t['origin']) ?> → <?= h($t['destination']) ?>
          <span style="float:right">
            <?php if ($rating['count'] > 0): ?>
              <b style="color:#d97706">★ <?= number_format((float)$rating['avg'], 1) ?></b> (<?= $rating['count'] ?>)
            <?php else: ?>
              <em>Chưa có đánh giá</em>
            <?php endif; ?>
          </span>
        </div>
        <h3><a href="<?= h(url('tour', ['id' => $tourId])) ?>"><?= h($t['title']) ?></a></h3>
        <div class="seller-line">✓ <?= h($t['company_name']) ?> (Đã xác minh)</div>
        <div class="card-departure">
          <?php if ($isSelling && $nearDate): ?>
            Lịch gần nhất: <b><?= day($nearDate) ?></b>
            <span>Còn <?= $nearRem ?> chỗ</span>
          <?php else: ?>
            <b style="color:#a04436">Hiện không có lịch mở bán</b>
            <span>Không khả dụng</span>
          <?php endif; ?>
        </div>
        <div class="card-bottom">
          <div>
            <small>Giá từ / người (chưa gồm phụ thu)</small>
            <strong><?= money($nearPrice) ?></strong>
          </div>
          <a href="<?= h(url('tour', ['id' => $tourId])) ?>" class="round-arrow" title="Xem chi tiết Tour">→</a>
        </div>
        <div class="card-actions-bar">
          <label class="compare-check">
            <input type="checkbox" class="compare-box" value="<?= $tourId ?>" data-title="<?= h($t['title']) ?>">
            So sánh tour này
          </label>
          <a href="<?= h(url('tour', ['id' => $tourId])) ?>" class="share-mini" data-share-url="<?= h(url('tour', ['id' => $tourId])) ?>">🔗 Chia sẻ</a>
        </div>
      </div>
    </article>
    <?php
}

function render_page(string $page): void {
    // Đồng bộ trạng thái tất cả các tour trước khi hiển thị
    $allIds = q("SELECT id FROM tours")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($allIds as $tid) sync_departure_and_tour((int)$tid);

    switch ($page) {
        case 'home':
            page_home();
            break;
        case 'tours':
            page_tours();
            break;
        case 'tour':
            page_tour_detail();
            break;
        case 'compare':
            page_compare();
            break;
        case 'saved_tours':
            page_saved_tours();
            break;
        case 'login':
            page_login();
            break;
        case 'register':
            page_register();
            break;
        case 'seller_register':
            page_seller_register();
            break;
        case 'checkout':
            page_checkout();
            break;
        case 'bookings':
            page_customer_bookings();
            break;
        case 'booking':
            page_booking_detail();
            break;
        case 'seller':
            page_seller_dashboard();
            break;
        case 'seller_tours':
            page_seller_tours();
            break;
        case 'seller_tour_view':
            page_seller_tour_view();
            break;
        case 'seller_tour_form':
            page_seller_tour_form();
            break;
        case 'seller_departures':
            page_seller_departures();
            break;
        case 'seller_bookings':
            page_seller_bookings();
            break;
        case 'admin':
            page_admin();
            break;
        case 'notifications':
            page_notifications();
            break;
        default:
            render_error('Trang bạn yêu cầu không tồn tại (404).');
    }
}

function page_home(): void {
    $user = me();
    $savedIds = $user ? saved_tour_ids((int)$user['id']) : [];
    $destinations = q("SELECT DISTINCT destination FROM tours ORDER BY destination")->fetchAll(PDO::FETCH_COLUMN);

    // Lấy danh sách tour đang mở bán ('selling') kèm lịch khởi hành gần nhất
    $tours = q("SELECT t.*, s.company_name,
                (SELECT d.start_date FROM departures d WHERE d.tour_id=t.id AND d.status='open' AND d.cutoff_at>NOW() ORDER BY d.start_date ASC LIMIT 1) AS nearest_date,
                (SELECT d.price_per_person FROM departures d WHERE d.tour_id=t.id AND d.status='open' AND d.cutoff_at>NOW() ORDER BY d.start_date ASC LIMIT 1) AS nearest_price,
                (SELECT GREATEST(0, CAST(d.capacity AS SIGNED) - COALESCE((SELECT SUM(b.people) FROM bookings b WHERE b.departure_id=d.id AND b.status IN ('pending_seller','confirmed','deposited','paid','completed')),0))
                 FROM departures d WHERE d.tour_id=t.id AND d.status='open' AND d.cutoff_at>NOW() ORDER BY d.start_date ASC LIMIT 1) AS nearest_remaining
                FROM tours t JOIN sellers s ON s.id=t.seller_id
                WHERE t.status='selling' AND s.status='verified'
                ORDER BY nearest_date ASC, t.id ASC LIMIT 9")->fetchAll();

    render_header('home');
    ?>
    <section class="hero">
      <img class="hero-img" src="assets/images/ha-long.jpg" alt="Vịnh Hạ Long Việt Nam">
      <div class="hero-shade"></div>
      <div class="container hero-content">
        <span class="eyebrow light">SÀN THƯƠNG MẠI ĐIỆN TỬ DU LỊCH VIỆT NAM</span>
        <h1>Khám phá hành trình <em>chuẩn hóa</em>, giữ chỗ thật theo thời gian thực.</h1>
        <p>Tìm kiếm theo điểm đến, lịch khởi hành và số người. Giá hiển thị minh bạch, giữ chỗ chờ Seller xác nhận trong 24 giờ, linh hoạt đặt cọc 50% hoặc thanh toán 100%.</p>

        <form class="searchbar" method="get" action="index.php" novalidate>
          <input type="hidden" name="page" value="tours">
          <input type="hidden" name="search_submitted" value="1">
          <label>
            ĐIỂM ĐẾN (BẮT BUỘC *)
            <input type="text" name="destination" list="dest-list" placeholder="Nhập điểm đến">
            <datalist id="dest-list">
              <?php foreach ($destinations as $dest): ?>
                <option value="<?= h($dest) ?>">
              <?php endforeach; ?>
            </datalist>
          </label>
          <label>
            LỊCH KHỞI HÀNH TỪ NGÀY (*)
            <input type="date" name="start_date" value="<?= date('Y-m-d') ?>">
          </label>
          <label>
            ĐẾN NGÀY
            <input type="date" name="end_date" value="<?= date('Y-m-d', strtotime('+90 days')) ?>">
          </label>
          <label style="max-width:120px">
            SỐ NGƯỜI (*)
            <input type="number" name="people" min="1" max="50" value="2" placeholder="Số khách">
          </label>
          <label>
            TỪ KHÓA TỰ DO
            <input type="text" name="q" placeholder="Tên tour, địa danh, Seller...">
          </label>
          <button type="submit" class="btn">Tìm kiếm</button>
        </form>
      </div>
      <div class="hero-caption">Vịnh Hạ Long — Di sản thiên nhiên thế giới</div>
    </section>

    <section class="trust-band">
      <div class="container trust-grid">
        <div>
          <span>01 · CHUẨN HÓA & XÁC MINH</span>
          <h3>100% Seller có giấy phép lữ hành</h3>
          <p>Mọi đơn vị lữ hành đều được Quản trị viên đối chiếu giấy phép kinh doanh và giấy phép lữ hành trước khi mở bán.</p>
        </div>
        <div>
          <span>02 · QUỸ CHỖ THỜI GIAN THỰC</span>
          <h3>Giữ chỗ ngay, xác nhận trong 24 giờ</h3>
          <p>Quỹ chỗ dành riêng cho Sàn tự động trừ khi đặt và hoàn trả tức thời nếu quá hạn hoặc từ chối.</p>
        </div>
        <div>
          <span>03 · THANH TOÁN LINH HOẠT</span>
          <h3>Đặt cọc 50% hoặc thanh toán 100%</h3>
          <p>Sau khi Seller xác nhận booking, khách hàng chủ động chọn đặt cọc 50% hoặc thanh toán đủ 100% qua cổng Sàn thu hộ.</p>
        </div>
      </div>
    </section>

    <main class="container section">
      <div class="section-head">
        <div>
          <span class="eyebrow">DANH MỤC HÀNH TRÌNH ĐANG BÁN</span>
          <h2>Các Tour đang mở bán nổi bật</h2>
          <p>Bao gồm đầy đủ các phân khúc giá dưới 1 triệu, từ 1–3 triệu và trên 3 triệu đồng/người (chưa gồm phụ thu).</p>
        </div>
        <a class="text-link" href="<?= h(url('tours')) ?>">Mở bộ lọc & tìm kiếm nâng cao →</a>
      </div>

      <div class="card-grid">
        <?php foreach ($tours as $t): ?>
          <?php render_tour_card($t, $savedIds); ?>
        <?php endforeach; ?>
      </div>

    </main>
    <?php
    render_footer();
}

function page_tours(): void {
    // CN06. TÌM KIẾM TOUR - Bám sát 100% Sơ đồ Activity CN06 (image_d77a32.png) & Bảng 13 SRS
    $user = me();
    $savedIds = $user ? saved_tour_ids((int)$user['id']) : [];
    $destinations = q("SELECT DISTINCT destination FROM tours ORDER BY destination")->fetchAll(PDO::FETCH_COLUMN);

    $isSubmitted = isset($_GET['search_submitted']);
    $destination = trim((string)($_GET['destination'] ?? ''));
    $startDate = trim((string)($_GET['start_date'] ?? ''));
    $endDate = trim((string)($_GET['end_date'] ?? ''));
    $peopleRaw = trim((string)($_GET['people'] ?? ''));
    $keyword = trim((string)($_GET['q'] ?? ''));

    // Bộ lọc & sắp xếp (Bước 8 & 9: Thay đổi bộ lọc tự động cập nhật lại kết quả)
    $priceRange = (string)($_GET['price_range'] ?? ''); // under_1m | 1m_3m | over_3m
    $duration = (string)($_GET['duration'] ?? '');      // short | medium | long
    $minRating = (string)($_GET['min_rating'] ?? '');   // 4.5 | 4 | 3
    $sort = (string)($_GET['sort'] ?? 'date_asc');      // date_asc | price_desc | price_asc

    // Kiểm tra nhánh E3: Khách bấm chọn 1 Tour vừa chuyển sang không khả dụng
    $unavailNotice = '';
    if (!empty($_GET['check_unavailable'])) {
        $uTour = q("SELECT title FROM tours WHERE id=?", [(int)$_GET['check_unavailable']])->fetch();
        $uTitle = $uTour ? $uTour['title'] : 'Tour đã chọn';
        $unavailNotice = 'Tour "' . $uTitle . '" hiện không còn khả dụng (đã tạm ngừng hoặc hết lịch mở bán). Hệ thống đã cập nhật lại danh sách kết quả tìm kiếm.';
    }

    // Bước 3: Kiểm tra tiêu chí bắt buộc khi khách nhấn "Tìm kiếm" -> [không hợp lệ / thiếu] -> E1. Hiển thị lỗi tại trường
    $searchErrors = [];
    if ($isSubmitted) {
        if ($destination === '') {
            $searchErrors['destination'] = 'Vui lòng nhập hoặc chọn Điểm đến.';
        }
        if ($startDate === '') {
            $searchErrors['start_date'] = 'Vui lòng chọn Lịch khởi hành.';
        } elseif (!DateTimeImmutable::createFromFormat('!Y-m-d', $startDate)) {
            $searchErrors['start_date'] = 'Định dạng ngày khởi hành không hợp lệ.';
        } elseif ($endDate !== '' && (!DateTimeImmutable::createFromFormat('!Y-m-d', $endDate) || $startDate > $endDate)) {
            $searchErrors['start_date'] = 'Khoảng ngày khởi hành không hợp lệ: Ngày bắt đầu không được sau ngày kết thúc.';
        }
        if ($peopleRaw === '') {
            $searchErrors['people'] = 'Vui lòng nhập Số người.';
        } elseif (!is_numeric($peopleRaw) || (int)$peopleRaw < 1) {
            $searchErrors['people'] = 'Số người phải từ 1 trở lên.';
        }
    }

    $people = ($peopleRaw !== '' && (int)$peopleRaw >= 1) ? (int)$peopleRaw : 1;
    $results = [];

    // Chỉ thực hiện Bước 4–7 nếu không có lỗi E1 tại trường tìm kiếm
    if (empty($searchErrors)) {
        // Bước 4: Lọc Tour khả dụng (status='selling', Seller 'verified', có lịch 'open' thỏa ngày và remaining >= people)
        $tours = q("SELECT t.*, s.company_name
                    FROM tours t JOIN sellers s ON s.id=t.seller_id
                    WHERE t.status='selling' AND s.status='verified'")->fetchAll();

        $normDest = vn_normalize($destination);
        $normKw = vn_normalize($keyword);

        foreach ($tours as $t) {
            // So khớp điểm đến (nếu có nhập điểm đến)
            if ($normDest !== '' && $normDest !== 'tat ca' && !str_contains(vn_normalize($t['destination']), $normDest) && !str_contains(vn_normalize($t['title']), $normDest)) {
                continue;
            }

            // Bước 5: So khớp từ khóa tự do (không phân biệt hoa/thường và có/không dấu với Tên Tour, Lịch trình, Điểm đi, Điểm đến, Tên Seller)
            if ($normKw !== '') {
                $haystack = vn_normalize($t['title'] . ' ' . $t['itinerary'] . ' ' . $t['origin'] . ' ' . $t['destination'] . ' ' . $t['company_name'] . ' ' . $t['description']);
                if (!str_contains($haystack, $normKw)) {
                    continue;
                }
            }

            // Lấy các lịch khởi hành 'open' còn hạn chốt bán và còn đủ chỗ >= số người
            $deps = departure_rows((int)$t['id']);
            $matchingDeps = [];
            foreach ($deps as $d) {
                if ($d['status'] !== 'open' || strtotime($d['cutoff_at']) <= time()) continue;
                if ((int)$d['remaining'] < $people) continue;
                if ($startDate !== '' && $d['start_date'] < $startDate) continue;
                if ($endDate !== '' && $d['start_date'] > $endDate) continue;
                $matchingDeps[] = $d;
            }

            // Bước 6: Gom kết quả theo Tour (mỗi Tour chỉ hiển thị 1 lần kèm lịch khởi hành phù hợp gần nhất)
            if (empty($matchingDeps)) continue;
            $nearest = $matchingDeps[0];
            $t['nearest_date'] = $nearest['start_date'];
            $t['nearest_price'] = (int)$nearest['price_per_person'];
            $t['nearest_remaining'] = (int)$nearest['remaining'];

            $ratingInfo = tour_rating_summary((int)$t['id']);
            $t['avg_rating'] = $ratingInfo['avg'];
            $t['review_count'] = $ratingInfo['count'];

            // Bước 8 & 9: Áp dụng bộ lọc (Khoảng giá 3 mức: <1tr, 1-3tr, trên 3tr; Thời lượng; Điểm đánh giá)
            if ($priceRange === 'under_1m' && $t['nearest_price'] >= 1000000) continue;
            if ($priceRange === '1m_3m' && ($t['nearest_price'] < 1000000 || $t['nearest_price'] > 3000000)) continue;
            if ($priceRange === 'over_3m' && $t['nearest_price'] <= 3000000) continue;

            if ($duration === 'short' && (int)$t['days'] > 2) continue;
            if ($duration === 'medium' && ((int)$t['days'] < 3 || (int)$t['days'] > 4)) continue;
            if ($duration === 'long' && (int)$t['days'] < 5) continue;

            if ($minRating !== '' && (float)$minRating > 0) {
                if ($t['avg_rating'] === null || (float)$t['avg_rating'] < (float)$minRating) continue;
            }

            $results[] = $t;
        }

        // Sắp xếp kết quả (Mặc định: ngày khởi hành gần nhất trước; hoặc theo giá giảm dần / tăng dần)
        usort($results, function (array $a, array $b) use ($sort): int {
            if ($sort === 'price_desc') return $b['nearest_price'] <=> $a['nearest_price'];
            if ($sort === 'price_asc') return $a['nearest_price'] <=> $b['nearest_price'];
            return strcmp((string)$a['nearest_date'], (string)$b['nearest_date']);
        });
    }

    render_header('tours');
    ?>
    <main class="container section">
      <div class="page-intro">
        <span class="eyebrow">TÌM KIẾM, LỌC & SẮP XẾP TOUR</span>
        <h1>Tìm kiếm Tour du lịch</h1>
        <p>Nhập <b>Điểm đến</b>, <b>Lịch khởi hành</b> và <b>Số người</b> để tìm các lịch khởi hành còn đủ chỗ. Bộ lọc khoảng giá chia rõ 3 mức: <b>Dưới 1 triệu</b>, <b>1 – 3 triệu</b>, <b>Trên 3 triệu</b>.</p>
      </div>

      <?php if ($unavailNotice !== ''): ?>
        <div class="flash error" style="margin-bottom:20px;border:2px solid #c93b2b">
          <strong>⚠ <?= h($unavailNotice) ?></strong>
        </div>
      <?php endif; ?>

      <form class="panel search-panel" method="get" action="index.php" id="cn06-search-form" novalidate>
        <input type="hidden" name="page" value="tours">
        <input type="hidden" name="search_submitted" value="1">

        <div class="search-grid-cn06">
          <div>
            <label>Điểm đến <span class="req">*</span></label>
            <input type="text" name="destination" list="dest-list-page" value="<?= h($destination) ?>" placeholder="Nhập điểm đến" class="<?= isset($searchErrors['destination']) ? 'input-error' : '' ?>">
            <datalist id="dest-list-page">
              <option value="Tất cả">Tất cả điểm đến</option>
              <?php foreach ($destinations as $dest): ?>
                <option value="<?= h($dest) ?>">
              <?php endforeach; ?>
            </datalist>
            <?php if (isset($searchErrors['destination'])): ?>
              <span class="field-error">⚠ <?= h($searchErrors['destination']) ?></span>
            <?php endif; ?>
          </div>

          <div>
            <label>Lịch khởi hành (Từ ngày) <span class="req">*</span></label>
            <input type="date" name="start_date" value="<?= h($startDate) ?>" class="<?= isset($searchErrors['start_date']) ? 'input-error' : '' ?>">
            <?php if (isset($searchErrors['start_date'])): ?>
              <span class="field-error">⚠ <?= h($searchErrors['start_date']) ?></span>
            <?php endif; ?>
          </div>

          <div>
            <label>Đến ngày (Tùy chọn)</label>
            <input type="date" name="end_date" value="<?= h($endDate) ?>">
          </div>

          <div>
            <label>Số người <span class="req">*</span></label>
            <input type="number" name="people" min="1" max="50" value="<?= h($peopleRaw) ?>" placeholder="Nhập số khách (≥ 1)" class="<?= isset($searchErrors['people']) ? 'input-error' : '' ?>">
            <?php if (isset($searchErrors['people'])): ?>
              <span class="field-error">⚠ <?= h($searchErrors['people']) ?></span>
            <?php endif; ?>
          </div>

          <div>
            <label>Từ khóa tự do (Không bắt buộc)</label>
            <input type="text" name="q" value="<?= h($keyword) ?>" placeholder="Tên tour, địa danh lịch trình, Seller...">
          </div>

          <div style="display:flex;align-items:end;gap:8px">
            <button type="submit" class="btn btn-wide">🔍 Tìm kiếm</button>
          </div>
        </div>

        <div class="filters" style="margin-bottom:0;margin-top:18px;background:var(--cream)">
          <label>
            LỌC THEO MỨC GIÁ / SẮP XẾP GIÁ (3 TRƯỜNG)
            <select name="price_range" onchange="this.form.submit()">
              <option value="">Tất cả mức giá</option>
              <option value="under_1m" <?= $priceRange === 'under_1m' ? 'selected' : '' ?>>Dưới 1 triệu (&lt; 1.000.000 ₫)</option>
              <option value="1m_3m" <?= $priceRange === '1m_3m' ? 'selected' : '' ?>>Từ 1 – 3 triệu (1.000.000 ₫ – 3.000.000 ₫)</option>
              <option value="over_3m" <?= $priceRange === 'over_3m' ? 'selected' : '' ?>>Trên 3 triệu (&gt; 3.000.000 ₫)</option>
            </select>
          </label>

          <label>
            LỌC THỜI LƯỢNG TOUR
            <select name="duration" onchange="this.form.submit()">
              <option value="">Mọi thời lượng</option>
              <option value="short" <?= $duration === 'short' ? 'selected' : '' ?>>Ngắn ngày (1 – 2 ngày)</option>
              <option value="medium" <?= $duration === 'medium' ? 'selected' : '' ?>>Trung bình (3 – 4 ngày)</option>
              <option value="long" <?= $duration === 'long' ? 'selected' : '' ?>>Dài ngày (≥ 5 ngày)</option>
            </select>
          </label>

          <label>
            LỌC ĐIỂM ĐÁNH GIÁ TB
            <select name="min_rating" onchange="this.form.submit()">
              <option value="">Mọi mức đánh giá</option>
              <option value="4.5" <?= $minRating === '4.5' ? 'selected' : '' ?>>Từ 4.5 ★ trở lên</option>
              <option value="4" <?= $minRating === '4' ? 'selected' : '' ?>>Từ 4.0 ★ trở lên</option>
              <option value="3" <?= $minRating === '3' ? 'selected' : '' ?>>Từ 3.0 ★ trở lên</option>
            </select>
          </label>

          <label>
            THỨ TỰ SẮP XẾP
            <select name="sort" onchange="this.form.submit()">
              <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Lịch khởi hành gần nhất (Mặc định)</option>
              <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần (Cao đến thấp)</option>
              <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần (Thấp đến cao)</option>
            </select>
          </label>

          <div style="display:flex;gap:8px">
            <a href="<?= h(url('tours')) ?>" class="btn btn-outline btn-small">Đặt lại bộ lọc</a>
          </div>
        </div>
      </form>

      <?php if (!empty($searchErrors)): ?>
        <div class="empty" style="margin-top:24px;border-color:#c93b2b;background:#fff6f4">
          <h3 style="color:#a44634">⚠ Vui lòng kiểm tra lại thông tin tìm kiếm</h3>
          <p>Vui lòng điền đầy đủ <b>Điểm đến</b>, <b>Lịch khởi hành</b> và <b>Số người</b>, sau đó sửa các thông tin không hợp lệ được đánh dấu bên dưới.</p>
        </div>
      <?php elseif (empty($results)): ?>
        <div class="empty" style="margin-top:24px">
          <h3>Không tìm thấy Tour phù hợp</h3>
          <p>Hiện không có Tour "Đang bán" nào có lịch khởi hành "Mở bán" còn đủ <b><?= $people ?> chỗ</b> khớp với tiêu chí và bộ lọc bạn đã chọn.</p>
          <p><b>Gợi ý:</b> Hãy thử đổi Điểm đến (hoặc chọn <em>"Tất cả"</em>), nới rộng khoảng Ngày khởi hành, giảm Số người hoặc chọn lại khoảng giá (< 1tr, 1–3tr, trên 3tr).</p>
          <div style="margin-top:16px">
            <a href="<?= h(url('tours')) ?>" class="btn btn-outline">Xem toàn bộ Tour đang mở bán</a>
          </div>
        </div>
      <?php else: ?>
        <div class="result-line">
          <span>Tìm thấy <b><?= count($results) ?></b> Tour "Đang bán" phù hợp (Mỗi Tour hiển thị 1 lần kèm lịch gần nhất)</span>
          <span>Giá hiển thị: <b>Từ X ₫/người (chưa gồm phụ thu)</b></span>
        </div>
        <div class="card-grid">
          <?php foreach ($results as $t): ?>
            <?php render_tour_card($t, $savedIds); ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </main>
    <?php
    render_footer();
}

function page_tour_detail(): void {
    // Xem chi tiết Tour
    $user = me();
    $savedIds = $user ? saved_tour_ids((int)$user['id']) : [];
    $id = (int)($_GET['id'] ?? 0);
    $tour = tour_with_seller($id);
    if (!$tour) {
        render_error('Tour không tồn tại trên hệ thống.');
        return;
    }

    $isSelling = ($tour['status'] === 'selling' && $tour['seller_status'] === 'verified');
    $deps = departure_rows($id);
    $ratingInfo = tour_rating_summary($id);
    $sellerInfo = seller_stats((int)$tour['seller_id']);
    $isSaved = in_array($id, $savedIds, true);
    $gallery = json_decode((string)($tour['gallery_json'] ?? '[]'), true) ?: [$tour['image_path']];

    // Khách chọn lịch và số người để kiểm tra chỗ và tính tổng giá dự kiến
    $selectedDepId = (int)($_GET['dep_id'] ?? 0);
    $calcPeople = isset($_GET['calc_people']) ? (int)$_GET['calc_people'] : 0;
    $calcError = '';
    $calcSuccess = null;

    if ($isSelling && ($selectedDepId > 0 || $calcPeople > 0)) {
        $chosenDep = null;
        foreach ($deps as $d) {
            if ((int)$d['id'] === $selectedDepId) { $chosenDep = $d; break; }
        }
        if (!$chosenDep) {
            $calcError = 'Vui lòng chọn một Lịch khởi hành đang mở bán.';
        } elseif ($chosenDep['status'] !== 'open' || strtotime($chosenDep['cutoff_at']) <= time()) {
            // Nhánh E2: Báo lịch không khả dụng & cập nhật danh sách lịch
            $calcError = 'Lịch khởi hành bạn chọn hiện không khả dụng (' . ($chosenDep['status'] === 'full' ? 'Hết chỗ' : 'Đóng bán / Đã hủy') . '). Danh sách lịch đã được cập nhật, vui lòng chọn lịch Mở bán khác!';
        } elseif ($calcPeople < 1) {
            $calcError = 'Vui lòng nhập số người dự kiến lớn hơn hoặc bằng 1.';
        } elseif ($calcPeople > (int)$chosenDep['remaining']) {
            // Nhánh E3: Thông báo không đủ chỗ
            $calcError = 'Lịch khởi hành ngày ' . day($chosenDep['start_date']) . ' chỉ còn ' . $chosenDep['remaining'] . ' chỗ trống, không đủ cho ' . $calcPeople . ' người. Vui lòng giảm số người hoặc chọn lịch khác!';
        } else {
            // Tính tổng giá dự kiến từ giá mỗi người và số lượng khách
            $unitPrice = (int)$chosenDep['price_per_person'];
            $calcSuccess = [
                'dep' => $chosenDep,
                'people' => $calcPeople,
                'unit_price' => $unitPrice,
                'estimated_total' => $unitPrice * $calcPeople,
            ];
        }
    }

    render_header('tours');
    ?>
    <main class="container section">
      <a class="back-link" href="<?= h(url('tours')) ?>">← Quay lại danh sách tìm kiếm Tour</a>

      <!-- Bước 2: Kiểm tra trạng thái Tour -> [không mở bán] -> E1. Thông báo Tour hiện không mở bán -->
      <?php if (!$isSelling): ?>
        <div class="panel" style="border:2px solid #c93b2b;background:#fff5f3;margin-bottom:28px">
          <span class="eyebrow" style="color:#c93b2b">THÔNG BÁO</span>
          <h2 style="color:#a04436;margin:8px 0">⚠ Tour hiện không mở bán</h2>
          <p style="color:#173c4b;font-size:15px">
            Tour <b>"<?= h($tour['title']) ?>"</b> hiện đang ở trạng thái <?= badge($tour['status']) ?> (do đơn vị lữ hành tạm ngừng nhận khách hoặc không còn lịch khởi hành nào ở trạng thái "Mở bán"). Hệ thống đã khóa chức năng đặt tour đối với hành trình này.
          </p>
          <div style="margin-top:16px;display:flex;gap:12px">
            <a href="<?= h(url('tours')) ?>" class="btn">Tìm kiếm các Tour đang bán khác</a>
            <a href="<?= h(url('compare')) ?>" class="btn btn-outline">Về bảng so sánh</a>
          </div>
        </div>
      <?php endif; ?>

      <div class="detail-title">
        <div>
          <span class="eyebrow">MÃ TOUR: <?= h($tour['code'] ?? ('TOUR-' . $tour['id'])) ?> · <?= h($tour['origin']) ?> → <?= h($tour['destination']) ?></span>
          <h1><?= h($tour['title']) ?></h1>
          <p>
            Trạng thái Tour: <?= badge($tour['status']) ?> ·
            Thời lượng: <b><?= (int)$tour['days'] ?> ngày <?= (int)$tour['nights'] ?> đêm</b> ·
            Đơn vị tổ chức: <b><?= h($tour['company_name']) ?></b> <?= badge($tour['seller_status']) ?> ·
            Đánh giá:
            <?php if ($ratingInfo['count'] > 0): ?>
              <b style="color:#d97706">★ <?= number_format((float)$ratingInfo['avg'], 1) ?>/5.0</b> (<?= $ratingInfo['count'] ?> đánh giá)
            <?php else: ?>
              <b style="color:var(--muted)">Chưa có đánh giá</b>
            <?php endif; ?>
          </p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <label class="btn btn-outline btn-small" style="cursor:pointer">
            <input type="checkbox" class="compare-box" value="<?= $id ?>" data-title="<?= h($tour['title']) ?>" style="margin-right:6px">
            So sánh tour này
          </label>
          <a href="#" class="btn btn-outline btn-small" data-share>🔗 Chia sẻ đường dẫn</a>
          <form method="post" action="index.php" style="margin:0">
            <?= hidden_csrf() ?>
            <input type="hidden" name="action" value="toggle_save_tour">
            <input type="hidden" name="tour_id" value="<?= $id ?>">
            <input type="hidden" name="return_url" value="<?= h(url('tour', ['id' => $id])) ?>">
            <button type="submit" class="btn <?= $isSaved ? '' : 'btn-outline' ?> btn-small">
              <?= $isSaved ? '♥ Đã lưu (Bỏ lưu)' : '♡ Lưu Tour' ?>
            </button>
          </form>
        </div>
      </div>

      <!-- Bộ sưu tập hình ảnh thực tế của Tour để kiểm tra trực quan -->
      <div class="detail-visual">
        <img id="main-tour-photo" src="<?= h($tour['image_path']) ?>" alt="<?= h($tour['title']) ?>">
        <div class="visual-label">
          📷 <?= h($tour['destination']) ?>
        </div>
      </div>
      <?php if (count($gallery) > 1): ?>
        <div class="photo-thumbs">
          <?php foreach ($gallery as $gIdx => $gPath): ?>
            <button type="button" class="thumb-btn <?= $gIdx === 0 ? 'active' : '' ?>" onclick="document.getElementById('main-tour-photo').src='<?= h($gPath) ?>';document.querySelectorAll('.thumb-btn').forEach(b=>b.classList.remove('active'));this.classList.add('active');">
              <img src="<?= h($gPath) ?>" alt="Ảnh <?= $gIdx + 1 ?>">
              <span>Ảnh #<?= $gIdx + 1 ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="detail-grid">
        <div class="detail-main">
          <!-- Bước 3: Hiển thị thông tin Tour chuẩn hóa (Đã bỏ phần option phòng theo đúng yêu cầu) -->
          <section class="panel">
            <h2>Tổng quan & Thông tin tour</h2>
            <p><?= nl2br(h($tour['description'])) ?></p>
            <div class="detail-facts">
              <div><small>Điểm khởi hành</small><b><?= h($tour['origin']) ?></b></div>
              <div><small>Điểm đến</small><b><?= h($tour['destination']) ?></b></div>
              <div><small>Điểm đón / tập trung</small><b><?= h($tour['pickup']) ?></b></div>
              <div><small>Phương tiện di chuyển</small><b><?= h($tour['transport']) ?></b></div>
              <div><small>Tiêu chuẩn lưu trú</small><b><?= h($tour['accommodation']) ?></b></div>
              <div><small>Chính sách hủy/đổi (Khung của Sàn)</small><b><?= h($tour['cancellation_policy']) ?></b></div>
            </div>
            <div class="info-note" style="margin-bottom:0">
              <b>📄 Giấy tờ cần chuẩn bị & Thời hạn chuẩn bị:</b><br>
              <?= h($tour['required_documents'] ?: 'Căn cước công dân hoặc giấy tờ tùy thân hợp lệ còn hạn (chuẩn bị trước ngày khởi hành).') ?>
            </div>
          </section>

          <section class="panel">
            <h2>2. Lịch trình chi tiết từng ngày (<?= (int)$tour['days'] ?> ngày)</h2>
            <?php
            $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string)$tour['itinerary']) ?: [])));
            foreach ($lines as $idx => $line):
            ?>
              <div class="timeline-item">
                <span>N<?= $idx + 1 ?></span>
                <div>
                  <b>Ngày <?= $idx + 1 ?></b>
                  <p><?= h($line) ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </section>

          <section class="panel">
            <h2>3. Dịch vụ bao gồm & Không bao gồm</h2>
            <div class="two-cols">
              <div>
                <h3>✓ Giá tour bao gồm</h3>
                <p><?= nl2br(h($tour['included'])) ?></p>
              </div>
              <div>
                <h3>✕ Giá tour không bao gồm</h3>
                <p><?= nl2br(h($tour['excluded'])) ?></p>
                <p><b>Khoản phụ thu bắt buộc:</b> <?= money((int)$tour['surcharge_per_person']) ?>/khách (<?= h($tour['surcharge_label']) ?>).</p>
              </div>
            </div>
          </section>

          <section class="panel" id="departures-section">
            <h2>Danh sách lịch khởi hành & Số chỗ còn lại</h2>
            <p>Chỉ lịch <b>"Mở bán"</b> mới được phép chọn để tính giá và đặt tour. Lịch <b>"Hết chỗ"</b>, <b>"Đóng bán"</b> hoặc <b>"Đã hủy"</b> vẫn hiển thị để bạn theo dõi.</p>
            <?php if (empty($deps)): ?>
              <div class="empty compact">Tour này hiện chưa có lịch khởi hành nào được tạo.</div>
            <?php else: ?>
              <div class="table-wrap">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Mã lịch</th>
                      <th>Ngày khởi hành</th>
                      <th>Ngày kết thúc</th>
                      <th>Giờ tập trung</th>
                      <th>Giá/người (chưa phụ thu)</th>
                      <th>Số chỗ còn lại</th>
                      <th>Trạng thái lịch</th>
                      <th>Thao tác</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($deps as $d):
                        $canSelect = $isSelling && $d['status'] === 'open' && (int)$d['remaining'] > 0 && strtotime($d['cutoff_at']) > time();
                    ?>
                      <tr>
                        <td><b><?= h($d['code'] ?? ('DEP-' . $d['id'])) ?></b></td>
                        <td><?= day($d['start_date']) ?></td>
                        <td><?= day($d['end_date']) ?></td>
                        <td><?= h($d['meeting_time'] ?: 'Theo thông báo') ?></td>
                        <td><b><?= money((int)$d['price_per_person']) ?></b></td>
                        <td>
                          <?php if ($canSelect): ?>
                            <b style="color:var(--green)"><?= (int)$d['remaining'] ?> / <?= (int)$d['capacity'] ?> chỗ</b>
                          <?php else: ?>
                            <span class="muted">0 khả dụng</span>
                          <?php endif; ?>
                        </td>
                        <td><?= badge($d['status']) ?></td>
                        <td>
                          <?php if ($canSelect): ?>
                            <a href="<?= h(url('tour', ['id' => $id, 'dep_id' => $d['id'], 'calc_people' => max(1, $calcPeople ?: 2)])) ?>#price-calculator" class="btn btn-small">Chọn lịch</a>
                          <?php else: ?>
                            <span class="muted">Không thể chọn</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </section>

          <!-- Bước 8: Thông tin Seller & Đánh giá (Có đánh giá vs A2: Chưa có đánh giá) -->
          <section class="panel" id="reviews-section">
            <h2>Thông tin đơn vị tổ chức & Đánh giá</h2>
            <div class="detail-facts" style="margin-bottom:22px">
              <div>
                <small>Đơn vị tổ chức (Seller)</small>
                <b><?= h($tour['company_name']) ?> <?= badge($tour['seller_status']) ?></b>
              </div>
              <div>
                <small>Thời gian hoạt động trên Sàn</small>
                <b>Hoạt động từ <?= day($tour['seller_created_at'] ?? '2025-01-15') ?></b>
              </div>
              <div>
                <small>Tổng số lượt đặt thành công</small>
                <b><?= (int)$sellerInfo['booking_count'] ?> booking</b>
              </div>
              <div>
                <small>Điểm đánh giá trung bình</small>
                <?php if ($ratingInfo['count'] > 0): ?>
                  <b style="color:#d97706;font-size:16px">★ <?= number_format((float)$ratingInfo['avg'], 1) ?> / 5.0 (<?= $ratingInfo['count'] ?> đánh giá)</b>
                <?php else: ?>
                  <b style="color:var(--accent-dark)">Chưa có đánh giá</b>
                <?php endif; ?>
              </div>
            </div>

            <hr>
            <h3>Đánh giá từ khách hàng đã trải nghiệm Tour</h3>
            <?php if ($ratingInfo['count'] === 0): ?>
              <!-- Nhánh A2 trên sơ đồ CN07: Hiển thị "Chưa có đánh giá" -->
              <div class="empty compact" style="background:var(--cream)">
                <strong>Chưa có đánh giá</strong>
                <p style="margin:6px 0 0">Tour này hiện chưa có đánh giá nào từ khách hàng. Hãy là người đầu tiên đặt tour và chia sẻ cảm nhận!</p>
              </div>
            <?php else: ?>
              <div class="reviews-list">
                <?php foreach ($ratingInfo['reviews'] as $rv): ?>
                  <div class="review-item">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                      <b>👤 <?= h($rv['user_name']) ?></b>
                      <span style="color:#d97706;font-weight:700"><?= str_repeat('★', (int)$rv['rating']) . str_repeat('☆', 5 - (int)$rv['rating']) ?> (<?= (int)$rv['rating'] ?>/5)</span>
                    </div>
                    <p style="margin:6px 0 4px;color:var(--ink)"><?= h($rv['comment']) ?></p>
                    <small class="muted">Đã đánh giá ngày <?= dt($rv['created_at']) ?></small>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <?php if ($user && $user['role'] === 'customer'): ?>
              <form method="post" action="index.php" class="review-form" style="margin-top:20px;background:var(--cream);padding:18px;border-radius:10px">
                <?= hidden_csrf() ?>
                <input type="hidden" name="action" value="review_submit">
                <input type="hidden" name="tour_id" value="<?= $id ?>">
                <h4 style="margin:0 0 10px">Gửi đánh giá của bạn cho Tour này</h4>
                <div class="form-grid">
                  <label>
                    Số sao đánh giá
                    <select name="rating">
                      <option value="5">★★★★★ — 5 sao (Tuyệt vời)</option>
                      <option value="4">★★★★☆ — 4 sao (Rất tốt)</option>
                      <option value="3">★★★☆☆ — 3 sao (Khá)</option>
                      <option value="2">★★☆☆☆ — 2 sao (Trung bình)</option>
                      <option value="1">★☆☆☆☆ — 1 sao (Cần cải thiện)</option>
                    </select>
                  </label>
                  <label class="span-2">
                    Nhận xét trải nghiệm của bạn
                    <textarea name="comment" rows="2" required placeholder="Chia sẻ cảm nhận về lịch trình, hướng dẫn viên, khách sạn..."></textarea>
                  </label>
                </div>
                <button type="submit" class="btn btn-small">Gửi đánh giá</button>
              </form>
            <?php endif; ?>
          </section>
        </div>

        <!-- Cột phải: Bước 5, 6, 7 - Chọn lịch & nhập số người -> Kiểm tra chỗ & tính tổng giá dự kiến (chưa gồm phụ thu) -->
        <aside class="panel sticky" id="price-calculator">
          <span class="eyebrow">TÍNH GIÁ & KIỂM TRA CHỖ</span>
          <p class="price-label">Giá cơ bản (chưa gồm phụ thu)</p>
          <div class="big-price">
            <?= money((int)$tour['base_price']) ?> <small>/ người</small>
          </div>
          <p class="surcharge-note">
            ⓘ Giá hiển thị và tổng giá dự kiến tại trang Chi tiết Tour <b>chưa bao gồm phụ thu</b> (<?= money((int)$tour['surcharge_per_person']) ?>/khách – <?= h($tour['surcharge_label']) ?>). Khoản phụ thu sẽ được cộng chi tiết trong phần Tóm tắt Đặt tour.
          </p>

          <?php if (!$isSelling): ?>
            <div class="flash error" style="margin-top:16px">
              <b>Tour hiện không mở bán</b><br>
              Không thể chọn lịch hoặc đặt tour lúc này.
            </div>
          <?php else: ?>
            <form method="get" action="index.php" id="detail-calc-form">
              <input type="hidden" name="page" value="tour">
              <input type="hidden" name="id" value="<?= $id ?>">

              <label>
                Chọn lịch khởi hành
                <select name="dep_id" id="detail-dep-select" required>
                  <option value="">-- Chọn lịch khởi hành --</option>
                  <?php foreach ($deps as $d):
                      $isOpen = $d['status'] === 'open' && (int)$d['remaining'] > 0 && strtotime($d['cutoff_at']) > time();
                      $statusLabels = ['open' => 'Mở bán', 'full' => 'Hết chỗ', 'closed' => 'Đóng bán', 'cancelled' => 'Đã hủy'];
                      $statusLabel = $statusLabels[$d['status']] ?? 'Không khả dụng';
                  ?>
                    <option value="<?= (int)$d['id'] ?>"
                            data-price="<?= (int)$d['price_per_person'] ?>"
                            data-remaining="<?= (int)$d['remaining'] ?>"
                            data-status="<?= h($d['status']) ?>"
                            <?= $selectedDepId === (int)$d['id'] ? 'selected' : '' ?>
                            <?= !$isOpen ? 'disabled' : '' ?>>
                      <?= day($d['start_date']) ?> — <?= money((int)$d['price_per_person']) ?> (<?= $isOpen ? 'Còn ' . $d['remaining'] . ' chỗ' : h($statusLabel) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>

              <label>
                Số người dự kiến
                <input type="number" name="calc_people" id="detail-people-input" min="1" max="50" value="<?= $calcPeople > 0 ? $calcPeople : 2 ?>" required>
              </label>

              <button type="submit" class="btn btn-outline btn-wide" style="margin-bottom:12px">
                Kiểm tra chỗ & Tính tổng giá dự kiến
              </button>
            </form>

            <!-- Kết quả Bước 6 & 7 (Cập nhật tức thời bằng cả JS & PHP) -->
            <div id="detail-calc-feedback">
              <?php if ($calcError !== ''): ?>
                <div class="flash error" style="margin-top:8px;margin-bottom:14px">
                  <?= h($calcError) ?>
                </div>
              <?php elseif ($calcSuccess): ?>
                <div class="payment-box" style="margin-top:8px">
                  <span class="eyebrow">TỔNG GIÁ DỰ KIẾN</span>
                  <p style="margin:6px 0;color:var(--ink)">
                    Lịch khởi hành: <b><?= day($calcSuccess['dep']['start_date']) ?></b> (Còn <?= (int)$calcSuccess['dep']['remaining'] ?> chỗ)<br>
                    Đơn giá lịch: <b><?= money($calcSuccess['unit_price']) ?> / người</b><br>
                    Số lượng khách: <b><?= $calcSuccess['people'] ?> người</b>
                  </p>
                  <div style="font-size:22px;font-weight:700;color:var(--accent-dark);margin:8px 0">
                    Tổng dự kiến: <?= money($calcSuccess['estimated_total']) ?>
                  </div>
                  <small style="color:#925335;display:block">
                    * Giá dự kiến trên <b>chưa bao gồm phụ thu</b> nếu phát sinh.
                  </small>
                </div>
              <?php endif; ?>
            </div>

            <!-- Bước 1 của AD-CN09: Nút "Đặt tour" -->
            <?php
            $bookDepId = $calcSuccess ? (int)$calcSuccess['dep']['id'] : $selectedDepId;
            $bookPeople = $calcSuccess ? (int)$calcSuccess['people'] : ($calcPeople > 0 ? $calcPeople : 2);
            if ($bookDepId <= 0) {
                foreach ($deps as $d) {
                    if ($d['status'] === 'open' && (int)$d['remaining'] > 0 && strtotime($d['cutoff_at']) > time()) {
                        $bookDepId = (int)$d['id'];
                        break;
                    }
                }
            }
            ?>
            <?php if ($bookDepId > 0 && $calcError === ''): ?>
              <a id="detail-book-btn" href="<?= h(url('checkout', ['tour_id' => $id, 'departure_id' => $bookDepId, 'people' => $bookPeople])) ?>" class="btn btn-large btn-wide" style="margin-top:10px">
                Đặt tour →
              </a>
            <?php else: ?>
              <button type="button" class="btn btn-large btn-wide" disabled style="opacity:0.5;cursor:not-allowed;margin-top:10px">
                Chọn lịch còn đủ chỗ để Đặt tour
              </button>
            <?php endif; ?>
          <?php endif; ?>

          <p class="safe-note">
            ✓ Không thu phí giữ chỗ ban đầu · Xác nhận chính thức từ Seller trong tối đa 24 giờ.
          </p>
        </aside>
      </div>
    </main>
    <?php
    render_footer();
}


function page_compare(): void {
    // AD-CN08: So sánh, chia sẻ và lưu tour - Bám sát 100% Sơ đồ Activity AD-CN08 (image_d77a59.png) & Bảng 15 SRS
    $user = me();
    $savedIds = $user ? saved_tour_ids((int)$user['id']) : [];
    $rawIds = array_values(array_unique(array_filter(array_map('intval', explode(',', (string)($_GET['ids'] ?? ''))))));

    // Giới hạn tối đa 3 Tour (E1: Không cho chọn thêm quá 3 Tour)
    $overLimitNotice = '';
    if (count($rawIds) > 3) {
        $rawIds = array_slice($rawIds, 0, 3);
        $overLimitNotice = 'Danh sách so sánh tối đa 3 Tour. Hệ thống chỉ giữ lại 3 Tour đầu tiên.';
    }

    $tours = [];
    $skippedInvalid = false;
    foreach ($rawIds as $tid) {
        $t = tour_with_seller($tid);
        if (!$t || !in_array($t['status'], ['selling', 'approved', 'paused'], true)) {
            // Nhánh [Tour lỗi] -> E4. Bỏ qua Tour không khả dụng
            $skippedInvalid = true;
            continue;
        }
        $deps = departure_rows($tid);
        $nearDep = null;
        foreach ($deps as $d) {
            if ($d['status'] === 'open' && (int)$d['remaining'] > 0 && strtotime($d['cutoff_at']) > time()) {
                $nearDep = $d;
                break;
            }
        }
        $t['near_dep'] = $nearDep;
        $t['rating'] = tour_rating_summary($tid);
        $tours[] = $t;
    }

    render_header('compare');
    ?>
    <main class="container section">
      <div class="section-head">
        <div>
          <span class="eyebrow">SO SÁNH, CHIA SẺ VÀ LƯU TOUR</span>
          <h1>Bảng đối chiếu & So sánh Tour song song</h1>
          <p>So sánh từ <b>2 đến tối đa 3 Tour</b>. Nếu một Tour trong đường dẫn chia sẻ ngừng bán, hệ thống vẫn hiển thị và gắn nhãn <b>"Không còn mở bán"</b>.</p>
        </div>
        <?php if (count($tours) >= 2): ?>
          <div style="display:flex;gap:10px">
            <a href="#" class="btn btn-outline btn-small" data-share>🔗 Sao chép đường dẫn chia sẻ</a>
            <a href="<?= h(url('tours')) ?>" class="btn btn-small">+ Chọn thêm / Đổi Tour</a>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($overLimitNotice !== ''): ?>
        <div class="flash error" style="margin-bottom:18px"><?= h($overLimitNotice) ?></div>
      <?php endif; ?>

      <?php if ($skippedInvalid): ?>
        <div class="flash error" style="margin-bottom:18px">
          <strong>⚠ Một số Tour không khả dụng:</strong> Tour trong đường dẫn không tồn tại hoặc không hợp lệ đã được tự động bỏ qua.
        </div>
      <?php endif; ?>

      <?php if (count($tours) < 2): ?>
        <!-- Bước 4 -> [< 2 Tour] -> E2. Chưa cho so sánh -->
        <div class="panel" style="border:2px dashed #cb7550;text-align:center;padding:48px 24px">
          <span class="eyebrow">SO SÁNH TOUR</span>
          <h2 style="color:#aa5636;margin:10px 0">Chọn ít nhất 2 Tour để so sánh</h2>
          <p style="max-width:620px;margin:0 auto 20px">
            Danh sách hiện có <b><?= count($tours) ?> Tour</b>. Vui lòng chọn ít nhất 2 Tour và tối đa 3 Tour để mở bảng so sánh.
          </p>
          <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap">
            <a href="<?= h(url('tours')) ?>" class="btn">Quay lại chọn thêm Tour để so sánh</a>
          </div>
        </div>
      <?php else: ?>
        <!-- Bước 5 & 6: Hiển thị bảng so sánh song song (2–3 Tour) -->
        <div class="table-wrap">
          <table class="data-table compare-table">
            <thead>
              <tr>
                <th style="width:190px">Tiêu chí đối chiếu</th>
                <?php foreach ($tours as $t):
                    $tid = (int)$t['id'];
                    $remainIds = array_values(array_filter(array_map(fn($x) => (int)$x['id'], $tours), fn($x) => $x !== $tid));
                    $removeUrl = url('compare', ['ids' => implode(',', $remainIds)]);
                    $isSelling = $t['status'] === 'selling';
                ?>
                  <th>
                    <img class="compare-img" src="<?= h($t['image_path']) ?>" alt="<?= h($t['title']) ?>">
                    <div style="margin-top:10px">
                      <?php if ($isSelling): ?>
                        <?= badge('selling') ?>
                      <?php else: ?>
                        <span class="badge status-rejected">Không còn mở bán</span>
                      <?php endif; ?>
                      <span class="badge"><?= h($t['code'] ?? ('TOUR-' . $tid)) ?></span>
                    </div>
                    <h3 style="margin:8px 0"><a href="<?= h(url('tour', ['id' => $tid])) ?>"><?= h($t['title']) ?></a></h3>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
                      <a href="<?= h($removeUrl) ?>" class="btn btn-outline btn-small" data-remove-compare="<?= $tid ?>">✕ Bỏ so sánh</a>
                      <form method="post" action="index.php" style="margin:0">
                        <?= hidden_csrf() ?>
                        <input type="hidden" name="action" value="toggle_save_tour">
                        <input type="hidden" name="tour_id" value="<?= $tid ?>">
                        <input type="hidden" name="return_url" value="<?= h($_SERVER['REQUEST_URI'] ?? url('compare', ['ids' => implode(',', $rawIds)])) ?>">
                        <button type="submit" class="btn <?= in_array($tid, $savedIds, true) ? '' : 'btn-outline' ?> btn-small">
                          <?= in_array($tid, $savedIds, true) ? '♥ Đã lưu (Bỏ lưu)' : '♡ Lưu Tour' ?>
                        </button>
                      </form>
                    </div>
                  </th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><b>Đơn vị tổ chức (Seller)</b></td>
                <?php foreach ($tours as $t): ?>
                  <td><b><?= h($t['company_name']) ?></b><br><small style="color:var(--green)">✓ Đã xác minh giấy phép</small></td>
                <?php endforeach; ?>
              </tr>
              <tr>
                <td><b>Giá/người (chưa gồm phụ thu)</b></td>
                <?php foreach ($tours as $t):
                    $price = (int)($t['near_dep']['price_per_person'] ?? $t['base_price']);
                ?>
                  <td>
                    <strong style="font-size:20px;color:var(--accent-dark)"><?= money($price) ?></strong> / người
                    <small style="display:block;color:var(--muted)">Chưa gồm phụ thu: <?= money((int)$t['surcharge_per_person']) ?> (<?= h($t['surcharge_label']) ?>)</small>
                  </td>
                <?php endforeach; ?>
              </tr>
              <tr>
                <td><b>Thời lượng & Hành trình</b></td>
                <?php foreach ($tours as $t): ?>
                  <td><b><?= (int)$t['days'] ?> ngày <?= (int)$t['nights'] ?> đêm</b><br>📍 <?= h($t['origin']) ?> → <?= h($t['destination']) ?></td>
                <?php endforeach; ?>
              </tr>
              <tr>
                <td><b>Lịch khởi hành gần nhất</b></td>
                <?php foreach ($tours as $t): ?>
                  <td>
                    <?php if ($t['status'] === 'selling' && $t['near_dep']): ?>
                      <b><?= day($t['near_dep']['start_date']) ?></b> (Còn <?= (int)$t['near_dep']['remaining'] ?> chỗ)
                    <?php else: ?>
                      <span style="color:#a04436;font-weight:700">Không còn mở bán</span>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
              <tr>
                <td><b>Điểm đánh giá trung bình</b></td>
                <?php foreach ($tours as $t): ?>
                  <td>
                    <?php if ($t['rating']['count'] > 0): ?>
                      <b style="color:#d97706">★ <?= number_format((float)$t['rating']['avg'], 1) ?> / 5.0</b> (<?= $t['rating']['count'] ?> đánh giá)
                    <?php else: ?>
                      <span class="muted"><b>Chưa có đánh giá</b></span>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
              <tr>
                <td><b>Lịch trình tóm tắt</b></td>
                <?php foreach ($tours as $t): ?>
                  <td style="font-size:12px"><?= nl2br(h($t['itinerary'])) ?></td>
                <?php endforeach; ?>
              </tr>
              <tr>
                <td><b>Nơi lưu trú & Phương tiện</b></td>
                <?php foreach ($tours as $t): ?>
                  <td>🏨 <?= h($t['accommodation']) ?><br>🚌 <?= h($t['transport']) ?></td>
                <?php endforeach; ?>
              </tr>
              <tr>
                <td><b>Dịch vụ bao gồm</b></td>
                <?php foreach ($tours as $t): ?>
                  <td style="font-size:12px;color:var(--green)"><?= nl2br(h($t['included'])) ?></td>
                <?php endforeach; ?>
              </tr>
              <tr>
                <td><b>Dịch vụ không bao gồm</b></td>
                <?php foreach ($tours as $t): ?>
                  <td style="font-size:12px;color:#925335"><?= nl2br(h($t['excluded'])) ?></td>
                <?php endforeach; ?>
              </tr>
              <tr>
                <td><b>Mức chính sách hủy/đổi</b></td>
                <?php foreach ($tours as $t): ?>
                  <td><b><?= h($t['cancellation_policy']) ?></b></td>
                <?php endforeach; ?>
              </tr>
              <tr>
                <td><b>Thao tác đặt tour</b></td>
                <?php foreach ($tours as $t):
                    $isSelling = $t['status'] === 'selling' && $t['near_dep'];
                ?>
                  <td>
                    <?php if ($isSelling): ?>
                      <a href="<?= h(url('tour', ['id' => (int)$t['id']])) ?>" class="btn btn-small btn-wide">Xem chi tiết & Đặt tour</a>
                    <?php else: ?>
                      <button type="button" class="btn btn-outline btn-small btn-wide" disabled style="opacity:0.55;cursor:not-allowed">Không còn mở bán (Đã khóa)</button>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </main>
    <?php
    render_footer();
}

function page_saved_tours(): void {
    // AD-CN08: Danh sách "Tour đã lưu" của Khách hàng (A2.1 – A2.6)
    $user = me();
    render_header('saved_tours');
    ?>
    <main class="container section">
      <div class="section-head">
        <div>
          <span class="eyebrow">TOUR ĐÃ LƯU</span>
          <h1>Danh sách Tour đã lưu của bạn</h1>
          <p>Lưu các Tour yêu thích để theo dõi lịch khởi hành, so sánh song song hoặc đặt chỗ nhanh chóng.</p>
        </div>
        <a href="<?= h(url('tours')) ?>" class="btn btn-outline btn-small">+ Khám phá thêm Tour</a>
      </div>

      <?php if (!$user): ?>
        <div class="panel" style="text-align:center;padding:48px 24px">
          <h3>Vui lòng đăng nhập để xem và quản lý "Tour đã lưu"</h3>
          <p>Bạn cần đăng nhập bằng tài khoản Khách hàng để lưu Tour yêu thích.</p>
          <div style="margin-top:16px">
            <a href="<?= h(url('login')) ?>" class="btn">Đăng nhập tài khoản Khách hàng</a>
          </div>
        </div>
      <?php else:
          $savedIds = saved_tour_ids((int)$user['id']);
          $tours = [];
          if ($savedIds) {
              $marks = implode(',', array_fill(0, count($savedIds), '?'));
              $tours = q("SELECT t.*, s.company_name,
                          (SELECT d.start_date FROM departures d WHERE d.tour_id=t.id AND d.status='open' AND d.cutoff_at>NOW() ORDER BY d.start_date ASC LIMIT 1) AS nearest_date,
                          (SELECT d.price_per_person FROM departures d WHERE d.tour_id=t.id AND d.status='open' AND d.cutoff_at>NOW() ORDER BY d.start_date ASC LIMIT 1) AS nearest_price,
                          (SELECT GREATEST(0, CAST(d.capacity AS SIGNED) - COALESCE((SELECT SUM(b.people) FROM bookings b WHERE b.departure_id=d.id AND b.status IN ('pending_seller','confirmed','deposited','paid','completed')),0))
                           FROM departures d WHERE d.tour_id=t.id AND d.status='open' AND d.cutoff_at>NOW() ORDER BY d.start_date ASC LIMIT 1) AS nearest_remaining
                          FROM tours t JOIN sellers s ON s.id=t.seller_id
                          WHERE t.id IN ($marks) ORDER BY t.id DESC", $savedIds)->fetchAll();
          }
      ?>
        <?php if (empty($tours)): ?>
          <div class="empty">
            <h3>Bạn chưa lưu Tour nào trong danh sách quan tâm</h3>
            <p>Bấm vào nút <b>"♡ Lưu tour"</b> trên thẻ Tour, trang Chi tiết Tour hoặc Bảng so sánh để thêm vào danh sách này.</p>
            <a href="<?= h(url('tours')) ?>" class="btn" style="margin-top:12px">Tìm kiếm Tour ngay</a>
          </div>
        <?php else: ?>
          <div class="card-grid">
            <?php foreach ($tours as $t): ?>
              <?php render_tour_card($t, $savedIds); ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </main>
    <?php
    render_footer();
}

function page_login(): void {
    $pendingSaveId = (int)($_SESSION['pending_save_tour_id'] ?? 0);
    render_header('login');
    ?>
    <main class="auth-bg">
      <div class="container">
        <div class="panel auth-card">
          <span class="eyebrow">ĐĂNG NHẬP HỆ THỐNG</span>
          <h1>Chào mừng trở lại</h1>
          <p>Đăng nhập để đặt tour, lưu tour yêu thích hoặc quản lý hoạt động kinh doanh trên <?= h(APP_NAME) ?>.</p>

          <?php if ($pendingSaveId > 0): ?>
            <div class="info-note" style="border:1px solid var(--accent)">
              <b>♥ Đăng nhập để lưu Tour:</b> Bạn đang chọn lưu Tour <b>#<?= $pendingSaveId ?></b>. Sau khi đăng nhập thành công, hệ thống sẽ tự động thêm Tour vào danh sách "Tour đã lưu" và đưa bạn quay lại màn hình trước!
              <form method="post" action="index.php" style="margin-top:10px">
                <?= hidden_csrf() ?>
                <input type="hidden" name="action" value="cancel_pending_save">
                <button type="submit" class="btn btn-outline btn-small">✕ Hủy lưu Tour & Quay lại</button>
              </form>
            </div>
          <?php endif; ?>

          <form method="post" action="index.php" novalidate>
            <?= hidden_csrf() ?>
            <input type="hidden" name="action" value="login">
            <label>
              Email đăng nhập
              <input type="email" name="email" value="<?= h(old_val('email')) ?>" required placeholder="nhap@email.com">
              <?= field_error('email') ?>
            </label>
            <label>
              Mật khẩu
              <input type="password" name="password" required>
              <?= field_error('password') ?>
            </label>
            <button type="submit" class="btn btn-wide" style="margin-top:8px">Đăng nhập</button>
          </form>

          <div class="auth-links">
            <a href="<?= h(url('register')) ?>">Chưa có tài khoản Khách hàng? Đăng ký</a>
            <a href="<?= h(url('seller_register')) ?>">Đăng ký tài khoản Seller →</a>
          </div>
        </div>
      </div>
    </main>
    <?php
    render_footer();
}

function page_register(): void {
    render_header('register');
    ?>
    <main class="auth-bg">
      <div class="container">
        <div class="panel auth-card">
          <span class="eyebrow">TÀI KHOẢN DU KHÁCH</span>
          <h1>Đăng ký Khách hàng</h1>
          <p>Tạo tài khoản Khách hàng để lưu Tour yêu thích, đặt chỗ trực tuyến và quản lý hành khách.</p>

          <form method="post" action="index.php" novalidate>
            <?= hidden_csrf() ?>
            <input type="hidden" name="action" value="register">
            <label>
              Họ và tên <span class="req">*</span>
              <input type="text" name="name" value="<?= h(old_val('name')) ?>" required placeholder="Nguyễn Văn A">
              <?= field_error('name') ?>
            </label>
            <label>
              Email <span class="req">*</span>
              <input type="email" name="email" value="<?= h(old_val('email')) ?>" required placeholder="ban@gmail.com">
              <?= field_error('email') ?>
            </label>
            <label>
              Số điện thoại
              <input type="text" name="phone" value="<?= h(old_val('phone')) ?>" placeholder="0912345678">
              <?= field_error('phone') ?>
            </label>
            <label>
              Mật khẩu (tối thiểu 8 ký tự) <span class="req">*</span>
              <input type="password" name="password" required placeholder="••••••••">
              <?= field_error('password') ?>
            </label>
            <button type="submit" class="btn btn-wide">Tạo tài khoản Khách hàng</button>
          </form>

          <div class="auth-links">
            <a href="<?= h(url('login')) ?>">Đã có tài khoản? Đăng nhập</a>
            <a href="<?= h(url('seller_register')) ?>">Bạn là đơn vị lữ hành? Đăng ký Seller →</a>
          </div>
        </div>
      </div>
    </main>
    <?php
    render_footer();
}

function page_seller_register(): void {
    // CN01: Đăng ký tài khoản Seller
    // ĐÃ XÓA trường Họ và tên ở phần tạo tài khoản theo đúng yêu cầu: chỉ điền Gmail (email), SĐT (phone), Mật khẩu và Nhập lại mật khẩu!
    $user = me();
    render_header('seller_register');
    ?>
    <main class="auth-bg">
      <div class="container">
        <div class="panel auth-card" style="width:min(820px,100%)">
          <span class="eyebrow">ĐĂNG KÝ TÀI KHOẢN SELLER</span>
          <h1>Đăng ký Đơn vị Lữ hành (Seller)</h1>

          <?php if ($user && $user['role'] === 'customer'): ?>
            <!-- E1(a): Đang đăng nhập bằng tài khoản Customer -->
            <div class="flash error">
              <strong>Bạn đang đăng nhập bằng tài khoản Khách hàng.</strong> Vui lòng đăng xuất và sử dụng email chưa đăng ký để tạo tài khoản đơn vị lữ hành.
            </div>
            <form method="post" action="index.php" style="margin-top:16px">
              <?= hidden_csrf() ?>
              <input type="hidden" name="action" value="logout">
              <button type="submit" class="btn">Đăng xuất tài khoản Customer để đăng ký Seller</button>
            </form>

          <?php elseif ($user && $user['role'] === 'seller'):
              $seller = q('SELECT * FROM sellers WHERE user_id=?', [$user['id']])->fetch();
          ?>
            <?php if ($seller && $seller['status'] === 'rejected'): ?>
              <!-- Nhánh A1: Gửi lại hồ sơ Seller bị từ chối -->
              <div class="flash error" style="margin-bottom:18px">
                <b>Hồ sơ Seller của bạn đang ở trạng thái "Bị từ chối".</b><br>
                Lý do từ chối từ Quản trị viên: <b><?= h($seller['rejection_reason'] ?: 'Cần bổ sung giấy phép rõ nét hơn.') ?></b>
              </div>
              <form method="post" action="index.php" enctype="multipart/form-data" novalidate>
                <?= hidden_csrf() ?>
                <input type="hidden" name="action" value="seller_resubmit">
                <div class="form-grid">
                  <label>
                    Email đăng nhập (Đã xác minh - Chỉ đọc)
                    <input type="email" value="<?= h($user['email']) ?>" readonly style="background:#eef2f0">
                  </label>
                  <label>
                    Số điện thoại liên hệ <span class="req">*</span>
                    <input type="text" name="phone" value="<?= h(old_val('phone', $seller['phone'])) ?>" required>
                    <?= field_error('phone') ?>
                  </label>
                  <label>
                    Tên doanh nghiệp lữ hành <span class="req">*</span>
                    <input type="text" name="company" value="<?= h(old_val('company', $seller['company_name'])) ?>" required>
                    <?= field_error('company') ?>
                  </label>
                  <label>
                    Mã số thuế (10 hoặc 13 số) <span class="req">*</span>
                    <input type="text" name="tax_code" value="<?= h(old_val('tax_code', $seller['tax_code'])) ?>" required>
                    <?= field_error('tax_code') ?>
                  </label>
                  <label class="span-2">
                    Địa chỉ trụ sở doanh nghiệp <span class="req">*</span>
                    <input type="text" name="address" value="<?= h(old_val('address', $seller['address'])) ?>" required>
                    <?= field_error('address') ?>
                  </label>
                  <label>
                    Người đại diện theo pháp luật <span class="req">*</span>
                    <input type="text" name="representative" value="<?= h(old_val('representative', $seller['representative'])) ?>" required>
                    <?= field_error('representative') ?>
                  </label>
                  <label>
                    Loại hình lữ hành <span class="req">*</span>
                    <select name="travel_type">
                      <option value="domestic" <?= $seller['travel_type'] === 'domestic' ? 'selected' : '' ?>>Lữ hành Nội địa</option>
                      <option value="international" <?= $seller['travel_type'] === 'international' ? 'selected' : '' ?>>Lữ hành Quốc tế</option>
                    </select>
                    <?= field_error('travel_type') ?>
                  </label>
                  <label>
                    Cập nhật Giấy phép đăng ký kinh doanh (nếu đổi)
                    <input type="file" name="business_license" accept=".pdf,.jpg,.jpeg,.png">
                  </label>
                  <label>
                    Cập nhật Giấy phép kinh doanh dịch vụ lữ hành (nếu đổi)
                    <input type="file" name="travel_license" accept=".pdf,.jpg,.jpeg,.png">
                  </label>
                  <label class="span-2 check-line">
                    <input type="checkbox" name="quota_agreement" value="1" id="resubmit-agree" <?= old_val('quota_agreement') === '1' ? 'checked' : '' ?>>
                    <span>Tôi cam kết số chỗ (quỹ chỗ) khai báo trên Sàn chỉ được bán qua Sàn.</span>
                  </label>
                </div>
                <?= field_error('quota_agreement') ?>
                <button type="submit" id="resubmit-btn" class="btn btn-wide">Gửi lại hồ sơ xác minh</button>
              </form>
            <?php else: ?>
              <!-- E1(b): Hồ sơ đang Chờ xác minh hoặc Đã xác minh -->
              <div class="info-note">
                <b>Trạng thái hồ sơ Seller hiện tại:</b> <?= badge($seller['status'] ?? 'pending') ?><br>
                Doanh nghiệp: <b><?= h($seller['company_name'] ?? $user['name']) ?></b> · MST: <b><?= h($seller['tax_code'] ?? '') ?></b>
              </div>
              <a href="<?= h(url('seller')) ?>" class="btn">Vào Cổng Seller →</a>
            <?php endif; ?>

          <?php else:
              $signup = $_SESSION['seller_signup'] ?? null;
              $isVerified = $signup && !empty($signup['verified']) && $signup['otp_expires'] >= time();
              $demoOtp = $_SESSION['demo_otp'] ?? null;
          ?>
            <div class="panel" style="background:var(--cream);margin-bottom:22px;padding:22px">
              <div style="display:flex;justify-content:space-between;align-items:center">
                <h3 style="margin:0">Thông tin tài khoản Seller</h3>
                <?php if ($signup): ?>
                  <form method="post" action="index.php" style="margin:0">
                    <?= hidden_csrf() ?>
                    <input type="hidden" name="action" value="seller_reset_account">
                    <button type="submit" class="link-button" style="font-size:12px">Đổi Gmail khác</button>
                  </form>
                <?php endif; ?>
              </div>

              <?php if (!$signup): ?>
                <form method="post" action="index.php" novalidate style="margin-top:14px">
                  <?= hidden_csrf() ?>
                  <input type="hidden" name="action" value="seller_otp_send">
                  <div class="form-grid">
                    <label>
                      Địa chỉ Gmail / Email <span class="req">*</span>
                      <input type="email" name="email" value="<?= h(old_val('email')) ?>" required placeholder="doanhnghiep@gmail.com">
                      <?= field_error('email') ?>
                    </label>
                    <label>
                      Mật khẩu (tối thiểu 8 ký tự) <span class="req">*</span>
                      <input type="password" name="password" required placeholder="••••••••">
                      <?= field_error('password') ?>
                    </label>
                    <label>
                      Nhập lại mật khẩu <span class="req">*</span>
                      <input type="password" name="password_confirm" required placeholder="••••••••">
                      <?= field_error('password_confirm') ?>
                    </label>
                  </div>
                  <button type="submit" class="btn">Gửi mã xác minh OTP</button>
                </form>

              <?php elseif (!$isVerified): ?>
                <div class="info-note" style="margin:12px 0">
                  Đã gửi mã OTP tới Gmail: <b><?= h($signup['email']) ?></b>.
                  <?php if ($demoOtp): ?>
                    <br>🔑 <b>Mã xác minh OTP: <code style="font-size:16px;color:#c93b2b"><?= h($demoOtp) ?></code></b>
                  <?php endif; ?>
                </div>
                <form method="post" action="index.php" style="display:flex;gap:12px;align-items:start;flex-wrap:wrap">
                  <?= hidden_csrf() ?>
                  <input type="hidden" name="action" value="seller_otp_verify">
                  <div style="flex:1;min-width:220px">
                    <input type="text" name="otp" value="<?= h((string)$demoOtp) ?>" placeholder="Nhập 6 chữ số OTP" required>
                    <?= field_error('otp') ?>
                  </div>
                  <button type="submit" class="btn">Xác nhận OTP</button>
                </form>
                <form method="post" action="index.php" style="margin-top:10px">
                  <?= hidden_csrf() ?>
                  <input type="hidden" name="action" value="seller_otp_resend">
                  <button type="submit" class="btn btn-outline btn-small">Gửi lại mã OTP</button>
                </form>

              <?php else: ?>
                <div class="flash" style="margin-top:12px">
                  ✓ Gmail <b><?= h($signup['email']) ?></b> đã được xác minh. Mời bạn hoàn tất hồ sơ bên dưới!
                </div>
              <?php endif; ?>
            </div>

            <form method="post" action="index.php" enctype="multipart/form-data" novalidate>
              <?= hidden_csrf() ?>
              <input type="hidden" name="action" value="seller_register">
              <fieldset <?= !$isVerified ? 'disabled' : '' ?> style="border:0;padding:0;margin:0;<?= !$isVerified ? 'opacity:0.55' : '' ?>">
                <h3>Hồ sơ pháp lý doanh nghiệp & Giấy phép lữ hành <?= !$isVerified ? '(Xác minh OTP để tiếp tục)' : '' ?></h3>
                <div class="form-grid">
                  <label>
                    Tên doanh nghiệp lữ hành <span class="req">*</span>
                    <input type="text" name="company" value="<?= h(old_val('company')) ?>" required placeholder="Công ty TNHH Lữ hành ...">
                    <?= field_error('company') ?>
                  </label>
                  <label>
                    Mã số thuế (10 hoặc 13 chữ số) <span class="req">*</span>
                    <input type="text" name="tax_code" value="<?= h(old_val('tax_code')) ?>" required placeholder="0101234567">
                    <?= field_error('tax_code') ?>
                  </label>
                  <label class="span-2">
                    Địa chỉ trụ sở doanh nghiệp <span class="req">*</span>
                    <input type="text" name="address" value="<?= h(old_val('address')) ?>" required placeholder="Số nhà, đường, quận/huyện, tỉnh/thành phố">
                    <?= field_error('address') ?>
                  </label>
                  <label>
                    Người đại diện theo pháp luật <span class="req">*</span>
                    <input type="text" name="representative" value="<?= h(old_val('representative')) ?>" required placeholder="Họ và tên người đại diện">
                    <?= field_error('representative') ?>
                  </label>
                  <label>
                    Số điện thoại doanh nghiệp <span class="req">*</span>
                    <input type="text" name="phone" value="<?= h(old_val('phone')) ?>" required placeholder="0988888888">
                    <?= field_error('phone') ?>
                  </label>
                  <label class="span-2">
                    Loại hình kinh doanh lữ hành <span class="req">*</span>
                    <select name="travel_type" required>
                      <option value="">-- Chọn loại hình lữ hành --</option>
                      <option value="domestic" <?= old_val('travel_type') === 'domestic' ? 'selected' : '' ?>>Lữ hành Nội địa</option>
                      <option value="international" <?= old_val('travel_type') === 'international' ? 'selected' : '' ?>>Lữ hành Quốc tế</option>
                    </select>
                    <?= field_error('travel_type') ?>
                  </label>
                  <label>
                    Tệp 1: Giấy phép đăng ký kinh doanh (PDF/JPG/PNG ≤ 5MB) <span class="req">*</span>
                    <input type="file" name="business_license" accept=".pdf,.jpg,.jpeg,.png" onchange="previewLicenseFile(this, 'prev-biz')">
                    <?= field_error('business_license') ?>
                    <div id="prev-biz" class="file-preview-box"></div>
                  </label>
                  <label>
                    Giấy phép kinh doanh dịch vụ lữ hành <span class="req">*</span>
                    <input type="file" name="travel_license" accept=".pdf,.jpg,.jpeg,.png" onchange="previewLicenseFile(this, 'prev-trv')">
                    <?= field_error('travel_license') ?>
                    <div id="prev-trv" class="file-preview-box"></div>
                  </label>
                  <label class="span-2 check-line">
                    <input type="checkbox" name="quota_agreement" value="1" id="seller-quota-check" <?= old_val('quota_agreement') === '1' ? 'checked' : '' ?>>
                    <span><b>Điều khoản quỹ chỗ:</b> Tôi cam kết toàn bộ quỹ chỗ khai báo trên Sàn chỉ được bán qua Sàn, không bán trùng ra kênh ngoài.</span>
                  </label>
                </div>
                <?= field_error('quota_agreement') ?>
                <button type="submit" id="seller-submit-btn" class="btn btn-wide">Gửi hồ sơ đăng ký Seller</button>
              </fieldset>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </main>
    <?php
    render_footer();
}

function page_checkout(): void {
    // AD-CN09: Đặt tour và giữ chỗ - Bám sát 100% Sơ đồ Activity AD-CN09 (image_d77d3a.png) & Bảng 16 SRS
    // Bước 2: Kiểm tra khách hàng đã đăng nhập -> [chưa] -> A1. Đăng nhập, quay lại với lựa chọn đã giữ
    $customer = require_role('customer');
    $tourId = (int)($_GET['tour_id'] ?? ($_SESSION['checkout_draft']['tour_id'] ?? 0));
    $depId = (int)($_GET['departure_id'] ?? ($_SESSION['checkout_draft']['departure_id'] ?? 0));
    $people = max(1, (int)($_GET['people'] ?? ($_SESSION['checkout_draft']['people'] ?? 2)));
    $step = (string)($_GET['step'] ?? 'form');
    $insufficient = !empty($_GET['insufficient']);

    $tour = tour_with_seller($tourId);
    if (!$tour || $tour['status'] !== 'selling') {
        render_error('Tour hiện không mở bán hoặc không khả dụng để đặt chỗ.');
        return;
    }

    $deps = departure_rows($tourId);
    $openDeps = array_values(array_filter($deps, fn($d) => $d['status'] === 'open' && (int)$d['remaining'] > 0 && strtotime($d['cutoff_at']) > time()));
    if (empty($openDeps)) {
        render_error('Tour hiện không còn lịch khởi hành nào đang mở bán.');
        return;
    }

    $selectedDep = null;
    foreach ($deps as $d) {
        if ((int)$d['id'] === $depId) { $selectedDep = $d; break; }
    }
    if (!$selectedDep) $selectedDep = $openDeps[0];
    $depId = (int)$selectedDep['id'];

    $draft = $_SESSION['checkout_draft'] ?? [];
    $contactName = old_val('contact_name', (string)($draft['contact_name'] ?? $customer['name']));
    $contactPhone = old_val('contact_phone', (string)($draft['contact_phone'] ?? ($customer['phone'] ?: '0912345678')));
    $contactEmail = old_val('contact_email', (string)($draft['contact_email'] ?? $customer['email']));
    $note = old_val('note', (string)($draft['note'] ?? ''));

    // Bước 4: Hệ thống tính tổng tiền, cọc 50%, các hạn; cảnh báo nếu sát hạn
    $baseUnit = (int)$selectedDep['price_per_person'];
    $surchargeUnit = (int)$tour['surcharge_per_person'];
    $totalPrice = ($baseUnit + $surchargeUnit) * $people;
    $depositAmount = (int)ceil($totalPrice / 2);
    $nowTs = time();
    $cutoffTs = strtotime($selectedDep['cutoff_at']);
    $sellerDeadlineTs = min($nowTs + 86400, $cutoffTs);
    $isShortened = $sellerDeadlineTs < ($nowTs + 86400);
    $balanceDeadlineStr = balance_deadline($selectedDep['start_date'], (bool)$tour['is_international']);
    $warnFullPayment = strtotime($balanceDeadlineStr) <= ($nowTs + 48 * 3600);

    render_header('bookings');
    ?>
    <main class="container section booking-flow">
      <a class="back-link" href="<?= h(url('tour', ['id' => $tourId])) ?>">← Quay lại trang Chi tiết Tour</a>

      <?php if ($insufficient): ?>
        <!-- Nhánh E2 trên sơ đồ AD-CN09: Không đủ chỗ -> Xem thông báo, gợi ý lịch khác còn chỗ -->
        <div class="panel" style="border:2px solid #c93b2b;background:#fff5f3;margin-bottom:24px">
          <span class="eyebrow" style="color:#c93b2b">LỊCH KHỞI HÀNH</span>
          <h3 style="color:#a04436;margin:6px 0">⚠ Không đủ chỗ cho số lượng khách đã chọn</h3>
          <p>Lịch khởi hành bạn chọn không còn đủ chỗ cho <b><?= $people ?> người</b>. Vui lòng chọn một trong các lịch khởi hành còn chỗ bên dưới:</p>
          <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">
            <?php foreach ($openDeps as $od): ?>
              <a href="<?= h(url('checkout', ['tour_id' => $tourId, 'departure_id' => $od['id'], 'people' => min($people, (int)$od['remaining'])])) ?>" class="btn btn-outline btn-small">
                Chọn lịch <?= day($od['start_date']) ?> (Còn <?= (int)$od['remaining'] ?> chỗ — <?= money((int)$od['price_per_person']) ?>)
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="checkout-grid">
        <?php if ($step !== 'summary'): ?>
          <!-- MÀN HÌNH 1 (Bước 3–5): Nhập thông tin đặt tour — Nút gửi CHỈ ĐỂ LÀ "Tiếp tục" theo đúng yêu cầu -->
          <section class="panel">
            <span class="eyebrow">THÔNG TIN ĐẶT TOUR</span>
            <h1>Thông tin đặt tour</h1>
            <p>Chọn lịch khởi hành, số lượng khách và nhập thông tin người liên hệ.</p>

            <form method="post" action="index.php" novalidate>
              <?= hidden_csrf() ?>
              <input type="hidden" name="action" value="booking_step1">
              <input type="hidden" name="tour_id" value="<?= $tourId ?>">

              <div class="form-grid">
                <label class="span-2">
                  Lịch khởi hành (Chỉ hiển thị lịch Mở bán) <span class="req">*</span>
                  <select name="departure_id" onchange="location.href='index.php?page=checkout&tour_id=<?= $tourId ?>&departure_id='+this.value+'&people='+document.querySelector('[name=people]').value">
                    <?php foreach ($openDeps as $d): ?>
                      <option value="<?= (int)$d['id'] ?>" <?= (int)$d['id'] === $depId ? 'selected' : '' ?>>
                        <?= h($d['code'] ?? ('DEP-' . $d['id'])) ?> · Khởi hành <?= day($d['start_date']) ?> → <?= day($d['end_date']) ?> · Giá: <?= money((int)$d['price_per_person']) ?>/khách (Còn <?= (int)$d['remaining'] ?> chỗ)
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <?= field_error('departure_id') ?>
                </label>

                <label>
                  Số lượng khách <span class="req">*</span>
                  <input type="number" name="people" min="1" max="<?= (int)$selectedDep['remaining'] ?>" value="<?= $people ?>" required>
                  <small class="muted">Lịch này hiện còn tối đa <?= (int)$selectedDep['remaining'] ?> chỗ trống.</small>
                  <?= field_error('people') ?>
                </label>

                <label>
                  Họ và tên người liên hệ <span class="req">*</span>
                  <input type="text" name="contact_name" value="<?= h($contactName) ?>" required placeholder="Nguyễn Văn A">
                  <?= field_error('contact_name') ?>
                </label>

                <label>
                  Số điện thoại liên hệ <span class="req">*</span>
                  <input type="text" name="contact_phone" value="<?= h($contactPhone) ?>" required placeholder="0912345678">
                  <?= field_error('contact_phone') ?>
                </label>

                <label>
                  Email nhận thông báo & voucher <span class="req">*</span>
                  <input type="email" name="contact_email" value="<?= h($contactEmail) ?>" required placeholder="ban@email.com">
                  <?= field_error('contact_email') ?>
                </label>

                <label class="span-2">
                  Ghi chú cho Seller (Không bắt buộc)
                  <textarea name="note" rows="3" placeholder="Yêu cầu đặc biệt (nếu có)"><?= h($note) ?></textarea>
                </label>
              </div>

              <!-- Đúng yêu cầu người dùng: Trang đặt tour nút chỉ để là "Tiếp tục" -->
              <button type="submit" class="btn btn-large btn-wide">Tiếp tục</button>
            </form>
          </section>

        <?php else: ?>
          <!-- MÀN HÌNH 2 (Bước 6 & 7): Hiển thị tóm tắt, chính sách hủy/đổi, nhắc nhở & nút "Xác nhận đặt" -->
          <section class="panel">
            <span class="eyebrow">TÓM TẮT ĐẶT TOUR</span>
            <h1>Tóm tắt thông tin Đặt tour & Chính sách</h1>
            <p>Vui lòng kiểm tra kỹ bảng tóm tắt chi phí, các mốc thời hạn giữ chỗ và đánh dấu đồng ý chính sách hủy/đổi để hoàn tất giữ chỗ.</p>

            <div class="summary-rows">
              <p><span>Hành trình Tour</span><b><?= h($tour['title']) ?> (<?= h($tour['code']) ?>)</b></p>
              <p><span>Đơn vị tổ chức (Seller)</span><b><?= h($tour['company_name']) ?></b></p>
              <p><span>Đơn vị thu hộ thanh toán</span><b>Sàn TMĐT <?= h(APP_NAME) ?> (Thu hộ)</b></p>
              <p><span>Lịch khởi hành & Giờ tập trung</span><b><?= day($selectedDep['start_date']) ?> → <?= day($selectedDep['end_date']) ?> (<?= h($selectedDep['meeting_time'] ?: '06:30') ?>)</b></p>
              <p><span>Người liên hệ</span><b><?= h($contactName) ?> · <?= h($contactPhone) ?> · <?= h($contactEmail) ?></b></p>
              <p><span>Ghi chú gửi Seller</span><b><?= h($note ?: 'Không có ghi chú') ?></b></p>
              <p><span>Giá cơ bản theo lịch (<?= $people ?> khách × <?= money($baseUnit) ?>)</span><b><?= money($baseUnit * $people) ?></b></p>
              <p><span><?= h($tour['surcharge_label']) ?> (<?= $people ?> khách × <?= money($surchargeUnit) ?>)</span><b><?= money($surchargeUnit * $people) ?></b></p>
              <p class="summary-total"><span>Tổng giá trị Booking cố định</span><strong><?= money($totalPrice) ?></strong></p>
              <p><span>Mức đặt cọc cố định 50% (sau khi Seller xác nhận)</span><b style="color:var(--green)"><?= money($depositAmount) ?> (hoặc chọn Thanh toán 100%)</b></p>
              <p>
                <span>Hạn Seller xác nhận (tối đa 24h)</span>
                <b><?= dt(date('Y-m-d H:i:s', $sellerDeadlineTs)) ?> <?= $isShortened ? '<span class="badge status-pending">Đã rút ngắn theo hạn chốt bán</span>' : '' ?></b>
              </p>
              <p><span>Hạn thanh toán phần còn lại (50% cuối)</span><b><?= dt($balanceDeadlineStr) ?></b></p>
              <p><span>Mức chính sách hủy/đổi áp dụng</span><b><?= h($tour['cancellation_policy']) ?></b></p>
            </div>

            <?php if ($warnFullPayment): ?>
              <!-- AC09.8: Cảnh báo nếu hạn trả phần còn lại sát hạn đặt cọc -->
              <div class="flash error" style="margin-top:18px">
                <strong>⚠ Cảnh báo hạn thanh toán:</strong> Có thể phải thanh toán 100% vì hạn trả phần còn lại (<b><?= day($balanceDeadlineStr) ?></b>) sát hạn đặt cọc; số tiền cần thanh toán cuối cùng sẽ được xác định khi Seller xác nhận booking.
              </div>
            <?php endif; ?>

            <form method="post" action="index.php" style="margin-top:22px" novalidate>
              <?= hidden_csrf() ?>
              <input type="hidden" name="action" value="booking_create">
              <input type="hidden" name="tour_id" value="<?= $tourId ?>">
              <input type="hidden" name="departure_id" value="<?= $depId ?>">
              <input type="hidden" name="people" value="<?= $people ?>">
              <input type="hidden" name="contact_name" value="<?= h($contactName) ?>">
              <input type="hidden" name="contact_phone" value="<?= h($contactPhone) ?>">
              <input type="hidden" name="contact_email" value="<?= h($contactEmail) ?>">
              <input type="hidden" name="note" value="<?= h($note) ?>">

              <label class="check-line" style="background:var(--cream);padding:14px;border-radius:9px;border:1px solid var(--line)">
                <input type="checkbox" name="accept_policy" value="1" id="accept-policy-box" onchange="document.getElementById('confirm-book-btn').disabled=!this.checked">
                <span>Tôi đã đọc và đồng ý với mức chính sách hủy/đổi <b>"<?= h($tour['cancellation_policy']) ?>"</b> cùng quy định giữ chỗ chờ Seller xác nhận trong 24 giờ.</span>
              </label>
              <?= field_error('accept_policy') ?>

              <div style="display:flex;gap:12px;margin-top:16px">
                <a href="<?= h(url('checkout', ['tour_id' => $tourId, 'departure_id' => $depId, 'people' => $people])) ?>" class="btn btn-outline">← Sửa lại thông tin</a>
                <button type="submit" id="confirm-book-btn" class="btn btn-large" style="flex:1" disabled>Xác nhận đặt & Giữ chỗ ngay</button>
              </div>
            </form>
          </section>
        <?php endif; ?>

        <!-- Cột phải: Tóm tắt đơn hàng -->
        <aside class="panel order-summary sticky">
          <img src="<?= h($tour['image_path']) ?>" alt="<?= h($tour['title']) ?>">
          <h3><?= h($tour['title']) ?></h3>
          <p><span>Mã Tour</span><b><?= h($tour['code']) ?></b></p>
          <p><span>Điểm đi → Điểm đến</span><b><?= h($tour['origin']) ?> → <?= h($tour['destination']) ?></b></p>
          <p><span>Lịch đã chọn</span><b><?= day($selectedDep['start_date']) ?></b></p>
          <p><span>Số chỗ còn lại</span><b><?= (int)$selectedDep['remaining'] ?> chỗ</b></p>
          <p><span>Số khách đặt</span><b><?= $people ?> người</b></p>
          <hr>
          <p><span>Giá lịch / người</span><b><?= money($baseUnit) ?></b></p>
          <p><span>Phụ thu / người</span><b><?= money($surchargeUnit) ?></b></p>
          <p class="summary-total"><span>Tổng cộng</span><strong><?= money($totalPrice) ?></strong></p>
          <small>Cọc 50% sau khi Seller xác nhận: <b><?= money($depositAmount) ?></b> (hoặc trả đủ 100%).</small>
        </aside>
      </div>
    </main>
    <?php
    render_footer();
}

function page_customer_bookings(): void {
    // AD-CN10 Bước 1 & 2: Mở "Booking của tôi" -> Hiển thị danh sách tour đã đặt
    $customer = require_role('customer');
    $rows = q("SELECT b.*, d.start_date, d.end_date, d.code AS dep_code, t.title, t.image_path, t.destination,
               (SELECT COUNT(*) FROM passengers p WHERE p.booking_id=b.id) AS saved_passengers
               FROM bookings b
               JOIN departures d ON d.id=b.departure_id
               JOIN tours t ON t.id=d.tour_id
               WHERE b.customer_id=?
               ORDER BY b.created_at DESC", [$customer['id']])->fetchAll();

    render_header('bookings');
    ?>
    <main class="container section">
      <div class="section-head">
        <div>
          <span class="eyebrow">BOOKING CỦA TÔI</span>
          <h1>Danh sách Tour đã đặt của bạn</h1>
          <p>Chọn một Booking để nhập danh sách hành khách, theo dõi thời hạn Seller xác nhận và thực hiện <b>Đặt cọc 50% hoặc Thanh toán 100%</b>.</p>
        </div>
        <a href="<?= h(url('tours')) ?>" class="btn btn-outline btn-small">+ Đặt thêm Tour mới</a>
      </div>

      <?php if (empty($rows)): ?>
        <div class="empty">
          <h3>Bạn chưa có Booking nào</h3>
          <p>Hãy tìm kiếm một hành trình phù hợp và đặt giữ chỗ ngay hôm nay.</p>
          <a href="<?= h(url('tours')) ?>" class="btn" style="margin-top:12px">Khám phá Tour đang bán</a>
        </div>
      <?php else: ?>
        <?php foreach ($rows as $b):
            $pComplete = (int)$b['saved_passengers'] === (int)$b['people'];
        ?>
          <div class="panel booking-row">
            <img src="<?= h($b['image_path']) ?>" alt="<?= h($b['title']) ?>">
            <div style="flex:1">
              <span class="eyebrow">MÃ BOOKING: <?= h($b['code']) ?> · LỊCH <?= h($b['dep_code'] ?? '') ?> (<?= day($b['start_date']) ?> → <?= day($b['end_date']) ?>)</span>
              <h2><a href="<?= h(url('booking', ['id' => (int)$b['id']])) ?>"><?= h($b['title']) ?></a></h2>
              <p>
                Số khách: <b><?= (int)$b['people'] ?> người</b> ·
                Tổng tiền: <b><?= money((int)$b['total']) ?></b> (Đã thanh toán: <b><?= money((int)$b['paid_amount']) ?></b>) ·
                Hành khách:
                <?php if ($pComplete): ?>
                  <span class="badge status-approved">Hoàn tất (<?= (int)$b['saved_passengers'] ?>/<?= (int)$b['people'] ?>)</span>
                <?php else: ?>
                  <span class="badge status-pending">Chưa hoàn tất (<?= (int)$b['saved_passengers'] ?>/<?= (int)$b['people'] ?>)</span>
                <?php endif; ?>
              </p>
              <small class="muted">
                <?php if ($b['status'] === 'pending_seller'): ?>
                  ⏳ Hạn Seller xác nhận: <b><?= dt($b['seller_deadline']) ?></b>
                <?php elseif ($b['status'] === 'confirmed'): ?>
                  💳 Hạn đặt cọc 50% / thanh toán 100%: <b><?= dt($b['deposit_deadline']) ?></b>
                <?php elseif ($b['status'] === 'deposited'): ?>
                  💳 Hạn thanh toán 50% còn lại: <b><?= dt($b['balance_deadline']) ?></b>
                <?php elseif ($b['cancellation_reason']): ?>
                  ⓘ Lý do hủy/từ chối: <b><?= h($b['cancellation_reason']) ?></b>
                <?php endif; ?>
              </small>
            </div>
            <div style="text-align:right">
              <div style="margin-bottom:10px"><?= badge($b['status']) ?></div>
              <a href="<?= h(url('booking', ['id' => (int)$b['id']])) ?>" class="btn btn-small">
                <?= in_array($b['status'], ['pending_seller', 'confirmed', 'deposited'], true) ? 'Nhập hành khách / Thanh toán →' : 'Xem chi tiết Booking →' ?>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </main>
    <?php
    render_footer();
}

function page_booking_detail(): void {
    // AD-CN10: Nhập danh sách hành khách + Thanh toán Đặt cọc 50% hoặc Thanh toán 100% sau khi Seller xác nhận
    $user = me();
    must((bool)$user, 'Vui lòng đăng nhập để xem chi tiết Booking.');
    $id = (int)($_GET['id'] ?? 0);
    $b = booking_for($id);
    must((bool)$b, 'Không tìm thấy Booking.');

    // Kiểm tra quyền truy cập dữ liệu cá nhân hành khách (AC10.4 / NFR-27: Chỉ khách đặt, Seller sở hữu tour hoặc Admin)
    $canAccess = ($user['role'] === 'customer' && (int)$b['customer_id'] === (int)$user['id'])
              || ($user['role'] === 'seller' && (int)$b['seller_user_id'] === (int)$user['id'])
              || ($user['role'] === 'admin');
    must($canAccess, 'Bạn không có quyền truy cập thông tin booking và dữ liệu cá nhân hành khách này.');

    $passengers = q('SELECT * FROM passengers WHERE booking_id=? ORDER BY id ASC', [$id])->fetchAll();
    $draftRows = $_SESSION['passenger_draft_' . $id] ?? null;
    $payments = q('SELECT * FROM payments WHERE booking_id=? ORDER BY created_at DESC', [$id])->fetchAll();
    $canEditPassengers = $user['role'] === 'customer' && in_array($b['status'], ['pending_seller', 'confirmed', 'deposited'], true);
    $isPassengersComplete = count($passengers) === (int)$b['people'];
    $activeTab = (string)($_GET['tab'] ?? 'manual');

    render_header('bookings');
    ?>
    <main class="container section">
      <a class="back-link" href="<?= h(url($user['role'] === 'seller' ? 'seller_bookings' : 'bookings')) ?>">← Quay lại danh sách Booking</a>

      <div class="detail-title">
        <div>
          <span class="eyebrow">MÃ BOOKING: <?= h($b['code']) ?> · ĐẶT LÚC <?= dt($b['created_at']) ?></span>
          <h1><?= h($b['title']) ?></h1>
          <p>
            Trạng thái Booking: <?= badge($b['status']) ?> ·
            Danh sách hành khách:
            <?php if ($isPassengersComplete): ?>
              <span class="badge status-approved">Hoàn tất (<?= count($passengers) ?>/<?= (int)$b['people'] ?> khách)</span>
            <?php else: ?>
              <span class="badge status-pending">Chưa hoàn tất (<?= count($passengers) ?>/<?= (int)$b['people'] ?> khách)</span>
            <?php endif; ?>
          </p>
        </div>
        <button type="button" onclick="window.print()" class="btn btn-outline btn-small">🖨 In Phiếu xác nhận (Voucher)</button>
      </div>

      <div class="checkout-grid" style="margin-top:20px">
        <div class="detail-main">
          <!-- PHẦN 1: THANH TOÁN ĐẶT CỌC 50% HOẶC THANH TOÁN 100% SAU KHI SELLER XÁC NHẬN -->
          <?php if ($user['role'] === 'customer' && in_array($b['status'], ['confirmed', 'deposited'], true)):
              $depositAmt = (int)ceil((int)$b['total'] / 2);
              $remainAmt = (int)$b['total'] - (int)$b['paid_amount'];
              $allowDeposit = ($b['status'] === 'confirmed' && (int)$b['full_required'] === 0);
          ?>
            <section class="panel" style="border:2px solid var(--green);background:#f3faf6">
              <span class="eyebrow" style="color:var(--green)">THANH TOÁN QUA CỔNG SÀN THU HỘ (SAU KHI SELLER ĐÃ XÁC NHẬN)</span>
              <h2 style="margin:6px 0 12px">Chọn hình thức Đặt cọc 50% hoặc Thanh toán 100%</h2>
              <?php if (!$isPassengersComplete): ?>
                <div class="flash error" style="margin-bottom:14px">
                  <strong>⚠ Yêu cầu trước khi thanh toán:</strong> Bạn mới lưu <b><?= count($passengers) ?>/<?= (int)$b['people'] ?></b> hành khách (Trạng thái: <b>Chưa hoàn tất</b>). Vui lòng nhập và bấm <b>"Lưu danh sách"</b> đủ <?= (int)$b['people'] ?> hành khách ở biểu mẫu bên dưới trước khi bấm thanh toán!
                </div>
              <?php endif; ?>

              <form method="post" action="index.php">
                <?= hidden_csrf() ?>
                <input type="hidden" name="action" value="demo_pay">
                <input type="hidden" name="booking_id" value="<?= $id ?>">

                <div class="payment-box" style="margin:10px 0">
                  <?php if ($allowDeposit): ?>
                    <label class="radio-choice">
                      <input type="radio" name="payment_choice" value="deposit" checked>
                      <b>Phương án 1: Đặt cọc 50% tổng giá trị Booking — <?= money($depositAmt) ?></b>
                      <small style="display:block;color:var(--muted);margin-left:22px">
                        Giữ chỗ chính thức; 50% còn lại (<?= money((int)$b['total'] - $depositAmt) ?>) thanh toán trước <?= dt($b['balance_deadline']) ?>.
                      </small>
                    </label>
                  <?php elseif ((int)$b['full_required'] === 1 && $b['status'] === 'confirmed'): ?>
                    <div class="info-note" style="margin:0 0 10px">
                      <b>Booking này yêu cầu thanh toán 100%.</b> Hạn trả phần còn lại rơi trước hoặc trùng hạn đặt cọc nên không thể đặt cọc 50%.
                    </div>
                  <?php endif; ?>

                  <label class="radio-choice">
                    <input type="radio" name="payment_choice" value="full" <?= !$allowDeposit ? 'checked' : '' ?>>
                    <b><?= $b['status'] === 'deposited' ? 'Thanh toán 50% phần còn lại (Hoàn tất 100%)' : 'Phương án 2: Thanh toán ngay 100% toàn bộ Booking' ?> — <?= money($remainAmt) ?></b>
                    <small style="display:block;color:var(--muted);margin-left:22px">
                      Hoàn tất toàn bộ nghĩa vụ thanh toán qua Cổng thanh toán Sàn thu hộ; xuất Phiếu xác nhận (Voucher).
                    </small>
                  </label>
                </div>

                <button type="submit" class="btn btn-large btn-wide" <?= !$isPassengersComplete ? 'disabled style="opacity:0.5;cursor:not-allowed"' : '' ?>>
                  💳 Xác nhận Thanh toán qua Cổng Sàn Thu Hộ
                </button>
              </form>
            </section>
          <?php elseif ($b['status'] === 'pending_seller'): ?>
            <section class="panel" style="background:#fdf8ef;border:1px solid #ecd4b0">
              <span class="eyebrow">CHỜ SELLER XÁC NHẬN</span>
              <h3 style="margin:6px 0">Booking đang được giữ chỗ và chờ Seller phản hồi trước <?= dt($b['seller_deadline']) ?></h3>
              <p style="margin:0">Sau khi Seller xác nhận booking, bạn có thể chọn <b>Đặt cọc 50% hoặc Thanh toán 100%</b>. Trong lúc chờ đợi, bạn có thể nhập trước danh sách hành khách bên dưới.</p>
            </section>
          <?php endif; ?>

          <!-- PHẦN 2: AD-CN10 NHẬP DANH SÁCH HÀNH KHÁCH (NHẬP TAY BƯỚC 5 HOẶC TẢI TỆP EXCEL/CSV A1.1-A1.2) -->
          <section class="panel" id="passengers-section">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
              <div>
                <span class="eyebrow">DANH SÁCH HÀNH KHÁCH</span>
                <h2 style="margin:4px 0">Danh sách hành khách (<?= count($passengers) ?>/<?= (int)$b['people'] ?> người)</h2>
              </div>
              <?php if ($canEditPassengers): ?>
                <div style="display:flex;gap:8px">
                  <a href="<?= h(url('booking', ['id' => $id, 'tab' => 'manual'])) ?>#passengers-section" class="btn <?= $activeTab !== 'excel' ? '' : 'btn-outline' ?> btn-small">✍️ Nhập từng hành khách</a>
                  <a href="<?= h(url('booking', ['id' => $id, 'tab' => 'excel'])) ?>#passengers-section" class="btn <?= $activeTab === 'excel' ? '' : 'btn-outline' ?> btn-small">📂 Tải tệp CSV</a>
                </div>
              <?php endif; ?>
            </div>

            <?= field_error('passengers_general') ?>

            <?php if ($canEditPassengers && $activeTab === 'excel'): ?>
              <div class="info-note" style="margin-top:16px">
                <a href="file.php?type=passenger_template&intl=<?= (int)$b['is_international'] ?>" class="btn btn-outline btn-small">⬇ Tải tệp CSV</a>
              </div>

              <form method="post" action="index.php" enctype="multipart/form-data" style="margin-top:16px">
                <?= hidden_csrf() ?>
                <input type="hidden" name="action" value="passengers_upload">
                <input type="hidden" name="booking_id" value="<?= $id ?>">
                <label>
                  Chọn tệp danh sách hành khách (.CSV từ Excel) <span class="req">*</span>
                  <input type="file" name="passenger_file" accept=".csv,.txt" required>
                </label>
                <button type="submit" class="btn">Kiểm tra tệp & Lưu danh sách hành khách</button>
              </form>

            <?php elseif ($canEditPassengers): ?>
              <p style="margin-top:10px">
                Nhập đầy đủ <b><?= (int)$b['people'] ?> hành khách</b> để hoàn tất danh sách, hoặc nhập trước một số hành khách rồi lưu để tiếp tục sau.
                <?php if ($b['is_international']): ?>
                  <br><b style="color:#aa5636">Lưu ý Tour Quốc tế:</b> Bắt buộc nhập Số hộ chiếu, Quốc tịch và Hạn hộ chiếu phải còn ít nhất 6 tháng tính từ ngày kết thúc tour (<b>>= <?= day(date('Y-m-d', strtotime($b['end_date'] . ' +6 months'))) ?></b>).
                <?php endif; ?>
              </p>

              <form method="post" action="index.php" novalidate>
                <?= hidden_csrf() ?>
                <input type="hidden" name="action" value="passengers_save">
                <input type="hidden" name="booking_id" value="<?= $id ?>">

                <?php for ($i = 0; $i < (int)$b['people']; $i++):
                    $row = $draftRows[$i] ?? ($passengers[$i] ?? []);
                    $pName = (string)($row['full_name'] ?? '');
                    $pGender = (string)($row['gender'] ?? 'female');
                    $pDob = (string)($row['dob'] ?? ($row['date_of_birth'] ?? ''));
                    $pPhone = (string)($row['phone'] ?? '');
                    $pNote = (string)($row['note'] ?? '');
                    $pPassport = (string)($row['passport'] ?? ($row['passport_number'] ?? ''));
                    $pExpiry = (string)($row['passport_expiry'] ?? '');
                    $pNat = (string)($row['nationality'] ?? '');
                ?>
                  <div class="passenger">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                      <h3>Hành khách #<?= $i + 1 ?> <?= $i === 0 ? '<small class="muted">(Bắt buộc ít nhất 1 khách)</small>' : '' ?></h3>
                      <button type="button" class="link-button" style="font-size:12px" onclick="this.closest('.passenger').querySelectorAll('input').forEach(el=>el.value='')">Xóa dòng này</button>
                    </div>
                    <div class="form-grid">
                      <label>
                        Họ và tên <span class="req">*</span>
                        <input type="text" name="passenger_name[<?= $i ?>]" value="<?= h($pName) ?>" placeholder="Họ tên trên giấy tờ">
                        <?= field_error("p_{$i}_name") ?>
                      </label>
                      <label>
                        Giới tính <span class="req">*</span>
                        <select name="gender[<?= $i ?>]">
                          <option value="female" <?= $pGender === 'female' ? 'selected' : '' ?>>Nữ</option>
                          <option value="male" <?= $pGender === 'male' ? 'selected' : '' ?>>Nam</option>
                          <option value="other" <?= $pGender === 'other' ? 'selected' : '' ?>>Khác</option>
                        </select>
                        <?= field_error("p_{$i}_gender") ?>
                      </label>
                      <label>
                        Ngày sinh <span class="req">*</span>
                        <input type="date" name="dob[<?= $i ?>]" value="<?= h($pDob) ?>">
                        <?= field_error("p_{$i}_dob") ?>
                      </label>
                      <label>
                        Số điện thoại <span class="req">*</span>
                        <input type="text" name="passenger_phone[<?= $i ?>]" value="<?= h($pPhone) ?>" placeholder="0912345678">
                        <?= field_error("p_{$i}_phone") ?>
                      </label>
                      <label class="span-2">
                        Ghi chú riêng (Ăn chay, dị ứng, hỗ trợ đặc biệt)
                        <input type="text" name="passenger_note[<?= $i ?>]" value="<?= h($pNote) ?>" placeholder="Ghi chú cho hành khách">
                      </label>
                      <?php if ($b['is_international']): ?>
                        <label>
                          Số hộ chiếu (Tour quốc tế *) <span class="req">*</span>
                          <input type="text" name="passport[<?= $i ?>]" value="<?= h($pPassport) ?>" placeholder="C1234567">
                          <?= field_error("p_{$i}_passport") ?>
                        </label>
                        <label>
                          Hạn hộ chiếu (≥ 6 tháng sau ngày về *) <span class="req">*</span>
                          <input type="date" name="passport_expiry[<?= $i ?>]" value="<?= h($pExpiry) ?>">
                          <?= field_error("p_{$i}_expiry") ?>
                        </label>
                        <label class="span-2">
                          Quốc tịch <span class="req">*</span>
                          <input type="text" name="nationality[<?= $i ?>]" value="<?= h($pNat ?: 'Việt Nam') ?>" placeholder="Việt Nam">
                          <?= field_error("p_{$i}_nationality") ?>
                        </label>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endfor; ?>

                <div style="display:flex;gap:12px;flex-wrap:wrap">
                  <button type="submit" class="btn btn-large">Lưu danh sách hành khách</button>
                </div>
              </form>

            <?php else: ?>
              <!-- Chế độ xem danh sách hành khách (Dành cho Seller sở hữu tour AC10.6 hoặc Booking đã khóa sửa) -->
              <?php if (empty($passengers)): ?>
                <div class="empty compact">Khách hàng chưa lưu danh sách hành khách cho Booking này.</div>
              <?php else: ?>
                <div class="table-wrap" style="margin-top:14px">
                  <table class="data-table">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Họ và tên</th>
                        <th>Giới tính</th>
                        <th>Ngày sinh</th>
                        <th>Số điện thoại</th>
                        <th>Ghi chú</th>
                        <?php if ($b['is_international']): ?>
                          <th>Số hộ chiếu</th>
                          <th>Hạn hộ chiếu</th>
                          <th>Quốc tịch</th>
                        <?php endif; ?>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($passengers as $idx => $p): ?>
                        <tr>
                          <td><?= $idx + 1 ?></td>
                          <td><b><?= h($p['full_name']) ?></b></td>
                          <td><?= $p['gender'] === 'male' ? 'Nam' : ($p['gender'] === 'female' ? 'Nữ' : 'Khác') ?></td>
                          <td><?= day($p['date_of_birth']) ?></td>
                          <td><?= h($p['phone']) ?></td>
                          <td><?= h($p['note'] ?: '—') ?></td>
                          <?php if ($b['is_international']): ?>
                            <td><b><?= h($p['passport_number'] ?: '—') ?></b></td>
                            <td><?= day($p['passport_expiry']) ?></td>
                            <td><?= h($p['nationality'] ?: '—') ?></td>
                          <?php endif; ?>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            <?php endif; ?>
          </section>

          <!-- Lịch sử giao dịch thanh toán -->
          <?php if ($payments): ?>
            <section class="panel">
              <h3>Lịch sử giao dịch thanh toán qua Cổng Sàn Thu Hộ</h3>
              <?php foreach ($payments as $pm): ?>
                <p class="history-line">
                  ✓ <b><?= h($pm['reference']) ?></b> — Số tiền: <b><?= money((int)$pm['amount']) ?></b> — Phương thức: <?= h($pm['method']) ?> — Thời gian: <?= dt($pm['created_at']) ?>
                </p>
              <?php endforeach; ?>
            </section>
          <?php endif; ?>
        </div>

        <!-- Cột phải: Thông tin Phiếu xác nhận (Voucher FR06.09) & Tiến trình -->
        <aside class="panel sticky">
          <span class="eyebrow">PHIẾU XÁC NHẬN BOOKING (VOUCHER)</span>
          <h3 style="margin:8px 0"><?= h($b['code']) ?></h3>
          <div class="progress-list" style="margin:14px 0">
            <div class="done">Đặt tour & Tạm giữ <?= (int)$b['people'] ?> chỗ<small><?= dt($b['created_at']) ?></small></div>
            <div class="<?= in_array($b['status'], ['confirmed', 'deposited', 'paid', 'completed'], true) ? 'done' : ($b['status'] === 'pending_seller' ? 'current' : '') ?>">
              Seller xác nhận trong 24 giờ
              <small><?= $b['confirmed_at'] ? 'Đã xác nhận lúc ' . dt($b['confirmed_at']) : 'Hạn: ' . dt($b['seller_deadline']) ?></small>
            </div>
            <div class="<?= in_array($b['status'], ['deposited', 'paid', 'completed'], true) ? 'done' : ($b['status'] === 'confirmed' ? 'current' : '') ?>">
              Đặt cọc 50% hoặc Thanh toán 100%
              <small><?= $b['deposit_deadline'] ? 'Hạn cọc: ' . dt($b['deposit_deadline']) : 'Mở sau khi Seller xác nhận' ?></small>
            </div>
            <div class="<?= in_array($b['status'], ['paid', 'completed'], true) ? 'done' : ($b['status'] === 'deposited' ? 'current' : '') ?>">
              Hoàn tất thanh toán 100%
              <small>Hạn phần còn lại: <?= dt($b['balance_deadline']) ?></small>
            </div>
          </div>

          <hr>
          <div class="summary-rows" style="font-size:13px">
            <p><span>Đơn vị tổ chức</span><b><?= h($b['company_name']) ?></b></p>
            <p><span>Ngày đi → Ngày về</span><b><?= day($b['start_date']) ?> → <?= day($b['end_date']) ?></b></p>
            <p><span>Giờ tập trung</span><b><?= h($b['meeting_time'] ?: '06:30') ?></b></p>
            <p><span>Điểm đón</span><b><?= h($b['pickup']) ?></b></p>
            <p><span>Người liên hệ</span><b><?= h($b['contact_name']) ?> (<?= h($b['contact_phone']) ?>)</b></p>
            <p><span>Ghi chú booking</span><b><?= h($b['note'] ?: '—') ?></b></p>
            <p><span>Tổng tiền cố định</span><b><?= money((int)$b['total']) ?></b></p>
            <p><span>Đã thanh toán</span><b style="color:var(--green)"><?= money((int)$b['paid_amount']) ?></b></p>
            <p><span>Còn lại cần thanh toán</span><b style="color:var(--accent-dark)"><?= money(max(0, (int)$b['total'] - (int)$b['paid_amount'])) ?></b></p>
          </div>
        </aside>
      </div>
    </main>
    <?php
    render_footer();
}


function get_current_seller(): array {
    $user = require_role('seller');
    $seller = q('SELECT * FROM sellers WHERE user_id=?', [$user['id']])->fetch();
    if (!$seller) {
        flash('Vui lòng hoàn tất hồ sơ Seller trước.', 'error');
        go('seller_register');
    }
    return [$user, $seller];
}

function render_seller_subnav(string $active): void {
    ?>
    <div class="seller-subnav">
      <a href="<?= h(url('seller')) ?>" class="<?= $active === 'seller' ? 'active' : '' ?>">📊 Tổng quan Seller</a>
      <a href="<?= h(url('seller_tours')) ?>" class="<?= in_array($active, ['seller_tours', 'seller_tour_view', 'seller_tour_form'], true) ? 'active' : '' ?>">🗺 Quản lý Tour</a>
      <a href="<?= h(url('seller_departures')) ?>" class="<?= $active === 'seller_departures' ? 'active' : '' ?>">📅 Lịch khởi hành & Quỹ chỗ</a>
      <a href="<?= h(url('seller_bookings')) ?>" class="<?= $active === 'seller_bookings' ? 'active' : '' ?>">✅ Xác nhận Booking</a>
    </div>
    <?php
}

function page_seller_dashboard(): void {
    [$user, $seller] = get_current_seller();
    $sellerId = (int)$seller['id'];

    $sellingCount = (int)q("SELECT COUNT(*) FROM tours WHERE seller_id=? AND status='selling'", [$sellerId])->fetchColumn();
    $pendingBookings = (int)q("SELECT COUNT(*) FROM bookings b JOIN departures d ON d.id=b.departure_id JOIN tours t ON t.id=d.tour_id WHERE t.seller_id=? AND b.status='pending_seller'", [$sellerId])->fetchColumn();
    $revenue = (int)q("SELECT COALESCE(SUM(b.paid_amount),0) FROM bookings b JOIN departures d ON d.id=b.departure_id JOIN tours t ON t.id=d.tour_id WHERE t.seller_id=? AND b.status IN ('deposited','paid','completed')", [$sellerId])->fetchColumn();

    render_header('seller');
    ?>
    <section class="portal-hero">
      <div class="container">
        <span class="eyebrow light">CỔNG ĐƠN VỊ LỮ HÀNH (SELLER PORTAL)</span>
        <h1><?= h($seller['company_name']) ?> <?= badge($seller['status']) ?></h1>
        <p>Mã số thuế: <b><?= h($seller['tax_code']) ?></b> · Người đại diện: <b><?= h($seller['representative']) ?></b> · Loại hình: <b><?= $seller['travel_type'] === 'international' ? 'Quốc tế' : 'Nội địa' ?></b></p>
      </div>
    </section>

    <main class="container section">
      <?php render_seller_subnav('seller'); ?>

      <?php if ($seller['status'] !== 'verified'): ?>
        <!-- AC01.16: Seller chờ xác minh chỉ xem trạng thái hồ sơ, chưa mở chức năng bán tour -->
        <div class="panel" style="border:2px solid #cb7550;background:#fff9f5">
          <span class="eyebrow">TRẠNG THÁI HỒ SƠ SELLER</span>
          <h2>Hồ sơ của bạn đang ở trạng thái: <?= badge($seller['status']) ?></h2>
          <?php if ($seller['status'] === 'pending'): ?>
            <p>Quản trị viên Sàn đang xem xét giấy phép kinh doanh và giấy phép lữ hành của bạn. Chức năng Đăng tour và Quản lý lịch khởi hành sẽ tự động mở ngay khi hồ sơ chuyển sang <b>"Đã xác minh"</b>.</p>
          <?php else: ?>
            <p style="color:#a04436"><b>Lý do từ chối:</b> <?= h($seller['rejection_reason'] ?: 'Cần bổ sung hồ sơ') ?></p>
            <a href="<?= h(url('seller_register')) ?>" class="btn">Chỉnh sửa & Gửi lại hồ sơ Seller</a>
          <?php endif; ?>
          <div class="two-cols" style="margin-top:20px">
            <div>
              <b>Xem Giấy phép đăng ký kinh doanh đã nộp:</b>
              <img src="file.php?id=<?= $sellerId ?>&type=business" alt="Giấy phép kinh doanh" style="margin-top:8px;border:1px solid var(--line);border-radius:8px;max-height:220px">
            </div>
            <div>
              <b>Xem Giấy phép kinh doanh dịch vụ lữ hành đã nộp:</b>
              <img src="file.php?id=<?= $sellerId ?>&type=travel" alt="Giấy phép lữ hành" style="margin-top:8px;border:1px solid var(--line);border-radius:8px;max-height:220px">
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="stats" style="margin-bottom:28px">
          <div class="panel stat">
            <span>Tour đang mở bán ("Đang bán")</span>
            <strong><?= $sellingCount ?></strong>
            <small><a href="<?= h(url('seller_tours')) ?>" class="text-link">Quản lý danh sách Tour →</a></small>
          </div>
          <div class="panel stat">
            <span>Booking chờ xác nhận (≤ 24h)</span>
            <strong><?= $pendingBookings ?></strong>
            <small><a href="<?= h(url('seller_bookings')) ?>" class="text-link">Mở Xác nhận Booking →</a></small>
          </div>
          <div class="panel stat">
            <span>Doanh thu Sàn thu hộ</span>
            <strong><?= money($revenue) ?></strong>
            <small><a href="<?= h(url('seller_departures')) ?>" class="text-link">Quản lý Lịch & Quỹ chỗ →</a></small>
          </div>
        </div>

        <div class="panel">
          <h2>Truy cập nhanh</h2>
          <div class="inline-actions" style="margin-top:14px">
            <a href="<?= h(url('seller_tour_form')) ?>" class="btn">+ Tạo Tour mới</a>
            <a href="<?= h(url('seller_tours')) ?>" class="btn btn-outline">Xem & Quản lý Tour</a>
            <a href="<?= h(url('seller_departures')) ?>" class="btn btn-outline">Quản lý Lịch khởi hành & Quỹ chỗ</a>
            <a href="<?= h(url('seller_bookings')) ?>" class="btn btn-outline">Xác nhận Booking mới</a>
          </div>
        </div>
      <?php endif; ?>
    </main>
    <?php
    render_footer();
}

function page_seller_tours(): void {
    // UC15 / CN03: Quản lý Tour (Bao gồm Xem chi tiết Tour & lịch sử, Tạo mới, Nhân bản A2, Sửa A3/A4, Tạm ngừng/Mở bán lại A5, sang trang Lịch khởi hành CN06)
    [$user, $seller] = get_current_seller();
    must($seller['status'] === 'verified', 'Hồ sơ Seller cần được xác minh trước khi quản lý Tour.');
    $tours = q("SELECT t.*,
                (SELECT COUNT(*) FROM departures d WHERE d.tour_id=t.id AND d.status='open' AND d.cutoff_at>NOW()) AS open_deps,
                (SELECT COUNT(*) FROM departures d WHERE d.tour_id=t.id) AS total_deps
                FROM tours t WHERE t.seller_id=? ORDER BY t.id DESC", [$seller['id']])->fetchAll();

    render_header('seller_tours');
    ?>
    <main class="container section">
      <?php render_seller_subnav('seller_tours'); ?>

      <div class="section-head">
        <div>
          <span class="eyebrow">QUẢN LÝ TOUR CỦA SELLER</span>
          <h1>Danh sách Tour của tôi (<?= count($tours) ?> Tour)</h1>
          <p>Trạng thái <b>"Đang bán"</b> được hệ thống tự động đồng bộ khi Tour <b>"Đã duyệt"</b> có ít nhất 1 lịch khởi hành <b>"Mở bán"</b>. Bạn có thể xem chi tiết, chỉnh sửa, nhân bản hoặc tạm ngừng Tour và quản lý lịch khởi hành.</p>
        </div>
        <a href="<?= h(url('seller_tour_form')) ?>" class="btn">+ Tạo Tour mới</a>
      </div>

      <?php foreach ($tours as $t):
          $tid = (int)$t['id'];
      ?>
        <div class="panel seller-tour">
          <img src="<?= h($t['image_path']) ?>" alt="<?= h($t['title']) ?>">
          <div class="seller-tour-text" style="flex:1">
            <div>
              <?= badge($t['status']) ?>
              <?php if ($t['pending_payload'] !== null): ?>
                <span class="badge status-pending">Có bản sửa đang chờ Admin duyệt</span>
              <?php endif; ?>
              <span class="badge"><?= h($t['code'] ?? ('TOUR-' . $tid)) ?></span>
              <span class="badge"><?= (int)$t['days'] ?>N<?= (int)$t['nights'] ?>Đ · <?= $t['is_international'] ? 'Quốc tế' : 'Trong nước' ?></span>
            </div>
            <h3><a href="<?= h(url('seller_tour_view', ['id' => $tid])) ?>"><?= h($t['title']) ?></a></h3>
            <p>
              📍 <?= h($t['origin']) ?> → <?= h($t['destination']) ?> ·
              Giá cơ bản (chưa phụ thu): <b><?= money((int)$t['base_price']) ?>/khách</b> ·
              Phụ thu: <b><?= money((int)$t['surcharge_per_person']) ?></b> ·
              Lịch đang mở bán: <b><?= (int)$t['open_deps'] ?> / <?= (int)$t['total_deps'] ?> lịch</b>
            </p>
            <?php if ($t['rejection_reason']): ?>
              <p style="color:#a04436;font-weight:700;margin-bottom:10px">⚠ Lý do Admin từ chối: <?= h($t['rejection_reason']) ?></p>
            <?php endif; ?>

            <div class="inline-actions">
              <!-- Bổ sung nút Xem chi tiết Tour & Lịch sử ở UC15 theo đúng yêu cầu người dùng -->
              <a href="<?= h(url('seller_tour_view', ['id' => $tid])) ?>" class="btn btn-small">🔍 Xem Tour & Lịch sử</a>

              <?php if (in_array($t['status'], ['approved', 'selling'], true)): ?>
                <a href="<?= h(url('seller_departures', ['tour_id' => $tid])) ?>" class="btn btn-outline btn-small">📅 Lịch khởi hành & Quỹ chỗ</a>
              <?php endif; ?>

              <?php if ($t['status'] !== 'pending'): ?>
                <a href="<?= h(url('seller_tour_form', ['id' => $tid])) ?>" class="btn btn-outline btn-small">
                  ✎ <?= in_array($t['status'], ['approved', 'selling', 'paused'], true) ? 'Sửa & Gửi duyệt lại' : 'Sửa Tour' ?>
                </a>
              <?php endif; ?>

              <form method="post" action="index.php" class="inline-form">
                <?= hidden_csrf() ?>
                <input type="hidden" name="action" value="tour_clone">
                <input type="hidden" name="tour_id" value="<?= $tid ?>">
                <button type="submit" class="btn btn-outline btn-small">⎘ Nhân bản</button>
              </form>

              <?php if (in_array($t['status'], ['approved', 'selling', 'paused'], true)): ?>
                <form method="post" action="index.php" class="inline-form" onsubmit="return confirm('Xác nhận đổi trạng thái Tạm ngừng / Mở bán lại cho Tour này?')">
                  <?= hidden_csrf() ?>
                  <input type="hidden" name="action" value="tour_pause">
                  <input type="hidden" name="tour_id" value="<?= $tid ?>">
                  <button type="submit" class="btn btn-outline btn-small">
                    <?= $t['status'] === 'paused' ? '▶ Mở bán lại' : '⏸ Tạm ngừng bán' ?>
                  </button>
                </form>
              <?php endif; ?>

              <a href="<?= h(url('tour', ['id' => $tid])) ?>" class="text-link" style="font-size:12px">Xem trên trang Khách hàng ↗</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </main>
    <?php
    render_footer();
}

function page_seller_tour_view(): void {
    // UC15 / CN03: Màn hình Xem chi tiết Tour của Seller & Lịch sử thay đổi (GD1 & GD12 / Nhánh A6)
    [$user, $seller] = get_current_seller();
    $id = (int)($_GET['id'] ?? 0);
    $tour = q("SELECT * FROM tours WHERE id=? AND seller_id=?", [$id, $seller['id']])->fetch();
    must((bool)$tour, 'Không tìm thấy Tour thuộc Seller này.');
    sync_departure_and_tour($id);
    $tour = q("SELECT * FROM tours WHERE id=?", [$id])->fetch();
    $deps = departure_rows($id);
    $logs = q("SELECT a.*, u.name AS actor_name FROM audit_log a LEFT JOIN users u ON u.id=a.actor_id WHERE a.object_type='tour' AND a.object_id=? ORDER BY a.created_at DESC", [$id])->fetchAll();
    $gallery = json_decode((string)($tour['gallery_json'] ?? '[]'), true) ?: [$tour['image_path']];

    render_header('seller_tours');
    ?>
    <main class="container section">
      <?php render_seller_subnav('seller_tour_view'); ?>
      <a class="back-link" href="<?= h(url('seller_tours')) ?>">← Quay lại danh sách Tour của tôi</a>

      <div class="detail-title">
        <div>
          <span class="eyebrow">CHI TIẾT TOUR & LỊCH SỬ THAY ĐỔI</span>
          <h1><?= h($tour['title']) ?></h1>
          <p>Mã Tour: <b><?= h($tour['code']) ?></b> · Trạng thái hiện tại: <?= badge($tour['status']) ?> · Giá cơ bản: <b><?= money((int)$tour['base_price']) ?>/người (chưa gồm phụ thu)</b></p>
        </div>
        <div class="inline-actions">
          <a href="<?= h(url('seller_tour_form', ['id' => $id])) ?>" class="btn btn-small">✎ Sửa thông tin Tour</a>
          <?php if (in_array($tour['status'], ['approved', 'selling'], true)): ?>
            <a href="<?= h(url('seller_departures', ['tour_id' => $id])) ?>" class="btn btn-outline btn-small">📅 Quản lý Lịch khởi hành & Quỹ chỗ</a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Kiểm tra hình ảnh trực quan của Tour trên giao diện Seller -->
      <section class="panel" style="margin-bottom:22px">
        <h2>1. Kiểm tra Hình ảnh Tour hiển thị trên Sàn</h2>
        <div style="display:grid;grid-template-columns:360px 1fr;gap:20px;align-items:start">
          <img id="seller-view-main-img" src="<?= h($tour['image_path']) ?>" alt="<?= h($tour['title']) ?>" style="width:100%;height:240px;object-fit:cover;border-radius:12px;border:1px solid var(--line)">
          <div>
            <p><b>Đường dẫn ảnh chính:</b> <code><?= h($tour['image_path']) ?></code></p>
            <p>Bấm vào từng ảnh trong bộ sưu tập bên dưới để kiểm tra hiển thị:</p>
            <div class="photo-thumbs">
              <?php foreach ($gallery as $idx => $gPath): ?>
                <button type="button" class="thumb-btn <?= $idx === 0 ? 'active' : '' ?>" onclick="document.getElementById('seller-view-main-img').src='<?= h($gPath) ?>'">
                  <img src="<?= h($gPath) ?>" alt="Ảnh #<?= $idx + 1 ?>">
                  <span>Ảnh #<?= $idx + 1 ?></span>
                </button>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </section>

      <?php if ($tour['pending_payload'] !== null):
          $pending = json_decode((string)$tour['pending_payload'], true) ?: [];
      ?>
        <section class="panel" style="border:2px solid #d97706;background:#fffbf2;margin-bottom:22px">
          <span class="eyebrow">BẢN SỬA ĐANG CHỜ QUẢN TRỊ VIÊN DUYỆT</span>
          <h3>So sánh bản đang bán và bản sửa chờ duyệt</h3>
          <p>Trong thời gian chờ duyệt, trang Khách hàng vẫn hiển thị và nhận booking theo <b>Bản đang bán</b>:</p>
          <div class="two-cols">
            <div>
              <b>Bản đang áp dụng hiện tại:</b>
              <p>Tên: <?= h($tour['title']) ?><br>Giá cơ bản: <b><?= money((int)$tour['base_price']) ?></b><br>Chính sách: <?= h($tour['cancellation_policy']) ?></p>
            </div>
            <div>
              <b>Bản sửa mới đang chờ duyệt:</b>
              <p>Tên: <?= h($pending['title'] ?? $tour['title']) ?><br>Giá cơ bản mới: <b><?= money((int)($pending['base_price'] ?? $tour['base_price'])) ?></b><br>Chính sách mới: <?= h($pending['cancellation_policy'] ?? $tour['cancellation_policy']) ?></p>
            </div>
          </div>
        </section>
      <?php endif; ?>

      <div class="two-cols" style="margin-bottom:22px">
        <section class="panel">
          <h2>2. Thông tin chi tiết Tour</h2>
          <p><b>Hành trình:</b> <?= h($tour['origin']) ?> → <?= h($tour['destination']) ?> (<?= (int)$tour['days'] ?> ngày <?= (int)$tour['nights'] ?> đêm)</p>
          <p><b>Điểm đón:</b> <?= h($tour['pickup']) ?></p>
          <p><b>Phương tiện:</b> <?= h($tour['transport']) ?> · <b>Lưu trú:</b> <?= h($tour['accommodation']) ?></p>
          <p><b>Chính sách hủy/đổi:</b> <?= h($tour['cancellation_policy']) ?></p>
          <p><b>Giấy tờ cần chuẩn bị:</b> <?= h($tour['required_documents'] ?: 'CCCD / Giấy tờ tùy thân') ?></p>
          <hr>
          <b>Lịch trình theo ngày:</b>
          <p><?= nl2br(h($tour['itinerary'])) ?></p>
        </section>

        <section class="panel">
          <h2>3. Tóm tắt Lịch khởi hành (<?= count($deps) ?> lịch)</h2>
          <?php if (empty($deps)): ?>
            <p>Chưa có lịch khởi hành nào.</p>
          <?php else: ?>
            <?php foreach ($deps as $d): ?>
              <div class="dep-admin">
                <b><?= h($d['code']) ?> · <?= day($d['start_date']) ?> → <?= day($d['end_date']) ?></b> <?= badge($d['status']) ?>
                <p>Giá: <b><?= money((int)$d['price_per_person']) ?></b> · Tổng: <?= (int)$d['capacity'] ?> · Đang giữ: <?= (int)$d['held_seats'] ?> · Đã bán: <?= (int)$d['sold_seats'] ?> · <b>Còn lại: <?= (int)$d['remaining'] ?> chỗ</b></p>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
          <?php if (in_array($tour['status'], ['approved', 'selling'], true)): ?>
            <a href="<?= h(url('seller_departures', ['tour_id' => $id])) ?>" class="btn btn-small" style="margin-top:10px">Quản lý Lịch khởi hành & Quỹ chỗ →</a>
          <?php endif; ?>
        </section>
      </div>

      <!-- Nhánh A6 (GD12): Lịch sử thay đổi Tour -->
      <section class="panel">
        <span class="eyebrow">LỊCH SỬ THAY ĐỔI</span>
        <h2>Lịch sử thay đổi & Kiểm duyệt Tour</h2>
        <?php if (empty($logs)): ?>
          <p class="muted">Chưa có bản ghi lịch sử nào.</p>
        <?php else: ?>
          <?php foreach ($logs as $lg): ?>
            <p class="history-line">
              🕒 <b><?= dt($lg['created_at']) ?></b> — Người thực hiện: <b><?= h($lg['actor_name'] ?: 'Hệ thống') ?></b> — Thao tác: <span class="badge"><?= h($lg['action']) ?></span> — Chi tiết: <?= h($lg['detail'] ?: '—') ?>
            </p>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>
    </main>
    <?php
    render_footer();
}

function page_seller_tour_form(): void {
    // UC15 / CN03: Biểu mẫu Tạo / Sửa Tour có Xem trước Ảnh trực quan trên giao diện
    [$user, $seller] = get_current_seller();
    must($seller['status'] === 'verified', 'Seller cần được xác minh trước khi tạo hoặc sửa Tour.');
    $id = (int)($_GET['id'] ?? 0);
    $tour = $id ? q('SELECT * FROM tours WHERE id=? AND seller_id=?', [$id, $seller['id']])->fetch() : null;
    if ($id && !$tour) {
        render_error('Không tìm thấy Tour cần sửa.');
        return;
    }

    $presetImages = [
        'assets/images/ha-long.jpg' => 'Vịnh Hạ Long',
        'assets/images/hoi-an.jpg' => 'Phố cổ Hội An',
        'assets/images/sa-pa.jpg' => 'Ruộng bậc thang Sa Pa',
        'assets/images/phu-quoc.jpg' => 'Đảo ngọc Phú Quốc',
        'assets/images/da-lat.jpg' => 'Cao nguyên Đà Lạt',
        'assets/images/ninh-binh.jpg' => 'Tràng An Ninh Bình',
        'assets/images/bangkok.jpg' => 'Bangkok Thái Lan',
        'assets/images/tokyo.jpg' => 'Tokyo Nhật Bản',
    ];
    $currentImg = old_val('image_path', (string)($tour['image_path'] ?? 'assets/images/ha-long.jpg'));

    render_header('seller_tours');
    ?>
    <main class="container section">
      <?php render_seller_subnav('seller_tour_form'); ?>
      <a class="back-link" href="<?= h(url('seller_tours')) ?>">← Quay lại danh sách Tour</a>

      <div class="panel">
        <span class="eyebrow"><?= $tour ? 'CẬP NHẬT TOUR (' . h($tour['code']) . ')' : 'TẠO TOUR MỚI' ?></span>
        <h1><?= $tour ? 'Chỉnh sửa: ' . h($tour['title']) : 'Tạo Tour du lịch mới' ?></h1>
        <?php if ($tour && $tour['status'] === 'rejected' && $tour['rejection_reason']): ?>
          <div class="flash error" style="margin-bottom:18px">
            <b>Tour bị từ chối:</b> Lý do từ Quản trị viên: <b><?= h($tour['rejection_reason']) ?></b>. Hãy chỉnh sửa và chọn "Gửi duyệt".
          </div>
        <?php elseif ($tour && in_array($tour['status'], ['approved', 'selling'], true)): ?>
          <div class="info-note">
            <b>Tour đang mở bán:</b> Mọi chỉnh sửa giá, lịch trình, dịch vụ sẽ được lưu thành <b>Bản sửa chờ duyệt</b>. Trong lúc chờ duyệt, trang Khách hàng vẫn hiển thị và bán theo phiên bản đã duyệt hiện tại.
          </div>
        <?php endif; ?>

        <form method="post" action="index.php" enctype="multipart/form-data" novalidate>
          <?= hidden_csrf() ?>
          <input type="hidden" name="action" value="tour_save">
          <input type="hidden" name="tour_id" value="<?= $id ?>">

          <div class="form-grid">
            <label class="span-2">
              Tên Tour du lịch <span class="req">*</span>
              <input type="text" name="title" value="<?= h(old_val('title', (string)($tour['title'] ?? ''))) ?>" required>
              <?= field_error('title') ?>
            </label>

            <label>
              Điểm khởi hành <span class="req">*</span>
              <input type="text" name="origin" value="<?= h(old_val('origin', (string)($tour['origin'] ?? 'Hà Nội'))) ?>" required>
              <?= field_error('origin') ?>
            </label>

            <label>
              Điểm đến <span class="req">*</span>
              <input type="text" name="destination" value="<?= h(old_val('destination', (string)($tour['destination'] ?? ''))) ?>" required>
              <?= field_error('destination') ?>
            </label>

            <label>
              Số ngày (≥ 1) <span class="req">*</span>
              <input type="number" name="days" min="1" max="30" value="<?= h(old_val('days', (string)($tour['days'] ?? 3))) ?>" required>
              <?= field_error('days') ?>
            </label>

            <label>
              Số đêm <span class="req">*</span>
              <input type="number" name="nights" min="0" max="30" value="<?= h(old_val('nights', (string)($tour['nights'] ?? 2))) ?>" required>
              <?= field_error('nights') ?>
            </label>

            <label>
              Giá cơ bản / người (VNĐ, chưa gồm phụ thu, > 0) <span class="req">*</span>
              <input type="number" name="base_price" min="1" step="10000" value="<?= h(old_val('base_price', (string)($tour['base_price'] ?? 2500000))) ?>" required>
              <?= field_error('base_price') ?>
            </label>

            <label>
              Phụ thu bắt buộc / người (VNĐ)
              <input type="number" name="surcharge_per_person" min="0" step="10000" value="<?= h(old_val('surcharge_per_person', (string)($tour['surcharge_per_person'] ?? 150000))) ?>">
              <?= field_error('surcharge_per_person') ?>
            </label>

            <label>
              Tên khoản phụ thu
              <input type="text" name="surcharge_label" value="<?= h(old_val('surcharge_label', (string)($tour['surcharge_label'] ?? 'Phụ thu vé thắng cảnh & phí dịch vụ'))) ?>">
            </label>

            <label>
              Mức chính sách hủy/đổi <span class="req">*</span>
              <?php $curPol = old_val('cancellation_policy', (string)($tour['cancellation_policy'] ?? CANCEL_POLICY_FRAME[0])); ?>
              <select name="cancellation_policy" required>
                <?php foreach (CANCEL_POLICY_FRAME as $pol): ?>
                  <option value="<?= h($pol) ?>" <?= $curPol === $pol ? 'selected' : '' ?>><?= h($pol) ?></option>
                <?php endforeach; ?>
              </select>
              <?= field_error('cancellation_policy') ?>
            </label>

            <label>
              Điểm đón / Tập trung <span class="req">*</span>
              <input type="text" name="pickup" value="<?= h(old_val('pickup', (string)($tour['pickup'] ?? 'Nhà hát Lớn Hà Nội'))) ?>" required>
              <?= field_error('pickup') ?>
            </label>

            <label>
              Phương tiện di chuyển <span class="req">*</span>
              <input type="text" name="transport" value="<?= h(old_val('transport', (string)($tour['transport'] ?? 'Xe du lịch đời mới'))) ?>" required>
              <?= field_error('transport') ?>
            </label>

            <label class="span-2">
              Nơi lưu trú tiêu chuẩn <span class="req">*</span>
              <input type="text" name="accommodation" value="<?= h(old_val('accommodation', (string)($tour['accommodation'] ?? 'Khách sạn tiêu chuẩn 4 sao'))) ?>" required>
              <?= field_error('accommodation') ?>
            </label>

            <label class="span-2 check-line" style="background:var(--cream);padding:12px;border-radius:8px">
              <?php $isIntl = (int)old_val('is_international', (string)($tour['is_international'] ?? 0)) === 1; ?>
              <input type="checkbox" name="is_international" value="1" <?= $isIntl ? 'checked' : '' ?>>
              <span><b>Tour quốc tế hoặc qua cửa khẩu</b> (Bắt buộc khai báo giấy tờ cần chuẩn bị và thời hạn chuẩn bị)</span>
            </label>

            <label class="span-2">
              Giấy tờ cần chuẩn bị & Thời hạn chuẩn bị (Bắt buộc nếu là Tour quốc tế)
              <input type="text" name="required_documents" value="<?= h(old_val('required_documents', (string)($tour['required_documents'] ?? ''))) ?>">
              <?= field_error('required_documents') ?>
            </label>

            <label class="span-2">
              Mô tả tổng quan hành trình <span class="req">*</span>
              <textarea name="description" rows="2" required><?= h(old_val('description', (string)($tour['description'] ?? ''))) ?></textarea>
              <?= field_error('description') ?>
            </label>

            <label class="span-2">
              Lịch trình chi tiết theo từng ngày <span class="req">*</span>
              <textarea name="itinerary" rows="4" required><?= h(old_val('itinerary', (string)($tour['itinerary'] ?? ''))) ?></textarea>
              <?= field_error('itinerary') ?>
            </label>

            <label>
              Dịch vụ bao gồm trong giá <span class="req">*</span>
              <textarea name="included" rows="3" required><?= h(old_val('included', (string)($tour['included'] ?? ''))) ?></textarea>
              <?= field_error('included') ?>
            </label>

            <label>
              Dịch vụ không bao gồm <span class="req">*</span>
              <textarea name="excluded" rows="3" required><?= h(old_val('excluded', (string)($tour['excluded'] ?? ''))) ?></textarea>
              <?= field_error('excluded') ?>
            </label>

            <!-- KHU VỰC CHỌN VÀ KIỂM TRA ẢNH TRỰC QUAN TRÊN GIAO DIỆN -->
            <div class="span-2 panel" style="background:var(--cream);margin-top:8px;margin-bottom:18px">
              <h3 style="margin-top:0">Hình ảnh Tour & Xem trước</h3>
              <div style="display:grid;grid-template-columns:280px 1fr;gap:20px;align-items:start">
                <div>
                  <small class="muted" style="display:block;margin-bottom:6px"><b>Ảnh đang chọn hiển thị trên Sàn:</b></small>
                  <img id="tour-form-preview" src="<?= h($currentImg) ?>" alt="Xem trước ảnh Tour" style="width:100%;height:190px;object-fit:cover;border-radius:10px;border:2px solid var(--accent)">
                </div>
                <div>
                  <label style="margin-top:0">
                    Chọn ảnh từ thư viện:
                  </label>
                  <div class="preset-image-grid">
                    <?php foreach ($presetImages as $imgPath => $imgLabel): ?>
                      <label class="preset-img-card <?= $currentImg === $imgPath ? 'selected' : '' ?>">
                        <input type="radio" name="image_path" value="<?= h($imgPath) ?>" <?= $currentImg === $imgPath ? 'checked' : '' ?> onchange="document.getElementById('tour-form-preview').src=this.value;document.querySelectorAll('.preset-img-card').forEach(c=>c.classList.remove('selected'));this.closest('.preset-img-card').classList.add('selected');">
                        <img src="<?= h($imgPath) ?>" alt="<?= h($imgLabel) ?>">
                        <span><?= h($imgLabel) ?></span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                  <?= field_error('image_path') ?>

                  <label style="margin-top:14px">
                    Tải ảnh từ máy tính (JPG, PNG ≤ 5 MB)
                    <input type="file" name="tour_image" accept=".jpg,.jpeg,.png" onchange="if(this.files&&this.files[0]){const r=new FileReader();r.onload=e=>document.getElementById('tour-form-preview').src=e.target.result;r.readAsDataURL(this.files[0]);}">
                    <?= field_error('tour_image') ?>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <div style="display:flex;gap:12px;flex-wrap:wrap">
            <?php if (!$tour || in_array($tour['status'], ['draft', 'rejected'], true)): ?>
              <button type="submit" name="intent" value="draft" class="btn btn-outline btn-large">Lưu nháp Tour</button>
            <?php endif; ?>
            <button type="submit" name="intent" value="submit" class="btn btn-large">
              <?= ($tour && in_array($tour['status'], ['approved', 'selling', 'paused'], true)) ? 'Gửi duyệt lại bản sửa' : 'Gửi kiểm duyệt Tour' ?>
            </button>
          </div>
        </form>
      </div>
    </main>
    <?php
    render_footer();
}

function page_seller_departures(): void {
    // AD-CN06: Quản lý lịch khởi hành và quỹ chỗ — TRANG RIÊNG BIỆT bám sát 100% Sơ đồ Activity AD-CN06 (image_d77d02.png) & Bảng 12 SRS
    [$user, $seller] = get_current_seller();
    must($seller['status'] === 'verified', 'Seller cần được xác minh trước khi quản lý lịch khởi hành.');

    $sellerTours = q("SELECT * FROM tours WHERE seller_id=? AND status IN ('approved','selling','paused') ORDER BY id ASC", [$seller['id']])->fetchAll();
    if (empty($sellerTours)) {
        render_header('seller_departures');
        ?>
        <main class="container section">
          <?php render_seller_subnav('seller_departures'); ?>
          <div class="empty">
            <h3>Chưa có Tour nào ở trạng thái "Đã duyệt" hoặc "Đang bán"</h3>
            <p>Chỉ các Tour đã qua Quản trị viên kiểm duyệt mới được phép tạo và quản lý Lịch khởi hành & Quỹ chỗ.</p>
            <a href="<?= h(url('seller_tours')) ?>" class="btn" style="margin-top:12px">Quay lại Quản lý Tour</a>
          </div>
        </main>
        <?php
        render_footer();
        return;
    }

    $selectedTourId = (int)($_GET['tour_id'] ?? $sellerTours[0]['id']);
    $currentTour = null;
    foreach ($sellerTours as $st) {
        if ((int)$st['id'] === $selectedTourId) { $currentTour = $st; break; }
    }
    if (!$currentTour) {
        $currentTour = $sellerTours[0];
        $selectedTourId = (int)$currentTour['id'];
    }

    $deps = departure_rows($selectedTourId);
    // Tải lại thông tin tour sau khi đồng bộ
    $currentTour = q("SELECT * FROM tours WHERE id=?", [$selectedTourId])->fetch();

    $editId = (int)($_GET['edit_id'] ?? 0);
    $editDep = null;
    if ($editId > 0) {
        foreach ($deps as $d) {
            if ((int)$d['id'] === $editId) { $editDep = $d; break; }
        }
    }

    // Lấy lịch sử thay đổi lịch khởi hành & quỹ chỗ của tour này (Bước 9)
    $depIds = array_map(fn($d) => (int)$d['id'], $deps);
    $historyLogs = [];
    if ($depIds) {
        $marks = implode(',', array_fill(0, count($depIds), '?'));
        $historyLogs = q("SELECT a.*, u.name AS actor_name FROM audit_log a LEFT JOIN users u ON u.id=a.actor_id WHERE a.object_type='departure' AND a.object_id IN ($marks) ORDER BY a.created_at DESC LIMIT 20", $depIds)->fetchAll();
    }

    render_header('seller_departures');
    ?>
    <main class="container section">
      <?php render_seller_subnav('seller_departures'); ?>

      <div class="section-head">
        <div>
          <span class="eyebrow">QUẢN LÝ LỊCH KHỞI HÀNH VÀ QUỸ CHỖ</span>
          <h1>Quản lý Lịch khởi hành & Quỹ chỗ dành cho Sàn</h1>
          <p>Chọn Tour để xem lịch khởi hành và số chỗ còn lại. Bạn có thể thêm hoặc sửa lịch, đóng bán hoặc mở bán lại trước hạn chốt bán. Trạng thái Tour và lịch sử được cập nhật tự động.</p>
        </div>
      </div>

      <section class="panel" style="margin-bottom:22px;background:var(--cream)">
        <form method="get" action="index.php" style="display:flex;gap:16px;align-items:end;flex-wrap:wrap">
          <input type="hidden" name="page" value="seller_departures">
          <label style="flex:1;min-width:280px;margin:0">
            <b>Chọn Tour</b>
            <select name="tour_id" onchange="this.form.submit()" style="margin-top:6px">
              <?php foreach ($sellerTours as $st): ?>
                <option value="<?= (int)$st['id'] ?>" <?= (int)$st['id'] === $selectedTourId ? 'selected' : '' ?>>
                  [<?= h($st['code']) ?>] <?= h($st['title']) ?> — Trạng thái: <?= $st['status'] === 'selling' ? 'Đang bán' : ($st['status'] === 'approved' ? 'Đã duyệt' : 'Tạm ngừng') ?> (Giá cơ bản: <?= money((int)$st['base_price']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </label>
          <div>
            Trạng thái Tour hiện tại: <?= badge($currentTour['status']) ?>
            <a href="<?= h(url('tour', ['id' => $selectedTourId])) ?>" class="btn btn-outline btn-small" style="margin-left:8px">Xem trang Tour công khai ↗</a>
          </div>
        </form>
      </section>

      <div class="checkout-grid">
        <section class="panel">
          <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:14px">
            <h2 style="margin:0">Danh sách Lịch khởi hành & Tình trạng quỹ chỗ (<?= count($deps) ?> lịch)</h2>
            <?php if ($editDep): ?>
              <a href="<?= h(url('seller_departures', ['tour_id' => $selectedTourId])) ?>" class="btn btn-small">+ Chuyển sang Thêm lịch mới</a>
            <?php endif; ?>
          </div>

          <?php if (empty($deps)): ?>
            <div class="empty compact">Tour này hiện chưa có lịch khởi hành nào. Hãy thêm lịch mới để Tour tự động chuyển sang trạng thái <b>"Đang bán"</b>.</div>
          <?php else: ?>
            <div class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Mã lịch</th>
                    <th>Khởi hành → Kết thúc</th>
                    <th>Giá/người</th>
                    <th>Hạn chốt bán & Giờ tập trung</th>
                    <th>Quỹ chỗ (Tổng / Giữ / Bán / Còn)</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($deps as $d):
                      $did = (int)$d['id'];
                      $pastCutoff = strtotime($d['cutoff_at']) <= time();
                  ?>
                    <tr style="<?= $editId === $did ? 'background:#fff8f2' : '' ?>">
                      <td><b><?= h($d['code'] ?? ('DEP-' . $did)) ?></b></td>
                      <td>
                        <b><?= day($d['start_date']) ?></b><br>
                        <small class="muted">đến <?= day($d['end_date']) ?></small>
                      </td>
                      <td><b><?= money((int)$d['price_per_person']) ?></b></td>
                      <td>
                        <small>Chốt: <b><?= dt($d['cutoff_at']) ?></b></small><br>
                        <small class="muted">Tập trung: <?= h($d['meeting_time'] ?: '—') ?></small>
                      </td>
                      <td>
                        Tổng: <b><?= (int)$d['capacity'] ?></b> ·
                        Đang giữ: <b><?= (int)$d['held_seats'] ?></b> ·
                        Đã bán: <b><?= (int)$d['sold_seats'] ?></b><br>
                        <span style="color:var(--green);font-weight:700">→ Còn lại: <?= (int)$d['remaining'] ?> chỗ</span>
                      </td>
                      <td><?= badge($d['status']) ?></td>
                      <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                          <a href="<?= h(url('seller_departures', ['tour_id' => $selectedTourId, 'edit_id' => $did])) ?>#dep-form-panel" class="btn btn-outline btn-small">
                            ✎ Sửa
                          </a>
                          <?php if ($d['status'] !== 'cancelled'): ?>
                            <form method="post" action="index.php" style="margin:0" onsubmit="return confirm('Xác nhận thực hiện thao tác Đóng bán / Mở bán lại lịch khởi hành này?')">
                              <?= hidden_csrf() ?>
                              <input type="hidden" name="action" value="departure_toggle">
                              <input type="hidden" name="departure_id" value="<?= $did ?>">
                              <button type="submit" class="btn <?= in_array($d['status'], ['open', 'full'], true) ? 'btn-outline' : '' ?> btn-small">
                                <?= in_array($d['status'], ['open', 'full'], true) ? '🔒 Đóng bán' : ($pastCutoff ? '🔓 Mở lại' : '🔓 Mở bán lại') ?>
                              </button>
                            </form>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

          <hr>
          <h3>Nhật ký thay đổi Lịch khởi hành & Quỹ chỗ</h3>
          <?php if (empty($historyLogs)): ?>
            <p class="muted">Chưa có lịch sử thay đổi nào cho các lịch của Tour này.</p>
          <?php else: ?>
            <?php foreach ($historyLogs as $lg): ?>
              <p class="history-line">
                🕒 <b><?= dt($lg['created_at']) ?></b> — <b><?= h($lg['actor_name'] ?: 'Hệ thống') ?></b> — <span class="badge"><?= h($lg['action']) ?></span> — <?= h($lg['detail'] ?: '') ?>
              </p>
            <?php endforeach; ?>
          <?php endif; ?>
        </section>

        <aside class="panel sticky" id="dep-form-panel">
          <?php if ($editDep):
              $minSeats = (int)$editDep['held_seats'] + (int)$editDep['sold_seats'];
          ?>
            <span class="eyebrow">SỬA LỊCH KHỞI HÀNH <?= h($editDep['code']) ?></span>
            <h2>Sửa lịch & Điều chỉnh quỹ chỗ</h2>
            <p>Ngày khởi hành đã khóa. Tổng số chỗ mới phải <b>≥ <?= $minSeats ?> chỗ</b> (Đang giữ: <?= (int)$editDep['held_seats'] ?> + Đã bán: <?= (int)$editDep['sold_seats'] ?>).</p>

            <form method="post" action="index.php" novalidate>
              <?= hidden_csrf() ?>
              <input type="hidden" name="action" value="departure_update">
              <input type="hidden" name="departure_id" value="<?= (int)$editDep['id'] ?>">

              <label>
                Ngày khởi hành (Đã khóa)
                <input type="date" value="<?= h($editDep['start_date']) ?>" readonly disabled style="background:#eef2f0">
              </label>

              <label>
                Tổng số chỗ dành cho Sàn (Tối thiểu: <?= max(1, $minSeats) ?> chỗ) <span class="req">*</span>
                <input type="number" name="capacity" min="1" max="999" value="<?= h(old_val('capacity', (string)$editDep['capacity'])) ?>" required>
                <?= field_error('edit_capacity') ?>
              </label>

              <label>
                Giá / người của lịch (VNĐ, > 0) <span class="req">*</span>
                <input type="number" name="price_per_person" min="1" step="10000" value="<?= h(old_val('price_per_person', (string)$editDep['price_per_person'])) ?>" required>
                <?= field_error('edit_price') ?>
              </label>

              <label>
                Hạn chốt bán (00:00 ngày chọn, mặc định 1 ngày trước khởi hành)
                <input type="date" name="cutoff_date" value="<?= h(old_val('cutoff_date', substr((string)$editDep['cutoff_at'], 0, 10))) ?>">
                <?= field_error('edit_cutoff') ?>
              </label>

              <label>
                Giờ tập trung & Điểm hẹn
                <input type="text" name="meeting_time" value="<?= h(old_val('meeting_time', (string)$editDep['meeting_time'])) ?>" placeholder="Giờ tập trung">
              </label>

              <div class="info-note" style="margin:12px 0">
                ✓ Cam kết quỹ chỗ chỉ bán qua Sàn đã được xác nhận khi tạo lịch.
              </div>

              <div style="display:flex;gap:8px">
                <a href="<?= h(url('seller_departures', ['tour_id' => $selectedTourId])) ?>" class="btn btn-outline">Hủy</a>
                <button type="submit" class="btn" style="flex:1">Lưu cập nhật lịch</button>
              </div>
            </form>

          <?php else:
              $defaultStart = date('Y-m-d', strtotime('+15 days'));
              $defaultCutoff = date('Y-m-d', strtotime('+14 days'));
          ?>
            <span class="eyebrow">THÊM LỊCH KHỞI HÀNH MỚI</span>
            <h2>Thêm lịch khởi hành</h2>
            <p>Giá/người được gợi ý theo giá cơ bản của Tour (<b><?= money((int)$currentTour['base_price']) ?></b>). Hạn chốt bán mặc định là 00:00 ngày liền trước ngày khởi hành.</p>

            <form method="post" action="index.php" novalidate>
              <?= hidden_csrf() ?>
              <input type="hidden" name="action" value="departure_save">
              <input type="hidden" name="tour_id" value="<?= $selectedTourId ?>">

              <label>
                Ngày khởi hành <span class="req">*</span>
                <input type="date" name="start_date" value="<?= h(old_val('start_date', $defaultStart)) ?>" required>
                <?= field_error('start_date') ?>
              </label>

              <label>
                Tổng số chỗ dành cho Sàn (> 0) <span class="req">*</span>
                <input type="number" name="capacity" min="1" max="999" value="<?= h(old_val('capacity', '20')) ?>" required>
                <?= field_error('capacity') ?>
              </label>

              <label>
                Giá / người (VNĐ, bắt buộc lớn hơn 0) <span class="req">*</span>
                <input type="number" name="price_per_person" min="1" step="10000" value="<?= h(old_val('price_per_person', (string)$currentTour['base_price'])) ?>" required>
                <?= field_error('price_per_person') ?>
              </label>

              <label>
                Hạn chốt bán (Tùy chọn đặt sớm hơn mặc định)
                <input type="date" name="cutoff_date" value="<?= h(old_val('cutoff_date', $defaultCutoff)) ?>">
                <?= field_error('cutoff_date') ?>
              </label>

              <label>
                Giờ tập trung (Không bắt buộc)
                <input type="text" name="meeting_time" value="<?= h(old_val('meeting_time', '06:30 tại điểm đón')) ?>" placeholder="Giờ tập trung">
              </label>

              <label class="check-line" style="background:var(--cream);padding:12px;border-radius:8px">
                <input type="checkbox" name="quota_agreement" value="1" <?= old_val('quota_agreement') ? 'checked' : '' ?>>
                <span>Tôi cam kết quỹ chỗ khai báo cho lịch khởi hành này chỉ được bán qua Sàn.</span>
              </label>
              <?= field_error('quota_agreement') ?>

              <button type="submit" class="btn btn-wide" style="margin-top:10px">Lưu & Mở bán lịch khởi hành</button>
            </form>
          <?php endif; ?>
        </aside>
      </div>
    </main>
    <?php
    render_footer();
}

function page_seller_bookings(): void {
    // AD-CN12: Seller xác nhận booking - Bám sát 100% Sơ đồ Activity AD-CN12 (image_d77d3a.png) & Bảng 19 SRS
    [$user, $seller] = get_current_seller();
    must($seller['status'] === 'verified', 'Seller cần được xác minh.');

    // Bước 3: Hiển thị các booking chờ xác nhận lên trước, sắp xếp theo hạn xác nhận gần nhất
    $rows = q("SELECT b.*, d.code AS dep_code, d.start_date, d.end_date, d.cutoff_at, t.title, t.code AS tour_code,
               (SELECT COUNT(*) FROM passengers p WHERE p.booking_id=b.id) AS p_count
               FROM bookings b
               JOIN departures d ON d.id=b.departure_id
               JOIN tours t ON t.id=d.tour_id
               WHERE t.seller_id=?
               ORDER BY (b.status='pending_seller') DESC, b.seller_deadline ASC, b.created_at DESC", [$seller['id']])->fetchAll();

    render_header('seller_bookings');
    ?>
    <main class="container section">
      <?php render_seller_subnav('seller_bookings'); ?>

      <div class="section-head">
        <div>
          <span class="eyebrow">SELLER XÁC NHẬN BOOKING TRONG 24 GIỜ</span>
          <h1>Quản lý & Xác nhận Booking của Khách hàng</h1>
          <p>Các Booking ở trạng thái <b>"Chờ xác nhận"</b> được sắp xếp theo thời hạn xử lý gần nhất. Khi Seller bấm <b>"Xác nhận booking"</b>, khách hàng sẽ nhận thông báo kèm hạn đặt cọc 24 giờ và có thể chọn <b>Đặt cọc 50% hoặc Thanh toán 100%</b>.</p>
        </div>
      </div>

      <?php if (empty($rows)): ?>
        <div class="empty">Chưa có Booking nào trên các Tour của bạn.</div>
      <?php else: ?>
        <?php foreach ($rows as $b):
            $bid = (int)$b['id'];
            $isPending = $b['status'] === 'pending_seller';
        ?>
          <div class="panel seller-booking" style="<?= $isPending ? 'border:2px solid var(--accent)' : '' ?>">
            <div>
              <div>
                <?= badge($b['status']) ?>
                <span class="badge"><?= h($b['code']) ?></span>
                <span class="badge">Lịch <?= h($b['dep_code'] ?? '') ?>: <?= day($b['start_date']) ?> → <?= day($b['end_date']) ?></span>
                <?php if ((int)$b['full_required'] === 1): ?>
                  <span class="badge status-pending">Cờ bắt buộc trả 100%</span>
                <?php endif; ?>
              </div>
              <h2><?= h($b['title']) ?></h2>
              <p>
                👤 Người liên hệ: <b><?= h($b['contact_name']) ?></b> · SĐT: <b><?= h($b['contact_phone']) ?></b> · Email: <b><?= h($b['contact_email']) ?></b><br>
                👥 Số lượng khách: <b><?= (int)$b['people'] ?> người</b> (Đã lưu hành khách: <b><?= (int)$b['p_count'] ?>/<?= (int)$b['people'] ?></b> — <a href="<?= h(url('booking', ['id' => $bid])) ?>" class="text-link">Xem danh sách hành khách & hộ chiếu ↗</a>)<br>
                💰 Tổng giá trị cố định: <b><?= money((int)$b['total']) ?></b> · Đã thanh toán: <b style="color:var(--green)"><?= money((int)$b['paid_amount']) ?></b><br>
                📝 Ghi chú của khách: <b><?= h($b['note'] ?: 'Không có ghi chú') ?></b>
              </p>
              <small class="muted">
                ⏱ Đặt lúc: <?= dt($b['created_at']) ?> ·
                <b>Hạn Seller xác nhận: <?= dt($b['seller_deadline']) ?></b>
                <?php if ($b['deposit_deadline']): ?> · Hạn khách đặt cọc: <b><?= dt($b['deposit_deadline']) ?></b><?php endif; ?>
                <?php if ($b['cancellation_reason']): ?><br><b style="color:#a04436">Lý do từ chối/hủy: <?= h($b['cancellation_reason']) ?></b><?php endif; ?>
              </small>
            </div>

            <?php if ($isPending): ?>
              <!-- Bước 5 & A1.1: Seller chọn Xác nhận booking hoặc Từ chối kèm lý do -->
              <div style="width:290px">
                <form method="post" action="index.php" style="margin-bottom:10px">
                  <?= hidden_csrf() ?>
                  <input type="hidden" name="action" value="booking_decide">
                  <input type="hidden" name="booking_id" value="<?= $bid ?>">
                  <input type="hidden" name="decision" value="confirm">
                  <button type="submit" class="btn btn-wide">✓ Xác nhận booking</button>
                </form>

                <form method="post" action="index.php" novalidate>
                  <?= hidden_csrf() ?>
                  <input type="hidden" name="action" value="booking_decide">
                  <input type="hidden" name="booking_id" value="<?= $bid ?>">
                  <input type="hidden" name="decision" value="reject">
                  <input type="text" name="reason" placeholder="Nhập lý do từ chối (bắt buộc)" style="margin-bottom:6px">
                  <?= field_error('booking_reason_' . $bid) ?>
                  <button type="submit" class="btn btn-outline btn-small btn-wide">✕ Từ chối & Trả chỗ về lịch</button>
                </form>
              </div>
            <?php else: ?>
              <div style="text-align:right">
                <a href="<?= h(url('booking', ['id' => $bid])) ?>" class="btn btn-outline btn-small">Xem chi tiết & Hành khách →</a>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </main>
    <?php
    render_footer();
}

function page_admin(): void {
    // CN02 (Xác minh Seller) & CN04 (Kiểm duyệt Tour mới và Bản sửa)
    // Có hiển thị đầy đủ hình ảnh Giấy phép và hình ảnh Tour ngay trên giao diện để Admin kiểm tra!
    $admin = require_role('admin');
    $pendingSellers = q("SELECT s.*, u.email FROM sellers s JOIN users u ON u.id=s.user_id WHERE s.status='pending' ORDER BY s.created_at ASC")->fetchAll();
    $pendingTours = q("SELECT t.*, s.company_name FROM tours t JOIN sellers s ON s.id=t.seller_id WHERE t.status='pending' OR t.pending_payload IS NOT NULL ORDER BY t.updated_at ASC")->fetchAll();
    $allTours = q("SELECT t.*, s.company_name FROM tours t JOIN sellers s ON s.id=t.seller_id ORDER BY t.id DESC")->fetchAll();

    render_header('admin');
    ?>
    <section class="portal-hero">
      <div class="container">
        <span class="eyebrow light">PHÂN HỆ QUẢN TRỊ SÀN (ADMINISTRATION)</span>
        <h1>Kiểm duyệt Hồ sơ Seller & Nội dung Tour</h1>
        <p>Khi Admin duyệt Tour, hệ thống tự động mở bán lịch khởi hành và chuyển trạng thái Tour sang <b>"Đang bán"</b> để hiển thị trên cổng Seller và trang Khách hàng.</p>
      </div>
    </section>

    <main class="container section">
      <!-- PHẦN 1: CN02 XÁC MINH HỒ SƠ SELLER (KÈM XEM TRỰC TIẾP ẢNH 2 GIẤY PHÉP TRÊN GIAO DIỆN) -->
      <div class="section-head">
        <div>
          <span class="eyebrow">XÁC MINH HỒ SƠ SELLER</span>
          <h2>Hồ sơ Seller chờ xác minh (<span class="count"><?= count($pendingSellers) ?></span>)</h2>
        </div>
      </div>

      <?php if (empty($pendingSellers)): ?>
        <div class="empty compact">Hiện không có hồ sơ Seller nào đang chờ xác minh.</div>
      <?php else: ?>
        <?php foreach ($pendingSellers as $s):
            $sid = (int)$s['id'];
        ?>
          <div class="panel review-card">
            <div>
              <?= badge($s['status']) ?>
              <h3><?= h($s['company_name']) ?> (MST: <?= h($s['tax_code']) ?>)</h3>
              <p>
                Người đại diện: <b><?= h($s['representative']) ?></b> · SĐT: <b><?= h($s['phone']) ?></b> · Email: <b><?= h($s['email']) ?></b><br>
                Địa chỉ: <?= h($s['address']) ?> · Loại hình: <b><?= $s['travel_type'] === 'international' ? 'Quốc tế' : 'Nội địa' ?></b>
              </p>
              <!-- Hiển thị trực tiếp hình ảnh 2 giấy phép ngay trên giao diện để Admin kiểm tra -->
              <div class="two-cols" style="margin-top:12px">
                <div>
                  <small><b>📄 Giấy phép đăng ký kinh doanh (<a href="file.php?id=<?= $sid ?>&type=business" target="_blank" class="text-link">Mở rộng ↗</a>):</b></small>
                  <img src="file.php?id=<?= $sid ?>&type=business" alt="Giấy phép kinh doanh" style="margin-top:6px;border:1px solid var(--line);border-radius:8px;max-height:180px">
                </div>
                <div>
                  <small><b>📄 Giấy phép kinh doanh lữ hành (<a href="file.php?id=<?= $sid ?>&type=travel" target="_blank" class="text-link">Mở rộng ↗</a>):</b></small>
                  <img src="file.php?id=<?= $sid ?>&type=travel" alt="Giấy phép lữ hành" style="margin-top:6px;border:1px solid var(--line);border-radius:8px;max-height:180px">
                </div>
              </div>
            </div>
            <form method="post" action="index.php" novalidate>
              <?= hidden_csrf() ?>
              <input type="hidden" name="action" value="seller_review">
              <input type="hidden" name="seller_id" value="<?= $sid ?>">
              <button type="submit" name="decision" value="verified" class="btn btn-wide" style="margin-bottom:10px">✓ Phê duyệt Seller</button>
              <input type="text" name="reason" placeholder="Nhập lý do nếu từ chối (Bắt buộc)" style="margin-bottom:6px">
              <?= field_error('seller_reason_' . $sid) ?>
              <button type="submit" name="decision" value="rejected" class="btn btn-outline btn-small btn-wide">✕ Từ chối hồ sơ</button>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- PHẦN 2: CN04 KIỂM DUYỆT TOUR MỚI & BẢN SỬA (KÈM ẢNH TOUR TRỰC QUAN TRÊN GIAO DIỆN) -->
      <div class="section-head second-head">
        <div>
          <span class="eyebrow">KIỂM DUYỆT TOUR & BẢN SỬA</span>
          <h2>Tour mới & Bản sửa đang chờ kiểm duyệt (<span class="count"><?= count($pendingTours) ?></span>)</h2>
        </div>
      </div>

      <?php if (empty($pendingTours)): ?>
        <div class="empty compact">Hiện không có Tour hoặc bản sửa nào đang chờ duyệt.</div>
      <?php else: ?>
        <?php foreach ($pendingTours as $t):
            $tid = (int)$t['id'];
            $rev = $t['pending_payload'] ? (json_decode((string)$t['pending_payload'], true) ?: []) : null;
            $previewImg = $rev['image_path'] ?? $t['image_path'];
        ?>
          <div class="panel review-card">
            <div style="display:flex;gap:20px;align-items:start">
              <!-- Hiển thị rõ ảnh Tour trên giao diện kiểm duyệt để check -->
              <div>
                <img class="review-img" src="<?= h($previewImg) ?>" alt="<?= h($t['title']) ?>" style="border-radius:10px;border:2px solid var(--accent)">
                <small class="muted" style="display:block;margin-top:4px">📷 Ảnh đại diện Tour: <code><?= h($previewImg) ?></code></small>
              </div>
              <div style="flex:1">
                <?= badge($rev ? 'pending' : $t['status']) ?>
                <span class="badge"><?= h($t['code']) ?></span>
                <span class="badge"><?= h($t['company_name']) ?></span>
                <h3><?= h($rev['title'] ?? $t['title']) ?></h3>
                <p>
                  📍 <?= h($rev['origin'] ?? $t['origin']) ?> → <?= h($rev['destination'] ?? $t['destination']) ?> ·
                  Thời lượng: <b><?= (int)($rev['days'] ?? $t['days']) ?>N<?= (int)($rev['nights'] ?? $t['nights']) ?>Đ</b> ·
                  Giá cơ bản: <b><?= money((int)($rev['base_price'] ?? $t['base_price'])) ?>/người</b>
                  <?php if ($rev && (int)$rev['base_price'] !== (int)$t['base_price']): ?>
                    <span class="badge status-pending">Đổi từ <?= money((int)$t['base_price']) ?></span>
                  <?php endif; ?>
                </p>
                <p><b>Giấy tờ cần chuẩn bị:</b> <?= h($rev['required_documents'] ?? $t['required_documents']) ?></p>
                <p><b>Chính sách hủy/đổi:</b> <?= h($rev['cancellation_policy'] ?? $t['cancellation_policy']) ?></p>
                <details>
                  <summary>Xem chi tiết Lịch trình & Dịch vụ bao gồm/không bao gồm</summary>
                  <p style="margin-top:8px"><?= nl2br(h($rev['itinerary'] ?? $t['itinerary'])) ?></p>
                </details>
              </div>
            </div>

            <form method="post" action="index.php" novalidate>
              <?= hidden_csrf() ?>
              <input type="hidden" name="action" value="tour_review">
              <input type="hidden" name="tour_id" value="<?= $tid ?>">
              <button type="submit" name="decision" value="approved" class="btn btn-wide" style="margin-bottom:10px">✓ Duyệt & Mở bán Tour</button>
              <input type="text" name="reason" placeholder="Nhập lý do từ chối (bắt buộc)" style="margin-bottom:6px">
              <?= field_error('tour_reason_' . $tid) ?>
              <button type="submit" name="decision" value="rejected" class="btn btn-outline btn-small btn-wide">✕ Từ chối Tour</button>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- PHẦN 3: DANH SÁCH TOÀN BỘ TOUR TRÊN SÀN ĐỂ ĐỐI CHIẾU TRẠNG THÁI -->
      <div class="section-head second-head">
        <div>
          <span class="eyebrow">ĐỐI CHIẾU TOÀN SÀN</span>
          <h2>3. Trạng thái toàn bộ Tour trên hệ thống (<?= count($allTours) ?> Tour)</h2>
        </div>
      </div>
      <div class="panel table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Ảnh</th>
              <th>Mã Tour</th>
              <th>Tên Tour</th>
              <th>Seller</th>
              <th>Giá cơ bản</th>
              <th>Trạng thái hiện tại</th>
              <th>Xem trang Khách</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($allTours as $at): ?>
              <tr>
                <td><img src="<?= h($at['image_path']) ?>" alt="" style="width:64px;height:44px;object-fit:cover;border-radius:6px"></td>
                <td><b><?= h($at['code']) ?></b></td>
                <td><b><?= h($at['title']) ?></b></td>
                <td><?= h($at['company_name']) ?></td>
                <td><?= money((int)$at['base_price']) ?></td>
                <td><?= badge($at['status']) ?></td>
                <td><a href="<?= h(url('tour', ['id' => (int)$at['id']])) ?>" class="text-link">Mở xem ↗</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
    <?php
    render_footer();
}

function page_notifications(): void {
    $user = me();
    must((bool)$user, 'Vui lòng đăng nhập để xem thông báo.');
    $list = q("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50", [$user['id']])->fetchAll();
    q("UPDATE notifications SET is_read=1 WHERE user_id=?", [$user['id']]);

    render_header('notifications');
    ?>
    <main class="container section">
      <div class="section-head">
        <div>
          <span class="eyebrow">TRUNG TÂM THÔNG BÁO HỆ THỐNG</span>
          <h1>Thông báo của bạn (<?= count($list) ?>)</h1>
          <p>Thông báo về hồ sơ, tour, booking, danh sách hành khách và các mốc thời hạn sẽ được hiển thị tại đây.</p>
        </div>
      </div>

      <div class="panel">
        <?php if (empty($list)): ?>
          <p class="muted">Hiện bạn chưa có thông báo nào.</p>
        <?php else: ?>
          <?php foreach ($list as $n): ?>
            <div class="history-line" style="display:flex;justify-content:space-between;align-items:center;gap:16px">
              <div>
                <span style="font-weight:<?= (int)$n['is_read'] === 0 ? '700' : '500' ?>;color:var(--ink)">🔔 <?= h($n['message']) ?></span>
                <small class="muted" style="display:block"><?= dt($n['created_at']) ?></small>
              </div>
              <?php if (!empty($n['link'])): ?>
                <a href="<?= h($n['link']) ?>" class="btn btn-outline btn-small">Mở xem →</a>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>
    <?php
    render_footer();
}
