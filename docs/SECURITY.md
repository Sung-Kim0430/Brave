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
  - `core/shortcodes.php`

- 评论输出净化（祝福板）：`commentPage.php` 对评论内容进行二次净化（白名单标签 + URL 协议校验 + 移除事件属性），并提供 `commentAllowImg` 开关来控制是否允许评论图片。
- Love List 输出加固：`core/App.php` 对 `[item]` 的 `status/img/title` 做了 `isset` 检查与上下文转义，并提供 `loveListTitleAllowHtml` 兼容开关（仅允许少量标签）。
- 主题设置 URL 输出加固：`base/nav.php`、`indexPage.php` 对头像/图标/跳转链接等配置项做 URL 规范化 + 属性转义，降低恶意协议（`javascript:` 等）与属性注入风险。
- JS 字符串输出加固：`base/footer.php` 的 `lovetime` 使用安全的 JS 字符串编码输出，避免配置被注入导致脚本语法错误或意外执行。
- Shortcodes 安全降级：`core/shortcodes.php` 在缺失 `wp_kses_*` 依赖时跳过 HTML 标签/属性内部短代码解析，避免 fatal 并减少属性注入风险。

## 高权限配置项的风险提示

主题设置中包含可直接输出 HTML/CSS/JS 的字段（见 `functions.php:52` 起）：

- 可通过 `enableCustomCode` 关闭这些字段在前台的输出（更安全；不需要自定义代码时建议关闭）。

- `头部自定义`：输出到 `<head>` 内
- `Css自定义`：输出到 `<style>` 内
- `底部自定义`：输出到 `</body>` 前
- `pjax回调`：输出到 JS 逻辑中执行

这些字段的能力等价于「在前台执行任意脚本」。请确保只有可信管理员账号可以修改主题设置，并启用强密码、二步验证（如有）、限制后台暴露面等。

补充说明：

- `Css自定义` 与 `pjax回调` 会做“关闭标签”序列的最小处理（例如 `</style>` / `</script>`），降低意外打断页面结构导致的注入风险；但它们仍属于高权限能力，不应开放给不可信账号。

## 供应链风险（CDN）

主题支持两种静态资源加载方式（见 `functions.php` 的 `assetsSource` 选项）：

- 本地（默认）：从主题目录 `base/vendor/` 加载 jQuery / Bootstrap / pjax / nprogress，降低供应链风险。
- CDN（兼容）：继续从第三方 CDN 加载资源（见 `base/head.php`、`base/footer.php`）。
  - 默认启用 `cdnEnableSRI`：为外链脚本/样式添加 `integrity`（SRI）校验与 `crossorigin="anonymous"`。
  - 默认启用 `cdnEnableCSP`：启用 CSP（Content-Security-Policy）；主题会尽量通过响应头发送 CSP，并在无法设置响应头时回退为 `<meta http-equiv>`。
  - 可选配置 `cspPolicy`：自定义 CSP 策略（留空使用主题内置默认策略）。

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
