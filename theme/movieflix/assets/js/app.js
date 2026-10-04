/* MovieFlix theme – front-end behaviour. No dependencies. */
(function () {
	'use strict';
	if (typeof MF === 'undefined') { window.MF = { ajax: '', nonce: '', loggedIn: false, loginUrl: '/', i18n: {} }; }
	var $ = function (s, c) { return (c || document).querySelector(s); };
	var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
	var t = function (k, d) { return (MF.i18n && MF.i18n[k]) || d; };

	function post(action, data) {
		var fd = new FormData();
		fd.append('action', action);
		fd.append('nonce', MF.nonce);
		Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
		return fetch(MF.ajax, { method: 'POST', credentials: 'same-origin', body: fd }).then(function (r) {
			return r.json().then(function (j) { j.status = r.status; return j; }, function () { return { success: false, status: r.status, data: {} }; });
		});
	}
	window.MFPost = post;

	function toast(msg) {
		var el = $('#mf-toast');
		if (!el) {
			el = document.createElement('div');
			el.id = 'mf-toast';
			el.setAttribute('role', 'status');
			el.setAttribute('aria-live', 'polite');
			el.style.cssText = 'position:fixed;left:50%;bottom:24px;transform:translateX(-50%);background:#23282a;color:#f5f1e9;padding:12px 18px;border-radius:10px;z-index:400;box-shadow:0 12px 32px rgba(0,0,0,.4);transition:opacity .3s;opacity:0';
			document.body.appendChild(el);
		}
		el.textContent = msg;
		el.style.opacity = '1';
		clearTimeout(el._t);
		el._t = setTimeout(function () { el.style.opacity = '0'; }, 2600);
	}
	window.MFToast = toast;
	function copyText(value) {
		if (navigator.clipboard && navigator.clipboard.writeText) { return navigator.clipboard.writeText(value); }
		return new Promise(function (resolve, reject) {
			var field = document.createElement('textarea');
			field.value = value; field.setAttribute('readonly', ''); field.style.position = 'fixed'; field.style.opacity = '0';
			document.body.appendChild(field); field.select();
			var ok = false;
			try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
			field.remove();
			ok ? resolve() : reject(new Error('Clipboard unavailable'));
		});
	}

	function needLogin(res) {
		if (res && res.status === 401) {
			var url = (res.data && res.data.login) || MF.loginUrl;
			window.location.href = url + (url.indexOf('?') > -1 ? '&' : '?') + 'mf_msg=login_required&redirect_to=' + encodeURIComponent(window.location.href);
			return true;
		}
		return false;
	}

	/* Header solid background on scroll */
	var hdr = document.getElementById('mf-header');
	if (hdr) {
		var onScroll = function () {
			hdr.classList.toggle('is-scrolled', window.scrollY > 40);
		};
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	}

	/* Row scrolling */
	$$('.mf-row').forEach(function (row) {
		var track = $('.mf-row__track', row);
		$$('.mf-row__nav', row).forEach(function (btn) {
			btn.addEventListener('click', function () {
				var dir = btn.classList.contains('mf-row__nav--prev') ? -1 : 1;
				track.scrollBy({ left: dir * track.clientWidth * 0.85, behavior: 'smooth' });
			});
		});
	});

	/* Mobile nav + user menu */
	var burger = $('.mf-burger'), nav = $('#mf-nav');
	if (burger && nav) {
		var setNav = function (open) {
			burger.setAttribute('aria-expanded', open ? 'true' : 'false');
			burger.setAttribute('aria-label', open ? t('closeMenu', 'Close menu') : t('openMenu', 'Open menu'));
			nav.classList.toggle('is-open', open);
			document.body.classList.toggle('mf-nav-open', open);
			if (open) {
				var first = $('a', nav);
				if (first && window.matchMedia('(max-width: 1100px)').matches) { first.focus(); }
			}
		};
		burger.addEventListener('click', function () { setNav(burger.getAttribute('aria-expanded') !== 'true'); });
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && burger.getAttribute('aria-expanded') === 'true') { setNav(false); burger.focus(); }
		});
		document.addEventListener('click', function (e) {
			if (burger.getAttribute('aria-expanded') === 'true' && !nav.contains(e.target) && !burger.contains(e.target)) { setNav(false); }
		});
		$$('a', nav).forEach(function (link) { link.addEventListener('click', function () { if (window.matchMedia('(max-width: 1100px)').matches) { setNav(false); } }); });
	}
	var um = $('.mf-user'), umb = $('.mf-user__btn');
	if (um && umb) {
		umb.addEventListener('click', function (e) {
			e.stopPropagation();
			var open = !um.classList.contains('is-open');
			um.classList.toggle('is-open', open);
			umb.setAttribute('aria-expanded', open ? 'true' : 'false');
			var menu = $('.mf-user__menu', um);
			if (menu) { menu.setAttribute('aria-hidden', open ? 'false' : 'true'); }
			if (open) { var first = $('a', menu); if (first) { first.focus(); } }
		});
		document.addEventListener('click', function (e) {
			if (!um.contains(e.target)) { um.classList.remove('is-open'); umb.setAttribute('aria-expanded', 'false'); var menu = $('.mf-user__menu', um); if (menu) { menu.setAttribute('aria-hidden', 'true'); } }
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && um.classList.contains('is-open')) { um.classList.remove('is-open'); umb.setAttribute('aria-expanded', 'false'); var menu = $('.mf-user__menu', um); if (menu) { menu.setAttribute('aria-hidden', 'true'); } umb.focus(); }
		});
	}

	/* Search overlay + suggestions */
	var sbox = $('#mf-search'), stog = $('.mf-search-toggle'), sin = $('#mf-search-input'), sug = $('#mf-suggest'), sstatus = $('#mf-search-status'), timer, requestId = 0;
	if (sbox && stog) {
		stog.addEventListener('click', function () {
			var open = sbox.hidden;
			sbox.hidden = !open;
			stog.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (!open && sin) { sin.setAttribute('aria-expanded', 'false'); if (sug) { sug.innerHTML = ''; } if (sstatus) { sstatus.textContent = ''; } }
			if (open && sin) { sin.focus(); }
			document.body.classList.toggle('mf-search-open', open);
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && !sbox.hidden) { sbox.hidden = true; stog.setAttribute('aria-expanded', 'false'); if (sin) { sin.setAttribute('aria-expanded', 'false'); } if (sug) { sug.innerHTML = ''; } if (sstatus) { sstatus.textContent = ''; } document.body.classList.remove('mf-search-open'); stog.focus(); }
		});
	}
	if (sin && sug) {
		sin.addEventListener('input', function () {
			clearTimeout(timer);
			var term = sin.value.trim();
			var thisRequest = ++requestId;
			sin.setAttribute('aria-expanded', term.length > 1 ? 'true' : 'false');
			if (term.length < 2) { sug.innerHTML = ''; if (sstatus) { sstatus.textContent = ''; } return; }
			if (sstatus) { sstatus.textContent = t('searching', 'Searching…'); }
			timer = setTimeout(function () {
				fetch(MF.ajax + '?action=mf_suggest&term=' + encodeURIComponent(term), { credentials: 'same-origin' })
					.then(function (r) { return r.json(); })
					.then(function (j) {
						if (thisRequest !== requestId) { return; }
						sug.innerHTML = '';
						var results = (j && j.data) || [];
						sin.setAttribute('aria-expanded', results.length ? 'true' : 'false');
						results.forEach(function (it) {
							var li = document.createElement('li');
							var a = document.createElement('a');
							a.href = it.url || '#';
							a.setAttribute('role', 'option');
							a.className = 'mf-suggest__item';
							if (it.img) {
								var img = document.createElement('img');
								img.src = it.img; img.alt = ''; img.width = 40; img.height = 60; img.loading = 'lazy';
								if (it.type === 'person') { img.className = 'mf-suggest__avatar'; img.width = 40; img.height = 40; }
								a.appendChild(img);
							} else {
								var ph = document.createElement('span');
								ph.className = 'mf-suggest__ph';
								ph.setAttribute('aria-hidden', 'true');
								ph.textContent = (it.title || '?').charAt(0);
								a.appendChild(ph);
							}
							var body = document.createElement('span');
							body.className = 'mf-suggest__body';
							var title = document.createElement('strong');
							title.textContent = it.title || '';
							body.appendChild(title);
							var row = document.createElement('span');
							row.className = 'mf-suggest__meta';
							if (it.badge) {
								var badge = document.createElement('em');
								badge.className = 'mf-suggest__badge mf-suggest__badge--' + (it.type || 'title');
								badge.textContent = it.badge;
								row.appendChild(badge);
							}
							var bits = [];
							if (it.year) { bits.push(it.year); }
							if (it.rating) { bits.push('★ ' + it.rating); }
							if (it.meta) { bits.push(it.meta); }
							if (bits.length) {
								var sm = document.createElement('small');
								sm.textContent = bits.join(' · ');
								row.appendChild(sm);
							}
							body.appendChild(row);
							a.appendChild(body);
							li.appendChild(a);
							sug.appendChild(li);
						});
						if (sstatus) { sstatus.textContent = results.length ? t('suggestions', 'Suggestions') : t('noResults', 'No matching titles. Press Enter to search all results.'); }
					}).catch(function () { if (thisRequest === requestId) { sug.innerHTML = ''; if (sstatus) { sstatus.textContent = t('error', 'Suggestions could not be loaded.'); } } });
			}, 250);
		});
		sin.addEventListener('keydown', function (e) {
			var links = $$('a[role="option"]', sug);
			if (e.key === 'ArrowDown' && links.length) { e.preventDefault(); links[0].focus(); }
		});
		sug.addEventListener('keydown', function (e) {
			if (!['ArrowDown', 'ArrowUp'].includes(e.key)) { return; }
			var links = $$('a[role="option"]', sug), i = links.indexOf(document.activeElement);
			if (e.key === 'ArrowUp' && i <= 0) { e.preventDefault(); sin.focus(); }
			else if (e.key === 'ArrowUp') { e.preventDefault(); links[i - 1].focus(); }
			else if (i < links.length - 1) { e.preventDefault(); links[i + 1].focus(); }
		});
	}

	/* My List toggle */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest ? e.target.closest('.mf-list-btn') : null;
		if (!btn) { return; }
		e.preventDefault();
		if (btn.disabled) { return; }
		btn.disabled = true;
		post('mf_toggle_list', { post_id: btn.getAttribute('data-id') }).then(function (res) {
			btn.disabled = false;
			if (needLogin(res)) { return; }
			if (!res.success) { toast((res.data && res.data.message) || t('error', 'Error')); return; }
			var on = !!res.data.in_list, large = btn.hasAttribute('data-large');
			btn.classList.toggle('is-active', on);
			btn.setAttribute('aria-pressed', on ? 'true' : 'false');
			btn.setAttribute('aria-label', on ? t('remove', 'Remove from My List') : t('add', 'Add to My List'));
			if (large) {
				var ic = btn.querySelector('.mf-list-btn__icon'), lb = btn.querySelector('.mf-list-btn__label');
				if (ic) { ic.textContent = on ? '✓' : '+'; }
				if (lb) { lb.textContent = on ? 'In My List' : 'My List'; }
			} else { btn.textContent = on ? '✓' : '+'; }
			toast(res.data.message);
		}).catch(function () { btn.disabled = false; toast(t('error', 'Error')); });
	});

	/* Star rating */
	$$('.mf-rating').forEach(function (box) {
		var stars = $$('.mf-star', box), id = box.getAttribute('data-id');
		function paint(n) { stars.forEach(function (s, i) { s.classList.toggle('is-on', i < n); }); }
		stars.forEach(function (s) {
			s.addEventListener('mouseenter', function () { paint(+s.getAttribute('data-value')); });
			s.addEventListener('focus', function () { paint(+s.getAttribute('data-value')); });
			s.addEventListener('click', function () {
				post('mf_rate', { post_id: id, rating: s.getAttribute('data-value') }).then(function (res) {
					if (needLogin(res)) { return; }
					if (!res.success) { toast((res.data && res.data.message) || t('error', 'Error')); paint(+box.getAttribute('data-mine')); return; }
					box.setAttribute('data-mine', res.data.user);
					stars.forEach(function (x, i) { x.setAttribute('aria-checked', (i + 1) === res.data.user ? 'true' : 'false'); });
					var a = $('.mf-rating__avg', box), c = $('.mf-rating__count', box), m = $('.mf-rating__mine', box);
					if (a) { a.textContent = res.data.avg; }
					if (c) { c.textContent = res.data.count; }
					if (m) { m.textContent = ' · ' + res.data.user + '★'; }
					toast(res.data.message);
				}).catch(function () { toast(t('error', 'Error')); });
			});
		});
		box.addEventListener('mouseleave', function () { paint(+box.getAttribute('data-mine')); });
	});

	/* Continue watching: remove / clear */
	document.addEventListener('click', function (e) {
		var rm = e.target.closest ? e.target.closest('.mf-remove-progress') : null;
		if (rm) {
			post('mf_remove_progress', { post_id: rm.getAttribute('data-id') }).then(function (res) {
				if (needLogin(res)) { return; }
				if (res.success) {
					var card = rm.closest('.mf-card');
					if (card) { card.remove(); }
					toast(res.data.message);
				} else { toast(t('error', 'Error')); }
			});
			return;
		}
		var cl = e.target.closest ? e.target.closest('.mf-clear-history') : null;
		if (cl) {
			if (!window.confirm('Clear your entire watch history?')) { return; }
			post('mf_clear_history', {}).then(function (res) {
				if (needLogin(res)) { return; }
				toast(res.success ? res.data.message : t('error', 'Error'));
				if (res.success) { setTimeout(function () { window.location.reload(); }, 700); }
			});
		}
	});

	/* Trailer modal */
	var modal = $('#mf-trailer');
	if (modal) {
		var body = $('.mf-modal__body', modal), lastFocus = null;
		var close = function () { modal.hidden = true; body.innerHTML = ''; document.body.classList.remove('mf-modal-open'); if (lastFocus) { lastFocus.focus(); } };
		$$('.mf-trailer-btn').forEach(function (b) {
			b.addEventListener('click', function () {
				lastFocus = b;
				var embed = b.getAttribute('data-embed'), src = b.getAttribute('data-src');
				body.innerHTML = '';
				if (embed) {
					var f = document.createElement('iframe');
					f.src = embed + (embed.indexOf('?') > -1 ? '&' : '?') + 'autoplay=1';
					f.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
					f.setAttribute('allowfullscreen', '');
					f.title = 'Trailer';
					body.appendChild(f);
				} else if (src) {
					var v = document.createElement('video');
					v.src = src; v.controls = true; v.autoplay = true; v.playsInline = true;
					body.appendChild(v);
				} else { return; }
				modal.hidden = false;
				document.body.classList.add('mf-modal-open');
				$('.mf-modal__close', modal).focus();
			});
		});
		$('.mf-modal__close', modal).addEventListener('click', close);
		modal.addEventListener('click', function (e) { if (e.target === modal) { close(); } });
		document.addEventListener('keydown', function (e) {
			if (modal.hidden) { return; }
			if (e.key === 'Escape') { close(); return; }
			if (e.key === 'Tab') {
				var focusable = $$('button, a[href], iframe, video', modal).filter(function (el) { return !el.disabled; });
				if (!focusable.length) { return; }
				var first = focusable[0], last = focusable[focusable.length - 1];
				if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
				else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
			}
		});
	}

	/* Season selector: only season panels rendered by the existing episode query. */
	var seasonSelect = $('.mf-season-select');
	if (seasonSelect) {
		seasonSelect.addEventListener('change', function () {
			$$('.mf-season-panel').forEach(function (panel) { panel.hidden = panel.getAttribute('data-season') !== seasonSelect.value; });
		});
	}

	/* Hero slider */
	var hero = $('.mf-hero--slider');
	if (hero) {
		var slides = $$('.mf-slide', hero), dots = $$('.mf-dot', hero), cur = 0, iv = null;
		var delay = parseInt(hero.getAttribute('data-autoplay'), 10) || 7000;
		hero.style.setProperty('--mf-slide', (delay / 1000) + 's');
		var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var show = function (n) {
			cur = (n + slides.length) % slides.length;
			slides.forEach(function (s, i) { s.classList.toggle('is-active', i === cur); });
			dots.forEach(function (d, i) {
				d.classList.remove('is-active');
				if (i === cur) { void d.offsetWidth; d.classList.add('is-active'); }
			});
		};
		var stop = function () { clearInterval(iv); iv = null; hero.classList.add('is-paused'); };
		var play = function () { if (reduce || iv) { return; } hero.classList.remove('is-paused'); iv = setInterval(function () { show(cur + 1); }, delay); };
		var prev = $('.mf-hero__nav--prev', hero), nextB = $('.mf-hero__nav--next', hero);
		if (prev) { prev.addEventListener('click', function () { stop(); show(cur - 1); play(); }); }
		if (nextB) { nextB.addEventListener('click', function () { stop(); show(cur + 1); play(); }); }
		dots.forEach(function (d, i) { d.addEventListener('click', function () { stop(); show(i); play(); }); });
		hero.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') { stop(); show(cur + (e.key === 'ArrowRight' ? 1 : -1)); play(); }
		});
		var touchX = null;
		hero.addEventListener('touchstart', function (e) { if (e.touches.length === 1) { touchX = e.touches[0].clientX; } }, { passive: true });
		hero.addEventListener('touchend', function (e) {
			if (touchX === null || !e.changedTouches.length) { return; }
			var delta = e.changedTouches[0].clientX - touchX;
			if (Math.abs(delta) > 48) { stop(); show(cur + (delta < 0 ? 1 : -1)); play(); }
			touchX = null;
		}, { passive: true });
		hero.addEventListener('mouseenter', stop);
		hero.addEventListener('mouseleave', play);
		hero.addEventListener('focusin', stop);
		hero.addEventListener('focusout', play);
		document.addEventListener('visibilitychange', function () { if (document.hidden) { stop(); } else { play(); } });
		if (reduce) { hero.classList.add('is-paused'); } else { play(); }
	}

	/* Share */
	$$('.mf-share-btn').forEach(function (b) {
		b.addEventListener('click', function () {
			var url = b.getAttribute('data-url') || location.href, title = b.getAttribute('data-title') || document.title;
			if (navigator.share) { navigator.share({ title: title, url: url }).catch(function () {}); return; }
			copyText(url).then(function () { toast('Link copied'); }, function () { toast(t('copyError', 'Could not copy the link.')); });
		});
	});
	document.addEventListener('click', function (e) {
		var action = e.target.closest ? e.target.closest('[data-share]') : null;
		if (!action) { return; }
		e.preventDefault();
		var wrap = action.closest('.mf-share-menu'), url = (wrap && wrap.getAttribute('data-url')) || location.href, title = (wrap && wrap.getAttribute('data-title')) || document.title;
		if (action.getAttribute('data-share') === 'native' && navigator.share) { navigator.share({ title: title, url: url }).catch(function () {}); }
		else { copyText(url).then(function () { toast('Link copied'); }, function () { toast(t('copyError', 'Could not copy the link.')); }); }
	});
})();

