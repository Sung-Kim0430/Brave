<?php
/** 仅供 php -S 127.0.0.1 的隔离 fixture，不得用作生产路由器。 */
if (PHP_SAPI !== 'cli-server' || !getenv('TYPECHO_ROOT')) {
    http_response_code(404);
    exit;
}
$fixtureRoot = realpath(getenv('TYPECHO_ROOT'));
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = realpath($fixtureRoot . $path);
// 只有 fixture 根目录内的真实静态文件才交给内置服务器；拒绝通过 ../ 越出测试目录。
if ($file && strpos($file, $fixtureRoot . DIRECTORY_SEPARATOR) === 0 && is_file($file)) {
    return false;
}
define('BRAVE_TEST_HTTP', true);
require __DIR__ . '/typecho13.integration.php';
