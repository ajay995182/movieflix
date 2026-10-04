/* HLS fallback for channel streams. No progress state is written for live channels. */
(function () {
	'use strict';
	var player = document.querySelector('[data-mf-channel-player]');
	if (!player) { return; }
	var video = player.querySelector('video');
	var status = player.querySelector('[data-mf-channel-status]');
	var source = video && video.querySelector('source');
	if (!video || !source || !source.src) { return; }
	var url = source.src;
	var isHls = player.getAttribute('data-type') === 'hls' || /\.m3u8(?:$|\?)/i.test(url);
	var i18n = window.MF && window.MF.i18n ? window.MF.i18n : {};
	function text(key, fallback) { return i18n[key] || fallback; }
	function report(message) { if (status) { status.textContent = message; } }
	video.addEventListener('playing', function () { report(text('streamPlaying', 'Playing live stream.')); });
	video.addEventListener('waiting', function () { report(text('streamBuffering', 'Buffering live stream…')); });
	video.addEventListener('error', function () { report(text('streamError', 'The stream could not be loaded. Please try again later.')); });
	if (!isHls) { return; }
	if (video.canPlayType('application/vnd.apple.mpegurl')) { return; }
	if (window.Hls && window.Hls.isSupported()) {
		var hls = new window.Hls();
		hls.loadSource(url);
		hls.attachMedia(video);
		hls.on(window.Hls.Events.ERROR, function (_event, data) {
			if (data && data.fatal) { report(text('streamUnavailable', 'The live stream is unavailable right now.')); }
		});
	} else {
		report(text('hlsUnsupported', 'This browser cannot play the HLS stream.'));
	}
})();