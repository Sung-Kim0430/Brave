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

主题设置定义在 `functions.php` 的 `themeConfig($form)` 中，常用项如下：

- `navsay`：导航栏右侧文字
- `heroimg`：头部大图链接
- `lovetime`：恋爱起始日期（示例：`2021/06/26`、`2021-06-26` 或 `2021/06/26 08:30`）；非法日期/时间不会被自动修正，前台会显示“日期无效”
- `boy` / `girl`：头像链接
- `boyname` / `girlname`：昵称
- `loveListPageIcon` / `loveListPageLink`：首页 Love List 卡片图标与链接
- `blessingPageIcon` / `blessingPageLink`：首页祝福板卡片图标与链接
- `timePageIcon` / `timePageLink`：首页点滴时光图标与链接；链接留空时默认指向站点 `blog/` 路径

提示：

- 如果未设置头像/图标链接（留空或填入非法协议），主题会自动显示内置占位样式，避免出现“破图/空白”。
- 如果首页祝福板或 Love List 链接未配置为安全 URL，对应卡片会显示为禁用态；点滴时光链接留空时仍回退到站点 `blog/` 路径。
- 页面链接可填写完整 `http(s)` URL、以 `/` 开头的站内路径，或 `blog/` 这类普通相对路径；`javascript:` 等危险协议会被拒绝。

导言（每页可开关 + 可覆盖文案；纯文本）：

- `introHomeEnable` / `introHomeText`：首页（`indexPage.php`）导言显示与内容
- `introIndexEnable` / `introIndexText`：点滴时光列表（`index.php`）导言显示与内容；内容留空会回退主题默认导言
- `introPostEnable` / `introPostText`：点滴时光文章页（`post.php`）导言显示与内容
- `introCommentEnable` / `introCommentText`：祝福板（`commentPage.php`）导言显示与内容
- `introLoveListEnable` / `introLoveListText`：恋爱清单（`loveListPage.php`）导言显示与内容；内容留空会回退主题默认导言

说明：

- 导言内容按纯文本处理：会做 HTML 转义；换行会自动转换为 `<br>`。
- 需要“完全不显示导言”时：把对应 `*Enable` 设为隐藏即可。

安全相关（推荐保持默认）：

- `commentAllowImg`：评论允许图片开关；关闭可减少追踪像素与外链风险
- `commentAntiSpam`：评论反垃圾开关；开启可降低垃圾评论风险
- `commentCheckReferer`：评论 Referer 检查；默认开启，若代理/隐私策略导致评论失败可切换为兼容关闭
- `commentMaxNestingLevels`：评论最大嵌套层数（建议 3~10）
- 祝福板关闭新评论时，已有祝愿仍会展示，仅隐藏提交表单并显示关闭提示
- `loveListTitleAllowHtml`：Love List 标题是否允许少量 HTML（兼容模式）
- `assetsSource`：静态资源加载方式（默认本地，减少 CDN 供应链风险）
- `fontSource`：字体加载方式（默认本地/系统字体；在线字体会引入第三方字体链接）
- `enableDarkMode`：暗色模式开关（默认关闭）；开启后导航栏出现切换按钮：默认跟随系统，可手动切换并用 localStorage 记忆（`brave-theme`；Shift+点击恢复跟随系统）
- `cdnEnableSRI`：CDN 模式下是否为外链资源启用 SRI（默认开启）
- `enableCSP`：是否启用 CSP（默认开启；本地/CDN 资源模式均生效）
- `cspPolicy`：自定义 CSP 策略（可选；留空使用主题内置默认）

高级项（具备脚本/样式执行能力，请谨慎授权）：

- `enableCustomCode`：是否在前台输出下述自定义 HTML/CSS/JS 字段（默认关闭；需要统计代码或自定义脚本时再开启）
- `头部自定义`：输出到 `base/head.php` 的 `<head>` 内
- `Css自定义`：输出到 `base/head.php` 的 `<style>` 内
- `底部自定义`：输出到 `base/footer.php` 的 `</body>` 之前
- `pjax回调`：Pjax `complete` 时执行的回调片段（输出到 `base/footer.php` 的 JS 里）

## Love List 短代码

短代码在 `core/App.php` 的 `App::parseShortCode()` 中由主题专用解析器处理。

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
