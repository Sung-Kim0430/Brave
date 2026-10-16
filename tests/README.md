# 测试说明

本主题无需构建，但已配置自动化测试。测试不是“无漏洞证明”；真实插件、代理和缓存环境还需要验证。

## 日常检查

```sh
node --check base/main.js
node --check base/comments.js
node --test tests/braveStaticContracts.test.mjs tests/vendorIntegrity.test.mjs
php tests/php/behavior.test.php
php tests/php/template.test.php
php tests/php/security.test.php
```

PHP 测试需要 DOM/libxml；缺失扩展时安全 suite 会报错而不是悄悄跳过。安全测试检查实际净化 DOM、常见 mXSS/XXE 输入、URL 歧义、非法 CSP、UTF-8 截断及 libxml 状态恢复。普通 Web 请求不能执行这些测试。

## 真 Typecho 1.3 集成测试

下载官方 Typecho `v1.3.0`，本轮固定提交 `e9f23304bf20c0c355ef1df9634d42148cbc53b5`，并把主题文件复制到该临时目录的 `usr/themes/Brave/`。至少包含根目录 PHP、`core/`、`base/`、`svg/`。不要连接已有站点或生产数据库。

需要 pdo_sqlite；Windows PHP 可能需要通过 php.ini 或 `-d extension=pdo_sqlite` 开启。设置 `TYPECHO_ROOT` 后运行：

```sh
TYPECHO_ROOT=/tmp/typecho php tests/php/typecho13.integration.php
```

PowerShell 设置方式：

```powershell
$env:TYPECHO_ROOT = 'C:/temp/typecho'
php tests/php/typecho13.integration.php
```

测试创建临时 SQLite，并调用真实 Options、Themes 配置流程、Security、Request、Archive、评论组件和 Markdown 渲染。覆盖主题 JSON 保存、全局评论配置、PJAX 令牌、普通页面、资源加载与密码保护。未指定 `BRAVE_TEST_DB` 时退出自动删除测试 DB。

CI 在 PHP 7.4、8.2、8.4 上运行上述 suite 与真内核集成；浏览器测试为本地可选项目，不隐式执行在线提交。

## 可选真实浏览器回归

**仅可针对临时 fixture 运行：会创建匿名评论。** 安装 Playwright/Chromium，或使用已有独立 Playwright 包和本机 Chrome。测试创建全新浏览器上下文，不读取用户浏览器资料。

在已准备的临时 Typecho 上启动本机服务器；数据库路径必须是新的临时文件：

```powershell
$env:BRAVE_TEST_DB = 'C:/temp/brave-browser-fixture.sqlite'
$env:BRAVE_TEST_ORIGIN = 'http://127.0.0.1:8133'
php -S 127.0.0.1:8133 -t $env:TYPECHO_ROOT tests/php/typecho13.router.php
```

在另一终端中运行（根据本机安装选择设置模块/浏览器路径）：

```powershell
$env:BRAVE_TEST_ORIGIN = 'http://127.0.0.1:8133'
$env:PLAYWRIGHT_MODULE = 'C:/tools/node_modules/playwright'
$env:CHROMIUM_EXECUTABLE = 'C:/Program Files/Google/Chrome/Application/chrome.exe'
node --test tests/typecho13.browser.test.mjs
```

脚本只接受 `http://127.0.0.1:端口`。验证实际 PJAX 而非整页跳转、回复/取消和唯一取消入口、错误令牌拒绝、有效令牌提交、内联事件被 CSP 拦截、默认模式无第三方网络请求和无前端异常。`BRAVE_SCREENSHOT` 可选指定截图文件。结束后停止服务器并删除临时 fixture 数据；不要发布这个测试服务器。

## 仍需人工复核

检查 Typecho 自定义模板、评论开关/网址必填、分页和登录退出；两种资源模式与远程字体；插件自定义脚本在 nonce CSP 下的兼容性；暗色模式及浏览器存储禁用场景；线上缓存、CDN、HTTP/HTTPS 代理和移动端。字体/首屏的线上速度需按 [性能说明](../docs/PERFORMANCE.md) 测量。
