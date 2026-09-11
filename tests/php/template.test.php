<?php
/**
 * 模板行为测试：真实渲染 base/comments.php（无需 Typecho 环境）
 *
 * 与 behavior.test.php 同一思路：断言「渲染出来的 HTML」，而不是源文件字符串。
 * 静态契约只能证明「模板里写了净化调用」，证明不了「渲染结果是对的」——
 * 评论净化误转义那次就是 30 个契约全绿、上线即坏。
 *
 * 这里用桩对象渲染共用评论区块，覆盖祝福板/文章页两套文案、嵌套回复开关、
 * 评论关闭的三种组合。
 *
 * 运行：php tests/php/template.test.php
 */

define('__TYPECHO_ROOT_DIR__', dirname(__DIR__, 2));

/** Typecho Helper 的最小桩：只需支持 isset($options->x) 与 $options->x 读取。 */
class Helper
{
    public static $opts = array();

    public static function set(array $opts)
    {
        self::$opts = $opts;
    }

    public static function options()
    {
        return new class {
            public function __isset($name)
            {
                return array_key_exists($name, Helper::$opts);
            }

            public function __get($name)
            {
                return array_key_exists($name, Helper::$opts) ? Helper::$opts[$name] : null;
            }
        };
    }
}

require dirname(__DIR__, 2) . '/core/App.php';

function _t($string)
{
    return $string;
}

function _e($string)
{
    echo $string;
}

$passed = 0;
$failed = 0;

