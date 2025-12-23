<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
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
    <link href="https://gfonts.ctfile.com/css2?family=Inter:wght@400;700&display=swap"
          rel="stylesheet">
    <link href="https://cdn.staticfile.org/bootstrap/4.6.2/css/bootstrap.min.css" type="text/css"
          rel="stylesheet"
          onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css';" />
    <script src="https://cdn.staticfile.org/jquery/3.7.1/jquery.min.js"
            type="application/javascript"></script>
    <script>
        window.jQuery || document.write('<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" type="application/javascript"><\\/script>');
    </script>
    <script src="https://cdn.staticfile.org/bootstrap/4.6.2/js/bootstrap.min.js" type="application/javascript"></script>
    <script>
        if (window.jQuery && (!window.jQuery.fn || !window.jQuery.fn.modal)) {
            document.write('<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js" type="application/javascript"><\\/script>');
        }
    </script>
    <?php $this->options->头部自定义(); ?>
</head>
<style>
    <?php $this->options->Css自定义(); ?>
</style>
<body>
