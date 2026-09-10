<?php
/**
 * App 核心行为测试（无需 Typecho 环境）
 *
 * 背景：tests/braveStaticContracts.test.mjs 全部是「源文件字符串契约」匹配，
 * 无法覆盖净化的实际输出。历史上评论净化把 Markdown 生成的 `<p>…</p>` 整体转义，
 * 30 个契约用例全绿却上线即坏，因此这里补上真正的输入 → 输出断言。
 *
 * 运行：php tests/php/behavior.test.php
 */

define('__TYPECHO_ROOT_DIR__', dirname(__DIR__, 2));

/**
 * Typecho Helper 的最小桩：只需支持 isset($options->x) 与 $options->x 读取。
 */
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

$passed = 0;
$failed = 0;

function describe($value)
{
    if (is_string($value)) {
        return strlen($value) > 160 ? '"' . substr($value, 0, 160) . '…"' : '"' . $value . '"';
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

Helper::set(array(
    'themeUrl' => 'https://example.com/usr/themes/Brave',
    'loveListTitleAllowHtml' => '0',
));

/* ------------------------------------------------------------------ *
 * 评论净化：Markdown 产物必须原样保留
 * Typecho 开启 commentsMarkdown 后，单段评论会渲染成 `<p>…</p>`，
 * 多段会渲染成 `<p>a</p><p>b</p>`（HyperDown 实测输出）。
 * ------------------------------------------------------------------ */
$commentCases = array(
    '单段评论'            => array('<p>祝你们幸福</p>', '<p>祝你们幸福</p>'),
    '多段评论'            => array('<p>第一段</p><p>第二段</p>', '<p>第一段</p><p>第二段</p>'),
    '段内换行'            => array('<p>a<br>b</p>', '<p>a<br>b</p>'),
    '行内标签'            => array('<p>你好 <strong>世界</strong></p>', '<p>你好 <strong>世界</strong></p>'),
    'em 标签'             => array('<em>x</em>', '<em>x</em>'),
    '纯文本'              => array('你好', '你好'),
    'del 标签'            => array('<del>x</del>', '<del>x</del>'),
    '列表'                => array('<ul><li>a</li></ul>', '<ul><li>a</li></ul>'),
    '引用'                => array('<blockquote><p>q</p></blockquote>', '<blockquote><p>q</p></blockquote>'),
);

foreach ($commentCases as $name => $case) {
    checkSame('sanitizeCommentHtml: ' . $name, App::sanitizeCommentHtml($case[0]), $case[1]);
}

/* ------------------------------------------------------------------ *
 * 评论净化：危险内容必须被剥离
 * ------------------------------------------------------------------ */
checkSame(
    'sanitizeCommentHtml: 移除 script 块',
    App::sanitizeCommentHtml('<p>a</p><script>alert(1)</script>'),
    '<p>a</p>'
);
checkSame(
    'sanitizeCommentHtml: 移除危险 href',
    App::sanitizeCommentHtml('<a href="javascript:alert(1)">x</a>'),
    '<a rel="nofollow ugc noopener noreferrer">x</a>'
);
checkSame(
    'sanitizeCommentHtml: 移除事件属性',
    App::sanitizeCommentHtml('<img src="/a.png" onerror="alert(1)">', true),
    '<img src="/a.png" loading="lazy" referrerpolicy="no-referrer">'
);
checkSame(
    'sanitizeCommentHtml: 缺 src 的 img 整体移除',
    App::sanitizeCommentHtml('<img alt="x">', true),
    ''
);
checkSame(
    'sanitizeCommentHtml: 默认不允许 img',
    App::sanitizeCommentHtml('<img src="/a.png">'),
    ''
);
checkSame(
    'sanitizeCommentHtml: 保留安全外链并补 rel',
    App::sanitizeCommentHtml('<a href="https://example.com/x">x</a>'),
    '<a href="https://example.com/x" rel="nofollow ugc noopener noreferrer">x</a>'
);
checkSame(
    'sanitizeCommentHtml: 保留 mailto',
    App::sanitizeCommentHtml('<a href="mailto:a@b.com">m</a>'),
    '<a href="mailto:a@b.com" rel="nofollow ugc noopener noreferrer">m</a>'
);
checkSame(
    'sanitizeCommentHtml: HTML 注释被移除',
    App::sanitizeCommentHtml('<p>a<!-- x --></p>'),
    '<p>a</p>'
);

/* ------------------------------------------------------------------ *
 * 超长内容：截断后仍继续净化（新增截断提示），不再整体转义
 * ------------------------------------------------------------------ */
$longComment = '<p>' . str_repeat('你好', 30000) . '</p>';
$longResult = App::sanitizeCommentHtml($longComment);
checkTrue('超长评论: 未被整体转义（仍保留 <p> 标签）', strpos($longResult, '<p>') === 0);
checkTrue('超长评论: 带截断提示', strpos($longResult, 'brave-truncated-notice') !== false);
checkTrue('超长评论: 输出长度受控', strlen($longResult) <= App::MAX_HTML_LENGTH + 200);

/* ------------------------------------------------------------------ *
 * Love List 标题净化
 * ------------------------------------------------------------------ */
Helper::set(array(
    'themeUrl' => 'https://example.com/usr/themes/Brave',
    'loveListTitleAllowHtml' => '1',
));
checkSame('sanitizeLoveListTitle(允许 HTML): strong 保留', App::sanitizeLoveListTitle('<strong>x</strong>', true), '<strong>x</strong>');
checkSame('sanitizeLoveListTitle(允许 HTML): em 保留', App::sanitizeLoveListTitle('<em>x</em>', true), '<em>x</em>');
checkSame('sanitizeLoveListTitle(允许 HTML): del 保留', App::sanitizeLoveListTitle('<del>x</del>', true), '<del>x</del>');
checkSame('sanitizeLoveListTitle(允许 HTML): script 块连内容整体移除', App::sanitizeLoveListTitle('<script>alert(1)</script>x', true), 'x');
Helper::set(array('themeUrl' => 'https://example.com/usr/themes/Brave', 'loveListTitleAllowHtml' => '0'));
checkSame('sanitizeLoveListTitle(纯文本): 标签转义', App::sanitizeLoveListTitle('<b>x</b>', false), '&lt;b&gt;x&lt;/b&gt;');

/* ------------------------------------------------------------------ *
 * URL 规范化
 * ------------------------------------------------------------------ */
$dangerous = array(
    'javascript:alert(1)',
    'JaVaScRiPt:alert(1)',
    "java\tscript:alert(1)",
    'data:text/html,<script>alert(1)</script>',
    'vbscript:msgbox(1)',
    'file:///etc/passwd',
    'java&#115;cript:alert(1)',
);
foreach ($dangerous as $url) {
    checkSame('normalizeUrl 拒绝危险协议: ' . substr($url, 0, 24), App::normalizeUrl($url, true, array('http', 'https')), '');
}

$allowed = array(
    'https://example.com/a.png' => 'https://example.com/a.png',
    '/img/a.png' => '/img/a.png',
    '#anchor' => '#anchor',
    './rel.png' => './rel.png',
    'blog/' => 'blog/',
    '//cdn.example.com/a.png' => '//cdn.example.com/a.png',
);
foreach ($allowed as $url => $expected) {
    checkSame('normalizeUrl 放行: ' . $url, App::normalizeUrl($url, true, array('http', 'https')), $expected);
}

checkSame('normalizeUrl 拒绝带用户信息的 URL', App::normalizeUrl('https://user:pass@example.com/a', true, array('http', 'https')), '');
checkSame('normalizeUrl 拒绝协议相对的用户信息', App::normalizeUrl('//user@example.com/a', true, array('http', 'https')), '');
checkSame('normalizeUrl 拒绝未在允许列表的协议', App::normalizeUrl('mailto:a@b.com', true, array('http', 'https')), '');

/* ------------------------------------------------------------------ *
 * 配置读取 helper
 * ------------------------------------------------------------------ */
Helper::set(array('flag1' => '1', 'flag0' => '0', 'int' => '99', 'choice' => 'cdn'));
checkSame('optionFlag: 1 → true', App::optionFlag('flag1', false), true);
checkSame('optionFlag: 0 → false', App::optionFlag('flag0', true), false);
checkSame('optionFlag: 缺失 → 默认值', App::optionFlag('missing', true), true);
checkSame('optionFlag: 异常值 → 默认值', App::optionFlag('int', false), false);
checkSame('optionIntRange: 上限收敛', App::optionIntRange('int', 10, 1, 10), 10);
checkSame('optionChoice: 命中白名单', App::optionChoice('choice', 'local', array('local', 'cdn')), 'cdn');
checkSame('optionChoice: 未命中回落默认', App::optionChoice('missing', 'local', array('local', 'cdn')), 'local');

/* ------------------------------------------------------------------ *
 * 自定义代码外链脚本域名判定
 * ------------------------------------------------------------------ */
$trustedHosts = array('cdn.staticfile.org', 'blog.example.com');

checkSame(
    'findUntrustedScriptHosts: 白名单内(双引号)放行',
    App::findUntrustedScriptHosts('<script src="https://cdn.staticfile.org/a.js"></script>', $trustedHosts),
    array()
);
checkSame(
    'findUntrustedScriptHosts: 本站(单引号)放行',
    App::findUntrustedScriptHosts("<script src='https://blog.example.com/a.js'></script>", $trustedHosts),
    array()
);
checkSame(
    'findUntrustedScriptHosts: 同源相对路径放行',
    App::findUntrustedScriptHosts('<script src="/base/vendor/x.js"></script>', $trustedHosts),
    array()
);
checkSame(
    'findUntrustedScriptHosts: 外站脚本被识别',
    App::findUntrustedScriptHosts('<script src="https://evil.example.com/a.js"></script>', $trustedHosts),
    array('evil.example.com')
);
checkSame(
    'findUntrustedScriptHosts: 前缀伪造域名被识别',
    App::findUntrustedScriptHosts('<script src="https://cdn.staticfile.org.evil.com/a.js"></script>', $trustedHosts),
    array('cdn.staticfile.org.evil.com')
);
checkSame(
    'findUntrustedScriptHosts: 协议相对外站被识别',
    App::findUntrustedScriptHosts('<script src="//evil.example.com/a.js"></script>', $trustedHosts),
    array('evil.example.com')
);
checkSame(
    'findUntrustedScriptHosts: 空 host 配置不会误伤',
    App::findUntrustedScriptHosts('<script src="https://cdn.staticfile.org/a.js"></script>', array('cdn.staticfile.org')),
    array()
);

/* ------------------------------------------------------------------ *
 * Love List 短代码
 * ------------------------------------------------------------------ */
Helper::set(array(
    'themeUrl' => 'https://example.com/usr/themes/Brave',
    'loveListTitleAllowHtml' => '0',
));

$listHtml = App::parseShortCode(
    "[loveList]\n[item status=\"0\" img=\"https://x/a.jpg\"]一起看海[/item]\n[item status=\"1\"]做饭[/item]\n[/loveList]"
);
checkSame('parseShortCode: 渲染卡片数', substr_count($listHtml, 'class="card"'), 2);
checkTrue('parseShortCode: 保留背景图', strpos($listHtml, 'background-image') !== false);
checkTrue('parseShortCode: 未完成项使用 todo 图标', strpos($listHtml, 'todo.svg') !== false);
checkTrue('parseShortCode: 已完成项使用 ok 图标', strpos($listHtml, 'ok.svg') !== false);
checkTrue('parseShortCode: 生成唯一容器 id', strpos($listHtml, 'id="loveList0"') !== false);

$selfClosing = App::parseShortCode('[loveList][item status="1"/][item status="0"]b[/item][/loveList]');
checkSame('parseShortCode: 自闭合项也渲染', substr_count($selfClosing, 'class="card"'), 2);
checkSame('parseShortCode: 自闭合项状态正确', substr_count($selfClosing, 'ok.svg'), 1);

$unquoted = App::parseShortCode('[loveList][item img=/img/a/b.png]t[/item][/loveList]');
checkTrue('parseShortCode: 未加引号且含斜杠的 img 可解析', strpos($unquoted, 'background-image') !== false);
checkTrue('parseShortCode: 背景图路径正确', strpos($unquoted, '/img/a/b.png') !== false);

$noItem = App::parseShortCode('[loveList]a<b>c[/loveList]');
checkSame('parseShortCode: 无 item 时按纯文本输出', $noItem, 'a&lt;b&gt;c');

checkSame(
    'parseShortCode: 不在 loveList 内的方括号内容原样返回',
    App::parseShortCode('[other]x[/other]'),
    '[other]x[/other]'
);

$escapedTitle = App::parseShortCode('[loveList][item status="0"]<b>x</b>[/item][/loveList]');
checkTrue('parseShortCode: 标题默认转义', strpos($escapedTitle, '&lt;b&gt;x&lt;/b&gt;') !== false);

$attrInjection = App::parseShortCode('[loveList][item onclick="alert(1)" status="1"]t[/item][/loveList]');
checkTrue('parseShortCode: 未知属性不进入输出', strpos($attrInjection, 'onclick') === false);

$badImg = App::parseShortCode('[loveList][item img="javascript:alert(1)"]t[/item][/loveList]');
checkTrue('parseShortCode: 危险 img 协议不产生 background-image', strpos($badImg, 'background-image') === false);

$start = microtime(true);
App::parseShortCode('[loveList]' . str_repeat('[item ', 4000) . '[/loveList]');
$elapsed = microtime(true) - $start;
checkTrue(sprintf('parseShortCode: 畸形输入耗时可控（%.3fs）', $elapsed), $elapsed < 1.0);

/* ------------------------------------------------------------------ *
 * 汇总
 * ------------------------------------------------------------------ */
echo "\n行为测试：{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
