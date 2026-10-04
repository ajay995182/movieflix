(function ($) {
	$(document).on('click', '.mf-tab-nav a', function (e) {
		e.preventDefault();
		var $t = $(this), $wrap = $t.closest('.mf-tabs');
		$wrap.find('.mf-tab-nav a').removeClass('active');
		$t.addClass('active');
		$wrap.find('.mf-tab-panel').hide();
		$($t.attr('href')).show();
	});
	$(document).on('click', '.mf-media-btn', function (e) {
		e.preventDefault();
		var target = $('#' + $(this).data('target'));
		var frame = wp.media({ title: 'Select media', button: { text: 'Use this' }, multiple: false });
		frame.on('select', function () {
			target.val(frame.state().get('selection').first().toJSON().url).trigger('change');
		});
		frame.open();
	});

	// Chunked uploader
	var CHUNK = 2 * 1024 * 1024;
	$(document).on('click', '.mf-up-start', function (e) {
		e.preventDefault();
		var $box = $(this).closest('.mf-uploader');
		var file = $box.find('.mf-up-file')[0].files[0];
		if (!file) { return; }
		var nonce = $box.data('nonce');
		var targetId = $box.find('.mf-up-target').val();
		var $progress = $box.find('.mf-up-progress').show();
		var $bar = $box.find('.mf-up-bar'), $pct = $box.find('.mf-up-pct'), $status = $box.find('.mf-up-status'), $name = $box.find('.mf-up-name');
		var $btn = $(this).prop('disabled', true).text('Uploading…');
		var uid = 'up-' + Date.now() + '-' + Math.random().toString(36).substr(2, 8);
		var total = Math.ceil(file.size / CHUNK), cur = 0;
		$name.text(file.name + ' (' + (file.size / 1048576).toFixed(1) + ' MB)');
		$status.text('Uploading…');
		function send() {
			var start = cur * CHUNK, end = Math.min(start + CHUNK, file.size);
			var fd = new FormData();
			fd.append('action', 'mf_upload_chunk');
			fd.append('nonce', nonce);
			fd.append('upload_id', uid);
			fd.append('chunk_index', cur);
			fd.append('total_chunks', total);
			fd.append('file_name', file.name);
			fd.append('is_last', cur === total - 1 ? '1' : '0');
			fd.append('chunk', file.slice(start, end), file.name);
			$.ajax({
				url: ajaxurl, type: 'POST', data: fd, processData: false, contentType: false,
				success: function (res) {
					if (!res.success) { $status.text('Error: ' + (res.data && res.data.message ? res.data.message : 'Unknown')); $btn.prop('disabled', false).text('Upload'); return; }
					cur++;
					var p = Math.round((cur / total) * 100);
					$bar.css('width', p + '%'); $pct.text(p + '%');
					if (res.data && res.data.done) {
						$status.text('Done!');
						if (targetId && res.data.url) { $('#' + targetId).val(res.data.url).trigger('change'); }
						$btn.prop('disabled', false).text('Upload');
					} else if (cur < total) { send(); }
				},
				error: function () { $status.text('Network error.'); $btn.prop('disabled', false).text('Upload'); }
			});
		}
		send();
	});

	// Demo import
	$(document).on('click', '#mf-run-demo', function (e) {
		e.preventDefault();
		var $btn = $(this).prop('disabled', true).text('Importing…');
		$.post(ajaxurl, { action: 'mf_import_demo_ajax', nonce: mfAdmin.nonce }, function (res) {
			if (res.success) { alert(res.data.message); window.location.reload(); }
			else { alert(res.data && res.data.message ? res.data.message : 'Error'); $btn.prop('disabled', false).text('⚡ Hydrate Site (Import Demo)'); }
		}).fail(function () { $btn.prop('disabled', false).text('⚡ Hydrate Site (Import Demo)'); });
	});
	$(document).on('click', '#mf-remove-demo', function (e) {
		e.preventDefault();
		if (!confirm('Remove all demo content?')) { return; }
		var $btn = $(this).prop('disabled', true).text('Removing…');
		$.post(ajaxurl, { action: 'mf_remove_demo', nonce: mfAdmin.nonce }, function (res) {
			if (res.success) { alert(res.data.message); window.location.reload(); }
			else { alert(res.data && res.data.message ? res.data.message : 'Error'); $btn.prop('disabled', false).text('Remove Demo Data'); }
		}).fail(function () { $btn.prop('disabled', false).text('Remove Demo Data'); });
	});
})(jQuery);

	// Series episodes manager — quick add
	$(document).on('click', '.mf-ep-create', function (e) {
		e.preventDefault();
		var $box = $(this).closest('.mf-ep-manager');
		var $btn = $(this).prop('disabled', true);
		var $status = $box.find('.mf-ep-status').text('Creating…');
		$.post(ajaxurl, {
			action: 'mf_add_episode',
			nonce: $box.data('nonce') || (window.mfAdmin && mfAdmin.nonce),
			series: $box.data('series'),
			season: $box.find('.mf-ep-season').val(),
			episode: $box.find('.mf-ep-num').val(),
			title: $box.find('.mf-ep-title').val()
		}, function (res) {
			if (res.success) {
				$status.text(res.data.message || 'Created');
				if (res.data.edit_url && confirm((res.data.message || 'Episode created.') + '\n\nOpen episode editor to upload video now?')) {
					window.location.href = res.data.edit_url;
					return;
				}
				window.location.reload();
			} else {
				$status.text(res.data && res.data.message ? res.data.message : 'Error');
				$btn.prop('disabled', false);
			}
		}).fail(function () {
			$status.text('Network error');
			$btn.prop('disabled', false);
		});
	});
	$(document).on('change', '.mf-ep-season', function () {
		var $box = $(this).closest('.mf-ep-manager');
		// Soft hint only; server still validates.
		var n = parseInt($box.find('.mf-ep-num').val(), 10) || 1;
		if (n < 1) { $box.find('.mf-ep-num').val(1); }
	});
