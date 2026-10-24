(() => {
  const menu = document.querySelector('.menu-toggle');
  if (menu) {
    menu.addEventListener('click', () => {
      const open = document.body.classList.toggle('nav-open');
      menu.setAttribute('aria-expanded', String(open));
    });
  }

  // Quản lý danh sách So sánh Tour
  const STORAGE_KEY = 'vivu_compare_ids_v2';
  let ids = [];
  try {
    const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
    if (Array.isArray(saved)) ids = saved.map(String).filter(Boolean).slice(0, 3);
  } catch {
    ids = [];
  }

  // Đồng bộ từ URL nếu đang ở trang compare
  const params = new URLSearchParams(location.search);
  if (params.get('page') === 'compare' && params.get('ids')) {
    const urlIds = params.get('ids').split(',').map(s => s.trim()).filter(Boolean).slice(0, 3);
    if (urlIds.length > 0) {
      ids = urlIds;
      try { localStorage.setItem(STORAGE_KEY, JSON.stringify(ids)); } catch {}
    }
  }

  const boxes = [...document.querySelectorAll('.compare-box')];
  const tray = document.querySelector('#compare-tray');
  const countEl = document.querySelector('#compare-count');
  const navBadge = document.querySelector('#nav-compare-badge');
  const hintEl = document.querySelector('#compare-hint');

  function saveIds() {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(ids)); } catch {}
  }

  function updateCompareUI() {
    boxes.forEach(box => {
      box.checked = ids.includes(String(box.value));
    });
    if (tray) tray.hidden = ids.length === 0;
    if (countEl) countEl.textContent = String(ids.length);
    if (navBadge) navBadge.textContent = String(ids.length);
    if (hintEl) {
      hintEl.textContent = ids.length < 2
        ? 'Cần chọn ít nhất 2 Tour mới được so sánh (hiện có ' + ids.length + ' Tour)'
        : 'Đã đủ điều kiện so sánh (' + ids.length + '/3 Tour)';
    }
  }

  boxes.forEach(box => {
    box.addEventListener('change', () => {
      const val = String(box.value);
      if (box.checked) {
        if (ids.includes(val)) return;
        if (ids.length >= 3) {
          box.checked = false;
          alert('Danh sách so sánh tối đa 3 Tour. Vui lòng bỏ bớt 1 Tour cũ trước khi chọn thêm.');
          return;
        }
        ids.push(val);
      } else {
        ids = ids.filter(id => id !== val);
      }
      saveIds();
      updateCompareUI();
    });
  });

  document.querySelectorAll('[data-remove-compare]').forEach(btn => {
    btn.addEventListener('click', () => {
      const rmId = String(btn.getAttribute('data-remove-compare'));
      ids = ids.filter(id => id !== rmId);
      saveIds();
    });
  });

  document.querySelector('#compare-clear')?.addEventListener('click', () => {
    ids = [];
    saveIds();
    updateCompareUI();
  });

  document.querySelector('#compare-go')?.addEventListener('click', () => {
    if (ids.length < 2) {
      alert('Vui lòng chọn ít nhất 2 Tour để mở bảng so sánh (tối đa 3 Tour).');
      return;
    }
    location.href = `index.php?page=compare&ids=${encodeURIComponent(ids.join(','))}`;
  });

  updateCompareUI();

  document.querySelectorAll('[data-share]').forEach(link => {
    link.addEventListener('click', async event => {
      event.preventDefault();
      const targetUrl = location.href;
      try {
        await navigator.clipboard.writeText(targetUrl);
        link.textContent = '✓ Đã sao chép đường dẫn';
      } catch {
        prompt('Sao chép đường dẫn chia sẻ:', targetUrl);
      }
    });
  });

  document.querySelectorAll('[data-share-url]').forEach(link => {
    link.addEventListener('click', async event => {
      event.preventDefault();
      const rel = link.getAttribute('data-share-url') || '';
      const fullUrl = new URL(rel, location.href).href;
      try {
        await navigator.clipboard.writeText(fullUrl);
        link.textContent = '✓ Đã sao chép link';
      } catch {
        prompt('Sao chép đường dẫn Tour:', fullUrl);
      }
    });
  });

  // Tính tổng giá dự kiến và kiểm tra đủ chỗ trên trang chi tiết Tour
  const depSelect = document.querySelector('#detail-dep-select');
  const peopleInput = document.querySelector('#detail-people-input');
  const feedbackBox = document.querySelector('#detail-calc-feedback');
  const bookBtn = document.querySelector('#detail-book-btn');

  function formatVnd(n) {
    return Number(n).toLocaleString('vi-VN') + ' ₫';
  }

  function updateDetailCalc() {
    if (!depSelect || !peopleInput || !feedbackBox) return;
    const opt = depSelect.options[depSelect.selectedIndex];
    if (!opt || !opt.value) return;
    const price = parseInt(opt.getAttribute('data-price') || '0', 10);
    const rem = parseInt(opt.getAttribute('data-remaining') || '0', 10);
    const people = parseInt(peopleInput.value || '0', 10);
    const tourId = new URLSearchParams(location.search).get('id') || '';

    if (people < 1) {
      feedbackBox.innerHTML = `<div class="flash error" style="margin-top:8px;margin-bottom:14px">⚠ Vui lòng nhập số người dự kiến ≥ 1.</div>`;
      if (bookBtn) { bookBtn.style.pointerEvents = 'none'; bookBtn.style.opacity = '0.5'; }
      return;
    }
    if (people > rem) {
      feedbackBox.innerHTML = `<div class="flash error" style="margin-top:8px;margin-bottom:14px">⚠ Lịch đã chọn chỉ còn <b>${rem} chỗ</b>, không đủ cho <b>${people} người</b>. Vui lòng giảm số người hoặc chọn lịch khác!</div>`;
      if (bookBtn) { bookBtn.style.pointerEvents = 'none'; bookBtn.style.opacity = '0.5'; }
      return;
    }

    const total = price * people;
    feedbackBox.innerHTML = `
      <div class="payment-box" style="margin-top:8px">
        <span class="eyebrow">TỔNG GIÁ DỰ KIẾN</span>
        <p style="margin:6px 0;color:var(--ink)">
          Đơn giá lịch: <b>${formatVnd(price)} / người</b> · Còn trống: <b>${rem} chỗ</b><br>
          Số lượng khách: <b>${people} người</b>
        </p>
        <div style="font-size:22px;font-weight:700;color:var(--accent-dark);margin:8px 0">
          Tổng dự kiến: ${formatVnd(total)}
        </div>
        <small style="color:#925335;display:block">
          * Giá dự kiến trên <b>chưa bao gồm phụ thu</b> nếu phát sinh.
        </small>
      </div>`;
    if (bookBtn) {
      bookBtn.style.pointerEvents = 'auto';
      bookBtn.style.opacity = '1';
      bookBtn.href = `index.php?page=checkout&tour_id=${encodeURIComponent(tourId)}&departure_id=${encodeURIComponent(opt.value)}&people=${encodeURIComponent(people)}`;
    }
  }

  depSelect?.addEventListener('change', updateDetailCalc);
  peopleInput?.addEventListener('input', updateDetailCalc);
})();

// Xem trước tệp Giấy phép Seller (CN01)
window.previewLicenseFile = function (input, targetId) {
  const box = document.getElementById(targetId);
  if (!box) return;
  box.innerHTML = '';
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];
  if (file.type.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = e => {
      box.innerHTML = `<img src="${e.target.result}" alt="Xem trước giấy phép" style="margin-top:8px;max-height:140px;border-radius:8px;border:1px solid #cbd7d4">`;
    };
    reader.readAsDataURL(file);
  } else {
    box.innerHTML = `<div class="badge status-approved" style="margin-top:8px">📄 Đã chọn tệp PDF: ${file.name} (${Math.round(file.size / 1024)} KB)</div>`;
  }
};
