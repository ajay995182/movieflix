/* MovieFlix player: progress, resume, speed, quality, HLS, subs, audio, skip intro, next overlay, Cast. */
(function () {
	'use strict';
	var box = document.getElementById('mf-player') || document.querySelector('.mf-player');
	var video = document.getElementById('mf-video');
	if (!box || !video || typeof MF === 'undefined') { return; }
	var cfg = (typeof MFPlayer !== 'undefined') ? MFPlayer : { speed: 1, autoplay: false };
	var id = box.getAttribute('data-id');
	var type = box.getAttribute('data-type');
	var src = box.getAttribute('data-src');
	var next = box.getAttribute('data-next') || '';
	var start = parseInt(box.getAttribute('data-start'), 10) || 0;
	var introStart = parseInt(box.getAttribute('data-intro-start'), 10) || 0;
	var introEnd = parseInt(box.getAttribute('data-intro-end'), 10) || 0;
	var qualities = [], subs = [], audioTracks = [];
	try { qualities = JSON.parse(box.getAttribute('data-qualities') || '[]'); } catch (e) { qualities = []; }
	try { subs = JSON.parse(box.getAttribute('data-subs') || '[]'); } catch (e) { subs = []; }
	try { audioTracks = JSON.parse(box.getAttribute('data-audio') || '[]'); } catch (e) { audioTracks = []; }

	var msg = document.getElementById('mf-msg');
	var speed = document.getElementById('mf-speed');
	var qwrap = document.getElementById('mf-quality-wrap');
	var qsel = document.getElementById('mf-quality');
	var pip = document.getElementById('mf-pip');
	var castBtn = document.getElementById('mf-cast');
	var subWrap = document.getElementById('mf-subs-wrap');
	var subSel = document.getElementById('mf-subs');
	var audioWrap = document.getElementById('mf-audio-wrap');
	var audioSel = document.getElementById('mf-audio');
	var skipBtn = document.getElementById('mf-skip-intro');
	var nextOverlay = document.getElementById('mf-next-overlay');
	var nextCountdown = document.getElementById('mf-next-countdown');
	var nextCancel = document.getElementById('mf-next-cancel');
	var hls = null, lastSave = 0, started = false, done = false, resumed = false, nextTimer = null, nextLeft = 8;

	function say(t) { if (msg) { msg.textContent = t || ''; } }
	function send(action, data, beacon) {
		var fd = new FormData();
		fd.append('action', action); fd.append('nonce', MF.nonce);
		Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
		if (beacon && navigator.sendBeacon) { navigator.sendBeacon(MF.ajax, fd); return; }
		fetch(MF.ajax, { method: 'POST', credentials: 'same-origin', body: fd, keepalive: true }).catch(function () {});
	}
	function save(beacon) {
		if (!MF.loggedIn || !video.duration || isNaN(video.duration)) { return; }
		send('mf_progress', { post_id: id, position: Math.floor(video.currentTime), duration: Math.floor(video.duration) }, beacon);
		lastSave = Date.now();
	}

	/* ---- Subtitles UI ---- */
	function setupSubs() {
		if (!subSel || !subWrap) { return; }
		var tracks = video.textTracks;
		var has = (tracks && tracks.length) || (subs && subs.length);
		if (!has) { return; }
		subWrap.hidden = false;
		// Prefer native tracks already in DOM
		if (tracks && tracks.length) {
			for (var i = 0; i < tracks.length; i++) {
				tracks[i].mode = 'disabled';
			}
			if (tracks.length && !subSel.options.length) {
				// options may already have Off
			}
			// Rebuild from video tracks if data-subs empty
			if (!subs.length) {
				subSel.innerHTML = '<option value="off">Off</option>';
				for (var j = 0; j < tracks.length; j++) {
					var o = document.createElement('option');
					o.value = String(j);
					o.textContent = tracks[j].label || tracks[j].language || ('Track ' + (j + 1));
					subSel.appendChild(o);
				}
			} else {
				subSel.innerHTML = '<option value="off">Off</option>';
				subs.forEach(function (s, idx) {
					var o = document.createElement('option');
					o.value = String(idx);
					o.textContent = s.label || s.code || ('Track ' + (idx + 1));
					subSel.appendChild(o);
				});
			}
			// Enable first by default if present
			if (tracks.length > 0) {
				subSel.value = '0';
				tracks[0].mode = 'showing';
			}
			subSel.addEventListener('change', function () {
				var v = subSel.value;
				for (var k = 0; k < tracks.length; k++) {
					tracks[k].mode = (v !== 'off' && String(k) === v) ? 'showing' : 'disabled';
				}
			});
		}
	}
	// tracks may load async
	video.addEventListener('loadedmetadata', setupSubs);
	setTimeout(setupSubs, 400);

	/* ---- Audio tracks (alternate video sources labeled as audio) ---- */
	if (audioSel && audioWrap && audioTracks.length) {
		audioWrap.hidden = false;
		audioSel.innerHTML = '';
		var def = document.createElement('option');
		def.value = src;
		def.textContent = 'Default';
		audioSel.appendChild(def);
		audioTracks.forEach(function (a) {
			var o = document.createElement('option');
			o.value = a.url;
			o.textContent = a.label || 'Audio';
			audioSel.appendChild(o);
		});
		audioSel.addEventListener('change', function () {
			var t = video.currentTime, playing = !video.paused;
			var newSrc = audioSel.value;
			if (type === 'hls' && hls) {
				hls.loadSource(newSrc);
				hls.attachMedia(video);
			} else {
				video.src = newSrc;
				video.load();
			}
			video.addEventListener('loadedmetadata', function once() {
				video.removeEventListener('loadedmetadata', once);
				try { video.currentTime = t; } catch (e) {}
				if (playing) { video.play().catch(function () {}); }
			});
		});
	}

	/* ---- Skip intro ---- */
	if (skipBtn && introEnd > introStart && introEnd > 0) {
		video.addEventListener('timeupdate', function () {
			var t = video.currentTime;
			if (t >= introStart && t < introEnd - 0.5) {
				skipBtn.hidden = false;
			} else {
				skipBtn.hidden = true;
			}
		});
		skipBtn.addEventListener('click', function () {
			try { video.currentTime = introEnd; } catch (e) {}
			skipBtn.hidden = true;
			say('Intro skipped');
			setTimeout(function () { say(''); }, 1500);
		});
	}

	/* ---- Resume ---- */
	function resume() {
		if (resumed) { return; }
		resumed = true;
		if (start > 0 && video.duration && start < video.duration - 5) {
			try { video.currentTime = start; } catch (e) {}
			say('Resumed from ' + Math.floor(start / 60) + ':' + ('0' + (start % 60)).slice(-2));
			setTimeout(function () { say(''); }, 3000);
		}
	}
	video.addEventListener('loadedmetadata', resume);

	/* ---- HLS / quality ---- */
	if (type === 'hls') {
		if (window.Hls && window.Hls.isSupported()) {
			hls = new window.Hls();
			hls.loadSource(src);
			hls.attachMedia(video);
			hls.on(window.Hls.Events.MANIFEST_PARSED, function () {
				if (hls.levels && hls.levels.length > 1 && qsel && qwrap) {
					qsel.innerHTML = '<option value="-1">Auto</option>';
					hls.levels.forEach(function (l, i) {
						var o = document.createElement('option');
						o.value = i;
						o.textContent = l.height ? (l.height + 'p') : (Math.round(l.bitrate / 1000) + 'k');
						qsel.appendChild(o);
					});
					qwrap.hidden = false;
					qsel.addEventListener('change', function () { hls.currentLevel = parseInt(qsel.value, 10); });
				}
			});
			hls.on(window.Hls.Events.ERROR, function (e, d) {
				if (!d.fatal) { return; }
				if (d.type === window.Hls.ErrorTypes.NETWORK_ERROR) { say('Network problem – retrying…'); hls.startLoad(); }
				else if (d.type === window.Hls.ErrorTypes.MEDIA_ERROR) { hls.recoverMediaError(); }
				else { say('This stream could not be played.'); hls.destroy(); }
			});
		} else if (video.canPlayType('application/vnd.apple.mpegurl')) {
			video.src = src;
		} else {
			say('HLS playback is not supported in this browser.');
		}
	} else if (qualities.length && qsel && qwrap) {
		qsel.innerHTML = '';
		var defQ = document.createElement('option');
		defQ.value = src; defQ.textContent = 'Default'; qsel.appendChild(defQ);
		qualities.forEach(function (q) {
			var o = document.createElement('option');
			o.value = q.url; o.textContent = q.label; qsel.appendChild(o);
		});
		qwrap.hidden = false;
		qsel.addEventListener('change', function () {
			var t = video.currentTime, playing = !video.paused;
			video.src = qsel.value;
			video.addEventListener('loadedmetadata', function once() {
				video.removeEventListener('loadedmetadata', once);
				try { video.currentTime = t; } catch (e) {}
				if (playing) { video.play().catch(function () {}); }
			});
			video.load();
		});
	}

	/* ---- Speed ---- */
	var sp = cfg.speed > 0 ? cfg.speed : 1;
	if (speed) {
		speed.value = String(sp);
		if (speed.value !== String(sp)) { speed.value = '1'; sp = 1; }
		speed.addEventListener('change', function () { video.playbackRate = parseFloat(speed.value) || 1; });
	}
	video.playbackRate = sp;
	video.addEventListener('play', function () { video.playbackRate = speed ? (parseFloat(speed.value) || 1) : sp; });

	/* ---- PiP ---- */
	if (pip && document.pictureInPictureEnabled && video.requestPictureInPicture) {
		pip.hidden = false;
		pip.addEventListener('click', function () {
			if (document.pictureInPictureElement) { document.exitPictureInPicture().catch(function () {}); }
			else { video.requestPictureInPicture().catch(function () { say('Picture-in-picture is unavailable.'); }); }
		});
	}

	/* ---- Chromecast / remote playback (best-effort) ---- */
	if (castBtn) {
		if (video.remote && typeof video.remote.watchAvailability === 'function') {
			video.remote.watchAvailability(function (avail) {
				castBtn.hidden = !avail;
			}).catch(function () { castBtn.hidden = true; });
			castBtn.addEventListener('click', function () {
				if (video.remote.state === 'disconnected') {
					video.remote.prompt().catch(function () { say('Cast is unavailable on this device.'); });
				} else if (video.remote.state === 'connected') {
					video.remote.cancel().catch(function () {});
				}
			});
		} else {
			castBtn.hidden = true;
		}
	}

	/* ---- Next episode overlay ---- */
	function clearNext() {
		if (nextTimer) { clearInterval(nextTimer); nextTimer = null; }
		if (nextOverlay) { nextOverlay.hidden = true; }
	}
	function startNextCountdown() {
		if (!next || !nextOverlay) { return; }
		nextLeft = 8;
		if (nextCountdown) { nextCountdown.textContent = String(nextLeft); }
		nextOverlay.hidden = false;
		nextTimer = setInterval(function () {
			nextLeft--;
			if (nextCountdown) { nextCountdown.textContent = String(Math.max(0, nextLeft)); }
			if (nextLeft <= 0) {
				clearNext();
				window.location.href = next;
			}
		}, 1000);
	}
	if (nextCancel) {
		nextCancel.addEventListener('click', function () { clearNext(); say(''); });
	}
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') { clearNext(); }
	});

	/* ---- Events + progress ---- */
	video.addEventListener('play', function () {
		if (!started) { started = true; send('mf_event', { post_id: id, event: 'start' }); }
	});
	video.addEventListener('timeupdate', function () { if (!video.paused && Date.now() - lastSave > 10000) { save(false); } });
	video.addEventListener('pause', function () { if (!video.ended) { save(false); } });
	video.addEventListener('ended', function () {
		if (!done) { done = true; send('mf_event', { post_id: id, event: 'complete' }); }
		if (MF.loggedIn && video.duration) {
			send('mf_progress', { post_id: id, position: Math.floor(video.duration), duration: Math.floor(video.duration) });
		}
		if (next) {
			if (cfg.autoplay !== false) {
				startNextCountdown();
			}
		}
	});
	window.addEventListener('pagehide', function () { if (!video.ended && video.currentTime > 0) { save(true); } });
	document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'hidden' && !video.paused) { save(true); } });

	video.addEventListener('error', function () {
		if (type === 'hls') { return; }
		var c = video.error ? video.error.code : 0;
		say(c === 4 ? 'This video format or URL is not supported or could not be found.' : 'The video failed to load. Please try again.');
	});
	video.addEventListener('stalled', function () { say('Buffering is taking longer than usual…'); });
	video.addEventListener('playing', function () { say(''); });

	if (cfg.autoplay) {
		var p = video.play();
		if (p && p.catch) { p.catch(function () { video.muted = true; video.play().catch(function () {}); }); }
	}
})();
