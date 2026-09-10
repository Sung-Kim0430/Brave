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
		    </div>
		</div>

<?php $this->need('base/footer.php'); ?>
