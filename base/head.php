<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$assetsSource = App::optionChoice('assetsSource', 'local', array('local', 'cdn'));
$cdnEnableSRI = App::optionFlag('cdnEnableSRI', true);
$enableSRI = ($assetsSource === 'cdn' && $cdnEnableSRI);
$enableCSP = App::optionFlag('enableCSP', true);

$fontSource = App::optionChoice('fontSource', 'local', array('local', 'remote'));
$enableRemoteFont = ($fontSource === 'remote');
$enableCustomCode = App::optionFlag('enableCustomCode', false);
$enableDarkMode = App::optionFlag('enableDarkMode', false);
// 自定义代码可能在 head/footer 立即调用 jQuery；显式启用该兼容能力时保留同步依赖。
$scriptLoading = $enableCustomCode ? '' : ' defer';

$cspPolicy = '';
$cspHeaderSent = false;
$cspHeaderRejected = false;
if ($enableCSP) {
    $customCsp = App::optionValue('cspPolicy', '');
    // 站长自定义严格策略可写 'nonce-{nonce}'；替换为本响应随机值，禁止静态 nonce 复用。
    $customCsp = str_replace('{nonce}', App::scriptNonce(), trim($customCsp));
    if ($customCsp !== '' && App::validCspPolicy($customCsp)) {
        $cspPolicy = $customCsp;
    } else {
        // 非法自定义策略必须回到有效默认策略；不能把被拒绝的值再塞进 meta 造成静默失防。
        $cspHeaderRejected = ($customCsp !== '');
        $styleSrc = array("'self'", "'unsafe-inline'");
        // 默认禁止 inline onclick/onerror。主题自身内联脚本逐个携带随机 nonce。
        // 开启高权限自定义代码时保留原有兼容行为，这一开关只适合可信管理员。
        $scriptSrc = array("'self'", $enableCustomCode ? "'unsafe-inline'" : "'nonce-" . App::scriptNonce() . "'");
        $fontSrc = array("'self'", 'data:');

        if ($assetsSource === 'cdn') {
            $styleSrc[] = 'https://cdn.jsdelivr.net';
            $scriptSrc[] = 'https://cdn.jsdelivr.net';
        }

        if ($enableRemoteFont) {
            $styleSrc[] = 'https://gfonts.ctfile.com';
            $fontSrc[] = 'https://gfonts.ctfile.com';
        }

        // HTTPS 站点不再放行 http: —— 否则混合内容图片会被静默允许（浏览器只警告不拦截），
        // CSP 的协议约束等于失效。HTTP 站点保留 http: 以兼容旧的外链图片。
        $imgSrc = "'self' data: blob: https:";
        if (!App::isHttpsRequest()) {
            $imgSrc .= ' http:';
        }

        $cspPolicy =
            "default-src 'self'; " .
            "base-uri 'self'; " .
            "object-src 'none'; " .
            "frame-ancestors 'self'; " .
            "form-action 'self'; " .
            "img-src " . $imgSrc . "; " .
            "font-src " . implode(' ', $fontSrc) . "; " .
            "style-src " . implode(' ', $styleSrc) . "; " .
            "script-src " . implode(' ', $scriptSrc) . "; " .
            "connect-src 'self';";
    }

    // Prefer response headers over meta tags when possible.
    if ($cspPolicy !== '' && !headers_sent()) {
        // Remove control chars AND newlines to prevent header injection
        $cspHeader = preg_replace('/[\x00-\x1F\x7F\r\n]+/', ' ', $cspPolicy);
        $cspHeader = trim($cspHeader);

        // Header 注入防护：换行已被清除，这里只需再排除能提前结束 meta/script 上下文的 < >。
        // 注意不要用「白名单字符集」校验：CSP 允许 hash/nonce（如 'sha256-xxx+/='）、
        // report-uri、逗号、@ 等字符，过严会把合法策略判为非法并静默降级。
        if (strpbrk($cspHeader, '<>') === false && strlen($cspHeader) < 4000) {
            header('Content-Security-Policy: ' . $cspHeader);
            $cspHeaderSent = true;
        } else {
            $cspHeaderRejected = true;
        }
    }
}
// 这些响应头不依赖 CSP 开关，关闭 CSP 不应同时关闭 MIME/Referer/点击劫持防护。
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
?>
<!DOCTYPE html>
<html lang="<?php echo App::escapeHtml(App::htmlLang()); ?>"<?php if ($enableDarkMode) : ?> data-darkmode="1"<?php endif; ?>>
<head>
    <meta charset="<?php echo App::escapeHtml(App::optionValue('charset', 'UTF-8')); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($cspHeaderRejected) : ?>
        <!-- 自定义 CSP 含非法字符或过长，已使用内置默认策略。 -->
    <?php endif; ?>
    <?php if ($enableCSP && !$cspHeaderSent) : ?>
        <!-- CSP 响应头不可用时回退 meta；不支持 frame-ancestors / report-uri / sandbox。 -->
        <?php /* 必须早于脚本、样式和内核 header()：meta CSP 不会追溯限制已加载的资源。 */ ?>
        <meta http-equiv="Content-Security-Policy" content="<?php echo App::escapeHtml($cspPolicy); ?>">
    <?php endif; ?>
    <?php if ($enableDarkMode) : ?>
        <meta name="color-scheme" content="light dark">
        <script nonce="<?php echo App::escapeHtml(App::scriptNonce()); ?>">
            (function() {
                try {
                    var key = 'brave-theme';
                    var saved = localStorage.getItem(key);
                    var mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
                    var theme = (saved === 'dark' || saved === 'light')
                        ? saved
                        : (mql && mql.matches ? 'dark' : 'light');
                    document.documentElement.setAttribute('data-theme', theme);
                } catch (e) {}
            })();
        </script>
    <?php endif; ?>
    <?php
    ob_start();
    $this->archiveTitle(array(
        'category' => _t('「%s」里的篇章'),
        'search' => _t('含「%s」的篇章'),
        'tag' => _t('关于「%s」的篇章'),
        'author' => _t('出自 %s 的篇章')
    ), '', ' - ');
    $archiveTitleText = ob_get_clean();
    ?>
    <title><?php echo App::escapeHtml($archiveTitleText); ?><?php echo App::escapeHtml(App::optionValue('title', '')); ?></title>
	    <?php
        // 评论令牌由表单携带，回复交互由外部 comments.js 提供，避免原生内联脚本在
        // PJAX 后不初始化，以及 nonce CSP 下被浏览器阻止。其余元数据和插件钩子保留。
        $this->header('antiSpam=&commentReply=');
        ?>

    <!-- Critical CSS: Inline above-the-fold styles for faster FCP -->
    <style>
        :root{--brave-brand-1:#ff5162;--brave-brand-2:#673ab7;--brave-bg:#fafbfc;--brave-text:#1f2937;--brave-text-secondary:#4b5563;--brave-card-bg:#fff;--brave-card-border:rgba(0,0,0,.06);--brave-shadow-sm:0 2px 8px rgba(0,0,0,.08);--brave-radius-md:12px;--brave-spacing-md:1rem;--brave-spacing-lg:1.5rem;--brave-transition-fast:0.15s ease;}
        html[data-theme="dark"]{--brave-brand-1:#fb7185;--brave-brand-2:#a78bfa;--brave-bg:#0f1419;--brave-text:#f3f4f6;--brave-text-secondary:#d1d5db;--brave-card-bg:rgba(26,31,46,.85);--brave-card-border:rgba(255,255,255,.08);}
        body{color:var(--brave-text);background:var(--brave-bg);font-family:Inter,'Noto Sans SC','Microsoft YaHei',sans-serif;line-height:1.6;margin:0;padding:0;}
        .navbar{position:sticky;top:0;z-index:1020;background:var(--brave-card-bg);border-bottom:1px solid var(--brave-card-border);box-shadow:var(--brave-shadow-sm);padding:0.5rem 0;}
        .container{max-width:1140px;margin:0 auto;padding:0 1rem;}
        .card{background:var(--brave-card-bg);border:1px solid var(--brave-card-border);border-radius:var(--brave-radius-md);margin-bottom:var(--brave-spacing-lg);}
    </style>

    <?php if ($enableRemoteFont) : ?>
        <?php /* 只有选择远程字体才建立连接；本地模式不能产生字体域名请求。
                 print 样式不会阻塞首屏，由 main.js 的外部事件监听器在加载后激活。
                 CSS 的 display=swap 让文字先使用系统字体，避免字体下载期间不可见。 */ ?>
        <link rel="preconnect" href="https://gfonts.ctfile.com" crossorigin>
        <link href="https://gfonts.ctfile.com/css2?family=Inter:wght@400;700&display=swap"
              id="brave-remote-font" rel="stylesheet" media="print">
    <?php endif; ?>

    <?php if ($assetsSource === 'cdn') : ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" type="text/css"
              rel="stylesheet"
              <?php if ($enableSRI) : ?>integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous"<?php endif; ?> />
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"
                <?php echo $scriptLoading; ?>
                type="application/javascript"
                <?php if ($enableSRI) : ?>integrity="sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs" crossorigin="anonymous"<?php endif; ?>></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" type="application/javascript"
                <?php echo $scriptLoading; ?>
                <?php if ($enableSRI) : ?>integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"<?php endif; ?>></script>
	    <?php else : ?>
	        <link href="<?php $this->options->themeUrl('/base/vendor/bootstrap-4.6.2.min.css'); ?>" type="text/css"
	              rel="stylesheet" />
	        <script src="<?php $this->options->themeUrl('/base/vendor/jquery-3.7.1.min.js'); ?>"
	                <?php echo $scriptLoading; ?> type="application/javascript"></script>
	        <script src="<?php $this->options->themeUrl('/base/vendor/bootstrap-4.6.2.bundle.min.js'); ?>"<?php echo $scriptLoading; ?> type="application/javascript"></script>
	    <?php endif; ?>
        <?php /* 主题样式必须在 Bootstrap 后，确保最终层叠顺序稳定。
                 普通 stylesheet 可被预加载扫描器并行发现；不依赖被 CSP 禁止的 onload 属性。 */ ?>
        <link rel="stylesheet" href="<?php $this->options->themeUrl('/base/style.css'); ?>">
        <?php $heroPreload = App::escapeUrlAttribute(App::optionValue('heroimg', '')); ?>
        <?php if ($heroPreload !== '') : ?>
            <?php /* hero 是 CSS 背景图，浏览器原本须等待 body 样式才能发现；提前提示可缩短 LCP 链路。 */ ?>
            <link rel="preload" as="image" href="<?php echo $heroPreload; ?>" fetchpriority="high">
        <?php endif; ?>
	    <?php if ($enableCustomCode) : ?>
		    <?php
		    // WARNING: Custom code is executed without sanitization
		    // Only enable if you trust all admin users with full code execution rights
		    ob_start();
		    $this->options->头部自定义();
		    $customHead = ob_get_clean();

		    // 防误配检查：外链 <script src> 的域名必须在白名单内（同源相对路径始终放行）。
		    // 这不是安全边界（内联 <script> 仍会放行），仅用于避免在主题设置里误粘贴第三方脚本。
		    // 历史实现用负向先行做域名校验，因 `["\']?` 可选导致白名单内脚本也被阻断，已修正。
		    $customCodeTrustedHosts = array(
		        'cdn.staticfile.org',
                'cdn.jsdelivr.net',
		        (string)parse_url(App::optionValue('siteUrl', ''), PHP_URL_HOST)
		    );
		    $customHeadBlocked = App::findUntrustedScriptHosts($customHead, $customCodeTrustedHosts);

		    if (!empty($customHeadBlocked)) {
		        echo '<!-- 头部自定义已阻止：检测到白名单外的外链脚本域名 '
		            . App::escapeHtml(App::describeUntrustedHosts($customHeadBlocked)) . ' -->';
		    } else {
		        echo $customHead;
		    }
		    ?>
	    <?php endif; ?>
<?php if ($enableCustomCode) : ?>
<style>
    <?php
    ob_start();
    $this->options->Css自定义();
    $customCss = ob_get_clean();
    echo App::guardInlineStyleSnippet($customCss);
    ?>
</style>
<?php endif; ?>
	</head>
<body>
<!-- PJAX加载状态屏幕阅读器反馈 -->
<div id="pjax-status" class="sr-only" role="status" aria-live="polite" aria-atomic="true"></div>
