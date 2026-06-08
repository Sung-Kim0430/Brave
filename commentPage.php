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
 * UpdateTime: 2026/6/8
 */
$this->need('base/head.php');
$this->need('base/nav.php');
$this->comments()->to($comments);
$siteUrl = App::siteUrl(true);

$introCommentHtml = App::pageIntroHtml(
    App::optionFlag('introCommentEnable', false),
    App::optionValue('introCommentText', '')
);
$commentRespondId = App::escapeHtml($this->respondId);
$commentFormAction = App::escapeUrlAttribute($this->commentUrl, true, array('http', 'https'));
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
                                echo App::sanitizeCommentAuthorHtml($authorHtml);
                                ?></span>
                            <em><?php $comments->date('Y-m-d H:i'); ?></em>
                        </div>
                        <div class="comment-text">
                            <?php
                            ob_start();
                            $comments->content();
                            $commentHtml = ob_get_clean();
                            $allowImages = App::optionFlag('commentAllowImg', false);
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
        <?php if ($comments->have()) : ?>
            <h5 class="text-center"><?php $this->commentsNum(_t('尚无祝愿'), _t('仅有一则祝愿'), _t('已收下<span class="bigfontNum"> %d </span>份祝愿')); ?></h5>
            <?php $comments->listComments(); ?>
            <?php $comments->pageNav('&laquo; 上一页', '下一页 &raquo;'); ?>
        <?php endif; ?>
        <?php if ($this->allow('comment')) : ?>
            <div id="<?php echo $commentRespondId; ?>" class="respond">
                <form method="post" action="<?php echo $commentFormAction; ?>" name="comment-form" id="comment-form" role="form" class="comment-form">
                    <?php if ($this->user->hasLogin()) : ?>
                        <?php
                        $profileUrl = App::safeCardLink(App::optionValue('profileUrl', ''), '#');
                        $logoutUrl = App::safeCardLink(App::optionValue('logoutUrl', ''), '#');
                        $screenName = App::escapeHtml($this->user->screenName);
                        ?>
                        <p><?php _e('当前身份: '); ?><a href="<?php echo $profileUrl; ?>"><?php echo $screenName; ?></a>.
                            <a href="<?php echo $logoutUrl; ?>" title="Logout"><?php _e('退出登录'); ?> &raquo;</a>
                        </p>
                    <?php else : ?>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="author" class="sr-only">称呼</label>
                                <input type="text" name="author" id="author"
                                       class="form-control"
                                       placeholder="<?php _e('你的称呼*'); ?>"
                                       value="<?php $this->remember('author'); ?>"
                                       required
                                       aria-required="true"
                                       aria-describedby="author-hint" />
                                <span id="author-hint" class="form-hint">必填</span>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="mail" class="sr-only">邮箱</label>
                                <input type="email" name="mail" id="mail"
                                       class="form-control"
                                       placeholder="<?php _e('邮箱*'); ?>"
                                       value="<?php $this->remember('mail'); ?>"
                                       <?php if ($this->options->commentsRequireMail) : ?>required aria-required="true"<?php endif; ?>
                                       aria-describedby="mail-hint" />
                                <span id="mail-hint" class="form-hint"><?php if ($this->options->commentsRequireMail) : ?>必填<?php else : ?>选填<?php endif; ?></span>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="url" class="sr-only">网站</label>
                                <input type="url" name="url" id="url"
                                       class="form-control"
                                       placeholder="<?php _e('网站/博客（可选）'); ?>"
                                       value="<?php $this->remember('url'); ?>"
                                       aria-describedby="url-hint" />
                                <span id="url-hint" class="form-hint">选填</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="textarea" class="sr-only">祝愿内容</label>
                        <textarea rows="3" cols="50" name="text" id="textarea"
                                  class="form-control"
                                  placeholder="<?php _e('把祝愿写给我们'); ?>"
                                  required
                                  aria-required="true"
                                  aria-describedby="textarea-hint"><?php $this->remember('text'); ?></textarea>
                        <span id="textarea-hint" class="form-hint">必填</span>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="float-right btn btn-outline-danger"><?php _e('送出祝愿'); ?></button>
                    </div>
                </form>
            </div>
        <?php else : ?>
            <h3 class="text-center"><?php _e('留言暂已关闭'); ?></h3>
        <?php endif; ?>
    </div>
</div>

<?php $this->need('base/footer.php'); ?>
