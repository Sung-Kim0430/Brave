</div>
<div class="p-5 text-center ">
	<h6>©<?php $this->options->title() ?></h6>
	<p class="h6"> Powered by <a href="http://typecho.org" target="_blank">Typecho</a> and <a href="https://github.com/Sung-Kim0430/Brave" target="_blank">Brave-Theme</a></p>
</div>
<script>
	window.jQuery || document.write('<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" type="application/javascript"><\\/script>');
</script>
<script src="https://cdn.staticfile.org/jquery.pjax/2.0.1/jquery.pjax.min.js" type="application/javascript"></script>
<script>
	if (window.jQuery && (!window.jQuery.fn || !window.jQuery.fn.pjax)) {
		document.write('<script src="https://cdn.jsdelivr.net/npm/jquery-pjax@2.0.1/jquery.pjax.min.js" type="application/javascript"><\\/script>');
	}
</script>
<script src="https://cdn.staticfile.org/nprogress/0.2.0/nprogress.min.js" type="application/javascript"></script>
<script>
	window.NProgress || document.write('<script src="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.min.js" type="application/javascript"><\\/script>');
</script>
<script>
	window.showSiteRuntime = function() {
        var site_runtime = $("#site_runtime");
		if (!site_runtime) return;
		window.setTimeout("showSiteRuntime()", 1000);
		start = new Date("<?php $this->options->lovetime(); ?>");
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
		site_runtime.html("第 <span class=\"bigfontNum\">" + D + "</span> 天 <span class=\"bigfontNum\">" + H + "</span> 小时 <span class=\"bigfontNum\">" + M + "</span> 分钟 <span class=\"bigfontNum\">" + S + "</span> 秒");
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
        <?php $this->options->pjax回调(); ?>
        NProgress.done();
    });
</script>
<script src="<?php $this->options->themeUrl('/base/main.js'); ?>"></script>
<?php $this->footer(); ?>
<?php $this->options->底部自定义(); ?>
</body>

</html>
