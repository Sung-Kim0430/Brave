<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$siteUrlRaw = isset(Helper::options()->siteUrl) ? (string)Helper::options()->siteUrl : '/';
$siteUrlRaw = rtrim($siteUrlRaw, '/') . '/';
$blogUrl = App::escapeUrlAttribute($siteUrlRaw . 'blog/', true, array('http', 'https'));
if ($blogUrl === '') {
    $blogUrl = '/index.php/blog/';
}
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
	        <h5 class="list-text">「<?php $this->title() ?>」</h5>
	        <time datetime="<?php $this->date('c'); ?>" itemprop="datePublished" class="d-block text-center text-muted small mb-4"><?php $this->date('Y-m-d'); ?></time>
	        <article>
	            <?php $this->content(); ?>
	        </article>
	    </div>
	</div>

<?php $this->need('base/footer.php'); ?>
