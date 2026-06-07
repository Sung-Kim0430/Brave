<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $siteTitle = App::escapeHtml(App::optionValue('title', '')); ?>
</div>
<footer class="brave-footer">
	<div class="brave-footer-content">
		<p class="brave-footer-copyright">©<?php echo $siteTitle; ?></p>
		<p class="brave-footer-powered">
			Powered by <a href="https://typecho.org" target="_blank" rel="noopener noreferrer">Typecho</a> 
			<span class="brave-footer-divider">·</span> 
			<a href="https://github.com/Sung-Kim0430/Brave" target="_blank" rel="noopener noreferrer">Brave Theme</a>
		</p>
	</div>
</footer>
<?php $assetsSource = App::optionChoice('assetsSource', 'local', array('local', 'cdn')); ?>
<?php $cdnEnableSRI = App::optionFlag('cdnEnableSRI', true); ?>
<?php $enableSRI = ($assetsSource === 'cdn' && $cdnEnableSRI); ?>
<?php $enableCustomCode = App::optionFlag('enableCustomCode', false); ?>
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

				var configuredDate = /^(\d{1,4})[\/-](\d{1,2})[\/-](\d{1,2})(?:[\sT]+(\d{1,2})(?::(\d{1,2})(?::(\d{1,2}))?)?)?$/.exec(str);
				if (configuredDate) {
					var y = Number(configuredDate[1]);
					var m = Number(configuredDate[2]);
					var d = Number(configuredDate[3]);
					var hh = configuredDate[4] !== undefined ? Number(configuredDate[4]) : 0;
					var mm = configuredDate[5] !== undefined ? Number(configuredDate[5]) : 0;
					var ss = configuredDate[6] !== undefined ? Number(configuredDate[6]) : 0;

					if (
						y < 1 || y > 9999 ||
						m < 1 || m > 12 ||
						d < 1 || d > 31 ||
						hh < 0 || hh > 23 ||
						mm < 0 || mm > 59 ||
						ss < 0 || ss > 59
					) {
						return null;
					}

					var dt = new Date(y, m - 1, d, hh, mm, ss);
					if (
						!isNaN(dt.getTime()) &&
						dt.getFullYear() === y &&
						dt.getMonth() === m - 1 &&
						dt.getDate() === d &&
						dt.getHours() === hh &&
						dt.getMinutes() === mm &&
						dt.getSeconds() === ss
					) {
						return dt;
					}
					return null;
				}

				if (/^\d{1,4}[\/-]\d{1,2}[\/-]/.test(str)) {
					return null;
				}

				var fallback = new Date(str);
				if (!isNaN(fallback.getTime())) return fallback;

				return null;
			};

				(function() {
					var startStr = <?php echo App::escapeJsString(App::optionValue('lovetime', '')); ?>;
					var hasStartValue = String(startStr || '').trim() !== '';
					var start = window.parseLoveTime(startStr);

					function clearRuntimeNode(node) {
						while (node.firstChild) {
							node.removeChild(node.firstChild);
						}
					}

					function appendRuntimeSegment(node, value, label) {
						var num = document.createElement('span');
						num.className = 'bigfontNum';
						num.textContent = String(value);
						node.appendChild(num);
						node.appendChild(document.createTextNode(label));
					}

					window.showSiteRuntime = function() {
						var siteRuntime = document.getElementById('site_runtime');
						if (!siteRuntime) return false;
						if (!start) {
							siteRuntime.textContent = hasStartValue ? "日期无效" : "未设置";
							return false;
						}

						var now = new Date();
						var T = (now.getTime() - start.getTime());
						if (T < 0) {
							siteRuntime.textContent = "未开始";
							return true;
						}

						var i = 24 * 60 * 60 * 1000;
						var d = T / i;
						var D = Math.floor(d);
						var h = (d - D) * 24;
						var H = Math.floor(h);
						var m = (h - H) * 60;
						var M = Math.floor(m);
						var s = (m - M) * 60;
						var S = Math.floor(s);
						clearRuntimeNode(siteRuntime);
						appendRuntimeSegment(siteRuntime, D, " 天 ");
						appendRuntimeSegment(siteRuntime, H, " 小时 ");
						appendRuntimeSegment(siteRuntime, M, " 分钟 ");
						appendRuntimeSegment(siteRuntime, S, " 秒");
						return true;
					};

					window.BraveTheme = window.BraveTheme || {};
					window.BraveTheme.ensureSiteRuntimeTicker = function() {
						try {
							if (window.BraveTheme._siteRuntimeTimer) {
								window.clearInterval(window.BraveTheme._siteRuntimeTimer);
								window.BraveTheme._siteRuntimeTimer = null;
							}
						} catch (e) {}

						if (window.showSiteRuntime && window.showSiteRuntime()) {
							try {
								window.BraveTheme._siteRuntimeTimer = window.setInterval(window.showSiteRuntime, 1000);
							} catch (e) {}
						}
					};

					window.BraveTheme.ensureSiteRuntimeTicker();
				})();

			    if (window.NProgress) {
			        NProgress.configure({ showSpinner: false, trickleSpeed: 120 });
			    }

			    function getSafeSameOriginUrl(url) {
			        if (!url) return '';
			        var parser = document.createElement('a');
			        parser.href = String(url);
			        if (parser.protocol !== window.location.protocol) return '';
			        if (parser.hostname !== window.location.hostname) return '';
			        if (parser.port !== window.location.port) return '';
			        if (parser.protocol !== 'http:' && parser.protocol !== 'https:') return '';
			        return parser.href;
			    }

			    var pjaxLinkSelector = 'a[href]' +
			        ':not([target])' +
			        ':not([download])' +
		        ':not([data-pjax=\"false\"])' +
		        ':not([href^=\"#\"])' +
		        ':not([href^=\"mailto:\"])' +
		        ':not([href^=\"tel:\"])';

		    if (window.jQuery && window.jQuery.pjax) {
		        $(document).pjax(pjaxLinkSelector, '#pjax-container', {
		            fragment: '#pjax-container',
		            timeout: 6000,
		            scrollTo: 0
		        });
		        $(document).on('pjax:send', function() {
		            $('body').addClass('is-pjax-loading');
		            if (window.NProgress) NProgress.start();
		        });
			        $(document).on('pjax:complete', function() {
			            $('body').removeClass('is-pjax-loading');
			            if (window.BraveTheme && window.BraveTheme.ensureSiteRuntimeTicker) {
			                window.BraveTheme.ensureSiteRuntimeTicker();
			            }
			            <?php if ($enableCustomCode) : ?>
			                <?php
			                ob_start();
			                $this->options->pjax回调();
		                $pjaxCallback = ob_get_clean();
			                echo App::guardInlineScriptSnippet($pjaxCallback);
		                ?>
		            <?php endif; ?>
		            if (window.NProgress) NProgress.done();
		        });
			        $(document).on('pjax:error', function(event, xhr, textStatus, error, options) {
			            $('body').removeClass('is-pjax-loading');
			            var fallbackUrl = getSafeSameOriginUrl(options && options.url);
			            if (fallbackUrl) {
			                window.location.assign(fallbackUrl);
			                return false;
			            }
			            return true;
		        });
		    }
</script>
<script src="<?php $this->options->themeUrl('/base/main.js'); ?>"></script>
<?php $this->footer(); ?>
<?php if ($enableCustomCode) : ?><?php $this->options->底部自定义(); ?><?php endif; ?>
</body>

</html>
