CREATE DATABASE IF NOT EXISTS TOURBUSINESSPROPOSAL8 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE TOURBUSINESSPROPOSAL8;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS saved_tours;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS passengers;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS departures;
DROP TABLE IF EXISTS tours;
DROP TABLE IF EXISTS sellers;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS system_config;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(25) NULL DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer','seller','admin') NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sellers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  company_name VARCHAR(190) NOT NULL,
  tax_code VARCHAR(30) NOT NULL UNIQUE,
  address VARCHAR(255) NOT NULL,
  representative VARCHAR(160) NOT NULL,
  phone VARCHAR(25) NOT NULL,
  travel_type ENUM('domestic','international') NOT NULL,
  business_license VARCHAR(255) NOT NULL,
  travel_license VARCHAR(255) NOT NULL,
  status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  rejection_reason TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tours (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(32) NULL,
  seller_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  destination VARCHAR(120) NOT NULL,
  origin VARCHAR(120) NOT NULL,
  days TINYINT UNSIGNED NOT NULL,
  nights TINYINT UNSIGNED NOT NULL,
  description TEXT NOT NULL,
  itinerary TEXT NOT NULL,
  included TEXT NOT NULL,
  excluded TEXT NOT NULL,
  pickup VARCHAR(255) NOT NULL,
  transport VARCHAR(100) NOT NULL,
  accommodation VARCHAR(180) NOT NULL,
  required_documents VARCHAR(255) NOT NULL DEFAULT '',
  cancellation_policy VARCHAR(160) NOT NULL,
  is_international TINYINT(1) NOT NULL DEFAULT 0,
  base_price INT UNSIGNED NOT NULL,
  surcharge_per_person INT UNSIGNED NOT NULL DEFAULT 0,
  surcharge_label VARCHAR(120) NOT NULL DEFAULT 'Phụ thu bắt buộc',
  image_path VARCHAR(255) NOT NULL,
  gallery_json TEXT NULL,
  status ENUM('draft','pending','approved','selling','rejected','paused') NOT NULL DEFAULT 'draft',
  pending_payload JSON NULL,
  rejection_reason TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE,
  INDEX idx_tour_status_dest (status, destination)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE departures (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(32) NULL,
  tour_id BIGINT UNSIGNED NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  capacity SMALLINT UNSIGNED NOT NULL,
  price_per_person INT UNSIGNED NOT NULL,
  cutoff_at DATETIME NOT NULL,
  meeting_time VARCHAR(80) NOT NULL DEFAULT '',
  status ENUM('open','full','closed','cancelled') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE,
  INDEX idx_dep_tour_start (tour_id, start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bookings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(24) NOT NULL UNIQUE,
  departure_id BIGINT UNSIGNED NOT NULL,
  customer_id BIGINT UNSIGNED NOT NULL,
  people SMALLINT UNSIGNED NOT NULL,
  contact_name VARCHAR(160) NOT NULL,
  contact_phone VARCHAR(25) NOT NULL,
  contact_email VARCHAR(190) NOT NULL,
  note TEXT NULL,
  base_unit INT UNSIGNED NOT NULL,
  surcharge_unit INT UNSIGNED NOT NULL,
  surcharge_label VARCHAR(120) NOT NULL,
  total INT UNSIGNED NOT NULL,
  paid_amount INT UNSIGNED NOT NULL DEFAULT 0,
  policy_snapshot VARCHAR(160) NOT NULL,
  passenger_status ENUM('incomplete','completed') NOT NULL DEFAULT 'incomplete',
  status ENUM('pending_seller','confirmed','deposited','paid','completed','rejected','expired','cancelled') NOT NULL DEFAULT 'pending_seller',
  seller_deadline DATETIME NOT NULL,
  deposit_deadline DATETIME NULL,
  balance_deadline DATETIME NOT NULL,
  full_required TINYINT(1) NOT NULL DEFAULT 0,
  reminded_seller_4h TINYINT(1) NOT NULL DEFAULT 0,
  reminded_deposit_12h TINYINT(1) NOT NULL DEFAULT 0,
  reminded_deposit_2h TINYINT(1) NOT NULL DEFAULT 0,
  reminded_balance TINYINT(1) NOT NULL DEFAULT 0,
  confirmed_at DATETIME NULL,
  cancellation_reason TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (departure_id) REFERENCES departures(id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_booking_dep_status (departure_id, status),
  INDEX idx_booking_cust_created (customer_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE passengers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id BIGINT UNSIGNED NOT NULL,
  full_name VARCHAR(160) NOT NULL,
  gender ENUM('female','male','other') NOT NULL,
  date_of_birth DATE NOT NULL,
  phone VARCHAR(25) NOT NULL,
  note VARCHAR(255) NULL DEFAULT NULL,
  passport_number VARCHAR(40) NULL,
  passport_expiry DATE NULL,
  nationality VARCHAR(80) NULL,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id BIGINT UNSIGNED NOT NULL,
  reference VARCHAR(48) NOT NULL UNIQUE,
  amount INT UNSIGNED NOT NULL,
  status ENUM('success','failed') NOT NULL,
  method VARCHAR(50) NOT NULL DEFAULT 'LOCAL_DEMO',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE saved_tours (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  tour_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_saved_user_tour (user_id, tour_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reviews (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tour_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  booking_id BIGINT UNSIGNED NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_id BIGINT UNSIGNED NULL,
  object_type VARCHAR(35) NOT NULL,
  object_id BIGINT UNSIGNED NOT NULL,
  action VARCHAR(70) NOT NULL,
  detail TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  message VARCHAR(500) NOT NULL,
  link VARCHAR(255) NULL DEFAULT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notif_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE system_config (
  config_key VARCHAR(64) PRIMARY KEY,
  config_value VARCHAR(255) NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO system_config (config_key, config_value) VALUES
('last_cron_run', NOW()),
('seller_confirm_hours', '24'),
('deposit_hours', '24'),
('deposit_percent', '50');

-- =====================================================================
-- DỮ LIỆU MẪU KHỞI TẠO (Mật khẩu mặc định cho mọi tài khoản: Demo@12345)
-- =====================================================================

INSERT INTO users (id, name, email, phone, password_hash, role) VALUES
(1, 'Nguyễn Lan Anh (Khách hàng)', 'customer@vivuviet.test', '0912345678', '$2y$10$abcdefghijklmnopqrstuu5T5CeOYs.j2nvgXERisBCwZwIaJ.8QO', 'customer'),
(2, 'ViVu Travel Việt Nam', 'seller@vivuviet.test', '0988888888', '$2y$10$abcdefghijklmnopqrstuu5T5CeOYs.j2nvgXERisBCwZwIaJ.8QO', 'seller'),
(3, 'Quản trị viên Sàn ViVu', 'admin@vivuviet.test', '0909000111', '$2y$10$abcdefghijklmnopqrstuu5T5CeOYs.j2nvgXERisBCwZwIaJ.8QO', 'admin'),
(4, 'Trần Minh Quân', 'quan.tran@vivuviet.test', '0933445566', '$2y$10$abcdefghijklmnopqrstuu5T5CeOYs.j2nvgXERisBCwZwIaJ.8QO', 'customer'),
(5, 'Lê Thu Hà', 'ha.le@vivuviet.test', '0977665544', '$2y$10$abcdefghijklmnopqrstuu5T5CeOYs.j2nvgXERisBCwZwIaJ.8QO', 'customer');

INSERT INTO sellers (id, user_id, company_name, tax_code, address, representative, phone, travel_type, business_license, travel_license, status, created_at) VALUES
(1, 2, 'Công ty TNHH Lữ Hành ViVu Travel Việt Nam', '0109988776', '18 Tràng Tiền, Quận Hoàn Kiếm, Hà Nội', 'Nguyễn Minh An', '0988888888', 'international', 'seed/demo-business.pdf', 'seed/demo-travel.pdf', 'verified', '2025-01-15 08:30:00');

-- Danh sách Tour ở nhiều phân khúc giá và trạng thái
INSERT INTO tours (id, code, seller_id, title, destination, origin, days, nights, description, itinerary, included, excluded, pickup, transport, accommodation, required_documents, cancellation_policy, is_international, base_price, surcharge_per_person, surcharge_label, image_path, gallery_json, status) VALUES
(1, 'TOUR-HL01', 1, 'Vịnh Hạ Long – Đêm Trên Du Thuyền 5 Sao', 'Hạ Long', 'Hà Nội', 3, 2,
 'Ngắm sắc nước ngọc bích trên kỳ quan Vịnh Hạ Long, chèo kayak khám phá hang Sửng Sốt, đảo Titop và đón bình minh từ boong du thuyền.',
 'Ngày 1: Di chuyển Hà Nội – Hạ Long, nhận cabin du thuyền, thăm hang Sửng Sốt.\nNgày 2: Chèo Kayak khám phá hang Luồn, tắm biển đảo Titop và ngắm hoàng hôn trên vịnh.\nNgày 3: Tập Thái Cực Quyền đón bình minh, ăn sáng nhẹ trên tàu, về lại Hà Nội.',
 'Xe Limousine đưa đón khứ hồi, cabin du thuyền tiêu chuẩn, các bữa ăn theo chương trình, vé thắng cảnh, kayak, bảo hiểm du lịch.',
 'Chi tiêu cá nhân, đồ uống tại quầy bar, thuế VAT và tiền tip cho thủy thủ đoàn.',
 'Nhà hát Lớn Hà Nội – Số 1 Tràng Tiền', 'Xe Limousine & Du thuyền', 'Du thuyền 5 sao trên vịnh',
 'Căn cước công dân hoặc Hộ chiếu bản gốc còn hạn (chuẩn bị trước khởi hành 3 ngày).',
 'Linh hoạt: hủy trước 7 ngày được hoàn 100%', 0, 2890000, 250000, 'Phụ thu tàu & phí cảng bắt buộc',
 'assets/images/ha-long.jpg', '["assets/images/ha-long.jpg","assets/images/ninh-binh.jpg","assets/images/phu-quoc.jpg"]', 'selling'),

(2, 'TOUR-HA02', 1, 'Hội An – Đà Nẵng: Phố Cổ Đèn Lồng & Biển Xanh', 'Hội An', 'Đà Nẵng', 3, 2,
 'Một nhịp điệu thong dong giữa phố cổ đèn lồng rực rỡ, chùa Cầu cổ kính, rừng dừa Bảy Mẫu và bờ biển Mỹ Khê trong xanh.',
 'Ngày 1: Đón khách tại Đà Nẵng, tắm biển Mỹ Khê và tham quan danh thắng Ngũ Hành Sơn.\nNgày 2: Khám phá rừng dừa Bảy Mẫu, dạo phố cổ Hội An thả hoa đăng trên sông Hoài.\nNgày 3: Tham quan làng nghề truyền thống, mua sắm đặc sản và tiễn khách tại Đà Nẵng.',
 'Xe du lịch đời mới, khách sạn 4 sao, hướng dẫn viên bản địa, vé tham quan phố cổ, bữa ăn đặc sản Cao Lầu – Cơm gà.',
 'Vé máy bay khứ hồi đến Đà Nẵng, chi phí giặt là và đồ uống riêng trong bữa ăn.',
 'Sân bay Quốc tế Đà Nẵng hoặc trung tâm TP. Đà Nẵng', 'Xe du lịch 29 chỗ', 'Khách sạn 4 sao phố cổ / ven biển',
 'Căn cước công dân hoặc giấy khai sinh bản sao cho trẻ em (chuẩn bị trước khởi hành 3 ngày).',
 'Linh hoạt: hủy trước 7 ngày được hoàn 100%', 0, 2350000, 180000, 'Phụ thu vé tham quan & thuyền sông Hoài',
 'assets/images/hoi-an.jpg', '["assets/images/hoi-an.jpg","assets/images/da-lat.jpg","assets/images/phu-quoc.jpg"]', 'selling'),

(3, 'TOUR-SP03', 1, 'Sa Pa – Bản Làng Giữa Tầng Mây & Đỉnh Fansipan', 'Sa Pa', 'Hà Nội', 3, 2,
 'Đi qua những thửa ruộng bậc thang mùa vàng, trekking bản Cát Cát – Tả Van và chinh phục nóc nhà Đông Dương trong làn sương mờ.',
 'Ngày 1: Khởi hành từ Hà Nội theo cao tốc Lào Cai, đến Sa Pa tham quan bản Cát Cát và thác Tiên Sa.\nNgày 2: Trekking thung lũng Mường Hoa – bản Lao Chải – Tả Van, thưởng thức thắng cố và cá hồi.\nNgày 3: Chinh phục đỉnh Fansipan, dạo chợ phiên Sa Pa và khởi hành về Hà Nội.',
 'Xe giường nằm cabin đôi khứ hồi, khách sạn 3 sao trung tâm, vé vào bản, các bữa ăn chính theo lịch trình.',
 'Vé cáp treo Fansipan tự túc theo nhu cầu, chi phí cá nhân ngoài chương trình.',
 '18 Tràng Tiền, Hoàn Kiếm hoặc Sân bay Nội Bài', 'Xe Cabin VIP & Xe du lịch', 'Khách sạn 3 sao view núi trung tâm Sa Pa',
 'Căn cước công dân còn hiệu lực (chuẩn bị trước khởi hành 2 ngày).',
 'Tiêu chuẩn: hủy trước 7 ngày hoàn 80%, trước 3 ngày hoàn 50%', 0, 2690000, 150000, 'Phụ thu phí bảo tồn & vệ sinh môi trường',
 'assets/images/sa-pa.jpg', '["assets/images/sa-pa.jpg","assets/images/ninh-binh.jpg","assets/images/da-lat.jpg"]', 'selling'),

(4, 'TOUR-PQ04', 1, 'Phú Quốc – Thiên Đường Đảo Ngọc & Lặn Ngắm San Hô', 'Phú Quốc', 'TP. Hồ Chí Minh', 4, 3,
 'Chạm vào làn nước biển xanh ngọc bích, đi cano 4 đảo Nam Phú Quốc, ngắm hoàng hôn Sunset Town và thưởng thức hải sản tươi sống.',
 'Ngày 1: Đón khách tại sân bay Phú Quốc, nhận phòng resort, dạo Dinh Cậu và chợ đêm Phú Quốc.\nNgày 2: Cano khám phá Hòn Móng Tay – Hòn Gầm Ghì – Hòn Mây Rút, lặn ngắm san hô.\nNgày 3: Tham quan Bắc Đảo, Rạch Vẹm sao biển, vườn tiêu, nhà thùng nước mắm và Sunset Town.\nNgày 4: Tự do tắm biển, mua đặc sản ngọc trai – hồ tiêu, tiễn sân bay Phú Quốc.',
 'Resort 4 sao sát biển, cano tham quan đảo, dụng cụ lặn ngắm san hô, các bữa ăn hải sản, bảo hiểm du lịch.',
 'Vé vui chơi VinWonders/Safari tự chọn, chi tiêu cá nhân.',
 'Sân bay Quốc tế Phú Quốc / Trung tâm Dương Đông', 'Máy bay & Cano cao tốc', 'Resort 4 sao bãi Trường Phú Quốc',
 'Căn cước công dân hoặc Hộ chiếu hợp lệ để làm thủ tục bay (trước khởi hành 3 ngày).',
 'Tiêu chuẩn: hủy trước 7 ngày hoàn 80%, trước 3 ngày hoàn 50%', 0, 4990000, 380000, 'Phụ thu tàu cao tốc & phí cảng biển',
 'assets/images/phu-quoc.jpg', '["assets/images/phu-quoc.jpg","assets/images/ha-long.jpg","assets/images/hoi-an.jpg"]', 'selling'),

(5, 'TOUR-DL05', 1, 'Đà Lạt – Săn Mây Cầu Đất & Mùa Hoa Cao Nguyên', 'Đà Lạt', 'TP. Hồ Chí Minh', 3, 2,
 'Một Đà Lạt dịu dàng với đồi chè Cầu Đất ngập sương sớm, rừng thông reo, hồ Tuyền Lâm và những quán cà phê gỗ nhìn ra thung lũng.',
 'Ngày 1: Khởi hành đi Đà Lạt, tham quan quảng trường Lâm Viên, hồ Xuân Hương và chợ đêm Đà Lạt.\nNgày 2: Săn mây đồi chè Cầu Đất, thăm vườn dâu tây công nghệ cao, Thiền viện Trúc Lâm – hồ Tuyền Lâm.\nNgày 3: Check-in nhà ga Đà Lạt cổ kính, thác Datanla, mua mứt đặc sản và về lại TP. Hồ Chí Minh.',
 'Xe Limousine ghế ngả cao cấp, khách sạn 3 sao trung tâm, vé tham quan, hướng dẫn viên suốt tuyến.',
 'Vé máng trượt Datanla, chi phí cà phê tự chọn ngoài lịch trình.',
 'Nhà văn hóa Thanh Niên – Quận 1, TP. Hồ Chí Minh', 'Xe Limousine VIP', 'Khách sạn 3 sao trung tâm Hòa Bình',
 'Căn cước công dân hợp lệ (chuẩn bị trước khởi hành 2 ngày).',
 'Linh hoạt: hủy trước 7 ngày được hoàn 100%', 0, 2490000, 120000, 'Phụ thu xe trung chuyển đồi săn mây',
 'assets/images/da-lat.jpg', '["assets/images/da-lat.jpg","assets/images/sa-pa.jpg","assets/images/hoi-an.jpg"]', 'selling'),

(6, 'TOUR-NB06', 1, 'Ninh Bình – Tràng An Cổ Kính & Hang Múa Trong Ngày (Giá < 1 Triệu)', 'Ninh Bình', 'Hà Nội', 1, 0,
 'Hành trình trong ngày tiết kiệm khám phá quần thể di sản thế giới Tràng An, cố đô Hoa Lư và chinh phục đỉnh Ngọa Long – Hang Múa.',
 'Ngày 1: Khởi hành từ Hà Nội đi Ninh Bình, thăm cố đô Hoa Lư, ngồi thuyền nan xuyên thủy động Tràng An, chiều leo đỉnh Hang Múa ngắm toàn cảnh Tam Cốc rồi về lại Hà Nội.',
 'Xe du lịch đưa đón khứ hồi từ Hà Nội, bữa trưa buffet dê núi Ninh Bình, vé thắng cảnh Hoa Lư, hướng dẫn viên.',
 'Đồ uống trong bữa trưa, chi phí xe điện cá nhân.',
 'Công viên Thống Nhất – Trần Nhân Tông, Hà Nội', 'Xe du lịch 16–29 chỗ', 'Đi về trong ngày (nhà hàng nghỉ trưa tiêu chuẩn)',
 'Căn cước công dân hoặc giấy tờ tùy thân (chuẩn bị trước khởi hành 1 ngày).',
 'Linh hoạt: hủy trước 7 ngày được hoàn 100%', 0, 890000, 90000, 'Phụ thu đò Tràng An bắt buộc',
 'assets/images/ninh-binh.jpg', '["assets/images/ninh-binh.jpg","assets/images/ha-long.jpg"]', 'selling'),

(7, 'TOUR-BK07', 1, 'Bangkok – Pattaya: Sắc Màu Xứ Chùa Vàng & Đảo Coral', 'Bangkok', 'Hà Nội', 4, 3,
 'Khám phá chùa Bình Minh Wat Arun, Hoàng cung Thái Lan, dạo thuyền sông Chao Phraya và tắm biển đảo san hô Coral tại Pattaya.',
 'Ngày 1: Bay Hà Nội – Bangkok, di chuyển đi Pattaya, thưởng thức Alcazar Show đặc sắc.\nNgày 2: Đi tàu cao tốc ra đảo Coral tắm biển, chiều thăm Trân Bảo Phật Sơn và chợ nổi Bốn Miền.\nNgày 3: Về lại Bangkok, viếng chùa Phật Vàng, chùa thuyền Wat Yannawa và dạo thuyền sông Chao Phraya.\nNgày 4: Thăm Wat Arun, tự do mua sắm tại Central World / Big C, ra sân bay Suvarnabhumi bay về Hà Nội.',
 'Vé máy bay khứ hồi, khách sạn 4 sao Thái Lan, các bữa ăn buffet quốc tế & lẩu Suki, vé tham quan, bảo hiểm quốc tế.',
 'Phí làm hộ chiếu, chi phí cá nhân, hành lý quá cước.',
 'Sân bay Quốc tế Nội Bài – Nhà ga T2', 'Máy bay & Xe du lịch đời mới', 'Khách sạn 4 sao tại Bangkok & Pattaya',
 'Hộ chiếu gốc còn hạn ít nhất 6 tháng tính từ ngày kết thúc tour (chuẩn bị trước khởi hành 14 ngày).',
 'Chặt chẽ: hủy trước 14 ngày hoàn 70%, dưới 7 ngày không hoàn', 1, 7690000, 650000, 'Phụ thu thuế sân bay quốc tế & tip HDV',
 'assets/images/bangkok.jpg', '["assets/images/bangkok.jpg","assets/images/tokyo.jpg"]', 'selling'),

(8, 'TOUR-TK08', 1, 'Tokyo – Núi Phú Sĩ – Kyoto: Cung Đường Vàng Nhật Bản', 'Tokyo', 'TP. Hồ Chí Minh', 5, 4,
 'Hành trình khám phá sự giao thoa kỳ diệu giữa Tokyo hiện đại, vẻ đẹp hùng vĩ của núi Phú Sĩ và nét cổ kính trầm mặc của cố đô Kyoto.',
 'Ngày 1: Bay từ TP. Hồ Chí Minh đến sân bay Narita (Tokyo), nhận phòng và khám phá phố đêm Shinjuku.\nNgày 2: Tham quan chùa cổ Asakusa Sensoji, tháp Tokyo Skytree và giao lộ Shibuya.\nNgày 3: Di chuyển tới núi Phú Sĩ, thăm làng cổ Oshino Hakkai và tắm suối khoáng nóng Onsen.\nNgày 4: Trải nghiệm tàu cao tốc Shinkansen đi Kyoto, thăm chùa Thanh Thủy Kiyomizu-dera.\nNgày 5: Tự do mua sắm tại Aeon Mall, ra sân bay làm thủ tục bay về TP. Hồ Chí Minh.',
 'Vé máy bay khứ hồi, visa đoàn Nhật Bản, khách sạn 4 sao, tàu Shinkansen, bữa ăn bò Kobe & cua tuyết, bảo hiểm quốc tế.',
 'Chi tiêu cá nhân, mua sắm ngoài chương trình.',
 'Sân bay Quốc tế Tân Sơn Nhất – Nhà ga Quốc tế', 'Máy bay & Tàu Shinkansen', 'Khách sạn 4 sao chuẩn Nhật & Ryokan Onsen',
 'Hộ chiếu còn hạn trên 6 tháng và Hồ sơ xin Visa Nhật Bản theo mẫu (nộp trước khởi hành 15 ngày).',
 'Chặt chẽ: hủy trước 14 ngày hoàn 70%, dưới 7 ngày không hoàn', 1, 17990000, 1200000, 'Phụ thu thuế sân bay & phí Visa đoàn',
 'assets/images/tokyo.jpg', '["assets/images/tokyo.jpg","assets/images/bangkok.jpg"]', 'selling'),

(9, 'TOUR-NB09', 1, 'Tam Cốc – Bích Động – Tuyệt Tình Cốc 2N1Đ (Chưa có đánh giá)', 'Ninh Bình', 'Hà Nội', 2, 1,
 'Hành trình nghỉ dưỡng 2 ngày 1 đêm giữa miền non nước Tam Cốc, đạp xe quanh làng quê yên bình và khám phá động Am Tiên.',
 'Ngày 1: Hà Nội – Tam Cốc, ngồi thuyền xuôi dòng Ngô Đồng, nhận phòng homestay ven núi đá vôi.\nNgày 2: Đạp xe thăm chùa Bích Động và Tuyệt Tình Cốc (Động Am Tiên), chiều về Hà Nội.',
 'Xe Limousine khứ hồi, 1 đêm nghỉ dưỡng Bungalow, vé thuyền Tam Cốc, 3 bữa ăn chính.',
 'Đồ uống cá nhân, chi phí thuê cổ phục chụp ảnh.',
 'Nhà hát Lớn Hà Nội', 'Xe Limousine 16 chỗ', 'Bungalow 3 sao view đồng lúa Tam Cốc',
 'Căn cước công dân hợp lệ (chuẩn bị trước khởi hành 2 ngày).',
 'Linh hoạt: hủy trước 7 ngày được hoàn 100%', 0, 1690000, 150000, 'Phụ thu vé thắng cảnh & thuyền nan',
 'assets/images/ninh-binh.jpg', '["assets/images/ninh-binh.jpg","assets/images/sa-pa.jpg"]', 'selling'),

(10, 'TOUR-HUE10', 1, 'Huế – Cố Đô Trầm Mặc & Du Thuyền Sông Hương', 'Huế', 'Hà Nội', 3, 2,
 'Khám phá Đại Nội, lăng tẩm triều Nguyễn và vẻ đẹp thơ mộng của sông Hương trong hành trình trọn vẹn tại Huế.',
 'Ngày 1: Hà Nội – Huế, tham quan Đại Nội Kinh Thành Huế và chùa Thiên Mụ.\nNgày 2: Thăm lăng Minh Mạng – Khải Định, tối nghe ca Huế trên sông Hương.\nNgày 3: Mua sắm chợ Đông Ba, tiễn khách ra sân bay Phú Bài.',
 'Xe đưa đón, khách sạn 4 sao ven sông Hương, vé tham quan Đại Nội và các lăng tẩm.',
 'Vé máy bay khứ hồi, chi tiêu cá nhân.',
 'Sân bay Quốc tế Nội Bài', 'Máy bay & Xe du lịch', 'Khách sạn 4 sao ven sông Hương',
 'Căn cước công dân hợp lệ (chuẩn bị trước 3 ngày).',
 'Tiêu chuẩn: hủy trước 7 ngày hoàn 80%, trước 3 ngày hoàn 50%', 0, 2750000, 200000, 'Phụ thu thuyền Rồng nghe ca Huế',
 'assets/images/hoi-an.jpg', '["assets/images/hoi-an.jpg","assets/images/da-lat.jpg"]', 'paused'),

(11, 'TOUR-QN11', 1, 'Quy Nhơn – Kỳ Co – Eo Gió: Thiên Đường Biển Miền Trung', 'Quy Nhơn', 'TP. Hồ Chí Minh', 3, 2,
 'Khám phá biển xanh Kỳ Co, cung đường ven biển Eo Gió và những nét văn hóa đặc sắc của Quy Nhơn.',
 'Ngày 1: Đón khách tại sân bay Phù Cát, tham quan Tháp Đôi và Ghềnh Ráng Tiên Sa.\nNgày 2: Cano ra đảo Kỳ Co, check-in Eo Gió và Tịnh xá Ngọc Hòa.\nNgày 3: Tham quan Bảo tàng Quang Trung, mua đặc sản bánh ít lá gai và ra sân bay.',
 'Xe du lịch đời mới, khách sạn 4 sao biển Quy Nhơn, cano Kỳ Co, các bữa ăn hải sản.',
 'Vé máy bay khứ hồi, chi phí cá nhân.',
 'Sân bay Tân Sơn Nhất / Sân bay Phù Cát', 'Máy bay & Cano', 'Khách sạn 4 sao ven biển Quy Nhơn',
 'Căn cước công dân còn hạn (chuẩn bị trước 3 ngày).',
 'Linh hoạt: hủy trước 7 ngày được hoàn 100%', 0, 2590000, 190000, 'Phụ thu cano & phí bảo tồn Kỳ Co',
 'assets/images/phu-quoc.jpg', '["assets/images/phu-quoc.jpg","assets/images/hoi-an.jpg"]', 'pending');

-- Lịch khởi hành của các Tour
INSERT INTO departures (id, code, tour_id, start_date, end_date, capacity, price_per_person, cutoff_at, meeting_time, status) VALUES
(1, 'DEP-HL-01', 1, DATE_ADD(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), 24, 2890000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 9 DAY), ' 00:00:00'), '06:30 tại Nhà hát Lớn Hà Nội', 'open'),
(2, 'DEP-HL-02', 1, DATE_ADD(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 27 DAY), 20, 2890000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 24 DAY), ' 00:00:00'), '06:30 tại Nhà hát Lớn Hà Nội', 'open'),
(3, 'DEP-HL-03', 1, DATE_ADD(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 42 DAY), 16, 2950000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 39 DAY), ' 00:00:00'), '06:30 tại Nhà hát Lớn Hà Nội', 'full'),
(4, 'DEP-HL-04', 1, DATE_ADD(CURDATE(), INTERVAL 55 DAY), DATE_ADD(CURDATE(), INTERVAL 57 DAY), 20, 2890000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 54 DAY), ' 00:00:00'), '06:30 tại Nhà hát Lớn Hà Nội', 'closed'),

(5, 'DEP-HA-01', 2, DATE_ADD(CURDATE(), INTERVAL 8 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 25, 2350000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), ' 00:00:00'), '08:00 tại Sân bay Đà Nẵng', 'open'),
(6, 'DEP-HA-02', 2, DATE_ADD(CURDATE(), INTERVAL 22 DAY), DATE_ADD(CURDATE(), INTERVAL 24 DAY), 25, 2350000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 21 DAY), ' 00:00:00'), '08:00 tại Sân bay Đà Nẵng', 'open'),

