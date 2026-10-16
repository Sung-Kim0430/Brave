# 安全说明（Brave 1.3.1 / Typecho 1.3）

更新：2026-10-16。以当前源码与自动化测试为准；旧审计报告属于历史记录，不能作为无漏洞保证。逐项检查范围见 [Typecho 1.3 适配与 OWASP 检查记录](TYPECHO_1_3_AUDIT.md)。

## 信任边界

- 匿名评论、评论作者、Cookie 和 URL 作为不可信输入处理。
- 文章正文遵循 Typecho 的发布权限，允许可信作者使用 HTML。主题不会把所有文章 HTML 限制成评论标签；不要给予不可信用户直接发布或安装插件的权限。
- 管理员可修改主题文件，`enableCustomCode=1` 允许前台执行自定义代码，属于高权限能力，默认关闭。脚本域名检查只是防误配措施，不是 JavaScript 沙箱。
- 数据库、账号口令、HTTPS、代理信任、限流、审计日志和告警仍由 Typecho 与部署环境负责。主题未新增登录、文件上传、远程抓取或独立评论 API。

## 评论、CSRF 与权限

评论列表、作者净化、表单位于 `base/comments.php`，文章、普通独立页面和祝福板共用。提交仍走原生 `Widget\Feedback`，由内核检查文章权限、父评论归属、登录身份、字段要求与评论间隔。

`commentAntiSpam`、`commentCheckReferer`、`commentMaxNestingLevels`、`commentAllowImg` 在管理员保存主题设置或首次启用主题时，通过 `themeConfigHandle()` 同步到内核评论选项。Typecho 1.3 外观配置回调接管整个保存过程，主题设置按 JSON 保存。该回调由内核的管理员权限和 CSRF 校验保护，前台访问不会写这些选项。

**升级后请保存一次主题设置并清缓存。** 未保存时，评论反垃圾与 Referer 校验以已有内核配置为准，避免只在 GET 开启、POST 实际未开启的错觉。保存会影响站点的全局评论配置，包括评论 Markdown/允许标签；切换到其他主题后这些内核设置仍然存在，可在「设置 → 评论」调整。主题保留 `commentsRequireUrl`，表单也会按该配置要求填写网址。

反垃圾开启时，表单使用原生 `Security::getToken(Request::getRequestUrl())` 生成 `_` 令牌。PJAX 请求中的内部 `_pjax` 参数被剔除，以匹配地址栏和提交 Referer。错误/缺失令牌由内核拒绝。令牌直接放在表单里，保留 CSRF 校验，但不再使用原生「等待鼠标/键盘操作再添加字段」启发式反垃圾；需要更强防刷时使用内核间隔限制或站点防刷设施。

回复交互由外部 `base/comments.js` 在捕获阶段委托处理，兼容 PJAX 与禁止 inline onclick 的 CSP。回复链接仍由内核生成，保留回复层数和权限判断；取消回复入口只渲染一次并随表单移动。无 JS 时通过原生 `replyTo` 链接及 `parent` 字段回复。

登录状态和请求 URL 会影响令牌。缓存不能在登录/匿名用户之间共享带令牌页面，也不能跨 URL 复用表单；缓存插件应清除旧页面并正确区分登录 Cookie。切换用户或修改安全设置后刷新页面。

完整页面缓存还必须考虑 CSP nonce：响应头与主题脚本属性必须使用同一响应的值。动态更新时须同时替换两处；不要长期重放静态 nonce 快照。具体缓存方案应在部署环境验证。

## XSS、URL、HTML 与资源消耗

- 文本与属性分别经过 `escapeHtml()`、`escapeUrlAttribute()` 等 helper。已有标题实体不会二次编码；非法 UTF-8 字节使用替换字符，避免整段内容变空。
- 评论 HTML 使用 DOM 白名单；去掉事件、style、id/name、未知属性和危险元素。链接限制 HTTP(S)/mailto，图片仅 HTTP(S)，`commentAllowImg=0` 为默认值。外链补 `nofollow ugc noopener noreferrer`，图片采用 lazy 与 no-referrer。
- HTML 解析不展开外部实体，禁用解析器外部加载并使用 `LIBXML_NONET`；不再调用 PHP 8 弃用且会污染全局状态的 `libxml_disable_entity_loader()`，并恢复 libxml 错误模式。
- URL 先解码一层 HTML 实体再校验协议；拒绝危险 scheme、反斜杠歧义、畸形 HTTP authority、用户名/密码段和非法 UTF-8。普通相对路径可用；绝对地址模式拒绝协议相对路径。
- Love List 的 `status` 仅明确为 `1` 才完成；标题默认纯文本，`loveListTitleAllowHtml=1` 时只允许少量格式标签。背景图通过 `buildBackgroundImageStyle()` 编码，缺图时不生成空占位。
- 相邻文章链接也经链接白名单净化，分类、标签、导言与记忆字段均按输出上下文处理。
- `contentMaxLength` 按 **UTF-8 字节**计量，默认 50000，范围 10000–200000。评论净化超限会安全截断并提示；短代码处理超限跳过解析、保留文章原文。短代码还限制属性长度及单个列表最多 200 项。这些上限只约束主题处理，并非 HTTP 请求体限流。

