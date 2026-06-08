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

$cspPolicy = '';
$cspHeaderSent = false;
if ($enableCSP) {
    $customCsp = App::optionValue('cspPolicy', '');
    $customCsp = trim($customCsp);
    if ($customCsp !== '') {
        $cspPolicy = $customCsp;
    } else {
        $styleSrc = array("'self'", "'unsafe-inline'");
        $scriptSrc = array("'self'", "'unsafe-inline'");
        $fontSrc = array("'self'", 'data:');

        if ($assetsSource === 'cdn') {
            $styleSrc[] = 'https://cdn.staticfile.org';
            $scriptSrc[] = 'https://cdn.staticfile.org';
        }

        if ($enableRemoteFont) {
            $styleSrc[] = 'https://gfonts.ctfile.com';
            $fontSrc[] = 'https://gfonts.ctfile.com';
        }

        $cspPolicy =
            "default-src 'self'; " .
            "base-uri 'self'; " .
            "object-src 'none'; " .
            "frame-ancestors 'self'; " .
            "form-action 'self'; " .
            "img-src 'self' data: blob: https: http:; " .
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

        // Validate CSP syntax (basic check to prevent injection)
        // Allow alphanumeric, spaces, quotes, hyphens, colons, slashes, dots, asterisks, semicolons, underscores
        if (preg_match('/^[a-z0-9\s\'\-:\/\.\*;_]+$/i', $cspHeader) && strlen($cspHeader) < 2000) {
            header('Content-Security-Policy: ' . $cspHeader);
            // Add additional security headers
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            $cspHeaderSent = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-cn"<?php if ($enableDarkMode) : ?> data-darkmode="1"<?php endif; ?>>
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($enableDarkMode) : ?>
        <meta name="color-scheme" content="light dark">
        <script>
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
	    <?php $this->header(); ?>

    <!-- Critical CSS: Inline above-the-fold styles for faster FCP -->
    <style>
        :root{--brave-brand-1:#ff5162;--brave-brand-2:#673ab7;--brave-bg:#fafbfc;--brave-text:#1f2937;--brave-text-secondary:#4b5563;--brave-card-bg:#fff;--brave-card-border:rgba(0,0,0,.06);--brave-shadow-sm:0 2px 8px rgba(0,0,0,.08);--brave-radius-md:12px;--brave-spacing-md:1rem;--brave-spacing-lg:1.5rem;--brave-transition-fast:0.15s ease;}
        html[data-theme="dark"]{--brave-brand-1:#fb7185;--brave-brand-2:#a78bfa;--brave-bg:#0f1419;--brave-text:#f3f4f6;--brave-text-secondary:#d1d5db;--brave-card-bg:rgba(26,31,46,.85);--brave-card-border:rgba(255,255,255,.08);}
        body{color:var(--brave-text);background:var(--brave-bg);font-family:Inter,'Noto Sans SC','Microsoft YaHei',sans-serif;line-height:1.6;margin:0;padding:0;}
        .navbar{position:sticky;top:0;z-index:1020;background:var(--brave-card-bg);border-bottom:1px solid var(--brave-card-border);box-shadow:var(--brave-shadow-sm);padding:0.5rem 0;}
        .container{max-width:1140px;margin:0 auto;padding:0 1rem;}
        .card{background:var(--brave-card-bg);border:1px solid var(--brave-card-border);border-radius:var(--brave-radius-md);margin-bottom:var(--brave-spacing-lg);}
    </style>

    <!-- Preload and async load full CSS -->
    <link rel="preload" href="<?php $this->options->themeUrl('/base/style.css'); ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="<?php $this->options->themeUrl('/base/style.css'); ?>"></noscript>

    <?php if ($enableRemoteFont) : ?>
        <link href="https://gfonts.ctfile.com/css2?family=Inter:wght@400;700&display=swap"
              rel="stylesheet">
    <?php endif; ?>

    <?php if ($enableCSP && !$cspHeaderSent) : ?>
        <meta http-equiv="Content-Security-Policy" content="<?php echo htmlspecialchars($cspPolicy, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>

    <?php if ($assetsSource === 'cdn') : ?>
        <link href="https://cdn.staticfile.org/bootstrap/4.6.2/css/bootstrap.min.css" type="text/css"
              rel="stylesheet"
              <?php if ($enableSRI) : ?>integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous"<?php endif; ?> />
        <script src="https://cdn.staticfile.org/jquery/3.7.1/jquery.min.js"
                type="application/javascript"
                <?php if ($enableSRI) : ?>integrity="sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs" crossorigin="anonymous"<?php endif; ?>></script>
        <script src="https://cdn.staticfile.org/bootstrap/4.6.2/js/bootstrap.min.js" type="application/javascript"
                <?php if ($enableSRI) : ?>integrity="sha384-+sLIOodYLS7CIrQpBjl+C7nPvqq+FbNUBDunl/OZv93DB7Ln/533i8e/mZXLi/P+" crossorigin="anonymous"<?php endif; ?>></script>
	    <?php else : ?>
	        <link href="<?php $this->options->themeUrl('/base/vendor/bootstrap-4.6.2.min.css'); ?>" type="text/css"
	              rel="stylesheet" />
	        <script src="<?php $this->options->themeUrl('/base/vendor/jquery-3.7.1.min.js'); ?>"
	                type="application/javascript"></script>
	        <script src="<?php $this->options->themeUrl('/base/vendor/bootstrap-4.6.2.min.js'); ?>" type="application/javascript"></script>
	    <?php endif; ?>
	    <?php if ($enableCustomCode) : ?>
		    <?php
		    // WARNING: Custom code is executed without sanitization
		    // Only enable if you trust all admin users with full code execution rights
		    ob_start();
		    $this->options->头部自定义();
		    $customHead = ob_get_clean();

		    // Basic security check: block external scripts from untrusted domains
		    $siteHost = parse_url(App::optionValue('siteUrl', ''), PHP_URL_HOST);
		    $trustedHosts = array('cdn.staticfile.org', $siteHost);
		    $trustedPattern = implode('|', array_map('preg_quote', $trustedHosts));

		    if (preg_match('/<script[^>]*src\s*=\s*["\']?(?!https?:\/\/(' . $trustedPattern . '))/i', $customHead)) {
		        echo '<!-- Custom header blocked: external script from untrusted domain detected -->';
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
