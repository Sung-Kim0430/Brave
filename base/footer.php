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
	window.showSiteRuntime = function() {
        var site_runtime = $("#site_runtime");
		if (!site_runtime) return;
		window.setTimeout(window.showSiteRuntime, 1000);
		start = new Date(<?php echo App::escapeJsString(isset($this->options->lovetime) ? (string)$this->options->lovetime : ''); ?>);
		now = new Date();
		T = (now.getTime() - start.getTime());
		i = 24 * 60 * 60 * 1000;
		d = T / i;
		D = Math.floor(d);
		h = (d - D) * 24;
		H = Math.floor(h);
		m = (h - H) * 60;
		M = Math.floor(m);
		s = (m - M) * 60
		S = Math.floor(s);
		site_runtime.html("<span class=\"bigfontNum\">" + D + "</span> 天 <span class=\"bigfontNum\">" + H + "</span> 小时 <span class=\"bigfontNum\">" + M + "</span> 分钟 <span class=\"bigfontNum\">" + S + "</span> 秒");
	};
	showSiteRuntime();

    $(document).pjax('a', '#pjax-container', {
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
