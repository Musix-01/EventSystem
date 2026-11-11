/*
 * Password helpers for the login, register and add-faculty forms.
 *
 *  <input type="checkbox" data-show-password="password,password_confirmation">
 *      shows or hides the listed password fields.
 *
 *  <div data-strength data-input="password"> ... </div>
 *      live strength meter and checklist for that field.
 */
(function () {
    // ---------- Show password ----------
    document.querySelectorAll('[data-show-password]').forEach(function (box) {
        const ids = box.getAttribute('data-show-password').split(',').map(function (s) { return s.trim(); }).filter(Boolean);

        function sync() {
            ids.forEach(function (id) {
                const field = document.getElementById(id);
                if (field) field.type = box.checked ? 'text' : 'password';
            });
        }

        box.addEventListener('change', sync);
        sync(); // the browser can restore a ticked box after a refresh
    });

    // ---------- Strength meter ----------
    const rules = {
        length: function (v) { return v.length >= 8; },
        upper:  function (v) { return /[A-Z]/.test(v); },
        number: function (v) { return /[0-9]/.test(v); }
    };
    const labels = ['Not entered', 'Weak', 'Medium', 'Strong'];
    const levelByMet = [1, 1, 2, 3]; // 0 or 1 rule met = Weak, 2 = Medium, 3 = Strong

    document.querySelectorAll('[data-strength]').forEach(function (box) {
        const input  = document.getElementById(box.getAttribute('data-input'));
        const status = box.querySelector('[data-status]');
        const items  = box.querySelectorAll('[data-rule]');
        if (!input || !status) return;

        function update() {
            const value = input.value;
            let met = 0;

            items.forEach(function (li) {
                const test = rules[li.getAttribute('data-rule')];
                const ok = test ? test(value) : false;
                li.classList.toggle('ok', ok);
                const sr = li.querySelector('[data-sr]');
                if (sr) sr.textContent = ok ? ' (done)' : ' (not yet)';
                if (ok) met++;
            });

            const level = value.length === 0 ? 0 : levelByMet[met];
            box.setAttribute('data-level', String(level));
            status.textContent = labels[level];
        }

        input.addEventListener('input', update);
        update();
    });
})();
