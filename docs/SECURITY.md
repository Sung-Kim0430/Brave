# 安全说明（Brave - Typecho 情侣主题）

本主题是前台渲染类主题，安全边界主要取决于「谁能发布内容」与「谁能修改主题设置」。

## 已做的基础加固

- 外链打开方式：`base/footer.php` 中对 `target="_blank"` 的外链补充 `rel="noopener noreferrer"`，降低 tabnabbing 风险。
- 直接访问保护：为以下文件补充 `__TYPECHO_ROOT_DIR__` 检查，避免被直接访问时触发错误信息泄露（在开启 `display_errors` 的环境尤为有用）：
  - `base/head.php`
  - `base/nav.php`
  - `base/footer.php`
  - `commentPage.php`
  - `indexPage.php`
  - `loveListPage.php`
  - `core/App.php`

- 评论输出净化（祝福板）：`commentPage.php` 对评论内容进行二次净化（白名单标签 + URL 协议校验 + 移除事件属性），并提供 `commentAllowImg` 开关来控制是否允许评论图片；图片开启时仍会移除缺少安全 `src` 的 `<img>`。
- 评论作者输出净化（祝福板）：`commentPage.php` 对 `$comments->author()` 的输出做更严格的白名单净化（仅允许 `<a>`），降低作者名/作者链接在不同 Typecho 版本与配置下带来的 XSS/排版注入风险。
- 登录态评论表单输出加固：`commentPage.php` 对当前登录用户昵称使用文本转义，并对资料/退出链接使用安全 URL 规范化。
- 评论表单属性输出加固：`commentPage.php` 对回复框 `id` 与评论提交 `action` 使用本地上下文转义，避免直接依赖 Typecho 的 echo 型辅助方法。
- HTML 解析防护：`core/App.php` 的 DOM 解析（用于评论/少量 HTML 白名单净化）显式禁用外部实体/网络访问（`LIBXML_NONET`），作为防御性措施避免潜在 XXE/意外外联。
- Love List 输出加固：`core/App.php` 对 `[item]` 的 `status/img/title` 做了 `isset` 检查与上下文转义，并提供 `loveListTitleAllowHtml` 兼容开关（仅允许少量标签）。
- Love List 状态解析收紧：仅 `status="1"` 视为已完成，其余缺省或异常值都按未完成渲染，避免异常输入被误判为完成态。
- Love List 图片 URL 统一使用 `buildBackgroundImageStyle()` 方法进行转义，确保 CSS 注入防护一致性。
- 主题设置 URL 输出加固：`base/nav.php`、`indexPage.php` 对头像/图标/跳转链接等配置项做 URL 规范化 + 属性转义，降低恶意协议（`javascript:` 等）与属性注入风险；URL 会先做一次 HTML entity 解码再校验和输出，避免已编码的安全 URL 在最终属性中被二次编码；在危险协议与显式 scheme 校验后，允许 `blog/` 这类普通相对站内路径。
- 页面导言输出加固：各页面导言相关配置项仅按纯文本渲染（HTML 转义 + 换行转 `<br>`），避免通过导言字段注入脚本。
- 标题文本输出加固：`base/head.php` 会捕获 Typecho 的归档/搜索标题片段，并与站点标题一起按 `<title>` 纯文本上下文转义输出；导航栏与页脚中的站点标题也使用主题本地转义，避免依赖不同 Typecho 版本的内部过滤行为。
- 文章列表/详情标题输出加固：`index.php`、`post.php` 对文章标题按纯文本输出转义，列表页文章链接也经过安全 URL 规范化。
- 主题安全开关默认值处理：`core/App.php` 的配置开关 helper 仅接受明确的 `1/0`，异常值回退到调用处声明的默认值；评论嵌套层数使用统一范围 helper 限制在 1~10。
- JS 字符串输出加固：`base/footer.php` 的 `lovetime` 使用安全的 JS 字符串编码输出，避免配置被注入导致脚本语法错误或意外执行。
- 前端 DOM 输出加固：`base/main.js` 的图片预览层与 `base/footer.php` 的运行时计数组件均使用 `createElement` / `textContent` 构建，避免新增一方 `innerHTML` / jQuery `.html()` 注入面。
- 图片预览链接收紧：文章图片预览只接管普通图片文件链接，不再把 `data:image` 链接提升为预览目标。
- PJAX 失败回退加固：`base/footer.php` 在回退跳转前校验 URL 为同协议、同主机、同端口的 HTTP(S) 地址，避免异常 URL 被直接传给导航 API。
- 日期解析边界检查：`base/footer.php` 的 `parseLoveTime()` 函数严格校验主题支持的日期/时间格式与年月日时分秒范围，避免错误输入被浏览器或 `parseInt` 宽松归一化。
- CSP 输出优化：`base/head.php` 优先通过 HTTP header 发送 CSP，仅在 header 发送失败时回退到 meta 标签，避免重复输出。
- Love List 短代码解析：`core/App.php` 使用主题专用解析器，仅处理 `[loveList]` 内的 `[item]`，避免引入通用短代码兼容层的额外复杂度与属性注入面。
- SVG 外链 DTD 清理：`svg/*.svg` 移除 `<!DOCTYPE ...>` 外链声明，减少浏览器/解析器尝试加载外部 DTD 的风险与额外请求。

