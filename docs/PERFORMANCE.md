# 字体与首屏性能

更新：2026-10-16，适用于 Brave 1.3.1。下面区分已经实现的改动与需要部署条件的进一步优化；没有虚构性能提升百分比。

## 已实现

1. 默认 `fontSource=local` 使用系统字体回退，不下载字体，也不请求远程字体 CSS。“local”不表示主题自带 Inter 文件。
2. `fontSource=remote` 时，仅对字体域名 preconnect；Inter CSS 使用 `display=swap`，先以 `media=print` 下载，再由外部 `main.js` 激活。字体 CSS/字体不可用时正文先使用系统字体。没有 inline onload，因此兼容默认 nonce CSP。
3. 默认将 jQuery、Bootstrap、PJAX、NProgress 和主题脚本设为 defer，保持依赖顺序并减少 HTML 解析阻塞；初始化等到 DOMContentLoaded。高权限自定义代码开启时保留同步加载，兼容立即执行的 jQuery 代码。
4. 主题 CSS 在 Bootstrap 后正常加载，避免异步 onload 在 CSP 下失效，并保证最终层叠顺序；head 内保留首屏基础样式。普通 stylesheet 能被浏览器预加载扫描器发现，不需要再创建重复的 CSS 请求。
5. 配置了头部背景图时，通过 image preload/fetchpriority 提前提示浏览器，缩短 CSS 背景图的发现链路。收益取决于图像大小、缓存和网络；请不要把过大的原图作为头图。
6. 默认资源使用固定本地文件，消除多个第三方域名连接；开启 CDN 时使用固定发布文件与 SRI。图片预览层/目录增强继续按需初始化，评论图片保持 lazy。

预加载头图与字体都不能代替图像压缩或服务器优化。禁止 JS 时系统字体仍可阅读，但可选远程字体不会被激活。

## 可继续优化

- **图片通常优先级最高**：将头图转为尺寸合适的 WebP/AVIF，避免几 MB 原图；为头像和卡片提供对应尺寸。当前只有单一头图 URL，不会自动生成响应式图片。
- **字体自托管**：如果必须精确使用 Inter，可放置许可允许的 WOFF2 子集，只保留所需字重，配置 `font-display: swap` 或 optional；仅预加载真正用于首屏的一个字体。不要为少量文字加载完整中文字体。当前没有随主题分发这些字体。
- **缓存和压缩**：为静态 JS/CSS/SVG/WOFF2 配置 Brotli/gzip 和合理的 Cache-Control，启用 HTTP/2 或 HTTP/3。版本化 vendor 文件可长期缓存；`style.css`、`main.js`、`comments.js` 文件名未带内容哈希，不应直接使用一年 immutable，升级应清缓存或在部署层提供版本化 URL。
- **服务端**：开启 PHP OPcache，优化 Typecho/数据库与页面缓存，测量 TTFB。页面缓存必须区分登录身份和请求 URL，不能跨用户共享评论令牌。当前 CSP 使用每响应随机 nonce：缓存完整 HTML 时需要由缓存层一致更新响应头与页面脚本的 nonce，或另行设计哈希策略；不要只替换响应头，也不要长期复用含 nonce 的整页快照。
- **减少框架体积**：后续可评估以原生 JS/CSS 替换 jQuery/Bootstrap/PJAX，或只交付所需组件。该方案需要单独验证菜单、折叠、评论和自定义代码兼容，本轮没有进行框架迁移。
- **远程字体/CDN可用性**：按访问者实际网络测试。切换远程源不保证更快；系统字体与本地依赖仍是默认选择。额外统计、播放器和广告脚本会重新增加首屏负担。

## 测量方法与本轮证据

在真实站点用 DevTools/Lighthouse 或 WebPageTest 测量冷缓存、热缓存、移动端节流下的 TTFB、FCP、LCP、CLS 和字体请求瀑布；固定网络条件，至少重复多次并比较中位数。检查 LCP 元素是否为头图，别只比较资源总大小。

本轮真浏览器测试已验证本地模式没有第三方请求、defer 后依赖/评论正常、默认 CSP 下不执行内联事件。隔离环境的回归结果不能代表生产网络速度；未测得线上 FCP/LCP 改善数值。

另在临时 fixture 中，向浏览器提供已校验的 CDN 文件副本并启用真实 SRI，验证依赖执行顺序、自定义 `{nonce}` 策略与暗色模式；模拟字体 CSS 先于/晚于 main.js 到达，两种情况下均成功激活。字体 CSS 使用测试响应，这只验证加载逻辑，不代表远程字体的下载速度、字形渲染或 CDN 的实际可达性。

原理参考：[web.dev 字体最佳实践](https://web.dev/articles/font-best-practices)、[LCP 优化](https://web.dev/articles/optimize-lcp)。优先测量当前 LCP 元素及其关键请求链，再决定压缩图片、字体子集或服务器缓存的投入。
