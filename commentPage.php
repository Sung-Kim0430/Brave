<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 祝福板
 * @package custom
 * Editor: Sung Kim
 * Creator: Veen Zhao
 * CreateTime: 2020/9/6 15:38
 * UpdateTime: 2026/1/1 00:54
 */
$this->need('base/head.php');
$this->need('base/nav.php');
$this->comments()->to($comments);
$siteUrlRaw = isset(Helper::options()->siteUrl) ? (string)Helper::options()->siteUrl : '/';
$siteUrlRaw = rtrim($siteUrlRaw, '/') . '/';
$siteUrl = App::escapeUrlAttribute($siteUrlRaw, true, array('http', 'https'));
if ($siteUrl === '') {
    $siteUrl = '/';
}

$introCommentEnabled = isset($this->options->introCommentEnable) && (string)$this->options->introCommentEnable === '1';
$introCommentTextRaw = isset($this->options->introCommentText) ? (string)$this->options->introCommentText : '';
$introCommentTextRaw = trim($introCommentTextRaw);
$introCommentHtml = ($introCommentEnabled && $introCommentTextRaw !== '') ? App::escapeTextWithBr($introCommentTextRaw) : '';
?>
<?php function threadedComments($comments, $options)
{
    $commentClass = '';
    if ($comments->authorId) {
        if ($comments->authorId == $comments->ownerId) {
            $commentClass .= ' comment-by-author';
        } else {
            $commentClass .= ' comment-by-user';
        }
    }
    $commentLevelClass = $comments->levels > 0 ? ' comment-child' : ' comment-parent';
?>
    <div id="li-<?php $comments->theId(); ?>" class=" comment-body<?php if ($comments->levels > 0) {
        echo ' comment-child';
        $comments->levelsAlt(' comment-level-odd', ' comment-level-even');
    } else {
        echo ' comment-parent';
    }
    $comments->alt(' comment-odd', ' comment-even');
    echo $commentClass;
    ?>">

        <div class="commentlist">
            <div class="comment">
                <div id="<?php $comments->theId(); ?>">
                    <div class="comment-body">
                        <div class="comment_author">
                            <span class="name"><?php
                                ob_start();
                                $comments->author();
                                $authorHtml = ob_get_clean();
                                echo App::sanitizeCommentHtml($authorHtml, false);
                                ?></span>
                            <em><?php $comments->date('Y-m-d H:i'); ?></em>
                        </div>
                        <div class="comment-text">
                            <?php
                            ob_start();
                            $comments->content();
                            $commentHtml = ob_get_clean();
                            $allowImages = isset(Helper::options()->commentAllowImg)
                                && (string)Helper::options()->commentAllowImg === '1';
                            echo App::sanitizeCommentHtml($commentHtml, $allowImages);
                            ?>
                        </div>
                    </div>
                    <?php $comments->threadedComments($options); ?>
                </div>
            </div>
        </div>
    </div>
<?php } ?>
<?php if ($this->allow('comment')) : ?>
    <div id="<?php $this->respondId(); ?>" class="respond list-content mx-auto mt-5">
	        <div class="list-top">
	            <div class="brave-page-actions">
	                <a class="brave-back-link" href="<?php echo $siteUrl; ?>" data-brave-back>
	                    <span class="brave-back-link__icon" aria-hidden="true">←</span>
	                    <span class="brave-back-link__text">返回</span>
	                </a>
	            </div>
	            <?php if ($introCommentHtml !== '') : ?>
	                <h5 class="list-text page-quote"><?php echo $introCommentHtml; ?></h5>
	                <hr class="quote-divider">
	            <?php endif; ?>
	            <?php if ($comments->have()) : ?>
	                <h5 class="text-center"><?php $this->commentsNum(_t('尚无祝愿'), _t('仅有一则祝愿'), _t('已收下<span class="bigfontNum"> %d </span>份祝愿')); ?></h5>
	                <?php $comments->listComments(); ?>
	                <?php $comments->pageNav('&laquo; 上一页', '下一页 &raquo;'); ?>
	            <?php endif; ?>
            <form method="post" action="<?php $this->commentUrl() ?>" name="comment-form" id="comment-form" role="form" class="comment-form">
                <?php if ($this->user->hasLogin()) : ?>
                    <p><?php _e('当前身份: '); ?><a href="<?php $this->options->profileUrl(); ?>"><?php $this->user->screenName(); ?></a>.
                        <a href="<?php $this->options->logoutUrl(); ?>" title="Logout"><?php _e('退出登录'); ?> &raquo;</a>
                    </p>
                <?php else : ?>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <input type="text" name="author" id="author" class="form-control" placeholder="<?php _e('你的称呼*'); ?>" value="<?php $this->remember('author'); ?>" required />
                        </div>
                        <div class="form-group col-md-4">
                            <input type="email" name="mail" id="mail" class="form-control" placeholder="<?php _e('邮箱*'); ?>" value="<?php $this->remember('mail'); ?>" <?php if ($this->options->commentsRequireMail) : ?> required<?php endif; ?> />
                        </div>
                        <div class="form-group col-md-4">
                            <input type="url" name="url" id="url" class="form-control" placeholder="<?php _e('网站/博客（可选）'); ?>" value="<?php $this->remember('url'); ?>" />
                        </div>
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <textarea rows="3" cols="50" name="text" id="textarea" class="form-control" placeholder="<?php _e('把祝愿写给我们'); ?>" required><?php $this->remember('text'); ?></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" class="float-right btn btn-outline-danger"><?php _e('送出祝愿'); ?></button>
                </div>
            </form>
        </div>
    </div>
<?php else : ?>
    <div class="list-content mx-auto mt-5">
	        <div class="list-top">
	            <div class="brave-page-actions">
	                <a class="brave-back-link" href="<?php echo $siteUrl; ?>" data-brave-back>
	                    <span class="brave-back-link__icon" aria-hidden="true">←</span>
	                    <span class="brave-back-link__text">返回</span>
	                </a>
	            </div>
	            <?php if ($introCommentHtml !== '') : ?>
	                <h5 class="list-text page-quote"><?php echo $introCommentHtml; ?></h5>
	                <hr class="quote-divider">
	            <?php endif; ?>
	            <h3 class="text-center"><?php _e('留言暂已关闭'); ?></h3>
	        </div>
	    </div>
	<?php endif; ?>

<?php $this->need('base/footer.php'); ?>
