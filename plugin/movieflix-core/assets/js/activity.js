/* Live updates for the MovieFlix Activity screen. */
(function () {
	'use strict';
	var cb = document.getElementById('mf-live'), body = document.getElementById('mf-act-body'), timer = null;
	if (!cb || !body || typeof MFAct === 'undefined') { return; }
	function tick() {
		var after = body.getAttribute('data-max') || '0';
		fetch(MFAct.ajax + '?action=mf_activity_live&nonce=' + encodeURIComponent(MFAct.nonce) + '&after=' + encodeURIComponent(after), { credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (j) {
				if (!j || !j.success) { return; }
				if (j.data.html) {
					var empty = body.querySelector('.mf-empty-row'); if (empty) { empty.remove(); }
					body.insertAdjacentHTML('afterbegin', j.data.html);
					body.setAttribute('data-max', j.data.max);
					while (body.rows.length > 100) { body.deleteRow(body.rows.length - 1); }
				}
			}).catch(function () {});
	}
	cb.addEventListener('change', function () {
		clearInterval(timer);
		if (cb.checked) { tick(); timer = setInterval(tick, 10000); }
	});
})();
