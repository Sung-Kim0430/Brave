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
        $cspHeader = preg_replace('/[\\x00-\\x1F\\x7F]+/', ' ', $cspPolicy);
        header('Content-Security-Policy: ' . $cspHeader);
        $cspHeaderSent = true;
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
    <link rel="stylesheet" href="<?php $this->options->themeUrl('/base/style.css'); ?>">
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
		    <?php $this->options->头部自定义(); ?>
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
