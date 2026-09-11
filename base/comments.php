<?php
/**
 * 评论区块（列表 + 分页 + 表单）
 *
 * 由模板用 include 引入，而不是 $this->need()：
 * need() 内部走的是另一个方法作用域，调用方预置的变量传不进来。
 * 调用方可在 include 之前设置以下变量（全部可选，不设置即沿用「祝福板」文案）：
 *
 *   $commentSectionLabel     string 区块的无障碍标签（默认「评论」）
 *   $commentCountLabels      array  条数文案 [0 条, 1 条, n 条]，第三项含 %d
 *   $commentSubmitLabel      string 提交按钮文案
 *   $commentPlaceholder      string 文本框占位符
 *   $commentTextLabel        string 文本框的屏幕阅读器标签（占位符不能当标签用）
 *   $commentShowClosedNotice bool   评论关闭时是否输出「留言暂已关闭」（默认 true；
 *                                   文章页传 false，避免无评论的文章凭空多一行提示）
 *
 * 结构行为（与改造前的 commentPage.php 一致）：
 * - 有评论  → 条数标题 + 评论列表 + 分页
 * - 开评论  → 评论表单（含嵌套回复的 parent 隐藏字段）
 * - 关评论  → 仅当 $commentShowClosedNotice 为真时输出关闭提示
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
if (!class_exists('App', false)) {
    require_once __DIR__ . '/../core/App.php';
}

$this->comments()->to($comments);

$commentSectionLabel = isset($commentSectionLabel) ? $commentSectionLabel : _t('评论');
$commentCountLabels = isset($commentCountLabels) ? $commentCountLabels : array(
    _t('尚无祝愿'),
    _t('仅有一则祝愿'),
    _t('已收下<span class="bigfontNum"> %d </span>份祝愿'),
);
$commentSubmitLabel = isset($commentSubmitLabel) ? $commentSubmitLabel : _t('送出祝愿');
$commentPlaceholder = isset($commentPlaceholder) ? $commentPlaceholder : _t('把祝愿写给我们');
$commentTextLabel = isset($commentTextLabel) ? $commentTextLabel : _t('祝愿内容');
$commentShowClosedNotice = isset($commentShowClosedNotice) ? (bool) $commentShowClosedNotice : true;

$commentHasList = $comments->have();
$commentFormOpen = $this->allow('comment');

// 三个分支都不成立时整块不输出，避免留下一个空的 <section>
if (!$commentHasList && !$commentFormOpen && !$commentShowClosedNotice) {
    return;
}

$commentRespondId = App::escapeHtml($this->respondId);
$commentFormAction = App::escapeUrlAttribute($this->commentUrl, true, array('http', 'https'));

if (!function_exists('threadedComments')) {
    function threadedComments($comments, $options)
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
                    <?php if (App::optionFlag('commentsThreaded', false)) : ?>
                        <?php /* 回复/取消回复的类名与内核 TypechoComment 脚本约定一致：cp-{id} / cl-{id} */ ?>
                        <div class="comment-actions">
                            <span class="comment-reply cp-<?php $comments->theId(); ?>">
                                <?php $comments->reply(_t('回复')); ?>
                            </span>
                            <span class="cancel-comment-reply cl-<?php $comments->theId(); ?>" style="display:none">
                                <?php $comments->cancelReply(_t('取消回复')); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <?php $comments->threadedComments($options); ?>
                </div>
            </div>
        </div>
    </div>
<?php
    }
}
?>
<section id="comments" class="comment-area" aria-label="<?php echo App::escapeHtml($commentSectionLabel); ?>">
    <?php if ($commentHasList) : ?>
        <h2 class="text-center comment-total"><?php
            $this->commentsNum($commentCountLabels[0], $commentCountLabels[1], $commentCountLabels[2]);
        ?></h2>
        <?php $comments->listComments(); ?>
        <?php $comments->pageNav('&laquo; 上一页', '下一页 &raquo;'); ?>
    <?php endif; ?>
    <?php if ($commentFormOpen) : ?>
        <div id="<?php echo $commentRespondId; ?>" class="respond">
            <form method="post" action="<?php echo $commentFormAction; ?>" name="comment-form" id="comment-form" role="form" class="comment-form">
                <?php /* 嵌套回复的父评论 id；内核 TypechoComment 脚本会复用这个 input（没有时才新建） */ ?>
                <input type="hidden" name="parent" id="comment-parent" value="0">
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
                    <label for="textarea" class="sr-only"><?php echo App::escapeHtml($commentTextLabel); ?></label>
                    <textarea rows="3" cols="50" name="text" id="textarea"
                              class="form-control"
                              placeholder="<?php echo App::escapeHtml($commentPlaceholder); ?>"
                              required
                              aria-required="true"
                              aria-describedby="textarea-hint"><?php $this->remember('text'); ?></textarea>
                    <span id="textarea-hint" class="form-hint">必填</span>
                </div>
                <div class="form-group">
                    <button type="submit" class="float-right btn btn-outline-danger"><?php echo App::escapeHtml($commentSubmitLabel); ?></button>
                </div>
            </form>
        </div>
    <?php elseif ($commentShowClosedNotice) : ?>
        <h2 class="text-center comment-closed"><?php _e('留言暂已关闭'); ?></h2>
    <?php endif; ?>
</section>