URL helper 只生成浏览器链接/图片，不在 PHP 发起请求。把私有 IP 从链接中删除不能构成服务器 SSRF 防护，因此没有这类误导性黑名单。主题未使用 XML 反序列化、用户可控 include、shell 执行或自有 unserialize。

## CSP 与响应头

`enableCSP=1` 默认开启，本地/CDN 模式均生效。优先发送响应头；已输出内容时，meta CSP 位于资源加载之前，但 meta 不支持 frame-ancestors、report-uri、sandbox。部署层应保证响应头可发送。

默认 `enableCustomCode=0` 时，`script-src` 使用 self 和每响应独立的随机 nonce，**不允许 unsafe-inline 脚本或内联事件**。主题自身脚本带 nonce；评论使用外部脚本，CSS 不依赖内联 onload。`style-src` 仍允许 unsafe-inline，以支持现有内联样式、背景图和 Bootstrap 布局，不能宣称这是完全严格的样式策略。

开启高权限自定义代码时，默认脚本策略改为 unsafe-inline 兼容模式，依赖脚本也保留同步加载以兼容现有自定义 jQuery 代码。自定义代码中的关闭 script/style 标签只做结构保护，不能把任意 JS/CSS 变成安全代码。

`cspPolicy` 为站长的完整策略替换：请在 `script-src` 中写入 `'nonce-{nonce}'`，主题会将 `{nonce}` 替换为本响应随机值，与主题内联初始化脚本一致。主题不会向自定义策略自动追加指令，也不会重写插件脚本；仍需自行允许所用插件、字体和 CDN 域名。不要填写固定 nonce。含控制字符、尖括号或替换后长度达到 4000 字节的策略会回退有效默认策略，不把无效配置塞入 meta。此检查验证可安全输出，不是完整 CSP 语法验证器。

默认 CSP 限制 object-src、form-action、base-uri、frame-ancestors 和 connect-src。图片允许 self、data/blob 和 HTTPS；HTTP 站点另允许 HTTP；请求安全判断与 Typecho 一致。浏览器仍可能自动升级/拦截混合内容，CSP 不能替代 HTTPS。

`X-Content-Type-Options: nosniff`、`X-Frame-Options: SAMEORIGIN`、`Referrer-Policy: strict-origin-when-cross-origin` 在响应头可写时独立发送，不依赖 CSP 开关。

## 依赖、SRI 与字体

默认 `assetsSource=local` 使用 `base/vendor/` 中固定版本：jQuery 3.7.1、Bootstrap 4.6.2 bundle/CSS、jquery-pjax 2.0.1、NProgress 0.2.0。版本、来源和哈希见 [依赖清单](../base/vendor/README.md) 及 `integrity.json`；测试核对本地哈希与模板 CDN/SRI 是否一致。

CDN 模式改用 jsDelivr 的固定 npm 版本发布文件。PJAX/NProgress 使用包内实际发布的原始 JS，避免动态生成 min.js 的压缩器更新导致固定 SRI 失效。`cdnEnableSRI=1` 默认开启，外链资源附 integrity 与 crossorigin。SRI 能验证拿到的字节，不能证明依赖本身没有漏洞，也不能防止 CDN 宕机。

`fontSource=local` 实际是系统字体回退，不包含随主题分发的字体文件，不请求 gfonts.ctfile.com。`fontSource=remote` 使用带 display=swap 的 Inter CSS；加载失败时保留系统字体。动态字体 CSS 无固定 SRI，远程字体仍有第三方可用性及供应链风险。

## 验证与部署

测试命令与隔离 fixture 说明见 [tests/README.md](../tests/README.md)。覆盖常见 XSS/mXSS 输入、URL 混淆、XXE 输入、CSP 非法配置、UTF-8 截断、内核配置保存、密码保护、PJAX 回复和真实评论提交。

仍需在实际站点验证插件兼容、自定义 CSP、缓存、CDN/远程字体网络、HTTPS 反向代理与移动端。生产环境关闭 display_errors，保留必要错误日志，不应将测试 fixture 或其临时数据库部署为正式站点。测试文件拒绝普通 Web 执行。