(7, 'DEP-SP-01', 3, DATE_ADD(CURDATE(), INTERVAL 12 DAY), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 20, 2690000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 11 DAY), ' 00:00:00'), '06:00 tại 18 Tràng Tiền', 'open'),
(8, 'DEP-SP-02', 3, DATE_ADD(CURDATE(), INTERVAL 28 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 20, 2690000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 27 DAY), ' 00:00:00'), '06:00 tại 18 Tràng Tiền', 'open'),

(9, 'DEP-PQ-01', 4, DATE_ADD(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 18 DAY), 24, 4990000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 14 DAY), ' 00:00:00'), '09:00 tại Sân bay Phú Quốc', 'open'),
(10, 'DEP-PQ-02', 4, DATE_ADD(CURDATE(), INTERVAL 32 DAY), DATE_ADD(CURDATE(), INTERVAL 35 DAY), 24, 4990000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 31 DAY), ' 00:00:00'), '09:00 tại Sân bay Phú Quốc', 'open'),

(11, 'DEP-DL-01', 5, DATE_ADD(CURDATE(), INTERVAL 9 DAY), DATE_ADD(CURDATE(), INTERVAL 11 DAY), 18, 2490000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 8 DAY), ' 00:00:00'), '22:00 tại NVH Thanh Niên Q.1', 'open'),
(12, 'DEP-DL-02', 5, DATE_ADD(CURDATE(), INTERVAL 24 DAY), DATE_ADD(CURDATE(), INTERVAL 26 DAY), 18, 2490000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 23 DAY), ' 00:00:00'), '22:00 tại NVH Thanh Niên Q.1', 'open'),

