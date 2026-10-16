<?php
/**
 * Typecho 1.3 真内核集成测试（独立临时 SQLite，绝不连接生产库）。
 * TYPECHO_ROOT 指向官方 v1.3.0 源码；先将主题复制/链接至该目录 usr/themes/Brave。
 * 运行：TYPECHO_ROOT=/tmp/typecho php tests/php/typecho13.integration.php
 * PHP 需开启 pdo_sqlite。测试可选 BRAVE_TEST_DB 指定临时 fixture DB，供本地浏览器复核。
 */
if (PHP_SAPI !== 'cli' && !(PHP_SAPI === 'cli-server' && defined('BRAVE_TEST_HTTP'))) {
    http_response_code(404);
    exit;
}
$typechoRoot = getenv('TYPECHO_ROOT');
if (!$typechoRoot || !is_file($typechoRoot . '/var/Typecho/Common.php')) {
    throw new RuntimeException('Set TYPECHO_ROOT to an official Typecho v1.3.0 checkout');
}
define('__TYPECHO_ROOT_DIR__', str_replace('\\', '/', realpath($typechoRoot)));
define('__TYPECHO_THEME_DIR__', '/usr/themes');
define('__TYPECHO_PLUGIN_DIR__', '/usr/plugins');
define('__TYPECHO_ADMIN_DIR__', '/admin/');
define('__TYPECHO_DEBUG__', true);
$origin = getenv('BRAVE_TEST_ORIGIN') ?: 'http://127.0.0.1:8133';
define('__TYPECHO_URL_PREFIX__', $origin);
$_SERVER += array('REQUEST_URI' => '/archives/1/', 'REQUEST_METHOD' => 'GET', 'HTTP_HOST' => '127.0.0.1:8133', 'SERVER_PORT' => '8133', 'REMOTE_ADDR' => '127.0.0.1');
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __TYPECHO_ROOT_DIR__ . '/index.php';
require __TYPECHO_ROOT_DIR__ . '/var/Typecho/Common.php';
if (\Typecho\Common::VERSION !== '1.3.0') throw new RuntimeException('Expected Typecho 1.3.0');

$testDb = getenv('BRAVE_TEST_DB') ?: tempnam(sys_get_temp_dir(), 'brave-typecho13-');
if (!getenv('BRAVE_TEST_DB')) register_shutdown_function(function () use ($testDb) { @unlink($testDb); });
$pdo = new PDO('sqlite:' . $testDb);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$routes = array();
foreach (array('index' => '/', 'index_page' => '/page/[page:digital]/', 'archive' => '/blog/', 'post' => '/archives/[cid:digital]/', 'page' => '/[slug].html', 'category' => '/category/[slug]/', 'tag' => '/tag/[slug]/', 'feed' => '/feed[feed:string:0]') as $name => $url) {
    $routes[$name] = array('url' => $url, 'widget' => '\\Widget\\Archive', 'action' => 'render');
}
$routes['comment_page'] = array('url' => '[permalink:string]/comment-page-[commentPage:digital]', 'widget' => '\\Widget\\CommentPage', 'action' => 'action');
$routes['feedback'] = array('url' => '[permalink:string]/[type:alpha]', 'widget' => '\\Widget\\Feedback', 'action' => 'action');
$defaults = array(
    'theme' => 'Brave', 'siteUrl' => $origin . '/', 'title' => 'Brave integration', 'description' => '', 'keywords' => '',
    'charset' => 'UTF-8', 'contentType' => 'text/html', 'lang' => 'zh_CN', 'timezone' => 28800, 'generator' => 'Typecho 1.3.0',
    'installed' => 1, 'rewrite' => 1, 'frontPage' => 'recent', 'frontArchive' => 0, 'pageSize' => 5,
    'plugins' => '{}', 'routingTable' => json_encode($routes), 'actionTable' => '{}', 'panelTable' => '{}',
    'secret' => 'local-fixture-secret-not-for-production', 'allowXmlRpc' => 0, 'commentsThreaded' => 1,
    'commentsAntiSpam' => 1, 'commentsCheckReferer' => 1, 'commentsRequireUrl' => 0, 'commentsRequireMail' => 0,
    'commentsRequireModeration' => 0, 'commentsPostIntervalEnable' => 0, 'commentsAutoClose' => 0,
    'commentsMarkdown' => 1, 'commentsHTMLTagAllowed' => '<p><a href=""><strong><em><br>',
    'commentsMaxNestingLevels' => 5, 'commentsOrder' => 'DESC', 'commentsPageDisplay' => 'first',
    'commentsPageBreak' => 1, 'commentsPageSize' => 20, 'commentsShowCommentOnly' => 1,
    'commentsShowUrl' => 1, 'commentsUrlNofollow' => 1, 'commentsWhitelist' => 0,
);
if (!$pdo->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name='typecho_options'")->fetchColumn()) {
    $pdo->exec(file_get_contents(__TYPECHO_ROOT_DIR__ . '/install/SQLite.sql'));
    $insert = $pdo->prepare('INSERT INTO typecho_options (name,user,value) VALUES (?,0,?)');
    foreach ($defaults as $name => $value) $insert->execute(array($name, (string)$value));
    $pdo->exec("INSERT INTO typecho_users (uid,name,screenName,mail,\"group\") VALUES (1,'owner','Owner','owner@example.test','administrator')");
    $insert = $pdo->prepare('INSERT INTO typecho_contents (cid,title,slug,created,modified,text,authorId,type,status,password,commentsNum,allowComment,allowPing,allowFeed,template) VALUES (?,?,?,?,?,?,1,\'post\',\'publish\',\'\',1,1,0,1,NULL)');
    for ($id = 1; $id <= 6; $id++) {
        $insert->execute(array($id, 'Test article ' . $id, 'post-' . $id, time() - 1000 + $id, time() - 1000 + $id, '<!--markdown-->正文 **安全测试**'));
    }
    $pdo->exec("UPDATE typecho_contents SET type='page',slug='about',title='普通独立页面' WHERE cid=2");
    $pdo->exec("UPDATE typecho_contents SET type='page',slug='love',template='loveListPage.php',text='[loveList][item status=\"1\"]一起旅行[/item][/loveList]' WHERE cid=3");
    $pdo->exec("UPDATE typecho_contents SET password='secret',text='TOP_SECRET_BODY' WHERE cid=4");
    $pdo->exec("INSERT INTO typecho_comments (coid,cid,created,author,authorId,ownerId,mail,url,ip,text,type,status,parent) VALUES (1,1,1,'Guest',0,1,'a@example.test','https://example.test','127.0.0.1','测试留言','comment','approved',0)");
}
if ($pdo->query("SELECT value FROM typecho_options WHERE name='secret' AND user=0")->fetchColumn() !== $defaults['secret']) {
    throw new RuntimeException('Refusing to use a database that is not the isolated Brave fixture');
}
$db = new \Typecho\Db('Pdo_SQLite');
$db->addServer(array('file' => $testDb), \Typecho\Db::READ | \Typecho\Db::WRITE);
\Typecho\Db::set($db);
\Widget\Init::alloc();

