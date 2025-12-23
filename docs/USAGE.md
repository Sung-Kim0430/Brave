# 使用说明（Brave - Typecho 情侣主题）

## 安装

1. 将主题目录放到 Typecho 的主题目录中（通常是 `usr/themes/`）。
2. 确保目录名为 `Brave`（与仓库名无关，以实际主题目录名为准）。
3. 进入 Typecho 后台 → `控制台` → `外观`，启用 `Brave` 主题。

## 页面模板

本主题包含多个自定义页面模板文件（文件头部含 `@package custom`），可在创建「独立页面」时选择对应模板：

- `indexPage.php`：主题首页（展示「已相伴」计时 + 三个入口卡片）
- `commentPage.php`：祝福板（评论墙）
- `loveListPage.php`：Love List（配合短代码渲染恋爱清单）

使用方式（以 Typecho 后台为准）：

1. 后台 → `管理` → `独立页面` → `新增`
2. 在页面设置中选择对应「模板」
3. 发布后将页面链接填入主题设置（见下文），或自行在导航/文章中引用

## 主题设置项

主题设置定义在 `functions.php:21` 的 `themeConfig($form)` 中，常用项如下：

- `navsay`：导航栏右侧文字
- `heroimg`：头部大图链接
- `lovetime`：恋爱起始日期（示例：`2021/06/26`）
- `boy` / `girl`：头像链接
- `boyname` / `girlname`：昵称
- `loveListPageIcon` / `loveListPageLink`：首页 Love List 卡片图标与链接
- `blessingPageIcon` / `blessingPageLink`：首页祝福板卡片图标与链接
- `timePageIcon`：首页点滴时光图标（链接当前写死为 `/index.php/blog/`，可自行改主题代码或通过路由配置适配）

安全相关（推荐保持默认）：

- `commentAllowImg`：评论允许图片开关；关闭可减少追踪像素与外链风险
- `commentAntiSpam`：评论反垃圾开关；开启可降低垃圾评论风险
- `commentCheckReferer`：评论 Referer 检查；更安全但可能导致部分环境无法评论
- `commentMaxNestingLevels`：评论最大嵌套层数（建议 3~10）
- `loveListTitleAllowHtml`：Love List 标题是否允许少量 HTML（兼容模式）
- `assetsSource`：静态资源加载方式（默认本地，减少 CDN 供应链风险）
- `fontSource`：字体加载方式（默认本地/系统字体；在线字体会引入第三方字体链接）
- `cdnEnableSRI`：CDN 模式下是否为外链资源启用 SRI（默认开启）
- `cdnEnableCSP`：CDN 模式下是否启用 CSP（默认开启）
- `cspPolicy`：自定义 CSP 策略（可选；留空使用主题内置默认）

高级项（具备脚本/样式执行能力，请谨慎授权）：

- `enableCustomCode`：是否在前台输出下述自定义 HTML/CSS/JS 字段（默认开启；关闭更安全）
- `头部自定义`：输出到 `base/head.php` 的 `<head>` 内
- `Css自定义`：输出到 `base/head.php` 的 `<style>` 内
- `底部自定义`：输出到 `base/footer.php` 的 `</body>` 之前
- `pjax回调`：Pjax `complete` 时执行的回调片段（输出到 `base/footer.php` 的 JS 里）

## Love List 短代码

短代码在 `core/App.php` 的 `loveListAcc()` 中实现（调用 `add_shortcode('loveList', ...)` 注册）。

格式示例（在文章/页面内容里使用）：

```text
[loveList]
[item status="0" img="https://example.com/a.jpg"]一起去看海[/item]
[item status="1" img="https://example.com/b.jpg"]一起做饭[/item]
[/loveList]
```

字段说明：

- `status="0"`：未完成（显示 `svg/todo.svg`）
- `status="1"`：已完成（显示 `svg/ok.svg`）
- `img`：卡片背景图（渲染为 `background-image`）