## 2026-09-11 修复（独立审计，共三轮）

详见 `docs/BUG_AUDIT_2026-09-11.md`。以下为触及安全边界的改动。

### 评论净化：移除「整体转义」fast path

- **原缺陷**：`core/App.php` 的 `sanitizeHtmlFragment()` 中存在一条 fast path，匹配 `^<(p|br|strong|em|b|i)>.*</\1>$`（`/s`）后直接 `htmlspecialchars()` 整段返回。HyperDown 渲染的单段/多段评论正好命中该形状，导致**祝福板所有评论显示成 `<p>祝你们幸福</p>` 原始标签文本**。
- **方向**：过度转义，不构成 XSS，但属防御性代码反向破坏功能。
- **现行为**：删除该 fast path，所有含标签的片段统一走 DOMDocument 白名单分支；仅保留 `strpos($html, '<') === false` 的纯文本 fast path（该分支行为正确）。
- **连带修复**：`loveListTitleAllowHtml=1` 时 `<strong>/<em>/<br>` 此前同样被转义，现已按开关文档生效。

### 超长输入：截断后继续净化

- 原实现在超过 50000 字符时直接返回转义后的纯文本（长文破版）。现改为**截断后继续走白名单净化**，并在末尾追加「（内容过长，已截断）」提示。
- 新增常量：`MAX_HTML_LENGTH` / `MAX_SHORTCODE_LENGTH` / `MAX_SHORTCODE_ATTR_LENGTH`。阈值目前不可配置。

### 自定义代码脚本白名单：修正判定口径

- **原缺陷**：`base/head.php`、`base/footer.php` 用 `src\s*=\s*["\']?(?!https?://(host))` 做域名校验，引号可选使负向先行可在**未消费引号**的位置求值 → 任何带引号的合规脚本（含白名单域名与本站）都被判为不可信并静默替换成一行注释；同时 `preg_quote(null)` 在 PHP 8.1+ 触发弃用告警。
- **现行为**：抽出 `App::findUntrustedScriptHosts($html, $trustedHosts)` —— 提取 `src`（引号/非引号三种写法）→ `parse_url` 取 host → 白名单比对，head/footer 共用同一实现。
- **重要**：该守卫**只是防误配的软约束，不是安全边界**。内联 `<script>` 仍会放行，而 `enableCustomCode` 本身就是「在前台执行任意脚本」的开关。不要用「外链域名白名单」当作限制不可信管理员的措施。

### CSP：校验放宽、降级可见、与样式加载解耦

- **校验**：原合法字符集 `[a-z0-9\s'\-:\/\.\*;_]` 缺少 `+ = , " @ % [ ]`，含 hash/nonce 的策略（如 `script-src 'sha256-abc+/='`）会被判非法并**静默降级为 meta**。现仅拒绝 `<>` 与 CR/LF，长度上限 2000 → 4000。
- **降级可见性**：一旦回退到 `<meta http-equiv="Content-Security-Policy">`，输出 HTML 注释说明原因，并提示 meta 形式**不支持** `frame-ancestors` / `report-uri` / `sandbox`。
- **样式加载解耦**：`style.css` 原先依赖 `<link rel="preload" onload="this.rel='stylesheet'">` 的内联事件处理器，需 `script-src 'unsafe-inline'`。自定义 CSP 一旦收紧，该处理器被拦 → **全站只剩内联关键 CSS**。现检测到策略中无 `'unsafe-inline'` 时改为同步加载 `style.css`。

