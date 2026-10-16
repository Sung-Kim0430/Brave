<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

// Typecho 1.3 的普通独立页面优先寻找 page.php；缺少时可能回退列表模板。
// 复用文章正文和评论布局，由内核 content()/allow() 保持密码保护与评论权限边界。
require __DIR__ . '/post.php';
