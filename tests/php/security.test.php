<?php
/**
 * 安全行为回归：使用真正的 DOMDocument 处理攻击输入，检查最终 DOM 而非只匹配源码。
 * 运行：php tests/php/security.test.php（需要 DOM/libxml）；不联网、不读取生产数据库。
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('__TYPECHO_ROOT_DIR__', dirname(__DIR__, 2));
class Helper
{
    public static $values = array();
    public static function options() { return (object)self::$values; }
}
require __TYPECHO_ROOT_DIR__ . '/core/App.php';
if (!class_exists('DOMDocument')) throw new RuntimeException('DOM extension required');
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
    return false;
});
$count = 0;
function verify($condition, $message)
{
    global $count;
    if (!$condition) throw new RuntimeException($message);
    $count++;
}

$payloads = array(
    '<img src=x onerror=alert(1)><script>alert(1)</script>',
    '<svg><a xlink:href="javascript:alert(1)">x</a></svg>',
    '<math><mtext><table><mglyph><style><!--</style><img title="--><img src=x onerror=alert(1)>">',
    '<noscript><p title="</noscript><img src=x onerror=alert(1)>">x',
    '<a href="jav&#x61;script&#58;alert(1)" onclick="alert(1)">x</a>',
    '<a href="java&#9;script:alert(1)" target="_blank" rel="opener">x</a>',
    '<a id="location" name="cookie" style="background:url(javascript:x)">x</a>',
    '<form action="https://evil.test"><input name="_" value="fake"></form>',
    '<iframe srcdoc="<script>alert(1)</script>"></iframe><object data="file:///etc/passwd"></object>',
    '<!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><p>&xxe;</p>',
    '<img src="data:image/svg+xml,<svg onload=alert(1)>" srcset="https://evil.test/x 2x">',
    '<p><b><i>broken</b></i><a href="https://example.com/?a=1&amp;b=2">safe</a></p>',
);
foreach ($payloads as $index => $payload) {
    foreach (array(false, true) as $images) {
        $safe = App::sanitizeCommentHtml($payload, $images);
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $safe . '</div>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        foreach ($dom->getElementsByTagName('*') as $node) {
            verify(!in_array(strtolower($node->tagName), array('script', 'svg', 'math', 'style', 'iframe', 'object', 'form', 'input', 'noscript')), 'dangerous element: ' . $index);
            foreach ($node->attributes as $attr) {
                verify(!preg_match('/^(?:on|id$|name$|style$|srcset$)/i', $attr->name), 'dangerous attribute: ' . $index);
                if ($attr->name === 'href' || $attr->name === 'src') {
                    verify(App::normalizeUrl($attr->value, true, array('http', 'https', 'mailto')) !== '', 'dangerous URL: ' . $index);
                }
            }
        }
        verify(strpos($safe, 'root:x:') === false, 'XXE local file expansion');
    }
}

foreach (array('https:evil.test', 'https:///evil.test', '//', '/\\evil.test/x', 'https://good.test\\@evil.test', 'https://user:pass@evil.test', "https://x/\xFF") as $url) {
    verify(App::normalizeUrl($url, true, array('http', 'https')) === '', 'ambiguous URL must be rejected: ' . $url);
}
verify(App::normalizeUrl('//evil.test', false, array('https')) === '', 'absolute-only URL boundary');
verify(App::normalizeUrl('//evil.test', true, array('mailto')) === '', 'protocol-relative scheme boundary');
verify(App::normalizeUrl('/blog/?a=1&amp;b=2', true, array('http', 'https')) === '/blog/?a=1&b=2', 'valid entity URL preserved');
verify(App::escapeHtml("中文\xFF") !== '', 'invalid UTF-8 must not erase text');
verify(preg_match('//u', App::sanitizeCommentHtml(str_repeat('中', 18000))) === 1, 'UTF-8 boundary on truncation');
verify(strpos(App::sanitizeCommentHtml(str_repeat('中', 18000)), '已截断') !== false, 'truncation notice');
verify(App::validCspPolicy("default-src 'self'; script-src 'nonce-ab+/='") === true, 'valid CSP nonce');
foreach (array("default-src 'self'\r\nX-Test: x", "script-src *\0", '<script>', str_repeat('x', 4000), '') as $policy) {
    verify(!App::validCspPolicy($policy), 'invalid CSP must fail closed');
}
verify(strlen(base64_decode(App::scriptNonce(), true)) >= 24, 'cryptographic nonce entropy');
verify(App::scriptNonce() === App::scriptNonce(), 'same response uses same nonce');
Helper::$values = array('off' => false, 'on' => true);
verify(!App::optionFlag('off', true), 'boolean false must not enable security option');
verify(App::optionFlag('on', false), 'boolean true preserved');
Helper::$values = array('invalid' => array('1'));
verify(App::optionValue('invalid', 'fallback') === 'fallback', 'non-scalar option must not trigger Array to string warning');
verify(App::optionFlag('invalid', false) === false, 'non-scalar flag must fail to safe default');
$before = libxml_use_internal_errors(false);
App::sanitizeCommentHtml('<p>broken<');
verify(libxml_use_internal_errors() === false, 'restore libxml global error mode');
libxml_use_internal_errors($before);
echo "安全测试：{$count} passed, 0 failed\n";
