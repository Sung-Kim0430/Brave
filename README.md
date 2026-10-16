# Brave

Brave 是一个 Typecho 情侣主题，提供「已相伴」计时、祝福板（评论墙）、Love List（恋爱清单）等页面模板与组件。

当前主题版本 **1.3.1**，适配 **Typecho 1.3.0**。PHP 最低 7.4（沿用内核要求），建议使用受维护的 PHP 并开启 DOM/libxml；缺少 DOM 时评论降级为纯文本。升级后请保存一次主题设置并清缓存，使评论防护配置同步到内核。

![Brave 主题截图](screenshot.png)

## 功能

- 主题首页：相伴时间计时 + 入口卡片（`indexPage.php`）
- 祝福板：以评论形式展示祝愿（`commentPage.php`）
- Love List：通过短代码渲染恋爱清单（`loveListPage.php` + `core/App.php`）
- Pjax + NProgress：页面无刷新加载与加载进度条（见 `base/footer.php`）

## 安装

1. 将主题放到 Typecho 主题目录（通常是 `usr/themes/`）
2. 主题目录名建议为 `Brave`
3. 进入 Typecho 后台 → `控制台` → `外观`，启用 `Brave`

更详细步骤与配置项说明见 `docs/USAGE.md`。

## 文档

- 使用与配置：`docs/USAGE.md`
- 安全说明：`docs/SECURITY.md`
- 适配与 OWASP 检查：`docs/TYPECHO_1_3_AUDIT.md`
- 字体与首屏优化：`docs/PERFORMANCE.md`
- 自动化与真内核测试：`tests/README.md`

## 安全提示（重要）

本主题包含可直接输出 HTML/CSS/JS 的设置项（例如 `头部自定义`、`Css自定义`、`底部自定义`、`pjax回调`）。
这些能力等价于「在前台执行任意脚本」，默认关闭；需要时请由可信管理员显式开启，并确保只有可信管理员账号能修改主题设置。

主题默认使用本地 `base/vendor/` 加载前端依赖，以降低 CDN 供应链风险；也可在主题设置中切换到 CDN 模式。
CDN 模式使用固定 npm 发布文件并默认开启 SRI；CSP 在本地/CDN 模式均默认开启。关闭自定义代码时采用随机 nonce 脚本策略。默认系统字体不发起字体网络请求；可选远程字体按非阻塞方式加载。详见对应文档，性能收益需要在实际部署环境测量。
