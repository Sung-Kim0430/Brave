<?php

/**
 * 勇敢爱 - Typecho情侣主题
 * @package     Brave
 * @author      Sung Kim
 * @creator     Veen Zhao
 * @version     1.2
 * @link        https://blog.zwying.com
 * @update      2026/6/7 23:05
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
if (!class_exists('App', false)) {
    require_once __DIR__ . '/core/App.php';
}
$siteUrl = App::siteUrl(true);
$this->need('base/head.php');
$this->need('base/nav.php');
?>

<div class="list-content mx-auto mt-5">
	    <div class="list-top">
	        <div class="brave-page-actions">
	            <a class="brave-back-link" href="<?php echo $siteUrl; ?>" data-brave-back>
	                <span class="brave-back-link__icon" aria-hidden="true">←</span>
	                <span class="brave-back-link__text">返回</span>
	            </a>
	        </div>
	        <?php
	        $introIndexHtml = App::pageIntroHtml(
	            App::optionFlag('introIndexEnable', true),
	            App::optionValue('introIndexText', ''),
	            "你要是愿意，我就永远爱你；你要是不愿意，我就永远相思。\n我活在世上，无非想要明白些道理，遇见些有趣的事。倘能如我所愿，我的一生就算成功。"
	        );
	        ?>
	        <?php if ($introIndexHtml !== '') : ?>
	            <h5 class="list-text page-quote"><?php echo $introIndexHtml; ?></h5>
	            <hr class="quote-divider">
	        <?php endif; ?>
	        <?php if ($this->have()) : ?>
	            <?php while ($this->next()) : ?>
	                <?php
	                ob_start();
	                $this->permalink();
	                $postPermalinkText = ob_get_clean();
	                $postPermalink = App::escapeUrlAttribute($postPermalinkText, true, array('http', 'https'));
	                ob_start();
	                $this->title();
	                $postTitleText = ob_get_clean();
	                $postTitle = App::escapeHtml($postTitleText);
	                ?>
	                <article class="post post-item text-center">
	                    <h4 class="post-title" itemprop="name headline"><a class=" list-wbc" itemprop="url" href="<?php echo $postPermalink; ?>"><?php echo $postTitle; ?></a></h4>
	                    <time datetime="<?php $this->date('c'); ?>" itemprop="datePublished"><?php $this->date('Y-m-d'); ?></time>
	                </article>
            <?php endwhile; ?>
        <?php else : ?>
            <article class="post">
                <h2 class="post-title"><?php _e('没有找到内容'); ?></h2>
            </article>
        <?php endif; ?>
        <?php $this->pageNav('&laquo; 上一页', '下一页 &raquo;'); ?>
    </div>
</div>

<?php $this->need('base/footer.php'); ?>