// 浏览器 fixture 仅由本机 CLI server 使用，与产品代码和生产路由隔离。
if (defined('BRAVE_TEST_HTTP')) {
    header('X-Brave-Test-Fixture: typecho13');
    \Typecho\Router::dispatch();
    return;
}
require __TYPECHO_ROOT_DIR__ . '/usr/themes/Brave/functions.php';
$count = 0;
function integrationCheck($value, $message)
{
    global $count;
    if (!$value) throw new RuntimeException($message);
    $count++;
}
$options = \Widget\Options::alloc();
$settings = array('commentAntiSpam' => '1', 'commentCheckReferer' => '1', 'commentMaxNestingLevels' => '999', 'commentAllowImg' => '0', 'boyname' => '中文昵称');
$options->theme = 'previous-theme-in-memory';
themeConfigHandle($settings, true);
$options->theme = 'Brave';
$themeRow = $db->fetchRow($db->select()->from('table.options')->where('name = ?', 'theme:Brave'));
integrationCheck(json_decode($themeRow['value'], true) === $settings, '1.3 JSON theme settings must persist');
$settings['commentAntiSpam'] = '0';
themeConfigHandle($settings, false);
integrationCheck((int)$db->fetchRow($db->select('value')->from('table.options')->where('name = ?', 'commentsAntiSpam'))['value'] === 0, 'Feedback must see saved anti-spam flag');
integrationCheck((int)$options->commentsMaxNestingLevels === 10, 'nesting clamp');
integrationCheck(strpos($options->commentsHTMLTagAllowed, '<img') === false, 'images default disabled');
$options->commentsRequireUrl = 1;
themeInit();
integrationCheck($options->commentsRequireUrl === 1, 'theme must preserve site required URL policy');
integrationCheck((int)$options->commentsAntiSpam === 0, 'GET must use same anti-spam setting as POST');
$settings['commentAntiSpam'] = '1';
themeConfigHandle($settings, false);

