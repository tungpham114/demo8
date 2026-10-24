<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function post_action(): void {
    must(hash_equals(csrf(), (string)($_POST['csrf'] ?? '')), 'Phiên biểu mẫu đã hết hạn. Vui lòng tải lại trang.');
    clear_form_state();
    $action = (string)($_POST['action'] ?? '');
    $user = me();

    switch ($action) {
        case 'register':
            $name = trim((string)($_POST['name'] ?? ''));
            $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $errors = [];
            if ($name === '') $errors['name'] = 'Vui lòng nhập họ và tên.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Vui lòng nhập địa chỉ email hợp lệ.';
            } elseif (q('SELECT id FROM users WHERE email=?', [$email])->fetch()) {
                $errors['email'] = 'Email này đã được đăng ký trên hệ thống.';
            }
            if ($phone !== '' && !preg_match('/^0[0-9]{9,10}$/', $phone)) {
                $errors['phone'] = 'Số điện thoại phải bắt đầu bằng số 0 và gồm 10–11 chữ số.';
            }
            if (strlen($password) < 8) {
                $errors['password'] = 'Mật khẩu phải có tối thiểu 8 ký tự.';
            }
            if ($errors) {
                set_field_errors($errors, ['name' => $name, 'email' => $email, 'phone' => $phone]);
                flash('Vui lòng kiểm tra lại thông tin đăng ký.', 'error');
                go('register');
            }
            q('INSERT INTO users(name,email,phone,password_hash,role) VALUES (?,?,?,?,?)', [
                $name, $email, $phone ?: null, password_hash($password, PASSWORD_DEFAULT), 'customer'
            ]);
            $id = (int)db()->lastInsertId();
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => $id, 'name' => $name, 'email' => $email, 'phone' => $phone, 'role' => 'customer'];

            // Nếu khách đang thực hiện Lưu Tour (AD-CN08 A2.2 -> A2.4)
            if (!empty($_SESSION['pending_save_tour_id'])) {
                $saveTourId = (int)$_SESSION['pending_save_tour_id'];
                unset($_SESSION['pending_save_tour_id']);
                q('INSERT IGNORE INTO saved_tours(user_id, tour_id) VALUES (?,?)', [$id, $saveTourId]);
                flash('Đăng ký thành công và đã thêm Tour vào danh sách "Tour đã lưu".');
                $ret = $_SESSION['return_to'] ?? '';
                unset($_SESSION['return_to']);
                if ($ret !== '') { header('Location: ' . $ret); exit; }
            }
            flash('Tạo tài khoản Khách hàng thành công.');
            go('home');

        case 'login':
            $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
            $password = (string)($_POST['password'] ?? '');
            $errors = [];
            if ($email === '') $errors['email'] = 'Vui lòng nhập email.';
            if ($password === '') $errors['password'] = 'Vui lòng nhập mật khẩu.';
            $row = $email !== '' ? q('SELECT * FROM users WHERE email=?', [$email])->fetch() : false;
            if (!$errors && (!$row || !password_verify($password, $row['password_hash']))) {
                $errors['email'] = 'Email hoặc mật khẩu không đúng.';
            }
            if ($errors) {
                set_field_errors($errors, ['email' => $email]);
                flash('Đăng nhập không thành công. Vui lòng kiểm tra lại.', 'error');
                go('login');
            }
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'phone' => $row['phone'] ?? '',
                'role' => $row['role'],
            ];

            // Xử lý tự động tiếp tục Lưu Tour sau khi đăng nhập thành công (AD-CN08 A2.2 -> [thành công] -> A2.4 / A2.5 -> A2.6)
            if (!empty($_SESSION['pending_save_tour_id']) && $row['role'] === 'customer') {
                $saveTourId = (int)$_SESSION['pending_save_tour_id'];
                unset($_SESSION['pending_save_tour_id']);
                $exists = q('SELECT id FROM saved_tours WHERE user_id=? AND tour_id=?', [(int)$row['id'], $saveTourId])->fetchColumn();
                if ($exists) {
                    q('DELETE FROM saved_tours WHERE user_id=? AND tour_id=?', [(int)$row['id'], $saveTourId]);
                    flash('Đã gỡ Tour khỏi danh sách "Tour đã lưu".');
                } else {
                    q('INSERT INTO saved_tours(user_id,tour_id) VALUES (?,?)', [(int)$row['id'], $saveTourId]);
                    flash('Đã thêm Tour vào danh sách "Tour đã lưu".');
                }
            } else {
                flash('Chào mừng trở lại, ' . $row['name'] . '.');
            }

            $return = $_SESSION['return_to'] ?? '';
            unset($_SESSION['return_to']);
            if (is_string($return) && $return !== '' && (str_starts_with($return, 'index.php') || str_contains($return, 'index.php?'))) {
                header('Location: ' . $return);
                exit;
            }
            go($row['role'] === 'seller' ? 'seller' : ($row['role'] === 'admin' ? 'admin' : 'home'));

        case 'cancel_pending_save':
            // AD-CN08 A2.3: Khách hủy đăng nhập khi lưu tour -> Không lưu, giữ nguyên danh sách đã lưu
            unset($_SESSION['pending_save_tour_id']);
            $return = $_SESSION['return_to'] ?? url('tours');
            unset($_SESSION['return_to']);
            flash('Đã hủy đăng nhập. Tour chưa được lưu và danh sách hiện tại vẫn được giữ nguyên.', 'error');
            header('Location: ' . $return);
            exit;

        case 'logout':
            $_SESSION = [];
            session_regenerate_id(true);
            flash('Bạn đã đăng xuất.');
            go('home');

        case 'toggle_save_tour':
            // AD-CN08: Lưu / Bỏ lưu Tour (A2.1 -> A2.6)
            $tourId = (int)($_POST['tour_id'] ?? 0);
            $returnUrl = trim((string)($_POST['return_url'] ?? url('tours')));
            if (!$user) {
                // A2.1 -> [chưa đăng nhập] -> A2.2 Đăng nhập tài khoản
                $_SESSION['pending_save_tour_id'] = $tourId;
                $_SESSION['return_to'] = $returnUrl;
                flash('Vui lòng đăng nhập bằng tài khoản Khách hàng để lưu Tour vào danh sách quan tâm.', 'error');
                go('login');
            }
            if ($user['role'] !== 'customer') {
                flash('Chỉ tài khoản Khách hàng mới thực hiện được chức năng Lưu Tour.', 'error');
                header('Location: ' . $returnUrl);
                exit;
            }
            $exists = q('SELECT id FROM saved_tours WHERE user_id=? AND tour_id=?', [$user['id'], $tourId])->fetchColumn();
            if ($exists) {
                // A2.5: Gỡ khỏi "Tour đã lưu"
                q('DELETE FROM saved_tours WHERE user_id=? AND tour_id=?', [$user['id'], $tourId]);
                audit($user['id'], 'tour', $tourId, 'unsaved_tour');
                flash('Đã gỡ Tour khỏi danh sách "Tour đã lưu".');
            } else {
                // A2.4: Thêm vào "Tour đã lưu"
                q('INSERT INTO saved_tours(user_id,tour_id) VALUES (?,?)', [$user['id'], $tourId]);
                audit($user['id'], 'tour', $tourId, 'saved_tour');
                flash('Đã thêm Tour vào danh sách "Tour đã lưu".');
            }
            header('Location: ' . $returnUrl);
            exit;

        case 'seller_otp_send':
            // Tạo tài khoản Seller và xác minh email trước khi nhập hồ sơ doanh nghiệp.
            must(!$user, 'Vui lòng đăng xuất trước khi đăng ký tài khoản Seller mới.');
            $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
            $password = (string)($_POST['password'] ?? '');
            $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

            $errors = [];
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Vui lòng nhập địa chỉ Gmail / Email hợp lệ.';
            } elseif (q('SELECT id FROM users WHERE email=?', [$email])->fetch()) {
                $errors['email'] = 'Email đã thuộc tài khoản khác trên Sàn. Vui lòng đăng nhập hoặc dùng email khác.';
            }
            if (strlen($password) < 8) {
                $errors['password'] = 'Mật khẩu phải có tối thiểu 8 ký tự.';
            }
            if ($passwordConfirm === '' || $password !== $passwordConfirm) {
                $errors['password_confirm'] = 'Mật khẩu nhập lại không khớp với mật khẩu đã nhập.';
            }

            if ($errors) {
                set_field_errors($errors, ['email' => $email]);
                flash('Thông tin tài khoản không hợp lệ. Vui lòng kiểm tra các trường báo lỗi.', 'error');
                go('seller_register');
            }

            $otp = (string)random_int(100000, 999999);
            $_SESSION['seller_signup'] = [
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'otp_hash' => password_hash($otp, PASSWORD_DEFAULT),
                'otp_expires' => time() + 600,
                'resend_count' => 0,
                'verified' => false,
            ];
            $_SESSION['demo_otp'] = $otp;
            flash('Đã gửi mã OTP xác minh tới email ' . $email . '. Vui lòng nhập mã xác nhận.');
            go('seller_register');

        case 'seller_otp_verify':
            $signup = $_SESSION['seller_signup'] ?? null;
            if (!$signup || $signup['verified'] || $signup['otp_expires'] < time()) {
                set_field_errors(['otp' => 'Mã OTP đã hết hiệu lực. Vui lòng chọn "Gửi lại mã".']);
                flash('Mã OTP không còn hiệu lực.', 'error');
                go('seller_register');
            }
            $otpInput = trim((string)($_POST['otp'] ?? ''));
            if ($otpInput === '' || !password_verify($otpInput, $signup['otp_hash'])) {
                set_field_errors(['otp' => 'Mã OTP không chính xác. Vui lòng nhập lại hoặc gửi lại mã.']);
                flash('Xác minh OTP thất bại.', 'error');
                go('seller_register');
            }
            $_SESSION['seller_signup']['verified'] = true;
            $_SESSION['seller_signup']['otp_expires'] = time() + 1800;
            unset($_SESSION['demo_otp']);
            flash('Đã xác minh email thành công! Phần hồ sơ doanh nghiệp đã được mở khóa.');
            go('seller_register');

        case 'seller_otp_resend':
            $signup = $_SESSION['seller_signup'] ?? null;
            must($signup && !$signup['verified'], 'Không có phiên xác minh email đang chờ.');
            $count = (int)($signup['resend_count'] ?? 0) + 1;
            if ($count > 5) {
                unset($_SESSION['seller_signup'], $_SESSION['demo_otp']);
                flash('Bạn đã vượt quá số lần gửi lại mã OTP cho phép. Vui lòng thử lại sau.', 'error');
                go('seller_register');
            }
            $otp = (string)random_int(100000, 999999);
            $_SESSION['seller_signup']['otp_hash'] = password_hash($otp, PASSWORD_DEFAULT);
            $_SESSION['seller_signup']['otp_expires'] = time() + 600;
            $_SESSION['seller_signup']['resend_count'] = $count;
            $_SESSION['demo_otp'] = $otp;
            flash('Đã tạo mã OTP mới; mã cũ không còn hiệu lực.');
            go('seller_register');

        case 'seller_reset_account':
            unset($_SESSION['seller_signup'], $_SESSION['demo_otp']);
            flash('Đã mở khóa để nhập lại địa chỉ email.');
            go('seller_register');

        case 'seller_register':
            must(!$user, 'Vui lòng đăng xuất trước khi tạo tài khoản Seller riêng.');
            $signup = $_SESSION['seller_signup'] ?? null;
            $company = trim((string)($_POST['company'] ?? ''));
            $tax = trim((string)($_POST['tax_code'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $address = trim((string)($_POST['address'] ?? ''));
            $representative = trim((string)($_POST['representative'] ?? ''));
            $travelType = (string)($_POST['travel_type'] ?? '');
            $oldData = [
                'company' => $company, 'tax_code' => $tax, 'phone' => $phone,
                'address' => $address, 'representative' => $representative, 'travel_type' => $travelType,
                'quota_agreement' => !empty($_POST['quota_agreement']) ? '1' : '',
            ];

            if (!$signup || !$signup['verified'] || $signup['otp_expires'] < time()) {
                unset($_SESSION['seller_signup'], $_SESSION['demo_otp']);
                set_field_errors(['email' => 'Phiên xác minh email đã hết hạn. Vui lòng xác minh lại email.'], $oldData);
                flash('Phiên xác minh email đã hết hạn. Dữ liệu hồ sơ đã nhập vẫn được giữ lại.', 'error');
                go('seller_register');
            }

            $errors = [];
            if ($company === '') $errors['company'] = 'Vui lòng nhập tên doanh nghiệp.';
            if (!preg_match('/^[0-9]{10}([0-9]{3})?$/', $tax)) {
                $errors['tax_code'] = 'Mã số thuế phải gồm 10 hoặc 13 chữ số.';
            } elseif (q('SELECT id FROM sellers WHERE tax_code=?', [$tax])->fetch()) {
                $errors['tax_code'] = 'Doanh nghiệp đã có tài khoản Seller trên Sàn.';
            }
            if (!preg_match('/^0[0-9]{9,10}$/', $phone)) $errors['phone'] = 'Số điện thoại không hợp lệ.';
            if ($address === '') $errors['address'] = 'Vui lòng nhập địa chỉ trụ sở doanh nghiệp.';
            if ($representative === '') $errors['representative'] = 'Vui lòng nhập họ tên người đại diện pháp luật.';
            if (!in_array($travelType, ['domestic', 'international'], true)) {
                $errors['travel_type'] = 'Vui lòng chọn loại hình lữ hành (Nội địa hoặc Quốc tế).';
            }
            if (empty($_POST['quota_agreement'])) {
                $errors['quota_agreement'] = 'Bạn phải đồng ý điều khoản cam kết quỹ chỗ trên Sàn.';
            }

            $paths = [];
            foreach (['business_license' => 'Giấy phép đăng ký kinh doanh', 'travel_license' => 'Giấy phép kinh doanh dịch vụ lữ hành'] as $key => $label) {
                $file = $_FILES[$key] ?? null;
                if (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] <= 0 || $file['size'] > 5 * 1024 * 1024) {
                    $errors[$key] = 'Vui lòng tải lên ' . $label . ' (PDF, JPG, PNG tối đa 5 MB).';
                } else {
                    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                    if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
                        $errors[$key] = $label . ' chỉ chấp nhận định dạng PDF, JPG hoặc PNG.';
                    }
                }
            }

            if ($errors) {
                set_field_errors($errors, $oldData);
                flash('Hồ sơ doanh nghiệp chưa hợp lệ. Vui lòng kiểm tra các trường báo lỗi.', 'error');
                go('seller_register');
            }

            foreach (['business_license', 'travel_license'] as $key) {
                $file = $_FILES[$key];
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                $ext = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'][$mime];
                $paths[$key] = 'private/' . bin2hex(random_bytes(16)) . '.' . $ext;
                move_uploaded_file($file['tmp_name'], __DIR__ . '/' . $paths[$key]);
            }

            $email = $signup['email'];
            db()->beginTransaction();
            try {
                q('INSERT INTO users(name,email,phone,password_hash,role) VALUES (?,?,?,?,?)', [
                    $company, $email, $phone, $signup['password_hash'], 'seller'
                ]);
                $id = (int)db()->lastInsertId();
                q('INSERT INTO sellers(user_id,company_name,tax_code,address,representative,phone,travel_type,business_license,travel_license,status) VALUES (?,?,?,?,?,?,?,?,?,?)', [
                    $id, $company, $tax, $address, $representative, $phone, $travelType, $paths['business_license'], $paths['travel_license'], 'pending'
                ]);
                $sellerId = (int)db()->lastInsertId();
                audit($id, 'seller', $sellerId, 'submitted', 'Gửi hồ sơ đăng ký Seller mới');
                $admins = q("SELECT id FROM users WHERE role='admin'")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($admins as $adminId) {
                    notice((int)$adminId, 'Hồ sơ Seller mới "' . $company . '" đang chờ xác minh.', url('admin'));
                }
                db()->commit();
            } catch (Throwable $e) {
                db()->rollBack();
                foreach ($paths as $path) @unlink(__DIR__ . '/' . $path);
                throw $e;
            }
            unset($_SESSION['seller_signup'], $_SESSION['demo_otp']);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => $id, 'name' => $company, 'email' => $email, 'phone' => $phone, 'role' => 'seller'];
            flash('Gửi hồ sơ thành công! Hồ sơ đang ở trạng thái "Chờ xác minh".');
            go('seller');

        case 'seller_resubmit':
            $sellerUser = require_role('seller');
            $seller = q('SELECT * FROM sellers WHERE user_id=?', [$sellerUser['id']])->fetch();
            must($seller && $seller['status'] === 'rejected', 'Chỉ hồ sơ bị từ chối mới gửi lại được.');
            $company = trim((string)($_POST['company'] ?? ''));
            $tax = trim((string)($_POST['tax_code'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $address = trim((string)($_POST['address'] ?? ''));
            $representative = trim((string)($_POST['representative'] ?? ''));
            $travelType = (string)($_POST['travel_type'] ?? '');

            $errors = [];
            if ($company === '') $errors['company'] = 'Vui lòng nhập tên doanh nghiệp.';
            if (!preg_match('/^[0-9]{10}([0-9]{3})?$/', $tax)) {
                $errors['tax_code'] = 'Mã số thuế phải gồm 10 hoặc 13 chữ số.';
            } elseif (q('SELECT id FROM sellers WHERE tax_code=? AND id<>?', [$tax, $seller['id']])->fetch()) {
                $errors['tax_code'] = 'Doanh nghiệp đã có tài khoản Seller khác.';
            }
            if (!preg_match('/^0[0-9]{9,10}$/', $phone)) $errors['phone'] = 'Số điện thoại không hợp lệ.';
            if ($address === '') $errors['address'] = 'Vui lòng nhập địa chỉ.';
            if ($representative === '') $errors['representative'] = 'Vui lòng nhập người đại diện.';
            if (!in_array($travelType, ['domestic', 'international'], true)) $errors['travel_type'] = 'Chọn loại hình lữ hành.';
            if (empty($_POST['quota_agreement'])) $errors['quota_agreement'] = 'Cần xác nhận lại cam kết quỹ chỗ.';

            if ($errors) {
                set_field_errors($errors, $_POST);
                flash('Vui lòng kiểm tra lại thông tin hồ sơ gửi lại.', 'error');
                go('seller_register');
            }

            $paths = [];
            foreach (['business_license', 'travel_license'] as $key) {
                $file = $_FILES[$key] ?? null;
                if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
                    $paths[$key] = $seller[$key];
                    continue;
                }
                must($file['error'] === UPLOAD_ERR_OK && $file['size'] > 0 && $file['size'] <= 5 * 1024 * 1024, 'Giấy phép tối đa 5 MB.');
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                must(in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true), 'Giấy phép chỉ nhận PDF/JPG/PNG.');
                $ext = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'][$mime];
                $paths[$key] = 'private/' . bin2hex(random_bytes(16)) . '.' . $ext;
                move_uploaded_file($file['tmp_name'], __DIR__ . '/' . $paths[$key]);
            }

            q("UPDATE sellers SET company_name=?,tax_code=?,address=?,representative=?,phone=?,travel_type=?,business_license=?,travel_license=?,status='pending',rejection_reason=NULL WHERE id=?", [
                $company, $tax, $address, $representative, $phone, $travelType, $paths['business_license'], $paths['travel_license'], $seller['id']
            ]);
            audit($sellerUser['id'], 'seller', (int)$seller['id'], 'resubmitted', 'Gửi lại hồ sơ Seller');
            $admins = q("SELECT id FROM users WHERE role='admin'")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($admins as $adminId) {
                notice((int)$adminId, 'Seller "' . $company . '" vừa bổ sung và gửi lại hồ sơ xác minh.', url('admin'));
            }
            flash('Đã gửi lại hồ sơ Seller thành công, chuyển về trạng thái "Chờ xác minh".');
            go('seller');

        case 'seller_review':
            // CN02: Xác minh hồ sơ Seller
            $admin = require_role('admin');
            $id = (int)($_POST['seller_id'] ?? 0);
            $decision = (string)($_POST['decision'] ?? '');
            must(in_array($decision, ['verified', 'rejected'], true), 'Quyết định không hợp lệ.');
            $reason = trim((string)($_POST['reason'] ?? ''));
            if ($decision === 'rejected' && $reason === '') {
                set_field_errors(['seller_reason_' . $id => 'Bắt buộc nhập lý do từ chối hồ sơ Seller.']);
                flash('Vui lòng nhập lý do khi từ chối hồ sơ Seller.', 'error');
                go('admin');
            }
            $count = q("UPDATE sellers SET status=?,rejection_reason=? WHERE id=? AND status='pending'", [$decision, $reason ?: null, $id])->rowCount();
            if ($count !== 1) {
                flash('Hồ sơ này đã được người khác xử lý trước đó, không ghi đè kết quả.', 'error');
                go('admin');
            }
            $seller = q('SELECT user_id, company_name FROM sellers WHERE id=?', [$id])->fetch();
            audit($admin['id'], 'seller', $id, $decision, $reason ?: 'Phê duyệt hồ sơ Seller');
            notice((int)$seller['user_id'], $decision === 'verified'
                ? 'Hồ sơ Seller "' . $seller['company_name'] . '" đã được phê duyệt (Đã xác minh). Bạn đã có thể đăng tour!'
                : 'Hồ sơ Seller bị từ chối: ' . $reason, url('seller'));
            flash($decision === 'verified' ? 'Đã phê duyệt hồ sơ Seller thành công.' : 'Đã từ chối hồ sơ Seller kèm lý do.');
            go('admin');

        case 'tour_save':
            // CN03 / UC15: Tạo / Sửa / Lưu nháp / Gửi duyệt Tour
            $sellerUser = require_role('seller');
            $seller = q('SELECT * FROM sellers WHERE user_id=?', [$sellerUser['id']])->fetch();
            must($seller && $seller['status'] === 'verified', 'Seller cần ở trạng thái "Đã xác minh" trước khi đăng tour.');
            $id = (int)($_POST['tour_id'] ?? 0);
            $submit = ($_POST['intent'] ?? '') === 'submit';

            $fields = ['title', 'destination', 'origin', 'description', 'itinerary', 'included', 'excluded', 'pickup', 'transport', 'accommodation', 'required_documents', 'cancellation_policy', 'surcharge_label'];
            $data = [];
            foreach ($fields as $field) $data[$field] = trim((string)($_POST[$field] ?? ''));
            $data['days'] = (int)($_POST['days'] ?? 0);
            $data['nights'] = (int)($_POST['nights'] ?? 0);
            $data['base_price'] = (int)($_POST['base_price'] ?? 0);
            $data['surcharge_per_person'] = (int)($_POST['surcharge_per_person'] ?? 0);
            if ($data['surcharge_label'] === '') $data['surcharge_label'] = 'Phụ thu dịch vụ bắt buộc';
            $data['is_international'] = empty($_POST['is_international']) ? 0 : 1;
            $data['image_path'] = (string)($_POST['image_path'] ?? 'assets/images/ha-long.jpg');

            $allowed = array_map(fn($x) => 'assets/images/' . $x . '.jpg', ['ha-long', 'hoi-an', 'sa-pa', 'phu-quoc', 'da-lat', 'ninh-binh', 'bangkok', 'tokyo']);
            $errors = [];

            if (isset($_FILES['tour_image']) && $_FILES['tour_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['tour_image'];
                if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] <= 0 || $file['size'] > 5 * 1024 * 1024) {
                    $errors['tour_image'] = 'Ảnh tải lên không hợp lệ hoặc vượt quá 5 MB.';
                } else {
                    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                    if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
                        $errors['tour_image'] = 'Ảnh tour chỉ chấp nhận định dạng JPG hoặc PNG.';
                    } else {
                        $data['image_path'] = 'assets/tours/' . bin2hex(random_bytes(16)) . ($mime === 'image/png' ? '.png' : '.jpg');
                        move_uploaded_file($file['tmp_name'], __DIR__ . '/' . $data['image_path']);
                    }
                }
            } elseif (!in_array($data['image_path'], $allowed, true) && !preg_match('~^assets/tours/[0-9a-f]{32}\.(jpg|png)$~', $data['image_path'])) {
                $errors['image_path'] = 'Vui lòng chọn ít nhất 1 ảnh đại diện hợp lệ cho tour.';
            }

            if ($submit) {
                $reqLabels = [
                    'title' => 'Tên tour', 'destination' => 'Điểm đến', 'origin' => 'Điểm khởi hành',
                    'description' => 'Mô tả tổng quan', 'itinerary' => 'Lịch trình chi tiết theo ngày',
                    'included' => 'Dịch vụ bao gồm', 'excluded' => 'Dịch vụ không bao gồm',
                    'pickup' => 'Điểm đón / tập trung', 'transport' => 'Phương tiện di chuyển',
                    'accommodation' => 'Nơi lưu trú', 'cancellation_policy' => 'Mức chính sách hủy/đổi',
                ];
                foreach ($reqLabels as $f => $lbl) {
                    if ($data[$f] === '') $errors[$f] = 'Trường bắt buộc: Vui lòng nhập ' . $lbl . '.';
                }
                if (!in_array($data['cancellation_policy'], CANCEL_POLICY_FRAME, true)) {
                    $errors['cancellation_policy'] = 'Vui lòng chọn một mức chính sách hủy/đổi trong khung chuẩn của Sàn.';
                }
                if ($data['days'] < 1) $errors['days'] = 'Số ngày của tour phải từ 1 ngày trở lên.';
                if ($data['nights'] < 0) $errors['nights'] = 'Số đêm không được âm.';
                if ($data['base_price'] <= 0) $errors['base_price'] = 'Giá phải lớn hơn 0.';
                if ($data['surcharge_per_person'] < 0) $errors['surcharge_per_person'] = 'Phụ thu không được âm.';
                $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $data['itinerary']) ?: [])));
                if ($data['days'] >= 1 && count($lines) !== $data['days']) {
                    $errors['itinerary'] = 'Số dòng lịch trình (' . count($lines) . ' dòng) phải bằng đúng số ngày của tour (' . $data['days'] . ' ngày).';
                }
                if ($data['is_international'] && $data['required_documents'] === '') {
                    $errors['required_documents'] = 'Tour quốc tế / qua cửa khẩu bắt buộc khai báo giấy tờ cần chuẩn bị và thời hạn.';
                }
            }

            if ($errors) {
                set_field_errors($errors, $_POST);
                flash('Dữ liệu tour chưa hợp lệ. Vui lòng kiểm tra các trường báo lỗi bên dưới.', 'error');
                go('seller_tour_form', $id ? ['id' => $id] : []);
            }

            $gallery = json_encode(array_values(array_unique([$data['image_path'], 'assets/images/ha-long.jpg', 'assets/images/hoi-an.jpg'])), JSON_UNESCAPED_UNICODE);
            $data['gallery_json'] = $gallery;

            if ($id) {
                $tour = q('SELECT * FROM tours WHERE id=? AND seller_id=?', [$id, $seller['id']])->fetch();
                must((bool)$tour, 'Không tìm thấy tour thuộc Seller này.');
                if (in_array($tour['status'], ['approved', 'selling', 'paused'], true)) {
                    must($submit, 'Bản sửa của tour đang hoạt động cần chọn "Gửi duyệt lại".');
                    $oldSummary = 'Giá cũ: ' . money((int)$tour['base_price']) . ' -> Giá mới: ' . money((int)$data['base_price']);
                    q('UPDATE tours SET pending_payload=?,rejection_reason=NULL WHERE id=?', [json_encode($data, JSON_UNESCAPED_UNICODE), $id]);
                    audit($sellerUser['id'], 'tour', $id, 'revision_submitted', 'Gửi duyệt lại bản sửa: ' . $oldSummary);
                } else {
                    must($tour['status'] !== 'pending', 'Tour đang chờ Quản trị viên duyệt.');
                    $sets = implode(',', array_map(fn($f) => "$f=?", array_keys($data)));
                    q("UPDATE tours SET $sets,status=?,rejection_reason=NULL WHERE id=?", [...array_values($data), $submit ? 'pending' : 'draft', $id]);
                    audit($sellerUser['id'], 'tour', $id, $submit ? 'submitted' : 'saved_draft', $submit ? 'Gửi duyệt tour #' . $id : 'Lưu nháp tour #' . $id);
                }
            } else {
                $columns = implode(',', array_keys($data));
                $marks = implode(',', array_fill(0, count($data), '?'));
                q("INSERT INTO tours(seller_id,$columns,status) VALUES (?,$marks,?)", [$seller['id'], ...array_values($data), $submit ? 'pending' : 'draft']);
                $id = (int)db()->lastInsertId();
                $code = 'TOUR-' . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
                q("UPDATE tours SET code=? WHERE id=?", [$code, $id]);
                audit($sellerUser['id'], 'tour', $id, $submit ? 'submitted' : 'saved_draft', ($submit ? 'Tạo và gửi duyệt ' : 'Tạo bản nháp ') . $code);
            }

            if ($submit) {
                $admins = q("SELECT id FROM users WHERE role='admin'")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($admins as $adminId) {
                    notice((int)$adminId, 'Tour "' . $data['title'] . '" đang chờ kiểm duyệt.', url('admin'));
                }
            }
            flash($submit ? 'Đã gửi duyệt tour thành công! Quản trị viên đã nhận thông báo.' : 'Đã lưu nháp tour thành công.');
            go('seller_tours');

        case 'tour_clone':
            // CN03 Nhánh A2: Nhân bản tour
            $sellerUser = require_role('seller');
            $seller = q('SELECT * FROM sellers WHERE user_id=?', [$sellerUser['id']])->fetch();
            must($seller && $seller['status'] === 'verified', 'Seller chưa được xác minh.');
            $source = q('SELECT * FROM tours WHERE id=? AND seller_id=?', [(int)($_POST['tour_id'] ?? 0), $seller['id']])->fetch();
            must((bool)$source, 'Tour nguồn không tồn tại.');
            $fields = ['title', 'destination', 'origin', 'days', 'nights', 'description', 'itinerary', 'included', 'excluded', 'pickup', 'transport', 'accommodation', 'required_documents', 'cancellation_policy', 'is_international', 'base_price', 'surcharge_per_person', 'surcharge_label', 'image_path', 'gallery_json'];
            $columns = implode(',', $fields);
            $marks = implode(',', array_fill(0, count($fields), '?'));
            $vals = array_map(fn($key) => $key === 'title' ? $source['title'] . ' (Bản sao)' : $source[$key], $fields);
            q("INSERT INTO tours(seller_id,$columns,status) VALUES (?,$marks,'draft')", [$seller['id'], ...$vals]);
            $id = (int)db()->lastInsertId();
            $code = 'TOUR-' . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
            q("UPDATE tours SET code=? WHERE id=?", [$code, $id]);
            audit($sellerUser['id'], 'tour', $id, 'cloned', 'Nhân bản từ tour #' . $source['id'] . ' (' . $source['code'] . ')');
            flash('Đã nhân bản tour thành bản nháp độc lập (' . $code . '), không sao chép lịch khởi hành và booking.');
            go('seller_tour_form', ['id' => $id]);

        case 'tour_review':
            // CN04 / UC24: Quản trị viên kiểm duyệt tour mới và bản sửa
            $admin = require_role('admin');
            $id = (int)($_POST['tour_id'] ?? 0);
            $decision = (string)($_POST['decision'] ?? '');
            must(in_array($decision, ['approved', 'rejected'], true), 'Quyết định không hợp lệ.');
            $reason = trim((string)($_POST['reason'] ?? ''));
            if ($decision === 'rejected' && $reason === '') {
                set_field_errors(['tour_reason_' . $id => 'Bắt buộc nhập lý do từ chối tour.']);
                flash('Vui lòng nhập lý do khi từ chối tour.', 'error');
                go('admin');
            }
            db()->beginTransaction();
            try {
                $tour = q('SELECT * FROM tours WHERE id=? FOR UPDATE', [$id])->fetch();
                must($tour && ($tour['status'] === 'pending' || $tour['pending_payload'] !== null), 'Tour không ở trạng thái chờ duyệt.');
                $detailLog = $reason;

                if ($tour['pending_payload'] !== null) {
                    if ($decision === 'approved') {
                        $data = json_decode($tour['pending_payload'], true, 512, JSON_THROW_ON_ERROR);
                        $detailLog = 'Duyệt bản sửa: Giá ' . money((int)$tour['base_price']) . ' -> ' . money((int)$data['base_price']);
                        $sets = implode(',', array_map(fn($f) => "$f=?", array_keys($data)));
                        q("UPDATE tours SET $sets,pending_payload=NULL,rejection_reason=NULL WHERE id=?", [...array_values($data), $id]);
                        if ((int)$data['base_price'] !== (int)$tour['base_price']) {
                            q('UPDATE departures SET price_per_person=? WHERE tour_id=? AND start_date > CURDATE()', [(int)$data['base_price'], $id]);
                        }
                    } else {
                        $detailLog = 'Từ chối bản sửa: ' . $reason;
                        q('UPDATE tours SET pending_payload=NULL,rejection_reason=? WHERE id=?', [$reason, $id]);
                    }
                } else {
                    if ($decision === 'approved') {
                        q("UPDATE tours SET status='approved',rejection_reason=NULL WHERE id=?", [$id]);
                        // Nếu tour mới được duyệt chưa có lịch khởi hành nào, tự động tạo lịch khởi hành mở bán mặc định
                        // để Seller hiện ngay "Đang bán" và Customer thấy ngay tour đã duyệt (NV2-CN4)
                        $depCount = (int)q("SELECT COUNT(*) FROM departures WHERE tour_id=?", [$id])->fetchColumn();
                        if ($depCount === 0) {
                            $start = (new DateTimeImmutable('today'))->modify('+14 days');
                            $end = $start->modify('+' . max(0, (int)$tour['days'] - 1) . ' days');
                            $cutoff = $start->modify('-1 day')->format('Y-m-d') . ' 00:00:00';
                            q("INSERT INTO departures(tour_id,start_date,end_date,capacity,price_per_person,cutoff_at,meeting_time,status) VALUES (?,?,?,?,?,?,?,'open')", [
                                $id, $start->format('Y-m-d'), $end->format('Y-m-d'), 20, (int)$tour['base_price'], $cutoff, '07:00 tại điểm đón',
                            ]);
                            $depId = (int)db()->lastInsertId();
                            q("UPDATE departures SET code=? WHERE id=?", ['DEP-' . str_pad((string)$depId, 3, '0', STR_PAD_LEFT), $depId]);
                        }
                        $detailLog = 'Duyệt tour mới -> Mở bán';
                    } else {
                        q("UPDATE tours SET status='rejected',rejection_reason=? WHERE id=?", [$reason, $id]);
                        $detailLog = 'Từ chối tour: ' . $reason;
                    }
                }

                sync_departure_and_tour($id);
                $updatedTour = q('SELECT status, title, seller_id FROM tours WHERE id=?', [$id])->fetch();
                audit($admin['id'], 'tour', $id, $decision, $detailLog);
                $owner = q('SELECT user_id FROM sellers WHERE id=?', [$tour['seller_id']])->fetch();
                notice((int)$owner['user_id'], $decision === 'approved'
                    ? 'Tour "' . $updatedTour['title'] . '" đã được Quản trị viên duyệt và chuyển sang trạng thái "' . ($updatedTour['status'] === 'selling' ? 'Đang bán' : 'Đã duyệt') . '".'
                    : 'Tour "' . $updatedTour['title'] . '" bị từ chối: ' . $reason, url('seller_tours'));
                db()->commit();
            } catch (Throwable $e) {
                db()->rollBack();
                throw $e;
            }
            flash($decision === 'approved'
                ? 'Đã duyệt tour thành công! Tour đã chuyển sang trạng thái "Đang bán" và hiển thị trên trang Khách hàng.'
                : 'Đã từ chối tour và gửi lý do cho Seller.');
            go('admin');

        case 'tour_pause':
            // CN03 Nhánh A5: Tạm ngừng / Mở bán lại tour
            $seller = require_role('seller');
            $id = (int)($_POST['tour_id'] ?? 0);
            $tour = q('SELECT t.* FROM tours t JOIN sellers s ON s.id=t.seller_id WHERE t.id=? AND s.user_id=?', [$id, $seller['id']])->fetch();
            must($tour && in_array($tour['status'], ['approved', 'selling', 'paused'], true), 'Tour không thể đổi trạng thái.');
            if ($tour['status'] === 'paused') {
                // Mở bán lại: chuyển sang Đang bán nếu còn lịch Mở bán, nếu không chuyển Đã duyệt (A5)
                q("UPDATE tours SET status='approved' WHERE id=?", [$id]);
                sync_departure_and_tour($id);
                $newSt = (string)q("SELECT status FROM tours WHERE id=?", [$id])->fetchColumn();
                audit($seller['id'], 'tour', $id, 'resumed_' . $newSt, 'Mở bán lại tour -> ' . ($newSt === 'selling' ? 'Đang bán' : 'Đã duyệt'));
                flash('Đã mở bán lại tour. Trạng thái hiện tại: ' . ($newSt === 'selling' ? 'Đang bán' : 'Đã duyệt (chưa có lịch mở bán)') . '.');
            } else {
                q("UPDATE tours SET status='paused' WHERE id=?", [$id]);
                audit($seller['id'], 'tour', $id, 'paused', 'Tạm ngừng nhận booking mới');
                flash('Đã chuyển tour sang trạng thái "Tạm ngừng" (ngừng nhận booking mới, giữ nguyên booking đã có).');
            }
            go('seller_tours');

        case 'departure_save':
            // AD-CN06 Bước 3–9: Thêm lịch khởi hành mới
            $seller = require_role('seller');
            $tourId = (int)($_POST['tour_id'] ?? 0);
            $tour = q('SELECT t.* FROM tours t JOIN sellers s ON s.id=t.seller_id WHERE t.id=? AND s.user_id=?', [$tourId, $seller['id']])->fetch();
            must($tour && in_array($tour['status'], ['approved', 'selling'], true), 'Chỉ tour ở trạng thái "Đã duyệt" hoặc "Đang bán" mới thêm được lịch khởi hành.');

            $date = trim((string)($_POST['start_date'] ?? ''));
            $capacity = (int)($_POST['capacity'] ?? 0);
            $price = (int)($_POST['price_per_person'] ?? 0);
            $cutoffInput = trim((string)($_POST['cutoff_date'] ?? ''));
            $meetingTime = trim((string)($_POST['meeting_time'] ?? ''));

            $errors = [];
            $start = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (!$start || $start->format('Y-m-d') !== $date || $start <= new DateTimeImmutable('today')) {
                $errors['start_date'] = 'Ngày khởi hành phải sau ngày hiện tại.';
            }
            if ($capacity < 1 || $capacity > 999) {
                $errors['capacity'] = 'Tổng số chỗ dành cho Sàn phải là số nguyên lớn hơn 0 (1–999).';
            }
            if ($price <= 0) {
                $errors['price_per_person'] = 'Giá/người là trường bắt buộc và phải lớn hơn 0.';
            }
            if (empty($_POST['quota_agreement'])) {
                $errors['quota_agreement'] = 'Bắt buộc xác nhận cam kết quỹ chỗ chỉ bán qua Sàn.';
            }

            $defaultCutoff = $start ? $start->modify('-1 day')->format('Y-m-d') . ' 00:00:00' : '';
            $cutoffAt = $defaultCutoff;
            if ($cutoffInput !== '' && $start) {
                $cDate = DateTimeImmutable::createFromFormat('!Y-m-d', $cutoffInput);
                if (!$cDate || $cDate->format('Y-m-d') !== $cutoffInput) {
                    $errors['cutoff_date'] = 'Định dạng ngày hạn chốt bán không hợp lệ.';
                } else {
                    $cutoffAt = $cDate->format('Y-m-d') . ' 00:00:00';
                    if (strtotime($cutoffAt) <= time()) {
                        $errors['cutoff_date'] = 'Hạn chốt bán phải sau thời điểm hiện tại.';
                    } elseif (strtotime($cutoffAt) > strtotime($defaultCutoff)) {
                        $errors['cutoff_date'] = 'Hạn chốt bán không được muộn hơn 00:00 ngày liền trước ngày khởi hành (' . day($defaultCutoff) . ').';
                    }
                }
            } elseif ($defaultCutoff !== '' && strtotime($defaultCutoff) <= time()) {
                $errors['start_date'] = 'Ngày khởi hành quá sát, đã qua hạn chốt bán mặc định.';
            }

            if ($errors) {
                set_field_errors($errors, $_POST);
                flash('Dữ liệu lịch khởi hành không hợp lệ. Vui lòng kiểm tra các trường được đánh dấu.', 'error');
                go('seller_departures', ['tour_id' => $tourId]);
            }

            $end = $start->modify('+' . max(0, (int)$tour['days'] - 1) . ' days');
            q("INSERT INTO departures(tour_id,start_date,end_date,capacity,price_per_person,cutoff_at,meeting_time,status) VALUES (?,?,?,?,?,?,?,'open')", [
                $tourId, $date, $end->format('Y-m-d'), $capacity, $price, $cutoffAt, $meetingTime,
            ]);
            $depId = (int)db()->lastInsertId();
            $depCode = 'DEP-' . str_pad((string)$depId, 3, '0', STR_PAD_LEFT);
            q("UPDATE departures SET code=? WHERE id=?", [$depCode, $depId]);

            // Bước 8: Tour tự động chuyển sang "Đang bán"
            sync_departure_and_tour($tourId);
            audit($seller['id'], 'departure', $depId, 'created', 'Thêm lịch ' . $depCode . ' (' . day($date) . '), quỹ chỗ: ' . $capacity . ', giá: ' . money($price));
            flash('Đã thêm lịch khởi hành ' . $depCode . ' ở trạng thái "Mở bán" (' . $capacity . ' chỗ); trạng thái Tour đã đồng bộ sang "Đang bán".');
            go('seller_departures', ['tour_id' => $tourId]);

        case 'departure_update':
            // AD-CN06 Nhánh A1: Sửa lịch khởi hành (khóa ngày đi, kiểm tra tổng số chỗ >= đang giữ + đã bán)
            $seller = require_role('seller');
            $depId = (int)($_POST['departure_id'] ?? 0);
            $d = q("SELECT d.*, t.days, t.id AS tour_id,
                    COALESCE((SELECT SUM(b.people) FROM bookings b WHERE b.departure_id=d.id AND b.status IN ('pending_seller','confirmed')),0) AS held_seats,
                    COALESCE((SELECT SUM(b.people) FROM bookings b WHERE b.departure_id=d.id AND b.status IN ('deposited','paid','completed')),0) AS sold_seats
                    FROM departures d JOIN tours t ON t.id=d.tour_id JOIN sellers s ON s.id=t.seller_id
                    WHERE d.id=? AND s.user_id=?", [$depId, $seller['id']])->fetch();
            must((bool)$d, 'Không tìm thấy lịch khởi hành cần sửa.');

            $tourId = (int)$d['tour_id'];
            $minSeats = (int)$d['held_seats'] + (int)$d['sold_seats'];
            $capacity = (int)($_POST['capacity'] ?? 0);
            $price = (int)($_POST['price_per_person'] ?? 0);
            $cutoffInput = trim((string)($_POST['cutoff_date'] ?? ''));
            $meetingTime = trim((string)($_POST['meeting_time'] ?? ''));

            $errors = [];
            if ($capacity < 1) {
                $errors['edit_capacity'] = 'Tổng số chỗ phải lớn hơn 0.';
            } elseif ($capacity < $minSeats) {
                $errors['edit_capacity'] = 'Tổng số chỗ không được nhỏ hơn số chỗ đang giữ (' . $d['held_seats'] . ') + đã bán (' . $d['sold_seats'] . '). Mức tối thiểu là ' . $minSeats . ' chỗ.';
            }
            if ($price <= 0) {
                $errors['edit_price'] = 'Giá/người phải lớn hơn 0.';
            }

            $startObj = new DateTimeImmutable($d['start_date']);
            $defaultCutoff = $startObj->modify('-1 day')->format('Y-m-d') . ' 00:00:00';
            $cutoffAt = $d['cutoff_at'];
            if ($cutoffInput !== '') {
                $cDate = DateTimeImmutable::createFromFormat('!Y-m-d', $cutoffInput);
                if (!$cDate || $cDate->format('Y-m-d') !== $cutoffInput) {
                    $errors['edit_cutoff'] = 'Ngày hạn chốt bán không hợp lệ.';
                } else {
                    $cutoffAt = $cDate->format('Y-m-d') . ' 00:00:00';
                    if (strtotime($cutoffAt) > strtotime($defaultCutoff)) {
                        $errors['edit_cutoff'] = 'Hạn chốt bán không được muộn hơn ' . day($defaultCutoff) . ' 00:00.';
                    }
                }
            }

            if ($errors) {
                set_field_errors($errors, $_POST);
                flash('Không thể lưu chỉnh sửa lịch khởi hành. Vui lòng kiểm tra các trường được đánh dấu.', 'error');
                go('seller_departures', ['tour_id' => $tourId, 'edit_id' => $depId]);
            }

            $beforeDetail = 'Chỗ: ' . $d['capacity'] . ' -> ' . $capacity . '; Giá: ' . money((int)$d['price_per_person']) . ' -> ' . money($price) . '; Giờ tập trung: ' . ($d['meeting_time'] ?: '—') . ' -> ' . ($meetingTime ?: '—');
            q("UPDATE departures SET capacity=?, price_per_person=?, cutoff_at=?, meeting_time=? WHERE id=?", [
                $capacity, $price, $cutoffAt, $meetingTime, $depId
            ]);
            // A1.2: Tính lại chỗ còn lại, cập nhật trạng thái lịch (Mở bán <-> Hết chỗ) & trạng thái tour (Đang bán <-> Đã duyệt)
            sync_departure_and_tour($tourId);
            audit($seller['id'], 'departure', $depId, 'updated', 'Cập nhật lịch ' . $d['code'] . ': ' . $beforeDetail);
            flash('Đã cập nhật lịch khởi hành ' . $d['code'] . ', tính lại chỗ còn lại và đồng bộ trạng thái lịch & tour.');
            go('seller_departures', ['tour_id' => $tourId]);

        case 'departure_toggle':
            // AD-CN06 Nhánh A2: Đóng bán / Mở bán lại lịch khởi hành
            $seller = require_role('seller');
            $id = (int)($_POST['departure_id'] ?? 0);
            $d = q('SELECT d.*, t.id AS tour_id FROM departures d JOIN tours t ON t.id=d.tour_id JOIN sellers s ON s.id=t.seller_id WHERE d.id=? AND s.user_id=?', [$id, $seller['id']])->fetch();
            must((bool)$d && $d['status'] !== 'cancelled', 'Không tìm thấy lịch khởi hành hợp lệ.');
            $tourId = (int)$d['tour_id'];

            // A2.1: Kiểm tra lịch còn trước hạn chốt bán
            if (strtotime($d['cutoff_at']) <= time()) {
                flash('Lịch khởi hành ' . $d['code'] . ' đã qua hạn chốt bán (' . dt($d['cutoff_at']) . '), không thể đóng/mở bán lại.', 'error');
                go('seller_departures', ['tour_id' => $tourId]);
            }

            // A2.2: Chuyển "Đóng bán" / "Mở bán" ("Hết chỗ"), cập nhật trạng thái tour
            if (in_array($d['status'], ['open', 'full'], true)) {
                q("UPDATE departures SET status='closed' WHERE id=?", [$id]);
                sync_departure_and_tour($tourId);
                $tourSt = (string)q("SELECT status FROM tours WHERE id=?", [$tourId])->fetchColumn();
                audit($seller['id'], 'departure', $id, 'closed', 'Chủ động đóng bán lịch ' . $d['code'] . '; Tour -> ' . $tourSt);
                flash('Đã chuyển lịch ' . $d['code'] . ' sang "Đóng bán". Trạng thái Tour hiện tại: ' . ($tourSt === 'selling' ? 'Đang bán' : 'Đã duyệt') . '.');
            } else {
                q("UPDATE departures SET status='open' WHERE id=?", [$id]);
                sync_departure_and_tour($tourId);
                $newDepSt = (string)q("SELECT status FROM departures WHERE id=?", [$id])->fetchColumn();
                $tourSt = (string)q("SELECT status FROM tours WHERE id=?", [$tourId])->fetchColumn();
                audit($seller['id'], 'departure', $id, 'reopened_' . $newDepSt, 'Mở bán lại lịch ' . $d['code'] . ' -> ' . $newDepSt . '; Tour -> ' . $tourSt);
                flash('Đã mở bán lại lịch ' . $d['code'] . ' (trạng thái lịch: ' . ($newDepSt === 'full' ? 'Hết chỗ' : 'Mở bán') . '; trạng thái Tour: ' . ($tourSt === 'selling' ? 'Đang bán' : 'Đã duyệt') . ').');
            }
            go('seller_departures', ['tour_id' => $tourId]);

        case 'booking_step1':
            // AD-CN09 Bước 3–5: Trang nhập thông tin đặt tour - nút "Tiếp tục"
            $customer = require_role('customer');
            $tourId = (int)($_POST['tour_id'] ?? 0);
            $depId = (int)($_POST['departure_id'] ?? 0);
            $people = (int)($_POST['people'] ?? 0);
            $contact = trim((string)($_POST['contact_name'] ?? ''));
            $phone = trim((string)($_POST['contact_phone'] ?? ''));
            $email = trim((string)($_POST['contact_email'] ?? ''));
            $note = trim((string)($_POST['note'] ?? ''));

            $errors = [];
            if ($depId <= 0) $errors['departure_id'] = 'Vui lòng chọn Lịch khởi hành đang mở bán.';
            if ($people < 1 || $people > 50) $errors['people'] = 'Số lượng khách phải từ 1 người trở lên.';
            if ($contact === '') $errors['contact_name'] = 'Vui lòng nhập họ và tên người liên hệ.';
            if (!preg_match('/^0[0-9]{9,10}$/', $phone)) $errors['contact_phone'] = 'Vui lòng nhập số điện thoại hợp lệ (10–11 chữ số, bắt đầu bằng 0).';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['contact_email'] = 'Vui lòng nhập địa chỉ email liên hệ hợp lệ.';

            if ($depId > 0) {
                $deps = departure_rows($tourId);
                $selectedDep = null;
                foreach ($deps as $row) {
                    if ((int)$row['id'] === $depId) { $selectedDep = $row; break; }
                }
                if (!$selectedDep || $selectedDep['status'] !== 'open' || strtotime($selectedDep['cutoff_at']) <= time()) {
                    $errors['departure_id'] = 'Lịch khởi hành đã chọn hiện không còn mở bán. Vui lòng chọn lịch khác.';
                } elseif ($people > (int)$selectedDep['remaining']) {
                    $errors['people'] = 'Lịch này chỉ còn ' . $selectedDep['remaining'] . ' chỗ trống. Vui lòng giảm số người hoặc chọn lịch khác.';
                }
            }

            if ($errors) {
                set_field_errors($errors, $_POST);
                flash('Vui lòng kiểm tra và bổ sung đầy đủ thông tin đặt tour trước khi tiếp tục.', 'error');
                go('checkout', ['tour_id' => $tourId, 'departure_id' => $depId, 'people' => max(1, $people)]);
            }

            $_SESSION['checkout_draft'] = [
                'tour_id' => $tourId,
                'departure_id' => $depId,
                'people' => $people,
                'contact_name' => $contact,
                'contact_phone' => $phone,
                'contact_email' => $email,
                'note' => $note,
            ];
            go('checkout', ['tour_id' => $tourId, 'departure_id' => $depId, 'people' => $people, 'step' => 'summary']);

        case 'booking_create':
            // AD-CN09 Bước 6–11: Xác nhận đặt tour và giữ chỗ tuần tự
            $customer = require_role('customer');
            $depId = (int)($_POST['departure_id'] ?? 0);
            $people = (int)($_POST['people'] ?? 0);
            $contact = trim((string)($_POST['contact_name'] ?? ''));
            $phone = trim((string)($_POST['contact_phone'] ?? ''));
            $email = trim((string)($_POST['contact_email'] ?? ''));
            $note = trim((string)($_POST['note'] ?? ''));

            if (empty($_POST['accept_policy'])) {
                set_field_errors(['accept_policy' => 'Bạn cần đánh dấu đã đọc và đồng ý chính sách hủy/đổi trước khi xác nhận đặt.']);
                flash('Vui lòng đánh dấu đồng ý chính sách hủy/đổi.', 'error');
                $tourId = (int)($_POST['tour_id'] ?? 0);
                go('checkout', ['tour_id' => $tourId, 'departure_id' => $depId, 'people' => $people, 'step' => 'summary']);
            }

            db()->beginTransaction();
            try {
                $d = q('SELECT * FROM departures WHERE id=? FOR UPDATE', [$depId])->fetch();
                must((bool)$d, 'Không tìm thấy lịch khởi hành.');
                $tourId = (int)$d['tour_id'];
                $tour = tour_with_seller($tourId);
                must($tour && $tour['status'] === 'selling' && $d['status'] === 'open' && strtotime($d['cutoff_at']) > time(), 'Tour hoặc lịch khởi hành hiện không còn mở bán.');

                // Bước 8: Kiểm tra booking "Chờ xác nhận" trùng trên cùng lịch khởi hành -> Nhánh E1
                $existingId = (int)q("SELECT id FROM bookings WHERE customer_id=? AND departure_id=? AND status='pending_seller' LIMIT 1", [$customer['id'], $depId])->fetchColumn();
                if ($existingId > 0) {
                    db()->commit();
                    unset($_SESSION['checkout_draft']);
                    flash('Bạn đã có một booking đang ở trạng thái "Chờ xác nhận" trên lịch khởi hành này. Hệ thống đã mở lại booking đó.', 'error');
                    go('booking', ['id' => $existingId]);
                }

                // Bước 9: Kiểm tra lại số chỗ (xử lý tuần tự) -> Nhánh E2 nếu không đủ chỗ
                $occupied = (int)q("SELECT COALESCE(SUM(people),0) FROM bookings WHERE departure_id=? AND status IN ('pending_seller','confirmed','deposited','paid','completed')", [$depId])->fetchColumn();
                $remaining = (int)$d['capacity'] - $occupied;
                if ($remaining < $people) {
                    db()->commit();
                    flash('Rất tiếc, lịch khởi hành này chỉ còn ' . max(0, $remaining) . ' chỗ (không đủ cho ' . $people . ' người). Vui lòng xem gợi ý các lịch khởi hành khác còn chỗ bên dưới!', 'error');
                    go('checkout', ['tour_id' => $tourId, 'departure_id' => $depId, 'people' => $people, 'insufficient' => 1]);
                }

                $now = new DateTimeImmutable();
                $sellerDeadlineTs = min($now->modify('+24 hours')->getTimestamp(), strtotime($d['cutoff_at']));
                must($sellerDeadlineTs > time(), 'Đã hết thời gian nhận booking cho lịch này.');

                $base = (int)$d['price_per_person'];
                $surcharge = (int)$tour['surcharge_per_person'];
                $total = ($base + $surcharge) * $people;
                $code = 'VV' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
                $balance = balance_deadline($d['start_date'], (bool)$tour['is_international']);

                q('INSERT INTO bookings(code,departure_id,customer_id,people,contact_name,contact_phone,contact_email,note,base_unit,surcharge_unit,surcharge_label,total,policy_snapshot,passenger_status,status,seller_deadline,balance_deadline) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                    $code, $depId, $customer['id'], $people, $contact, $phone, $email, $note,
                    $base, $surcharge, $tour['surcharge_label'], $total, $tour['cancellation_policy'],
                    'incomplete', 'pending_seller', date('Y-m-d H:i:s', $sellerDeadlineTs), $balance
                ]);
                $id = (int)db()->lastInsertId();

                // Tạm trừ chỗ ngay và đồng bộ trạng thái lịch / tour
                sync_departure_and_tour($tourId);

                audit($customer['id'], 'booking', $id, 'created', 'Tạo booking ' . $code . ' (Chờ xác nhận), giữ ' . $people . ' chỗ, tổng ' . money($total));
                $seller = q('SELECT user_id FROM sellers WHERE id=?', [$tour['seller_id']])->fetch();
                // Bước 10 & 11: Thông báo cho khách và Seller
                notice((int)$customer['id'], 'Yêu cầu đặt tour ' . $code . ' đã được ghi nhận (Chờ xác nhận). Seller sẽ phản hồi trước ' . date('d/m/Y H:i', $sellerDeadlineTs) . '.', url('booking', ['id' => $id]));
                notice((int)$seller['user_id'], 'Booking mới ' . $code . ' (' . $people . ' khách) cần xác nhận trước ' . date('d/m/Y H:i', $sellerDeadlineTs) . '.', url('seller_bookings'));
                db()->commit();
            } catch (Throwable $e) {
                db()->rollBack();
                throw $e;
            }
            unset($_SESSION['checkout_draft']);
            flash('Đặt tour thành công! Mã booking ' . $code . ' đang ở trạng thái "Chờ xác nhận" (đã tạm giữ ' . $people . ' chỗ). Vui lòng nhập danh sách hành khách bên dưới.');
            go('booking', ['id' => $id]);

        case 'passengers_save':
        case 'passengers_upload':
            // AD-CN10: Nhập danh sách hành khách (Hỗ trợ cả Nhập tay & Tải tệp Excel/CSV theo mẫu, Lưu Hoàn tất & Lưu tạm Chưa hoàn tất A2, thông báo Seller Bước 10)
            $customer = require_role('customer');
            $id = (int)($_POST['booking_id'] ?? 0);
            $b = booking_for($id);
            must($b && (int)$b['customer_id'] === (int)$customer['id'], 'Không tìm thấy booking thuộc tài khoản của bạn.');
            must(in_array($b['status'], ['pending_seller', 'confirmed', 'deposited'], true), 'Chỉ được cập nhật danh sách hành khách trước khi hoàn tất thanh toán 100%.');

            $rawRows = [];
            if ($action === 'passengers_upload') {
                // Nhánh A1.1 -> A1.2: Kiểm tra tệp Excel/CSV đúng mẫu
                $file = $_FILES['passenger_file'] ?? null;
                if (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] <= 0) {
                    flash('Vui lòng chọn tệp Excel/CSV danh sách hành khách để tải lên.', 'error');
                    go('booking', ['id' => $id, 'tab' => 'excel']);
                }
                $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['csv', 'txt'], true)) {
                    flash('Tệp tải lên không đúng định dạng. Vui lòng sử dụng tệp CSV.', 'error');
                    go('booking', ['id' => $id, 'tab' => 'excel']);
                }
                $handle = fopen($file['tmp_name'], 'r');
                must((bool)$handle, 'Không đọc được tệp tải lên.');
                $header = fgetcsv($handle);
                if (!$header || count($header) < 4) {
                    fclose($handle);
                    flash('Tệp không đúng cấu trúc cột (cần tối thiểu các cột: Họ tên, Giới tính, Ngày sinh, Số điện thoại).', 'error');
                    go('booking', ['id' => $id, 'tab' => 'excel']);
                }
                while (($line = fgetcsv($handle)) !== false) {
                    if (count(array_filter($line, fn($x) => trim((string)$x) !== '')) === 0) continue;
                    $rawRows[] = [
                        'full_name' => trim((string)($line[0] ?? '')),
                        'gender' => strtolower(trim((string)($line[1] ?? 'other'))),
                        'dob' => trim((string)($line[2] ?? '')),
                        'phone' => trim((string)($line[3] ?? '')),
                        'note' => trim((string)($line[4] ?? '')),
                        'passport' => trim((string)($line[5] ?? '')),
                        'passport_expiry' => trim((string)($line[6] ?? '')),
                        'nationality' => trim((string)($line[7] ?? '')),
                    ];
                }
                fclose($handle);
                if (!$rawRows) {
                    flash('Tệp tải lên không có dòng dữ liệu hành khách nào.', 'error');
                    go('booking', ['id' => $id, 'tab' => 'excel']);
                }
            } else {
                // Nhập tay
                $names = $_POST['passenger_name'] ?? [];
                if (!is_array($names)) $names = [];
                foreach ($names as $i => $nm) {
                    $nm = trim((string)$nm);
                    $g = trim((string)($_POST['gender'][$i] ?? 'female'));
                    $dob = trim((string)($_POST['dob'][$i] ?? ''));
                    $ph = trim((string)($_POST['passenger_phone'][$i] ?? ''));
                    $nt = trim((string)($_POST['passenger_note'][$i] ?? ''));
                    $pp = trim((string)($_POST['passport'][$i] ?? ''));
                    $pe = trim((string)($_POST['passport_expiry'][$i] ?? ''));
                    $nat = trim((string)($_POST['nationality'][$i] ?? ''));
                    // Bỏ qua dòng hoàn toàn trống nếu khách chỉ mới nhập một phần (để hỗ trợ A2 Lưu tạm)
                    if ($nm === '' && $dob === '' && $ph === '' && $pp === '') continue;
                    $rawRows[$i] = [
                        'full_name' => $nm, 'gender' => $g, 'dob' => $dob, 'phone' => $ph,
                        'note' => $nt, 'passport' => $pp, 'passport_expiry' => $pe, 'nationality' => $nat,
                    ];
                }
            }

            if (count($rawRows) === 0) {
                set_field_errors(['passengers_general' => 'Vui lòng nhập ít nhất 1 hành khách trước khi bấm Lưu danh sách.']);
                flash('Chưa có dữ liệu hành khách nào được nhập.', 'error');
                go('booking', ['id' => $id]);
            }

            if (count($rawRows) > (int)$b['people']) {
                set_field_errors(['passengers_general' => 'Số dòng hành khách (' . count($rawRows) . ') vượt quá số người đã đặt của booking (' . $b['people'] . ' người).']);
                flash('Số lượng hành khách vượt quá số chỗ của booking.', 'error');
                go('booking', ['id' => $id]);
            }

            // Bước 6: Kiểm tra từng dòng, từng cột bắt buộc -> E2: Xem dòng, cột lỗi
            $rowErrors = [];
            $validItems = [];
            $rowNum = 0;
            foreach ($rawRows as $idx => $r) {
                $rowNum++;
                $displayRow = is_int($idx) ? ($idx + 1) : $rowNum;
                $genderMap = ['nam' => 'male', 'nu' => 'female', 'nữ' => 'female', 'khac' => 'other', 'khác' => 'other', 'male' => 'male', 'female' => 'female', 'other' => 'other'];
                $g = $genderMap[$r['gender']] ?? '';

                if ($r['full_name'] === '') {
                    $rowErrors["p_{$idx}_name"] = "Dòng $displayRow – Cột Họ tên: Không được để trống.";
                }
                if ($g === '') {
                    $rowErrors["p_{$idx}_gender"] = "Dòng $displayRow – Cột Giới tính: Chọn Nam, Nữ hoặc Khác.";
                }
                if ($r['dob'] === '' || !strtotime($r['dob']) || strtotime($r['dob']) > time()) {
                    $rowErrors["p_{$idx}_dob"] = "Dòng $displayRow – Cột Ngày sinh: Ngày sinh không hợp lệ.";
                }
                if (!preg_match('/^0[0-9]{9,10}$/', $r['phone'])) {
                    $rowErrors["p_{$idx}_phone"] = "Dòng $displayRow – Cột Số điện thoại: Phải gồm 10–11 chữ số bắt đầu bằng 0.";
                }
                if ((int)$b['is_international'] === 1) {
                    if ($r['passport'] === '') {
                        $rowErrors["p_{$idx}_passport"] = "Dòng $displayRow – Cột Số hộ chiếu: Bắt buộc với tour quốc tế.";
                    }
                    if ($r['nationality'] === '') {
                        $rowErrors["p_{$idx}_nationality"] = "Dòng $displayRow – Cột Quốc tịch: Quốc tịch bắt buộc với tour nước ngoài.";
                    }
                    $minExpiry = strtotime($b['end_date'] . ' +6 months');
                    if ($r['passport_expiry'] === '' || !strtotime($r['passport_expiry']) || strtotime($r['passport_expiry']) < $minExpiry) {
                        $rowErrors["p_{$idx}_expiry"] = "Dòng $displayRow – Cột Hạn hộ chiếu: Hộ chiếu phải còn hạn ít nhất 6 tháng tính từ ngày về (" . day(date('Y-m-d', $minExpiry)) . ").";
                    }
                }

                $validItems[] = [
                    $r['full_name'], $g ?: 'other', $r['dob'], $r['phone'],
                    $r['note'] !== '' ? $r['note'] : null,
                    $r['passport'] !== '' ? $r['passport'] : null,
                    $r['passport_expiry'] !== '' ? $r['passport_expiry'] : null,
                    $r['nationality'] !== '' ? $r['nationality'] : null,
                ];
            }

            if ($rowErrors) {
                $_SESSION['passenger_draft_' . $id] = $rawRows;
                set_field_errors($rowErrors);
                flash('Phát hiện ' . count($rowErrors) . ' lỗi dữ liệu tại các dòng/cột hành khách. Vui lòng xem chi tiết dòng/cột lỗi bên dưới và sửa lại!', 'error');
                go('booking', ['id' => $id]);
            }

            unset($_SESSION['passenger_draft_' . $id]);
            // Bước 8: Kiểm tra số hành khách == số người
            $isComplete = count($validItems) === (int)$b['people'];
            $pStatus = $isComplete ? 'completed' : 'incomplete';

            db()->beginTransaction();
            try {
                q('DELETE FROM passengers WHERE booking_id=?', [$id]);
                foreach ($validItems as $item) {
                    q('INSERT INTO passengers(booking_id,full_name,gender,date_of_birth,phone,note,passport_number,passport_expiry,nationality) VALUES (?,?,?,?,?,?,?,?,?)', [
                        $id, ...$item
                    ]);
                }
                q('UPDATE bookings SET passenger_status=? WHERE id=?', [$pStatus, $id]);

                // Bước 10: Ghi nhật ký & Thông báo cho Seller đã cập nhật
                $logMsg = $isComplete
                    ? 'Đã lưu đầy đủ ' . count($validItems) . '/' . $b['people'] . ' hành khách'
                    : 'Đã lưu tạm ' . count($validItems) . '/' . $b['people'] . ' hành khách';
                audit($customer['id'], 'booking', $id, 'passengers_saved', $logMsg);

                notice((int)$b['seller_user_id'], 'Khách hàng vừa cập nhật danh sách hành khách cho Booking ' . $b['code'] . ' (' . count($validItems) . '/' . $b['people'] . ' khách – ' . ($isComplete ? 'Hoàn tất' : 'Lưu tạm') . ').', url('seller_bookings'));
                notice((int)$customer['id'], 'Đã lưu danh sách hành khách cho Booking ' . $b['code'] . ' (' . ($isComplete ? 'Hoàn tất' : 'Chưa hoàn tất – cần bổ sung đủ trước khi thanh toán') . ').', url('booking', ['id' => $id]));
                db()->commit();
            } catch (Throwable $e) {
                db()->rollBack();
                throw $e;
            }

            if ($isComplete) {
                flash('Đã lưu danh sách hành khách ở trạng thái "Hoàn tất" (' . count($validItems) . '/' . $b['people'] . ' người) và gửi thông báo cho Seller!');
            } else {
                flash('Đã lưu tạm ' . count($validItems) . '/' . $b['people'] . ' hành khách (Trạng thái: "Chưa hoàn tất"). Vui lòng bổ sung đủ số người trước khi đặt cọc/thanh toán!');
            }
            go('booking', ['id' => $id]);

        case 'booking_decide':
            // AD-CN12: Seller xác nhận hoặc từ chối booking
            $seller = require_role('seller');
            $id = (int)($_POST['booking_id'] ?? 0);
            $decision = (string)($_POST['decision'] ?? '');
            must(in_array($decision, ['confirm', 'reject'], true), 'Quyết định không hợp lệ.');
            $reason = trim((string)($_POST['reason'] ?? ''));

            if ($decision === 'reject' && $reason === '') {
                set_field_errors(['booking_reason_' . $id => 'Bắt buộc nhập lý do từ chối booking.']);
                flash('Vui lòng nhập lý do khi từ chối booking.', 'error');
                go('seller_bookings');
            }

            db()->beginTransaction();
            try {
                $b = booking_for($id, true);
                must($b && (int)$b['seller_user_id'] === (int)$seller['id'], 'Bạn không có quyền xử lý booking này.');

                // Bước 6: Kiểm tra booking vẫn "Chờ xác nhận" và chưa quá hạn -> Nhánh E1 nếu không còn chờ
                if ($b['status'] !== 'pending_seller' || strtotime($b['seller_deadline']) <= time()) {
                    if ($b['status'] === 'pending_seller' && strtotime($b['seller_deadline']) <= time()) {
                        q("UPDATE bookings SET status='rejected', cancellation_reason='Quá 24 giờ chưa xác nhận' WHERE id=?", [$id]);
                        sync_departure_and_tour((int)$b['tour_id']);
                    }
                    db()->commit();
                    flash('Booking ' . $b['code'] . ' đã quá hạn xác nhận hoặc đã được xử lý trước đó, không thay đổi dữ liệu.', 'error');
                    go('seller_bookings');
                }

                if ($decision === 'confirm') {
                    // Bước 7: Chuyển "Đã xác nhận", tính hạn đặt cọc (24h, không quá hạn chốt bán) & cờ bắt buộc trả 100%
                    $deadlineTs = min(time() + 86400, strtotime($b['cutoff_at']));
                    must($deadlineTs > time(), 'Lịch khởi hành đã qua hạn chốt bán.');
                    $deadline = date('Y-m-d H:i:s', $deadlineTs);
                    $forced = strtotime($b['balance_deadline']) <= $deadlineTs ? 1 : 0;

                    q("UPDATE bookings SET status='confirmed',confirmed_at=NOW(),deposit_deadline=?,full_required=? WHERE id=?", [
                        $deadline, $forced, $id
                    ]);
                    audit($seller['id'], 'booking', $id, 'confirmed', 'Xác nhận booking ' . $b['code'] . '; hạn cọc: ' . dt($deadline) . '; bắt buộc 100%: ' . ($forced ? 'Có' : 'Không'));
                    // Bước 8 & 9: Thông báo khách hàng kèm hạn đặt cọc
                    notice((int)$b['customer_id'], 'Booking ' . $b['code'] . ' đã được Seller xác nhận! Bạn có thể chọn Đặt cọc 50% hoặc Thanh toán 100% trước ' . dt($deadline) . '.', url('booking', ['id' => $id]));
                } else {
                    // Nhánh A1.2: Chuyển "Bị từ chối", trả chỗ về lịch
                    q("UPDATE bookings SET status='rejected',cancellation_reason=? WHERE id=?", [$reason, $id]);
                    sync_departure_and_tour((int)$b['tour_id']);
                    audit($seller['id'], 'booking', $id, 'rejected', 'Từ chối booking ' . $b['code'] . ': ' . $reason);
                    notice((int)$b['customer_id'], 'Booking ' . $b['code'] . ' đã bị Seller từ chối. Lý do: ' . $reason . '. Số chỗ đã được trả về lịch.', url('booking', ['id' => $id]));
                }
                db()->commit();
            } catch (Throwable $e) {
                db()->rollBack();
                throw $e;
            }
            flash($decision === 'confirm'
                ? 'Đã xác nhận booking! Khách hàng đã nhận thông báo và có thể đặt cọc 50% hoặc thanh toán 100%.'
                : 'Đã từ chối booking, số chỗ đã được hoàn lại vào lịch khởi hành và khách hàng đã nhận thông báo.');
            go('seller_bookings');

        case 'demo_pay':
            // Thanh toán Đặt cọc 50% hoặc Thanh toán 100% sau khi Seller xác nhận
            $customer = require_role('customer');
            $id = (int)($_POST['booking_id'] ?? 0);
            db()->beginTransaction();
            try {
                $b = booking_for($id, true);
                must($b && (int)$b['customer_id'] === (int)$customer['id'], 'Booking không thuộc tài khoản này.');
                must(in_array($b['status'], ['confirmed', 'deposited'], true), 'Chỉ booking ở trạng thái "Đã xác nhận" hoặc "Đã đặt cọc" mới được thanh toán.');
                $count = (int)q('SELECT COUNT(*) FROM passengers WHERE booking_id=?', [$id])->fetchColumn();
                if ($count < (int)$b['people']) {
                    db()->rollBack();
                    flash('Vui lòng nhập đủ danh sách ' . $b['people'] . ' hành khách (trạng thái "Hoàn tất") trước khi tiến hành đặt cọc / thanh toán!', 'error');
                    go('booking', ['id' => $id]);
                }
                $choice = (string)($_POST['payment_choice'] ?? '');
                must(in_array($choice, ['deposit', 'full'], true), 'Vui lòng chọn hình thức Đặt cọc 50% hoặc Thanh toán 100%.');
                must($b['status'] !== 'confirmed' || strtotime($b['deposit_deadline']) > time(), 'Đã quá thời hạn đặt cọc ban đầu.');
                must($b['status'] !== 'deposited' || strtotime($b['balance_deadline']) > time(), 'Đã quá thời hạn thanh toán phần còn lại.');
                must(!($choice === 'deposit' && ((int)$b['full_required'] === 1 || $b['status'] === 'deposited')), 'Booking này yêu cầu thanh toán toàn bộ số tiền còn lại.');

                $amount = $choice === 'deposit' ? (int)ceil((int)$b['total'] / 2) : (int)$b['total'] - (int)$b['paid_amount'];
                must($amount > 0, 'Booking đã thanh toán đủ.');
                $ref = 'VNPAY-' . strtoupper(bin2hex(random_bytes(6)));
                q('INSERT INTO payments(booking_id,reference,amount,status,method) VALUES (?,?,?,?,?)', [$id, $ref, $amount, 'success', 'Cổng Thanh Toán Sàn']);
                $paid = (int)$b['paid_amount'] + $amount;
                $status = $paid >= (int)$b['total'] ? 'paid' : 'deposited';
                q('UPDATE bookings SET paid_amount=?,status=? WHERE id=?', [$paid, $status, $id]);
                sync_departure_and_tour((int)$b['tour_id']);

                audit($customer['id'], 'booking', $id, $status, 'Thanh toán ' . ($status === 'paid' ? '100%' : 'cọc 50%') . ' (' . money($amount) . ') – Mã GD: ' . $ref);
                notice((int)$b['seller_user_id'], 'Khách hàng vừa ' . ($status === 'paid' ? 'thanh toán đủ 100%' : 'đặt cọc 50%') . ' (' . money($amount) . ') cho Booking ' . $b['code'] . '.', url('seller_bookings'));
                notice((int)$customer['id'], 'Thanh toán thành công ' . money($amount) . ' cho Booking ' . $b['code'] . ' (Trạng thái: ' . ($status === 'paid' ? 'Đã thanh toán 100%' : 'Đã đặt cọc 50%') . ').', url('booking', ['id' => $id]));
                db()->commit();
            } catch (Throwable $e) {
                if (db()->inTransaction()) db()->rollBack();
                throw $e;
            }
            flash('Thanh toán thành công số tiền ' . money($amount) . '! Trạng thái booking đã cập nhật.');
            go('booking', ['id' => $id]);

        case 'review_submit':
            // Đánh giá Tour (hiển thị tại CN07 & CN08)
            $customer = require_role('customer');
            $tourId = (int)($_POST['tour_id'] ?? 0);
            $rating = (int)($_POST['rating'] ?? 5);
            $comment = trim((string)($_POST['comment'] ?? ''));
            must($tourId > 0 && $rating >= 1 && $rating <= 5 && $comment !== '', 'Vui lòng chọn số sao (1–5) và nhập nội dung nhận xét.');
            q('INSERT INTO reviews(tour_id,user_id,rating,comment) VALUES (?,?,?,?)', [$tourId, $customer['id'], $rating, $comment]);
            audit($customer['id'], 'tour', $tourId, 'reviewed', 'Đánh giá ' . $rating . ' sao: ' . $comment);
            flash('Cảm ơn bạn đã gửi đánh giá cho Tour!');
            go('tour', ['id' => $tourId]);

        case 'mark_notifications_read':
            if ($user) {
                q('UPDATE notifications SET is_read=1 WHERE user_id=?', [$user['id']]);
                flash('Đã đánh dấu đọc tất cả thông báo.');
            }
            go('notifications');

        case 'run_cron_now':
            $processed = run_automated_tasks();
            flash('Đã thực thi tác vụ tự động cho booking và lịch khởi hành (' . $processed . ' tác vụ được xử lý).');
            $back = $_SERVER['HTTP_REFERER'] ?? url('home');
            header('Location: ' . $back);
            exit;

        default:
            throw new DomainException('Thao tác không được hỗ trợ.');
    }
}