function describe($value)
{
    if (is_string($value)) {
        return strlen($value) > 200 ? '"' . substr($value, 0, 200) . '…"' : '"' . $value . '"';
    }

    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function checkSame($label, $actual, $expected)
{
    global $passed, $failed;

    if ($actual === $expected) {
        $passed++;
        return;
    }

    $failed++;
    echo "FAIL  {$label}\n";
    echo "      期望: " . describe($expected) . "\n";
    echo "      实际: " . describe($actual) . "\n";
}

function checkTrue($label, $condition)
{
    checkSame($label, (bool)$condition, true);
}

function checkContains($label, $haystack, $needle)
{
    checkTrue($label . '（应包含 ' . describe($needle) . '）', strpos($haystack, $needle) !== false);
}

function checkNotContains($label, $haystack, $needle)
{
    checkTrue($label . '（不应包含 ' . describe($needle) . '）', strpos($haystack, $needle) === false);
}

/* ------------------------------------------------------------------ *
 * 桩对象
 * ------------------------------------------------------------------ */

final class FakeUser
{
    public $screenName = '站主';

    public function hasLogin()
    {
        return false;
    }
}

final class FakeOptions
{
    public $commentsRequireMail = 0;
}

final class FakeComment
{
    public $authorId;
    public $ownerId = 1;
    public $levels;
    public $id;
    public $authorName;
    public $contentHtml;

    public function __construct($id, $authorName, $contentHtml, $levels = 0, $authorId = 0)
    {
        $this->id = $id;
        $this->authorName = $authorName;
        $this->contentHtml = $contentHtml;
        $this->levels = $levels;
        $this->authorId = $authorId;
    }

    public function theId()
    {
        echo 'comment-' . $this->id;
    }

    public function date($format)
    {
        echo '2026-09-11 21:00';
    }

    public function author()
    {
        echo $this->authorName;
    }

    public function content()
    {
        echo $this->contentHtml;
    }

    public function alt($odd = ' comment-odd', $even = ' comment-even')
    {
        echo $even;
    }

    public function levelsAlt($odd = ' comment-level-odd', $even = ' comment-level-even')
    {
        echo $even;
    }

    /** 子评论：本测试不递归展开 */
    public function threadedComments($options)
    {
    }

    /** 与内核 TypechoComment.reply() 的输出形状保持一致 */
    public function reply($word = '')
    {
        echo '<a href="#respond" rel="nofollow" onclick="return TypechoComment.reply(\'comment-'
            . $this->id . '\', ' . (int)$this->id . ');">' . htmlspecialchars($word, ENT_QUOTES, 'UTF-8') . '</a>';
    }

    public function cancelReply($word = '')
    {
        echo '<a id="cancel-comment-reply-link" href="#respond" rel="nofollow" '
            . 'onclick="return TypechoComment.cancelReply();">' . htmlspecialchars($word, ENT_QUOTES, 'UTF-8') . '</a>';
    }
}

final class FakeComments
{
    public $items = array();
    public $options = array();
    public $pageNavCalled = false;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function have()
    {
        return count($this->items) > 0;
    }

    /** 内核里是 to(&$object)：按引用把自身赋给调用方的 $comments */
    public function to(&$object)
    {
        $object = $this;
        return $this;
    }

    /** 内核会以回调名 threadedComments() 渲染每条评论，这里复刻该行为 */
    public function listComments()
    {
        if (!function_exists('threadedComments')) {
            return;
        }

        foreach ($this->items as $item) {
            threadedComments($item, $this->options);
        }
    }

    public function pageNav($prev, $next)
    {
        $this->pageNavCalled = true;
        echo '<div class="page-navigator">' . $prev . $next . '</div>';
    }
}

final class FakeArchive
{
    public $respondId = 'respond';
    public $commentUrl = 'https://example.com/post/1/comment';
    public $user;
    public $options;
    public $allowComment = true;
    /** 记录 commentsNum 收到的三段文案，便于断言文案确实来自调用方 */
    public $countArgs = null;

    /* 文章页（post.php）需要的一组字段 */
    public $titleText = '文章标题';
    public $categories = array();
    public $tags = array();
    public $prevHtml = '';
    public $nextHtml = '';

    private $comments;

    public function __construct(FakeComments $comments)
    {
        $this->comments = $comments;
        $this->user = new FakeUser();
        $this->options = new FakeOptions();
    }

    public function comments()
    {
        return $this->comments;
    }

    public function allow($name)
    {
        return $name === 'comment' ? $this->allowComment : false;
    }

    public function commentsNum($zero, $one, $many)
    {
        $this->countArgs = array($zero, $one, $many);
        $total = count($this->comments->items);

        if ($total === 0) {
            echo $zero;
        } elseif ($total === 1) {
            echo $one;
        } else {
            echo sprintf($many, $total);
        }
    }

    public function remember($key)
    {
        echo '';
    }

    public function title()
    {
        echo $this->titleText;
    }

    public function date($format)
    {
        echo '2026-09-11';
    }

    public function content()
    {
        echo '<p>正文</p>';
    }

    public function thePrev($format = '%s', $default = null)
    {
        echo $this->prevHtml !== '' ? $this->prevHtml : (string)$default;
    }

    public function theNext($format = '%s', $default = null)
    {
        echo $this->nextHtml !== '' ? $this->nextHtml : (string)$default;
    }

    public function theId()
    {
        echo 'post-1';
    }

    /** 模板片段的 need()：只留占位注释，避免拉起 head/nav/footer 的依赖 */
    public function need($file)
    {
        echo '<!--need:' . $file . '-->';
    }

    /**
     * 渲染共用评论区块。用 include 而不是 $this->need()，与模板侧一致，
     * 这样 $vars 里的文案变量能传进被包含文件。
     */
    public function render(array $vars = array())
    {
        extract($vars);

        ob_start();
        include __DIR__ . '/../../base/comments.php';

        return ob_get_clean();
    }

    /** 渲染一个完整模板（post.php 等） */
    public function renderTemplate($file, array $vars = array())
    {
        extract($vars);

        ob_start();
        include __DIR__ . '/../../' . $file;

        return ob_get_clean();
    }
}

/* ------------------------------------------------------------------ *
 * 场景
 * ------------------------------------------------------------------ */

$blessingVars = array(); // 祝福板：什么都不传，走默认文案

$articleVars = array(
    'commentSectionLabel' => '评论',
    'commentCountLabels' => array(
        '还没有评论',
        '仅有一条评论',
        '已有<span class="bigfontNum"> %d </span>条评论',
    ),
    'commentSubmitLabel' => '发表评论',
    'commentPlaceholder' => '说点什么吧',
    'commentTextLabel' => '评论内容',
    'commentShowClosedNotice' => false,
);

/** 造一条顶级评论 + 一条作者本人的子评论 */
function makeComments()
{
    return new FakeComments(array(
        new FakeComment('101', '小明', '<p>祝你们幸福</p>', 0, 0),
        new FakeComment('102', '站主', '<p>谢谢，也祝你好运</p>', 1, 1),
    ));
}

function makeArchive($threaded = true, $allowComment = true, $withComments = true)
{
    Helper::set(array(
        'commentAllowImg' => '0',
        'commentsThreaded' => $threaded ? '1' : '0',
        'themeUrl' => 'https://example.com/usr/themes/Brave',
    ));

    $archive = new FakeArchive($withComments ? makeComments() : new FakeComments(array()));
    $archive->allowComment = $allowComment;

    return $archive;
}

/* ------------------------------------------------------------------ *
 * 1. 祝福板默认文案（commentPage.php 不传变量的情形）
 * ------------------------------------------------------------------ */
$archive = makeArchive();
$html = $archive->render($blessingVars);

checkContains('祝福板：条数文案用「祝愿」口径', $html, '已收下<span class="bigfontNum"> 2 </span>份祝愿');
checkContains('祝福板：提交按钮文案', $html, '送出祝愿');
checkContains('祝福板：文本框占位符', $html, '把祝愿写给我们');
checkTrue('祝福板：commentsNum 收到默认三段文案', $archive->countArgs === array(
    '尚无祝愿',
    '仅有一则祝愿',
    '已收下<span class="bigfontNum"> %d </span>份祝愿',
));
checkNotContains('祝福板：不出现文章口径文案', $html, '条评论');

checkSame('祝福板：渲染出两条评论', substr_count($html, 'class="commentlist"'), 2);
checkContains('祝福板：评论正文经过净化后保留段落', $html, '<p>祝你们幸福</p>');
checkContains('祝福板：顶级评论带 comment-parent', $html, 'comment-parent');
checkContains('祝福板：子评论带 comment-child 与层级类名', $html, 'comment-child comment-level-even');
checkContains('祝福板：作者本人回复带 comment-by-author', $html, 'comment-by-author');
checkContains('祝福板：评论 id 前缀保持内核约定', $html, 'id="li-comment-101"');
checkContains('祝福板：输出分页容器', $html, '<div class="page-navigator">');
checkTrue('祝福板：listComments 触发分页渲染', $archive->comments()->pageNavCalled);

checkContains('祝福板：表单带 parent 隐藏字段', $html, '<input type="hidden" name="parent" id="comment-parent" value="0">');
checkContains('祝福板：表单 action 用评论地址', $html, 'action="https://example.com/post/1/comment"');
checkContains('祝福板：respond 容器 id', $html, 'id="respond" class="respond"');
checkContains('祝福板：区块无障碍标签', $html, 'aria-label="评论"');
checkNotContains('祝福板：未关闭评论时不出现关闭提示', $html, '留言暂已关闭');

/* 嵌套回复入口 */
checkContains('嵌套开启：渲染回复链接', $html, "TypechoComment.reply('comment-101', 101)");
checkContains('嵌套开启：回复入口类名 cp-{id}', $html, 'comment-reply cp-comment-101');
checkContains('嵌套开启：取消回复类名 cl-{id}', $html, 'cancel-comment-reply cl-comment-101');
checkContains('嵌套开启：取消回复走内核脚本', $html, 'TypechoComment.cancelReply()');

/* ------------------------------------------------------------------ *
 * 2. 文章页文案（post.php 传变量的情形）
 * ------------------------------------------------------------------ */
$archive = makeArchive();
$html = $archive->render($articleVars);

checkContains('文章页：条数文案用「评论」口径', $html, '已有<span class="bigfontNum"> 2 </span>条评论');
checkContains('文章页：提交按钮文案', $html, '发表评论');
checkContains('文章页：文本框占位符', $html, '说点什么吧');
checkTrue('文章页：commentsNum 收到文章文案', $archive->countArgs === array(
    '还没有评论',
    '仅有一条评论',
    '已有<span class="bigfontNum"> %d </span>条评论',
));
checkNotContains('文章页：不出现祝福板文案', $html, '送出祝愿');
checkNotContains('文章页：不出现祝福板占位符', $html, '把祝愿写给我们');
checkContains('文章页：文本框标签用文章口径', $html, '<label for="textarea" class="sr-only">评论内容</label>');
checkContains('文章页：评论列表照常渲染', $html, '<p>祝你们幸福</p>');
checkContains('文章页：表单照常渲染', $html, 'name="comment-form"');

/* ------------------------------------------------------------------ *
 * 3. 评论关闭时的三种组合
 * ------------------------------------------------------------------ */
// 3a 有评论 + 关闭新评论（文章页）：保留列表，不出表单和关闭提示
$archive = makeArchive(true, false, true);
$html = $archive->render($articleVars);
checkContains('关闭评论：已有评论仍然渲染', $html, '<p>祝你们幸福</p>');
checkNotContains('关闭评论：不渲染表单', $html, '<form method="post"');
checkNotContains('关闭评论：文章页不渲染关闭提示', $html, '留言暂已关闭');
checkContains('关闭评论：仍输出区块容器', $html, '<section id="comments"');

// 3b 无评论 + 关闭新评论（文章页）：整块不输出
$archive = makeArchive(true, false, false);
$html = $archive->render($articleVars);
checkSame('关闭评论且无评论：文章页不留空区块', trim($html), '');

// 3c 无评论 + 关闭新评论（祝福板）：输出关闭提示
$archive = makeArchive(true, false, false);
$html = $archive->render($blessingVars);
checkContains('关闭评论且无评论：祝福板输出关闭提示', $html, '留言暂已关闭');
checkNotContains('关闭评论且无评论：祝福板不输出表单', $html, '<form method="post"');
checkNotContains('关闭评论且无评论：祝福板不输出条数标题', $html, 'comment-total');

/* ------------------------------------------------------------------ *
 * 4. 嵌套回复关闭时不出入口，但评论照常显示
 * ------------------------------------------------------------------ */
$archive = makeArchive(false);
$html = $archive->render($blessingVars);

checkNotContains('嵌套关闭：不渲染回复入口', $html, 'TypechoComment.reply');
checkNotContains('嵌套关闭：不渲染取消回复', $html, 'cancel-comment-reply');
checkContains('嵌套关闭：评论正文照常渲染', $html, '<p>祝你们幸福</p>');
checkContains('嵌套关闭：表单仍然可用', $html, 'name="comment-form"');

/* ------------------------------------------------------------------ *
 * 5. 调用方传入的文案会被转义，不外泄为标签
 * ------------------------------------------------------------------ */
$archive = makeArchive();
$html = $archive->render(array_merge($articleVars, array(
    'commentSectionLabel' => '<script>alert(1)</script>',
    'commentSubmitLabel' => '"><img src=x onerror=alert(1)>',
    'commentPlaceholder' => '<b>粗体</b>',
)));

checkContains('注入防护：区块标签被实体化', $html, 'aria-label="&lt;script&gt;alert(1)&lt;/script&gt;"');
checkNotContains('注入防护：aria-label 不出现裸标签', $html, 'aria-label="<script>');
checkNotContains('注入防护：按钮文案不产生属性逃逸', $html, '"><img src=x');
checkContains('注入防护：按钮文案被实体化', $html, '&quot;&gt;&lt;img src=x onerror=alert(1)&gt;');
checkContains('注入防护：占位符被实体化', $html, 'placeholder="&lt;b&gt;粗体&lt;/b&gt;"');

/* ------------------------------------------------------------------ *
 * 6. 未登录态的表单字段
 * ------------------------------------------------------------------ */
checkContains('表单：未登录时输出称呼字段', $html, 'name="author"');
checkContains('表单：未登录时输出邮箱字段', $html, 'name="mail"');
checkContains('表单：未登录时输出网站字段', $html, 'name="url"');
checkNotContains('表单：未登录时不输出身份行', $html, '当前身份: ');

/* ------------------------------------------------------------------ *
 * 7. 文章模板（post.php）：上下篇导航 + 分类标签转义 + 评论区
 * ------------------------------------------------------------------ */
$archive = makeArchive();
$archive->titleText = '文章标题';
$archive->categories = array(array('permalink' => 'https://example.com/cat/1', 'name' => '日常'));
$archive->tags = array(
    array('permalink' => 'https://example.com/tag/1', 'name' => '恋爱'),
    array('permalink' => 'javascript:alert(1)', 'name' => '<img src=x onerror=alert(1)>'),
);
$archive->prevHtml = '<a href="https://example.com/post/0">更早的一篇</a>';
$archive->nextHtml = '';

$html = $archive->renderTemplate('post.php');

checkContains('文章页：h1 是文章标题', $html, '<h1 class="list-text">「文章标题」</h1>');
checkContains('文章页：渲染分类链接', $html, '<a href="https://example.com/cat/1">日常</a>');
checkContains('文章页：渲染标签链接', $html, '<a href="https://example.com/tag/1">恋爱</a>');
checkContains('文章页：危险协议的标签链接被清空', $html, '<a href="">');
checkNotContains('文章页：不输出 javascript: 协议', $html, 'javascript:');
checkNotContains('文章页：标签名不产生裸标签', $html, '<img src=x');
checkContains('文章页：标签名被实体化', $html, '&lt;img src=x onerror=alert(1)&gt;');

checkContains('文章页：输出上下篇导航', $html, '<nav class="post-near"');
checkContains('文章页：渲染上一篇链接', $html, '<a href="https://example.com/post/0">更早的一篇</a>');
checkContains('文章页：缺失的下一篇用占位文案', $html, '<span class="post-near__empty">没有了</span>');
checkSame('文章页：占位文案只出现在缺失的一侧', substr_count($html, 'post-near__empty'), 1);

checkContains('文章页：评论区入口由文章口径文案渲染', $html, '已有<span class="bigfontNum"> 2 </span>条评论');
checkContains('文章页：评论列表渲染在同一页', $html, '<p>祝你们幸福</p>');
checkContains('文章页：表单可用', $html, 'name="comment-form"');

// 两篇都没有时不留空导航
$archive = makeArchive();
$archive->prevHtml = '';
$archive->nextHtml = '';
$htmlNoNav = $archive->renderTemplate('post.php');
checkNotContains('文章页：没有相邻文章时不渲染导航', $htmlNoNav, 'post-near');
checkContains('文章页：没有相邻文章时正文照常渲染', $htmlNoNav, '<p>正文</p>');

// 关闭评论且没有评论时，文章页不出评论区
$archive = makeArchive(true, false, false);
$htmlClosed = $archive->renderTemplate('post.php');
checkNotContains('文章页：关闭评论且无评论时不出评论区', $htmlClosed, '<section id="comments"');
checkNotContains('文章页：关闭评论且无评论时不出关闭提示', $htmlClosed, '留言暂已关闭');

/* ------------------------------------------------------------------ *
 * 汇总
 * ------------------------------------------------------------------ */
echo "\n模板测试：{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
