document.addEventListener('DOMContentLoaded', function () {
  // فتح/إغلاق القائمة الجانبية على الشاشات الصغيرة
  var toggleBtn = document.getElementById('sidebarToggle');
  var sidebar = document.getElementById('appSidebar');
  var overlay = document.getElementById('sidebarOverlay');

  function closeSidebar() {
    sidebar && sidebar.classList.remove('show');
    overlay && overlay.classList.remove('show');
  }

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('show');
      overlay && overlay.classList.toggle('show');
    });
  }
  if (overlay) {
    overlay.addEventListener('click', closeSidebar);
  }

  // إخفاء التنبيهات تلقائياً
  document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (el) {
    setTimeout(function () {
      var alert = bootstrap.Alert.getOrCreateInstance(el);
      alert.close();
    }, 5000);
  });

  // تأكيد قبل الحذف
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!confirm(form.getAttribute('data-confirm') || 'هل أنت متأكد من تنفيذ هذا الإجراء؟')) {
        e.preventDefault();
      }
    });
  });

  // تفعيل tooltips
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    new bootstrap.Tooltip(el);
  });
});
