<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php $this->archiveTitle(array(
            'category' => _t('分类 %s 下的文章'),
            'search' => _t('包含关键字 %s 的文章'),
            'tag' => _t('标签 %s 下的文章'),
            'author' => _t('%s 发布的文章')
        ), '', ' - '); ?><?php $this->options->title(); ?></title>
    <?php $this->header(); ?>
    <link rel="stylesheet" href="<?php $this->options->themeUrl('/base/style.css'); ?>">
    <link href="https://gfonts.ctfile.com/css2?family=Inter:wght@400;700&display=swap"
          rel="stylesheet">
    <link href="https://cdn.staticfile.org/bootstrap/4.6.1/css/bootstrap.min.css" type="text/css"
          rel="stylesheet"
          onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css';" />
    <script src="https://cdn.staticfile.org/jquery/3.6.0/jquery.min.js"
            type="application/javascript"></script>
    <script>
        window.jQuery || document.write('<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js" type="application/javascript"><\\/script>');
    </script>
    <script src="https://cdn.staticfile.org/bootstrap/4.6.1/js/bootstrap.min.js" type="application/javascript"></script>
    <script>
        if (window.jQuery && (!window.jQuery.fn || !window.jQuery.fn.modal)) {
            document.write('<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.min.js" type="application/javascript"><\\/script>');
        }
    </script>
    <?php $this->options->头部自定义(); ?>
</head>
<style>
    <?php $this->options->Css自定义(); ?>
</style>
<body>