/* ---- In-app notifications ---- */
(function () {
	if (typeof MF === 'undefined' || !MF.loggedIn) { return; }
	var btn = document.getElementById('mf-notify-btn');
	var panel = document.getElementById('mf-notify-panel');
	var list = document.getElementById('mf-notify-list');
	var badge = document.getElementById('mf-notify-badge');
	var readAll = document.getElementById('mf-notify-readall');
	var enableBtn = document.getElementById('mf-notify-enable');
	if (!btn || !panel || !list) { return; }

	function post(action, data, cb) {
		var fd = new FormData();
		fd.append('action', action);
		fd.append('nonce', MF.nonce);
		if (data) { Object.keys(data).forEach(function (k) { fd.append(k, data[k]); }); }
		fetch(MF.ajax, { method: 'POST', credentials: 'same-origin', body: fd })
			.then(function (r) { return r.json(); })
			.then(function (res) { if (cb) cb(res); })
			.catch(function () {});
	}
	function setBadge(n) {
		if (!badge) { return; }
		if (n > 0) { badge.hidden = false; badge.textContent = String(n); }
		else { badge.hidden = true; badge.textContent = '0'; }
	}
	function render(items) {
		if (!items || !items.length) {
			list.innerHTML = '<li class="mf-notify__empty">No notifications yet</li>';
			return;
		}
		list.innerHTML = items.map(function (it) {
			var cls = it.read ? '' : ' is-unread';
			var href = it.url ? it.url : '#';
			return '<li class="mf-notify__item' + cls + '" data-id="' + it.id + '">' +
				'<a href="' + href + '"><strong>' + (it.title || '') + '</strong>' +
				(it.body ? '<span>' + it.body + '</span>' : '') +
				'<small>' + (it.time || '') + '</small></a></li>';
		}).join('');
	}
	function load() {
		post('mf_notify_list', null, function (res) {
			if (res && res.success) {
				render(res.data.items);
				setBadge(res.data.unread || 0);
			} else {
				list.innerHTML = '<li class="mf-notify__empty">Could not load</li>';
			}
		});
	}
	btn.addEventListener('click', function (e) {
		e.stopPropagation();
		var open = panel.hidden;
		panel.hidden = !open;
		btn.setAttribute('aria-expanded', open ? 'true' : 'false');
		if (open) { load(); }
	});
	document.addEventListener('click', function (e) {
		if (!panel.hidden && !panel.contains(e.target) && e.target !== btn) {
			panel.hidden = true;
			btn.setAttribute('aria-expanded', 'false');
		}
	});
	list.addEventListener('click', function (e) {
		var li = e.target.closest('.mf-notify__item');
		if (!li) { return; }
		post('mf_notify_read', { id: li.getAttribute('data-id') }, function (res) {
			if (res && res.success) { setBadge(res.data.unread || 0); li.classList.remove('is-unread'); }
		});
	});
	if (readAll) {
		readAll.addEventListener('click', function () {
			post('mf_notify_read_all', null, function (res) {
				if (res && res.success) { setBadge(0); load(); }
			});
		});
	}
	function setEnableState(label, disabled) {
		if (!enableBtn) { return; }
		enableBtn.textContent = label;
		enableBtn.disabled = !!disabled;
	}
	function showToast(msg) {
		if (typeof window.mfToast === 'function') { window.mfToast(msg); return; }
		var t = document.getElementById('mf-toast');
		if (!t) {
			t = document.createElement('div');
			t.id = 'mf-toast';
			t.setAttribute('role', 'status');
			document.body.appendChild(t);
		}
		t.textContent = msg;
		t.classList.add('is-show');
		clearTimeout(t._hide);
		t._hide = setTimeout(function () { t.classList.remove('is-show'); }, 3200);
	}
	if (enableBtn) {
		var supportsNotify = (typeof window !== 'undefined' && 'Notification' in window);
		if (!supportsNotify) {
			setEnableState('Not supported', true);
		} else if (Notification.permission === 'granted') {
			setEnableState('Enabled', true);
		} else if (Notification.permission === 'denied') {
			setEnableState('Blocked', true);
			enableBtn.title = 'Notifications are blocked in browser settings. Allow them for this site to enable alerts.';
		}
		enableBtn.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			if (!supportsNotify) {
				showToast('This browser does not support notifications.');
				return;
			}
			if (Notification.permission === 'granted') {
				setEnableState('Enabled', true);
				showToast('Notifications already enabled.');
				return;
			}
			if (Notification.permission === 'denied') {
				showToast('Notifications are blocked. Open site settings and allow notifications, then reload.');
				return;
			}
			// Must call from a direct user gesture.
			var req = Notification.requestPermission();
			if (req && typeof req.then === 'function') {
				req.then(function (perm) {
					if (perm === 'granted') {
						post('mf_notify_push_sub', { subscription: 'browser' });
						try {
							new Notification('MovieFlix', {
								body: 'Notifications enabled. You will see alerts for new titles.',
								icon: (document.querySelector('link[rel="icon"]') || {}).href || '/favicon.ico'
							});
						} catch (err) {}
						setEnableState('Enabled', true);
						showToast('Browser notifications enabled.');
					} else if (perm === 'denied') {
						setEnableState('Blocked', true);
						showToast('Permission denied. Allow notifications in browser settings.');
					} else {
						showToast('Permission not granted.');
					}
				}).catch(function () {
					showToast('Could not request notification permission.');
				});
			} else {
				// Older Safari-style callback
				try {
					Notification.requestPermission(function (perm) {
						if (perm === 'granted') {
							post('mf_notify_push_sub', { subscription: 'browser' });
							setEnableState('Enabled', true);
							showToast('Browser notifications enabled.');
						} else {
							showToast('Permission not granted.');
						}
					});
				} catch (err2) {
					showToast('Could not request notification permission.');
				}
			}
		});
	}
})();

