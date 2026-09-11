<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
if (!class_exists('App', false)) {
    require_once __DIR__ . '/core/App.php';
}
$blogUrl = App::safeCardLink(App::optionValue('timePageLink', ''), App::siteUrl(true) . 'blog/');
ob_start();
$this->title();
$postTitleText = ob_get_clean();
$postTitle = App::escapeHtml($postTitleText);
$this->need('base/head.php');
$this->need('base/nav.php');
?>

		<div class="list-content mx-auto mt-5">
		    <div id="article" class="list-top">
	            <div class="brave-page-actions">
	                <a class="brave-back-link" href="<?php echo $blogUrl; ?>" data-brave-back>
	                    <span class="brave-back-link__icon" aria-hidden="true">←</span>
	                    <span class="brave-back-link__text">返回</span>
	                </a>
	            </div>
	            <?php
		            $introPostHtml = App::pageIntroHtml(
		                App::optionFlag('introPostEnable', false),
		                App::optionValue('introPostText', '')
		            );
	            ?>
	            <?php if ($introPostHtml !== '') : ?>
	                <h5 class="list-text page-quote"><?php echo $introPostHtml; ?></h5>
	                <hr class="quote-divider">
	            <?php endif; ?>
		        <h1 class="list-text">「<?php echo $postTitle; ?>」</h1>
		        <time datetime="<?php $this->date('c'); ?>" itemprop="datePublished" class="d-block text-center text-muted small mb-4"><?php $this->date('Y-m-d'); ?></time>
		        <article>
		            <?php
		            // 正文也走一遍短代码解析，保证 [loveList] 在普通文章/页面里同样生效
		            // （内容不含 `[loveList` 时 parseShortCode 会原样返回，开销可忽略）。
		            ob_start();
		            $this->content();
		            $contentHtml = ob_get_clean();
		            echo App::parseShortCode($contentHtml);
		            ?>
		        </article>
		        <?php
		        // 分类 / 标签：内核自带的 tags() / category() 会把标签名直接拼进 HTML 且不转义
		        // （标签名入库时未实体化），这里自己遍历并走 App 的转义 helper。
		        ?>
		        <?php if (!empty($this->categories) || !empty($this->tags)) : ?>
		            <div class="post-meta">
		                <?php if (!empty($this->categories)) : ?>
		                    <span class="post-meta__group">
		                        <span class="post-meta__label"><?php _e('分类'); ?>:</span>
		                        <?php foreach ($this->categories as $category) : ?><a href="<?php echo App::escapeUrlAttribute($category['permalink'], true, array('http', 'https')); ?>"><?php echo App::escapeHtml($category['name']); ?></a><?php endforeach; ?>
		                    </span>
		                <?php endif; ?>
		                <?php if (!empty($this->tags)) : ?>
		                    <span class="post-meta__group">
		                        <span class="post-meta__label"><?php _e('标签'); ?>:</span>
		                        <?php foreach ($this->tags as $tag) : ?><a href="<?php echo App::escapeUrlAttribute($tag['permalink'], true, array('http', 'https')); ?>"><?php echo App::escapeHtml($tag['name']); ?></a><?php endforeach; ?>
		                    </span>
		                <?php endif; ?>
		            </div>
		        <?php endif; ?>
		        <?php
		        // 评论区：文案按「文章」口径，且评论关闭时不输出「留言暂已关闭」——
		        // 没有评论功能的文章不该凭空多一行提示。
		        $commentSectionLabel = _t('评论');
		        $commentCountLabels = array(
		            _t('还没有评论'),
		            _t('仅有一条评论'),
		            _t('已有<span class="bigfontNum"> %d </span>条评论'),
		        );
		        $commentSubmitLabel = _t('发表评论');
		        $commentPlaceholder = _t('说点什么吧');
		        $commentTextLabel = _t('评论内容');
		        $commentShowClosedNotice = false;
		        include __DIR__ . '/base/comments.php';
		        ?>
		        <?php
		        // 上下篇导航：直接用内核 theLink 的输出（标题在入库时已由 Typecho 实体化、
		        // 链接由路由生成），交由内核渲染比在主题里重建一份更不容易出错。
		        ob_start();
		        $this->thePrev('%s', '');
		        $prevNavHtml = trim(ob_get_clean());
		        ob_start();
		        $this->theNext('%s', '');
		        $nextNavHtml = trim(ob_get_clean());
		        ?>
		        <?php if ($prevNavHtml !== '' || $nextNavHtml !== '') : ?>
		            <nav class="post-near" aria-label="<?php echo App::escapeHtml(_t('相邻文章')); ?>">
		                <div class="post-near__col post-near__prev">
		                    <span class="post-near__label"><?php _e('上一篇'); ?></span>
		                    <?php echo $prevNavHtml !== '' ? $prevNavHtml : '<span class="post-near__empty">' . App::escapeHtml(_t('没有了')) . '</span>'; ?>
		                </div>
		                <div class="post-near__col post-near__next">
		                    <span class="post-near__label"><?php _e('下一篇'); ?></span>
		                    <?php echo $nextNavHtml !== '' ? $nextNavHtml : '<span class="post-near__empty">' . App::escapeHtml(_t('没有了')) . '</span>'; ?>
		                </div>
		            </nav>
		        <?php endif; ?>
		    </div>
		</div>

<?php $this->need('base/footer.php'); ?>