(13, 'DEP-NB-01', 6, DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 30, 890000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 4 DAY), ' 00:00:00'), '07:00 tại Công viên Thống Nhất', 'open'),
(14, 'DEP-NB-02', 6, DATE_ADD(CURDATE(), INTERVAL 14 DAY), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 30, 890000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 13 DAY), ' 00:00:00'), '07:00 tại Công viên Thống Nhất', 'open'),

(15, 'DEP-BK-01', 7, DATE_ADD(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 23 DAY), 20, 7690000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 19 DAY), ' 00:00:00'), '05:30 tại Nhà ga T2 Nội Bài', 'open'),
(16, 'DEP-BK-02', 7, DATE_ADD(CURDATE(), INTERVAL 45 DAY), DATE_ADD(CURDATE(), INTERVAL 48 DAY), 20, 7690000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 44 DAY), ' 00:00:00'), '05:30 tại Nhà ga T2 Nội Bài', 'open'),

(17, 'DEP-TK-01', 8, DATE_ADD(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 34 DAY), 16, 17990000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 29 DAY), ' 00:00:00'), '21:00 tại Ga Quốc tế Tân Sơn Nhất', 'open'),
(18, 'DEP-TK-02', 8, DATE_ADD(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 64 DAY), 16, 17990000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 59 DAY), ' 00:00:00'), '21:00 tại Ga Quốc tế Tân Sơn Nhất', 'open'),