/* ---- PWA install prompt (after login) ---- */
(function () {
	if (typeof MF === 'undefined') { return; }
	var deferred = null;
	var dismissed = false;
	try { dismissed = localStorage.getItem('mf_pwa_dismiss') === '1'; } catch (e) {}

	window.addEventListener('beforeinstallprompt', function (e) {
		e.preventDefault();
		deferred = e;
		if (MF.loggedIn && !dismissed) { showBanner(); }
	});

	function showBanner() {
		if (document.getElementById('mf-pwa-banner')) { return; }
		if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) { return; }
		if (window.navigator.standalone) { return; }
		var el = document.createElement('div');
		el.id = 'mf-pwa-banner';
		el.className = 'mf-pwa-banner';
		el.innerHTML = '<div class="mf-pwa-banner__inner">' +
			'<div class="mf-pwa-banner__text"><strong>Install MovieFlix</strong><span>Add the app for full-screen, faster access</span></div>' +
			'<div class="mf-pwa-banner__actions">' +
			'<button type="button" class="mf-btn mf-btn--primary" id="mf-pwa-install">Install</button>' +
			'<button type="button" class="mf-btn mf-btn--ghost" id="mf-pwa-dismiss">Not now</button>' +
			'</div></div>';
		document.body.appendChild(el);
		document.getElementById('mf-pwa-install').addEventListener('click', function () {
			if (!deferred) { return; }
			deferred.prompt();
			deferred.userChoice.then(function () {
				deferred = null;
				el.remove();
			});
		});
		document.getElementById('mf-pwa-dismiss').addEventListener('click', function () {
			try { localStorage.setItem('mf_pwa_dismiss', '1'); } catch (e) {}
			el.remove();
		});
	}

	// If already logged in and event already fired, try after short delay
	if (MF.loggedIn) {
		setTimeout(function () {
			if (deferred && !dismissed) { showBanner(); }
		}, 1200);
	}

	// Standalone class for CSS
	if ((window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone) {
		document.documentElement.classList.add('mf-standalone');
		document.body.classList.add('mf-standalone');
	}
})();

