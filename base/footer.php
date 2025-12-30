<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
</div>
<div class="p-5 text-center ">
	<h6>©<?php $this->options->title() ?></h6>
	<p class="h6"> Powered by <a href="https://typecho.org" target="_blank" rel="noopener noreferrer">Typecho</a> and <a href="https://github.com/Sung-Kim0430/Brave" target="_blank" rel="noopener noreferrer">Brave-Theme</a></p>
</div>
<?php $assetsSource = (isset(Helper::options()->assetsSource) ? (string)Helper::options()->assetsSource : 'local'); ?>
<?php $cdnEnableSRI = !isset(Helper::options()->cdnEnableSRI) || (string)Helper::options()->cdnEnableSRI !== '0'; ?>
<?php $enableSRI = ($assetsSource === 'cdn' && $cdnEnableSRI); ?>
<?php $enableCustomCode = !isset(Helper::options()->enableCustomCode) || (string)Helper::options()->enableCustomCode !== '0'; ?>
<?php if ($assetsSource === 'cdn') : ?>
	<script src="https://cdn.staticfile.org/jquery.pjax/2.0.1/jquery.pjax.min.js" type="application/javascript"
	        <?php if ($enableSRI) : ?>integrity="sha384-VLg3MPOy+5T9leB7r4BBB56zHq4/e0We8vujbAvJwp3xNDhj3b7Fg6+jOVs6bym1" crossorigin="anonymous"<?php endif; ?>></script>
	<script src="https://cdn.staticfile.org/nprogress/0.2.0/nprogress.min.js" type="application/javascript"
	        <?php if ($enableSRI) : ?>integrity="sha384-WVrcwN/kiINFnwTi170GvMPVLHVBao1WfcXL/BZAK3VaUaaWX0OOsxMgCiFmrIb1" crossorigin="anonymous"<?php endif; ?>></script>
<?php else : ?>
	<script src="<?php $this->options->themeUrl('/base/vendor/jquery.pjax-2.0.1.min.js'); ?>" type="application/javascript"></script>
	<script src="<?php $this->options->themeUrl('/base/vendor/nprogress-0.2.0.min.js'); ?>" type="application/javascript"></script>
	<?php endif; ?>
		<script>
			window.parseLoveTime = function(value) {
				if (!value) return null;
				var str = String(value).trim();
				if (!str) return null;

				var parts = str.split(/\s+/);
				var datePart = parts[0] || '';
				var timePart = parts[1] || '';
				var datePieces = datePart.split(/[\/-]/);
				if (datePieces.length >= 3) {
					var y = parseInt(datePieces[0], 10);
					var m = parseInt(datePieces[1], 10);
					var d = parseInt(datePieces[2], 10);
					if (!isNaN(y) && !isNaN(m) && !isNaN(d)) {
						var hh = 0;
						var mm = 0;
						var ss = 0;
						if (timePart) {
							var timePieces = timePart.split(':');
							hh = parseInt(timePieces[0] || '0', 10);
							mm = parseInt(timePieces[1] || '0', 10);
							ss = parseInt(timePieces[2] || '0', 10);
							if (isNaN(hh)) hh = 0;
							if (isNaN(mm)) mm = 0;
							if (isNaN(ss)) ss = 0;
						}

						var dt = new Date(y, m - 1, d, hh, mm, ss);
						if (!isNaN(dt.getTime())) return dt;
					}
				}

				var fallback = new Date(str);
				if (!isNaN(fallback.getTime())) return fallback;

				return null;
			};

			window.showSiteRuntime = function() {
				window.setTimeout(window.showSiteRuntime, 1000);
	        var site_runtime = $("#site_runtime");
				if (!site_runtime || !site_runtime.length) return;
				var startStr = <?php echo App::escapeJsString(isset($this->options->lovetime) ? (string)$this->options->lovetime : ''); ?>;
            var start = window.parseLoveTime(startStr);
            if (!start) {
                site_runtime.html("Not set");
                return;
            }
			var now = new Date();
			var T = (now.getTime() - start.getTime());
			var i = 24 * 60 * 60 * 1000;
			var d = T / i;
			var D = Math.floor(d);
			var h = (d - D) * 24;
			var H = Math.floor(h);
			var m = (h - H) * 60;
			var M = Math.floor(m);
			var s = (m - M) * 60;
			var S = Math.floor(s);
			site_runtime.html("<span class=\"bigfontNum\">" + D + "</span> 天 <span class=\"bigfontNum\">" + H + "</span> 小时 <span class=\"bigfontNum\">" + M + "</span> 分钟 <span class=\"bigfontNum\">" + S + "</span> 秒");
		};
			window.showSiteRuntime();

	    $(document).pjax('a[href]:not([target]):not([download]):not([data-pjax=\"false\"])', '#pjax-container', {
	        fragment: '#pjax-container',
	        timeout: 6000
    });
    $(document).on('pjax:send', function() {
        NProgress.start();
    });
    $(document).on('pjax:complete', function() {
        <?php if ($enableCustomCode) : ?>
            <?php
            ob_start();
            $this->options->pjax回调();
            $pjaxCallback = ob_get_clean();
            echo App::escapeInlineScriptSnippet($pjaxCallback);
            ?>
        <?php endif; ?>
        NProgress.done();
    });
</script>
<script src="<?php $this->options->themeUrl('/base/main.js'); ?>"></script>
<?php $this->footer(); ?>
<?php if ($enableCustomCode) : ?><?php $this->options->底部自定义(); ?><?php endif; ?>
</body>

</html>
