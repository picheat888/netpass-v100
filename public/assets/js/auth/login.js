(function () {
    // toggle ดู/ซ่อนรหัสผ่าน
    document.querySelectorAll('.np-pwd-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var inp = btn.parentElement.querySelector('input');
            var reveal = inp.type === 'password';
            inp.type = reveal ? 'text' : 'password';
            btn.querySelector('i').className = reveal ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

    // ตอน submit: โชว์ spinner กลางปุ่ม + disable
    var form = document.getElementById('loginForm');
    var btn  = document.getElementById('loginBtn');
    form.addEventListener('submit', function () {
        btn.disabled = true;
        btn.querySelector('.np-btn-label').classList.add('d-none');
        btn.querySelector('.spinner-border').classList.remove('d-none');
    });
})();
