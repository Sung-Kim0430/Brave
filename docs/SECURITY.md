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

## 高权限配置项的风险提示

主题设置中包含可直接输出 HTML/CSS/JS 的字段（见 `functions.php:52` 起）：

- `头部自定义`：输出到 `<head>` 内
- `Css自定义`：输出到 `<style>` 内
- `底部自定义`：输出到 `</body>` 前
- `pjax回调`：输出到 JS 逻辑中执行

这些字段的能力等价于「在前台执行任意脚本」。请确保只有可信管理员账号可以修改主题设置，并启用强密码、二步验证（如有）、限制后台暴露面等。

## 供应链风险（CDN）

主题默认从第三方 CDN 加载 jQuery / Bootstrap / pjax / nprogress 等资源（见 `base/head.php`、`base/footer.php`）。

建议（可选）：

1. 尽量使用固定版本并自托管静态资源；
2. 如果继续使用 CDN，考虑为关键脚本引入 SRI（Subresource Integrity）与更严格的 CSP（Content-Security-Policy）。

## 评论与内容发布

若站点允许不可信用户发布内容/评论，请额外关注：

- 评论区允许的 HTML 标签范围
- Markdown 渲染策略与过滤
- 防刷与速率限制

以上属于站点整体策略，未在本次“保持功能不变”的修复范围内强制修改。

