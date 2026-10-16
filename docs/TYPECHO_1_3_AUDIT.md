# Typecho 1.3 适配与 OWASP 检查记录

日期：2026-10-16。目标：Brave 主题 1.3.1。对照官方 [Typecho v1.3.0](https://github.com/typecho/typecho/tree/e9f23304bf20c0c355ef1df9634d42148cbc53b5)，重点检查主题暴露的输入、输出和调用流程。不是 Typecho 核心、服务器或插件的完整渗透测试，也不构成零漏洞保证。

## 适配问题与修复

- **GET/POST 防护配置不一致**：原先在 themeInit 临时覆盖评论 AntiSpam/Referer；内核 Feedback 从外部 Archive 查找文章时跳过该初始化，导致“主题显示已开启”不能证明 POST 真正校验。新增受内核管理员/CSRF 流程保护的 themeConfigHandle，按 1.3 JSON 格式保存主题设置，同时持久保存内核评论配置。GET 沿用实际内核值；升级需保存一次设置。
- **PJAX 评论脚本失效**：原生 header 中的反垃圾脚本只绑定 DOMContentLoaded，回复脚本也捕获首次页面 respondId；PJAX 不更新 head。改用表单中的原生 `_` 令牌和外部委托回复；清除内部 `_pjax` 查询参数以匹配可见 Referer。
- **取消入口重复/不可见**：原先每条评论都输出相同 cancel-comment-reply-link id 且外层永久隐藏。改为响应框中的单一入口，随表单移动，支持捕获阶段处理与无 JS 回退。
- **网址必填不一致**：原先试图修改 commentsRequireURL，实际内核选项为 commentsRequireUrl。现在保留站点要求，并让表单反映必填配置。
- **普通独立页面缺少模板**：补 page.php，复用正文与评论布局，避免回退到列表模板。
- **输出/解析边界**：标题保留已有 HTML 实体，非法 UTF-8 和截断不会清空输出；URL 拒绝浏览器/PHP 解释不一致的反斜杠和畸形 authority；相邻文章链接追加白名单净化；移除弃用的全局实体加载开关并恢复 libxml 错误模式。
- **CSP 配置与降级**：默认由 unsafe-inline 脚本改为每响应随机 nonce；失效自定义策略回退有效默认策略，meta 在资源前输出；MIME、点击劫持和 Referer 响应头独立于 CSP 开关。插件和高权限自定义代码的边界见 SECURITY.md。
- **供应链完整性**：本地副本增加哈希清单与 LF 约束。对 CDN 实测发现动态生成 min.js 的压缩器版本会改变注释/字节；改为固定 npm 发布文件，重新验证 CDN SRI。

以上包含功能缺陷、条件性安全配置风险和纵深加固，不能把每一项都算成独立可利用漏洞。

## OWASP Top 10 2025 对照

基准采用 [OWASP Top 10:2025](https://owasp.org/Top10/2025/)，并补查 2021 列表中的 SSRF/XXE、CSRF 与相关 CWE。

1. **A01 访问控制失效**：评论提交保留原生 Feedback 权限/父评论归属检查；配置写入使用原生管理员+CSRF 流程；集成测试确认密码正文不泄漏、未解锁不能评论。主题没有自有管理员 API 或文件下载端点。CSRF 验证覆盖有效、无效令牌与 PJAX。
2. **A02 安全配置错误**：修复 GET/POST 保护设置分裂、无效 CSP 静默降级，默认 nonce CSP；资源默认本地、字体默认系统、自定义代码默认关闭。代理、缓存、生产 display_errors 仍需站点配置。
3. **A03 软件供应链失效**：审核固定依赖、发布文件、SRI、哈希与 CDN 来源。2026-10-16 向 OSV querybatch 提交 npm bootstrap 4.6.2、jquery 3.7.1、jquery-pjax 2.0.1、nprogress 0.2.0，返回均无匹配项；这是该次数据库查询结果，不表示依赖未来不会披露漏洞，也不是上游维护承诺。
4. **A04 密码学失效**：不新增口令或自有加密算法；CSP nonce 使用 random_bytes(24)。评论令牌沿用内核协议，不替换其算法。HTTPS、Cookie 安全属性和密钥轮换由内核/部署管理。
5. **A05 注入**：评论/作者 DOM 白名单、URL/CSS/JS/HTML 上下文处理，增加 mXSS、事件属性、协议混淆回归；配置 SQL 使用 Db 构造器与占位参数。主题无 shell 执行及用户控制的包含路径。可信正文允许 HTML，是已声明的发布权限边界。
6. **A06 不安全设计**：短代码/评论有处理长度与项目上限，限制嵌套；匿名请求不写主题配置。没有把浏览器图片链接黑名单伪称为 SSRF 防护。防刷/限流保留内核机制，未声称主题可抗 DDoS。
7. **A07 身份验证失效**：登录、退出、用户名冒用防护依赖 Typecho；身份昵称转义，退出链接排除 PJAX。账号 MFA、会话生命周期和登录限流不由主题实现。
8. **A08 软件或数据完整性失效**：主题设置用 JSON；外部发布资源默认 SRI；本地依赖哈希纳入测试。未新增 unserialize，未开放把用户内容当 PHP/JS 配置执行的接口。
9. **A09 安全日志与告警失效**：主题不记录邮箱、口令或 CSRF token 到日志，不新增伪日志系统；配置拒绝有可见注释说明，PHP 异常交由内核/服务器。日志聚合、审计保留与告警仍需部署层完成，本轮没有线上告警验证。
10. **A10 异常条件处理不当**：处理非法配置类型、UTF-8、HTML 解析失败/错误状态恢复、超限、缺图、非法日期和 PJAX 失败回退。错误页面不应显示生产调试信息。测试不覆盖磁盘满、数据库故障或所有插件异常。

**SSRF / XXE / 反序列化补查**：主题无服务器远程抓取；DOM HTML 解析禁止外部加载并测试实体输入；无 XML 反序列化或自有 PHP 对象反序列化。它们在当前主题范围内未发现可利用入口，不能推广为整个 Typecho/插件没有对应风险。

## 验证与限制

已使用 Windows PHP 8.2.34、8.4.26 运行行为/模板/安全与官方真内核 SQLite 集成测试；独立 Chrome 完成浏览器回归，详情与命令见 [测试说明](../tests/README.md)。浏览器实测了 PJAX 后回复、取消、成功提交和错误令牌拒绝，并验证 CSP 拦截内联事件及默认无第三方请求。CI 增加 PHP 7.4/8.2/8.4 真内核矩阵；本地未运行 PHP 7.4，尚不能把未运行的远端 CI 记为通过。

未提供生产数据库/站点，所以未完成生产插件、HTTPS 代理、真实缓存隔离、生产限流、日志告警、并发/容量、线上字体/CDN 可达性及真实 FCP/LCP 基准。需要部署环境验证的项目保留为明确边界，而非使用“全部清零”表述。

## 源码依据

- [Archive 初始化、header、commentUrl 与模板回退](https://github.com/typecho/typecho/blob/e9f23304bf20c0c355ef1df9634d42148cbc53b5/var/Widget/Archive.php)
- [Security 原生令牌协议](https://github.com/typecho/typecho/blob/e9f23304bf20c0c355ef1df9634d42148cbc53b5/var/Widget/Security.php)
- [Feedback 提交检查](https://github.com/typecho/typecho/blob/e9f23304bf20c0c355ef1df9634d42148cbc53b5/var/Widget/Feedback.php)
- [Themes Edit 配置保存与权限](https://github.com/typecho/typecho/blob/e9f23304bf20c0c355ef1df9634d42148cbc53b5/var/Widget/Themes/Edit.php)
- [jsDelivr 对动态文件与 SRI 的说明](https://www.jsdelivr.com/using-sri-with-dynamic-files)
- [OSV API](https://google.github.io/osv.dev/api/)
