# 前端依赖（固定本地副本与 CDN）

更新：2026-10-16。默认 `assetsSource=local`；主题不需要 npm 构建。依赖版本为 jQuery 3.7.1、Bootstrap 4.6.2（CSS 与含 Popper 的 bundle）、jquery-pjax 2.0.1、NProgress 0.2.0。保留上游许可证/声明，不手改供应商代码。

## 来源与字节校验

`integrity.json` 记录每个本地文件按 LF 规范化后的 SHA-384、CDN 固定 npm 发布地址和对应 SRI。`.gitattributes` 对新检出的 JS/CSS 强制 LF；测试兼容旧 Windows 工作副本的 CRLF。运行 `node --test tests/vendorIntegrity.test.mjs` 核对本地字节与模板声明。

jQuery/Bootstrap 的本地副本与固定 npm dist 文件一致。现有本地 PJAX/NProgress min 文件是 jsDelivr 较早版本压缩器生成的固定副本，继续自托管；没有重新下载或修改其功能代码。测试锁定这些已审阅字节。

## CDN 模式

CDN 使用 `https://cdn.jsdelivr.net/npm/包名@精确版本/发布文件`，默认 SRI/crossorigin。jQuery/Bootstrap 使用包中已有的 min/dist 文件；PJAX/NProgress 使用包中真正发布的 `jquery.pjax.js`/`nprogress.js`。

不要将固定 SRI 与 CDN 动态生成的 `.min.js` 搭配：即使包版本相同，CDN 更换压缩器也可能改变字节。本轮实测本地旧副本的压缩器注释为 Terser 5.37.0，在线动态生成内容为 5.39.0。[jsDelivr 说明](https://www.jsdelivr.com/using-sri-with-dynamic-files)。固定发布文件的在线字节已重新计算哈希，与模板及清单一致。

升级时同步修改清单、head/footer 引用与 SRI，并重新验证 DOM 交互、CSP 和许可证。哈希只能发现字节变化，不能证明依赖安全；版本漏洞查询结果见 [检查记录](../../docs/TYPECHO_1_3_AUDIT.md)。
