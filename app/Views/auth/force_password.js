(function () {
    var newPwd = document.getElementById('fNew');
    var confirmPwd = document.getElementById('fConfirm');
    var rulesBox = document.getElementById('fRules');
    var meter = document.getElementById('fMeter');
    var meterLabel = document.getElementById('fMeterLabel');
    var matchMsg = document.getElementById('fMatch');
    var submitBtn = document.getElementById('fSubmit');
    var rulesPassed = 0;   // จำนวนกฎที่ผ่านล่าสุด (อัปเดตใน evaluate)
    var STRENGTH = NP_FPW.strength;
    var MATCH_OK = NP_FPW.matchOk;
    var MATCH_BAD = NP_FPW.matchBad;
    var tests = {
        len: function (value) { return value.length >= 8; },
        upper: function (value) { return /[A-Z]/.test(value); },
        lower: function (value) { return /[a-z]/.test(value); },
        number: function (value) { return /[0-9]/.test(value); },
        symbol: function (value) { return /[^A-Za-z0-9]/.test(value); }
    };
    function evaluate() {
        var value = newPwd.value, passed = 0;
        rulesBox.querySelectorAll('li').forEach(function (item) {
            var ok = tests[item.dataset.rule](value);
            if (ok) passed++;
            item.classList.toggle('ok', ok);
            item.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
        });
        var level = value.length === 0 ? 0 : (passed <= 2 ? 1 : (passed === 3 ? 2 : (passed === 4 ? 3 : 4)));
        meter.dataset.lvl = level;
        meterLabel.textContent = STRENGTH[level];
        rulesPassed = passed;
        refreshSubmit();
    }
    function checkMatch() {
        if (confirmPwd.value === '') { matchMsg.className = 'np-match-msg'; matchMsg.innerHTML = ''; }
        else if (confirmPwd.value === newPwd.value) { matchMsg.className = 'np-match-msg is-ok'; matchMsg.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + MATCH_OK; }
        else { matchMsg.className = 'np-match-msg is-bad'; matchMsg.innerHTML = '<i class="bi bi-info-circle-fill"></i> ' + MATCH_BAD; }
        refreshSubmit();
    }

    // เปิดปุ่ม submit เมื่อกฎผ่านครบ 5 และ confirm ตรงกับ new password
    function refreshSubmit() {
        var ok = rulesPassed === 5 && confirmPwd.value !== '' && confirmPwd.value === newPwd.value;
        submitBtn.disabled = ! ok;
    }
    newPwd.addEventListener('input', function () {
        if (newPwd.value.length >= 1) { rulesBox.classList.add('show'); meter.classList.add('show'); }
        else { rulesBox.classList.remove('show'); meter.classList.remove('show'); }
        evaluate(); checkMatch();
    });
    confirmPwd.addEventListener('input', checkMatch);
    document.querySelectorAll('.np-pwd-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var inp = btn.parentElement.querySelector('input');
            var reveal = inp.type === 'password';
            inp.type = reveal ? 'text' : 'password';
            btn.querySelector('i').className = reveal ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

    // ตอน submit: spinner กลางปุ่ม + disable กัน double-submit
    document.querySelector('form').addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtn.querySelector('.np-btn-label').classList.add('d-none');
        submitBtn.querySelector('.spinner-border').classList.remove('d-none');
    });
})();