### `normalizeUrl()`：删除永不生效的私网拦截

- 私网/回环地址拦截位于 `!$allowRelative` 分支，而所有调用点都传 `true` → 该分支不可达，属死代码。`PROJECT_STATUS.md` 曾把「SSRF 防护增强」列为已修复，与实现不符。
- 明确：本主题的 `normalizeUrl()` 只做**输出上下文转义与协议白名单**，PHP 侧不会发起请求，本就不存在 SSRF 面。已删除误导性分支并在注释中记录原因。
- **仍然有效且被行为测试覆盖**：危险协议拦截（`javascript:` / `data:` / `vbscript:` / `file:`，含实体与空白混淆绕过）、`user:pass@host` 凭据过滤、控制字符剥离。

### 默认 CSP 的 `img-src` 随协议收紧

- 原默认策略固定为 `img-src 'self' data: blob: https: http:`。HTTPS 站点上 `http:` 会让**混合内容图片被静默放行**（浏览器对图片这类被动混合内容只告警不拦截），CSP 的协议约束形同虚设。
- 现按请求协议决定：HTTPS 只放行 `https:`；HTTP 站点保留 `http:` 以兼容旧的外链图片。
- 协议判定见 `App::isHttpsRequest()`，识别 `HTTPS`、`SERVER_PORT=443` 与 `X-Forwarded-Proto`（多级代理取第一段）。
- 若 HTTPS 站点确实需要外链 http 图片，请用 `cspPolicy` 自定义策略显式放行 —— 这是显式授权，而不是默认行为。

### 内容硬上限改为可配置

- 原先写死 50000 字符，超长评论/文章会被截断破版且无法调整。
- 现可用主题设置 `contentMaxLength` 覆盖（默认 50000，夹在 10000~200000）。下限保证防护有效，上限避免 DOM 解析开销失控。
- 行为不变：超过时先截断再继续净化，并追加「内容过长，已截断」提示（不是整体转义）。
- 常量 `MAX_SHORTCODE_LENGTH` 已删除（与 `MAX_HTML_LENGTH` 重复且未被引用）。

### 评论嵌套回复入口

- 原先 `threadedComments()` 保留了嵌套渲染，但表单没有 `parent` 字段，后台开启「启用评论回复」后用户无法实际使用。
- 现补上 `<input type="hidden" name="parent" id="comment-parent" value="0">`，并在每条评论下输出「回复 / 取消回复」入口；仅当内核 `commentsThreaded` 开启时显示。
- 类名（`comment-reply cp-{id}`、`cancel-comment-reply cl-{id}`）与内核 `TypechoComment` 脚本约定一致。该脚本由 `$this->header()` 自动注入，主题未重复实现 —— 若用 `header('commentReply=')` 关闭了它，回复功能会失效。
- 该入口依赖内联 `onclick`，因此同样需要 CSP 允许 `script-src 'unsafe-inline'`（与 Typecho 评论机制本身一致）。

### 页面语言

- `<html lang>` 原先硬编码 `zh-cn`，英文站点语义错误。现取 `htmlLang` 主题设置 → Typecho `lang` → `zh-CN`，并过滤为字母/数字/连字符后输出。属于语义/无障碍修正，不涉及安全边界。

### 评论区块抽出为 `base/comments.php`（第三轮）

- 评论列表、分页、表单原先只写在 `commentPage.php`，文章页要用就得复制一份。现抽为 `base/comments.php`，两个模板 `include` 同一份实现 —— 净化调用不会再出现「改了一处漏一处」。
- 用 `include` 而非 `$this->need()`：`need()` 会另开方法作用域，调用方预置的文案变量传不进去。这属于作用域选择，不改变信任边界。
- 所有从调用方传入的文案（区块标签、按钮、占位符、文本框标签）都经 `App::escapeHtml()` 实体化，不做「主题内传参所以可信」的假设。

### 文章页分类 / 标签输出改为主题侧转义（第三轮）

- 内核的 `$this->tags()` / `$this->category()` 把名称与链接**直接拼进 HTML 且不做任何转义**（`Widget\Base\Contents`），而标签名入库时并未实体化。
- 文章页不再调用这两个方法，改为自行遍历：链接走 `App::escapeUrlAttribute()`（拦掉 `javascript:` 等协议），名称走 `App::escapeHtml()`。
- 影响面：只有在后台创建「标签名/分类名为 HTML」时才体现，属纵深防御，不是当前的可利用漏洞。
- 上下篇导航仍直接用内核 `thePrev()` / `theNext()` 的输出：标题在入库时已由 `Contents::insert()` 实体化，链接由路由生成，属内核信任边界内。