(19, 'DEP-NB2-01', 9, DATE_ADD(CURDATE(), INTERVAL 11 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), 16, 1690000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 10 DAY), ' 00:00:00'), '07:30 tại Nhà hát Lớn Hà Nội', 'open'),

(20, 'DEP-HUE-01', 10, DATE_ADD(CURDATE(), INTERVAL 18 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 20, 2750000, CONCAT(DATE_ADD(CURDATE(), INTERVAL 17 DAY), ' 00:00:00'), '06:00 tại Sân bay Nội Bài', 'closed');

-- Đánh giá của khách hàng
INSERT INTO reviews (tour_id, user_id, rating, comment, created_at) VALUES
(1, 1, 5, 'Du thuyền rất sạch đẹp, đồ ăn hải sản tươi ngon, hướng dẫn viên nhiệt tình chu đáo suốt chuyến đi Hạ Long!', DATE_SUB(NOW(), INTERVAL 12 DAY)),
(1, 4, 5, 'Lịch trình hợp lý, chèo kayak ở hang Luồn và ngắm bình minh trên vịnh thực sự đáng nhớ.', DATE_SUB(NOW(), INTERVAL 8 DAY)),
(2, 1, 5, 'Phố cổ Hội An buổi tối lung linh, khách sạn 4 sao gần biển Đà Nẵng rất tiện đi dạo.', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(2, 5, 4, 'Tour tổ chức chuyên nghiệp, đồ ăn Cao Lầu ngon, xe đưa đón đúng giờ.', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3, 4, 5, 'Sa Pa mùa này săn mây tuyệt đẹp, bản Cát Cát và Tả Van không khí trong lành.', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(4, 1, 5, 'Cano đi 4 đảo Phú Quốc rất vui, nước biển trong vắt, dịch vụ của ViVu Travel rất tốt.', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(5, 5, 4, 'Đà Lạt thơ mộng, săn mây Cầu Đất sáng sớm cực kỳ ấn tượng, HDV chụp ảnh có tâm.', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(6, 1, 5, 'Tour Ninh Bình 1 ngày giá dưới 1 triệu mà chất lượng vượt mong đợi, buffet dê núi ngon.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(7, 4, 5, 'Chuyến đi Bangkok – Pattaya rất vui, Wat Arun đẹp xuất sắc, khách sạn trung tâm tiện mua sắm.', DATE_SUB(NOW(), INTERVAL 9 DAY)),
(8, 5, 5, 'Trải nghiệm tàu Shinkansen và tắm Onsen dưới chân núi Phú Sĩ tuyệt vời, đáng đồng tiền bát gạo!', DATE_SUB(NOW(), INTERVAL 4 DAY));

-- Booking ban đầu
INSERT INTO bookings (id, code, departure_id, customer_id, people, contact_name, contact_phone, contact_email, note, base_unit, surcharge_unit, surcharge_label, total, paid_amount, policy_snapshot, passenger_status, status, seller_deadline, deposit_deadline, balance_deadline, full_required, confirmed_at) VALUES
(1, 'VV261024A1B2', 1, 1, 2, 'Nguyễn Lan Anh', '0912345678', 'customer@vivuviet.test', 'Ưu tiên phòng tầng cao, có 1 khách ăn kiêng hải sản có vỏ.', 2890000, 250000, 'Phụ thu tàu & phí cảng bắt buộc', 6280000, 0, 'Linh hoạt: hủy trước 7 ngày được hoàn 100%', 'incomplete', 'confirmed', DATE_ADD(NOW(), INTERVAL 20 HOUR), DATE_ADD(NOW(), INTERVAL 24 HOUR), CONCAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), ' 23:59:59'), 0, NOW()),
(2, 'VV261024C3D4', 5, 1, 3, 'Nguyễn Lan Anh', '0912345678', 'customer@vivuviet.test', 'Đoàn gia đình 3 người, cần xe đón đúng giờ tại sân bay Đà Nẵng.', 2350000, 180000, 'Phụ thu vé tham quan & thuyền sông Hoài', 7590000, 0, 'Linh hoạt: hủy trước 7 ngày được hoàn 100%', 'incomplete', 'pending_seller', DATE_ADD(NOW(), INTERVAL 22 HOUR), NULL, CONCAT(DATE_ADD(CURDATE(), INTERVAL 5 DAY), ' 23:59:59'), 0, NULL);

INSERT INTO notifications (user_id, message, link, is_read) VALUES
(1, 'Booking VV261024A1B2 (Vịnh Hạ Long – Đêm Trên Du Thuyền 5 Sao) đã được Seller xác nhận. Vui lòng hoàn tất danh sách hành khách và đặt cọc 50% hoặc thanh toán 100%.', 'index.php?page=booking&id=1', 0),
(2, 'Booking mới VV261024C3D4 cần Seller xác nhận trong vòng 24 giờ.', 'index.php?page=seller_bookings', 0),
(3, 'Tour "Quy Nhơn – Kỳ Co – Eo Gió: Thiên Đường Biển Miền Trung" đang chờ Quản trị viên kiểm duyệt.', 'index.php?page=admin', 0);
