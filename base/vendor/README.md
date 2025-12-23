# 前端依赖（本地副本）

为降低第三方 CDN 供应链风险，本主题默认从 `base/vendor/` 加载核心前端依赖（可在主题设置中切换回 CDN 模式）。

文件来源（下载自 jsDelivr npm 镜像）：

- jQuery `3.7.1`：`jquery-3.7.1.min.js`
- Bootstrap `4.6.2`：`bootstrap-4.6.2.min.css`、`bootstrap-4.6.2.min.js`
- jquery-pjax `2.0.1`：`jquery.pjax-2.0.1.min.js`
- nprogress `0.2.0`：`nprogress-0.2.0.min.js`

提示：

- 这些文件为上游库的构建产物，通常包含其自身的许可证声明；请勿手动修改内容。
- 若需升级版本，建议同时更新 `base/head.php` / `base/footer.php` 中的引用路径与主题文档。