// Request 的 URL 有缓存；利用反射只重置测试对象，模拟下一次独立 HTTP 请求。
$request = \Typecho\Request::getInstance();
function fixtureUrl($uri)
{
    $property = new ReflectionProperty(\Typecho\Request::class, 'requestUri');
    $property->setAccessible(true);
    $property->setValue(\Typecho\Request::getInstance(), $uri);
}
fixtureUrl('/archives/1/?a=one%20two&_pjax=%23pjax-container');
$_SERVER['HTTP_X_PJAX'] = 'true';
integrationCheck(App::commentToken() === \Widget\Security::alloc()->getToken($origin . '/archives/1/?a=one%20two'), 'PJAX CSRF token must match browser Referer');
fixtureUrl('/archives/1/?_pjax=%23pjax-container');
integrationCheck(App::commentToken() === \Widget\Security::alloc()->getToken($origin . '/archives/1/'), 'PJAX-only query must not leave a trailing question mark');
unset($_SERVER['HTTP_X_PJAX']);
$_GET['replyTo'] = '1';
integrationCheck(App::commentReplyParent() === 1, 'native Widget Request filter API');
unset($_GET['replyTo']);
fixtureUrl('/about.html');
$page = \Widget\Archive::allocWithAlias('ordinary-page', array('type' => 'page', 'checkPermalink' => false), array('slug' => 'about'));
$options->头部自定义 = '<script>window.customCodeMustStayDisabled = true;</script>';
ob_start();
$page->render();
$html = ob_get_clean();
integrationCheck(strpos($html, '正文') !== false && strpos($html, '普通独立页面') !== false, 'ordinary page must render body through page.php');
integrationCheck(strpos($html, 'name="_"') !== false, 'real comment form includes CSRF token');
integrationCheck(strpos($html, 'onload="') === false, 'no inline stylesheet handler');
integrationCheck(strpos($html, 'media="print"') === false, 'system font default produces no remote font CSS');
integrationCheck(strpos($html, 'gfonts.ctfile.com') === false, 'local font default must make no remote connection');
integrationCheck(!preg_match('/<script src=[^>]*\bdefer\b/', $html), 'synchronous dependencies remain compatible');
integrationCheck(strpos($html, 'window.TypechoComment =') === false, 'header must not install stale native reply script');
integrationCheck(strpos($html, 'customCodeMustStayDisabled') === false, 'custom code stays opt-in');

// 真实 head 模板的配置分支：CDN 使用固定发布路径，远程字体按选项加载，非法 CSP 安全回退。
$options->assetsSource = 'cdn';
$options->fontSource = 'remote';
$options->cspPolicy = "default-src 'self'\r\nInjected: yes";
ob_start();
(function () { include __TYPECHO_ROOT_DIR__ . '/usr/themes/Brave/base/head.php'; })->call($page);
$cdnHead = ob_get_clean();
integrationCheck(strpos($cdnHead, 'jquery@3.7.1/dist/jquery.min.js') !== false, 'CDN fixed npm artifact');
integrationCheck(substr_count($cdnHead, 'integrity="sha384-') === 3, 'all head CDN assets carry SRI');
integrationCheck(strpos($cdnHead, 'gfonts.ctfile.com/css2?family=Inter:') !== false, 'remote font opt-in');
integrationCheck(strpos($cdnHead, '已使用内置默认策略') !== false, 'invalid custom CSP falls back');
// 自定义完整策略仍须匹配本响应 nonce，不能把占位符原样发给浏览器或复用静态值。
$options->cspPolicy = "default-src 'self'; script-src 'self' 'nonce-{nonce}'; style-src 'self' 'unsafe-inline'";
ob_start();
$noncePolicy = (function () {
    include __TYPECHO_ROOT_DIR__ . '/usr/themes/Brave/base/head.php';
    return $cspPolicy;
})->call($page);
ob_end_clean();
integrationCheck(strpos($noncePolicy, "'nonce-" . App::scriptNonce() . "'") !== false, 'custom policy uses the response nonce');
integrationCheck(strpos($noncePolicy, '{nonce}') === false, 'no unresolved CSP nonce placeholder');
$options->enableCustomCode = 1;
ob_start();
(function () { include __TYPECHO_ROOT_DIR__ . '/usr/themes/Brave/base/head.php'; })->call($page);
$customHead = ob_get_clean();
integrationCheck(strpos($customHead, 'customCodeMustStayDisabled') !== false, 'trusted custom code can be explicitly enabled');
integrationCheck(!preg_match('/<script src=[^>]*\bdefer\b/', $customHead), 'custom code mode preserves synchronous jQuery compatibility');
$options->assetsSource = 'local';
$options->fontSource = 'local';
$options->enableCustomCode = 0;
$options->cspPolicy = '';

$locked = \Widget\Archive::allocWithAlias('protected-post', array('type' => 'post', 'checkPermalink' => false), array('cid' => 4));
ob_start();
$locked->content();
$protectedHtml = ob_get_clean();
integrationCheck(strpos($protectedHtml, 'TOP_SECRET_BODY') === false, 'password-protected content must not leak');
integrationCheck(strpos($protectedHtml, 'protectPassword') !== false, 'password form remains available');
integrationCheck(!$locked->allow('comment'), 'cannot comment without content password');

echo "Typecho 1.3 集成测试：{$count} passed, 0 failed\n";
