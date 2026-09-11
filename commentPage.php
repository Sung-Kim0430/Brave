<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
if (!class_exists('App', false)) {
    require_once __DIR__ . '/core/App.php';
}

/**
 * 祝福板
 * @package custom
 * Editor: Sung Kim
 * Creator: Veen Zhao
 * CreateTime: 2020/9/6 15:38
 * UpdateTime: 2026/9/11
 */
$this->need('base/head.php');
$this->need('base/nav.php');
$siteUrl = App::siteUrl(true);

$introCommentHtml = App::pageIntroHtml(
    App::optionFlag('introCommentEnable', false),
    App::optionValue('introCommentText', '')
);
?>
<div class="list-content mx-auto mt-5">
    <div class="list-top">
        <div class="brave-page-actions">
            <a class="brave-back-link" href="<?php echo $siteUrl; ?>" data-brave-back>
                <span class="brave-back-link__icon" aria-hidden="true">←</span>
                <span class="brave-back-link__text">返回</span>
            </a>
        </div>
        <?php if ($introCommentHtml !== '') : ?>
            <h1 class="list-text page-quote"><?php echo $introCommentHtml; ?></h1>
            <hr class="quote-divider">
        <?php else : ?>
            <h1 class="sr-only"><?php _e('祝福墙'); ?></h1>
        <?php endif; ?>
        <?php /* 评论列表 / 分页 / 表单都在公用区块里，文案不传即用「祝福板」默认值 */ ?>
        <?php include __DIR__ . '/base/comments.php'; ?>
    </div>
</div>

<?php $this->need('base/footer.php'); ?>
