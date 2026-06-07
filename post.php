<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
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
		        <h5 class="list-text">「<?php echo $postTitle; ?>」</h5>
		        <time datetime="<?php $this->date('c'); ?>" itemprop="datePublished" class="d-block text-center text-muted small mb-4"><?php $this->date('Y-m-d'); ?></time>
		        <article>
		            <?php $this->content(); ?>
		        </article>
		    </div>
		</div>

<?php $this->need('base/footer.php'); ?>
