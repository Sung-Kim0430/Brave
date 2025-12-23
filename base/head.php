<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$assetsSource = (isset(Helper::options()->assetsSource) ? (string)Helper::options()->assetsSource : 'local');
$cdnEnableSRI = !isset(Helper::options()->cdnEnableSRI) || (string)Helper::options()->cdnEnableSRI !== '0';
$cdnEnableCSP = !isset(Helper::options()->cdnEnableCSP) || (string)Helper::options()->cdnEnableCSP !== '0';
$enableSRI = ($assetsSource === 'cdn' && $cdnEnableSRI);
$enableCSP = ($assetsSource === 'cdn' && $cdnEnableCSP);

$fontSource = isset(Helper::options()->fontSource) ? (string)Helper::options()->fontSource : 'local';
$enableRemoteFont = ($fontSource === 'remote');
$enableCustomCode = !isset(Helper::options()->enableCustomCode) || (string)Helper::options()->enableCustomCode !== '0';

$cspPolicy = '';
if ($enableCSP) {
    $customCsp = isset(Helper::options()->cspPolicy) ? (string)Helper::options()->cspPolicy : '';
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
        $cspHeader = str_replace(array("\r", "\n"), ' ', $cspPolicy);
        header('Content-Security-Policy: ' . $cspHeader);
    }
}
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php $this->archiveTitle(array(
            'category' => _t('「%s」里的篇章'),
            'search' => _t('含「%s」的篇章'),
            'tag' => _t('关于「%s」的篇章'),
            'author' => _t('出自 %s 的篇章')
        ), '', ' - '); ?><?php $this->options->title(); ?></title>
	    <?php $this->header(); ?>
    <link rel="stylesheet" href="<?php $this->options->themeUrl('/base/style.css'); ?>">
    <?php if ($enableRemoteFont) : ?>
        <link href="https://gfonts.ctfile.com/css2?family=Inter:wght@400;700&display=swap"
              rel="stylesheet">
    <?php endif; ?>

    <?php if ($enableCSP) : ?>
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
	</head>
<?php if ($enableCustomCode) : ?>
<style>
    <?php
    ob_start();
    $this->options->Css自定义();
    $customCss = ob_get_clean();
    echo App::escapeInlineStyleSnippet($customCss);
    ?>
</style>
<?php endif; ?>
<body>