/* ---- Channel favourites ---- */
(function () {
	if (typeof MF === 'undefined' || !MF.loggedIn) { return; }
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.mf-fav-channel');
		if (!btn) { return; }
		e.preventDefault();
		var id = btn.getAttribute('data-id');
		var fd = new FormData();
		fd.append('action', 'mf_fav_channel');
		fd.append('nonce', MF.nonce);
		fd.append('channel_id', id);
		fetch(MF.ajax, { method: 'POST', credentials: 'same-origin', body: fd })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res || !res.success) { return; }
				var on = res.data.favourited;
				btn.classList.toggle('is-active', on);
				btn.setAttribute('aria-pressed', on ? 'true' : 'false');
				btn.innerHTML = (on ? '★' : '☆') + ' Fav';
			}).catch(function () {});
	});
})();

/* ---- First-run onboarding tour ---- */
(function () {
	if (typeof MF === 'undefined') { return; }
	var key = 'mf_onboard_v1';
	try { if (localStorage.getItem(key) === '1') { return; } } catch (e) { return; }
	// Only for logged-in users on home, once.
	if (!MF.loggedIn) { return; }
	if (!document.body.classList.contains('home') && !document.body.classList.contains('front-page')) {
		// still allow on any page first visit after login within session
		try { if (sessionStorage.getItem('mf_onboard_skip') === '1') { return; } } catch (e2) {}
	}
	var steps = [
		{ title: 'Welcome to MovieFlix', text: 'Browse movies, TV shows and Live TV from the menu or bottom bar.' },
		{ title: 'Search anything', text: 'Tap the search icon to find titles, actors and genres — results show posters, year and type.' },
		{ title: 'My List & continue', text: 'Save titles to My List and pick up where you left off under Continue Watching.' },
		{ title: 'Profiles', text: 'Use Profiles for Kids mode — only family-friendly titles, with locked navigation.' }
	];
	var i = 0;
	var overlay = document.createElement('div');
	overlay.className = 'mf-tour';
	overlay.id = 'mf-tour';
	overlay.innerHTML = '<div class="mf-tour__card" role="dialog" aria-modal="true" aria-labelledby="mf-tour-title">' +
		'<p class="mf-tour__step" id="mf-tour-step"></p>' +
		'<h2 id="mf-tour-title"></h2><p id="mf-tour-text"></p>' +
		'<div class="mf-tour__actions">' +
		'<button type="button" class="mf-btn mf-btn--ghost" id="mf-tour-skip">Skip</button>' +
		'<button type="button" class="mf-btn mf-btn--primary" id="mf-tour-next">Next</button>' +
		'</div></div>';
	function render() {
		document.getElementById('mf-tour-step').textContent = (i + 1) + ' / ' + steps.length;
		document.getElementById('mf-tour-title').textContent = steps[i].title;
		document.getElementById('mf-tour-text').textContent = steps[i].text;
		document.getElementById('mf-tour-next').textContent = (i === steps.length - 1) ? 'Done' : 'Next';
	}
	function close() {
		try { localStorage.setItem(key, '1'); sessionStorage.setItem('mf_onboard_skip', '1'); } catch (e) {}
		overlay.remove();
	}
	setTimeout(function () {
		document.body.appendChild(overlay);
		render();
		document.getElementById('mf-tour-skip').addEventListener('click', close);
		document.getElementById('mf-tour-next').addEventListener('click', function () {
			if (i >= steps.length - 1) { close(); return; }
			i++;
			render();
		});
	}, 900);
})();