> ⚠️ **未决项**：正因为标题在入库时已实体化，`index.php` / `post.php` 里对 `$this->title()` 再套一层 `App::escapeHtml()` 可能在含 `&` 的标题上二次转义（显示成 `&amp;`）。本次未改动 —— 需在真实 Typecho 实例上验证入库行为后再决定改哪一侧，贸然去掉主题侧转义会引入存储型 XSS 风险。详见 `docs/BUG_AUDIT_2026-09-11.md` §5「未决观察」。

---

## 高权限配置项的风险提示

主题设置中包含可直接输出 HTML/CSS/JS 的字段（见 `functions.php` 的 `themeConfig($form)`）：

- 可通过 `enableCustomCode` 关闭这些字段在前台的输出（更安全；不需要自定义代码时建议关闭）。
- `enableCustomCode` 默认关闭；需要统计代码或自定义脚本时应由可信管理员显式开启。

- `头部自定义`：输出到 `<head>` 内
- `Css自定义`：输出到 `<style>` 内
- `底部自定义`：输出到 `</body>` 前
- `pjax回调`：输出到 JS 逻辑中执行

这些字段的能力等价于「在前台执行任意脚本」。请确保只有可信管理员账号可以修改主题设置，并启用强密码、二步验证（如有）、限制后台暴露面等。

补充说明：

- `Css自定义` 与 `pjax回调` 会做“关闭标签”序列的最小处理（例如 `</style>` / `</script>`），降低意外打断页面结构导致的注入风险；这不是 CSS/JS 净化，它们仍属于高权限能力，不应开放给不可信账号。

## 供应链风险（CDN）

主题支持两种静态资源加载方式（见 `functions.php` 的 `assetsSource` 选项）：

- 本地（默认）：从主题目录 `base/vendor/` 加载 jQuery / Bootstrap / pjax / nprogress，降低供应链风险。
  - 2026-09-11 变更：`bootstrap-4.6.2.min.js` 实测**不含 Popper**（`createPopper` 缺失），已替换为 `bootstrap-4.6.2.bundle.min.js`（含 Popper）。该文件下载自 CDN 并通过 SRI 逐字节校验，与 `base/head.php` 中 CDN 模式引用的哈希一致。
- CDN（兼容）：继续从第三方 CDN 加载资源（见 `base/head.php`、`base/footer.php`）。
  - 默认启用 `cdnEnableSRI`：为外链脚本/样式添加 `integrity`（SRI）校验与 `crossorigin="anonymous"`。
  - Bootstrap 同样改用 `bootstrap.bundle.min.js`，SRI 为 `sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct`。
  - 默认启用 `enableCSP`：启用 CSP（Content-Security-Policy）；本地/CDN 资源模式均生效，主题会尽量通过响应头发送 CSP，并在无法设置响应头时回退为 `<meta http-equiv>`（降级会输出注释说明，且 meta 不支持 `frame-ancestors` / `report-uri` / `sandbox`）。
  - 可选配置 `cspPolicy`：自定义 CSP 策略（留空使用主题内置默认策略）。**注意**：若策略中不含 `script-src 'unsafe-inline'`，主题会自动把 `style.css` 改为同步加载（见上文），无需站长手动处理。

字体与外链：

- `fontSource=local`（默认）：不引入第三方字体链接，减少外部依赖；样式表中 `Inter` 不存在时会自动回退到系统字体。
- `fontSource=remote`（兼容）：从 `https://gfonts.ctfile.com` 加载 Inter 字体，存在供应链/可用性风险；该类动态字体样式通常不适合使用固定 SRI。

建议（可选）：

1. 尽量使用固定版本并自托管静态资源（本主题已默认启用本地模式）；
2. 如果继续使用 CDN，建议进一步引入 SRI（Subresource Integrity）与更严格的 CSP（Content-Security-Policy）。

## 评论与内容发布

若站点允许不可信用户发布内容/评论，请额外关注：

- 评论区允许的 HTML 标签范围
- Markdown 渲染策略与过滤
- 防刷与速率限制

以上属于站点整体策略，未在本次“保持功能不变”的修复范围内强制修改。
