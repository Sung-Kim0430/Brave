import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const root = new URL('../', import.meta.url);
const manifest = JSON.parse(readFileSync(new URL('base/vendor/integrity.json', root), 'utf8'));
const templates = ['base/head.php', 'base/footer.php'].map(file => readFileSync(new URL(file, root), 'utf8')).join('\n');
for (const [file, asset] of Object.entries(manifest)) {
  test('pinned dependency integrity: ' + file, () => {
    // 兼容已有 Windows 工作副本；新检出由 .gitattributes 固定 LF，不修改供应商代码。
    const contents = readFileSync(new URL('base/vendor/' + file, root), 'utf8').replace(/\r\n/g, '\n');
    assert.equal(createHash('sha384').update(contents).digest('base64'), asset.localSha384);
    const offset = templates.indexOf('src="' + asset.cdnUrl + '"');
    const hrefOffset = templates.indexOf('href="' + asset.cdnUrl + '"');
    const start = Math.max(offset, hrefOffset);
    assert.ok(start >= 0, 'CDN URL must be pinned to the reviewed published file');
    assert.ok(templates.slice(start, start + 600).includes('sha384-' + asset.cdnSha384), 'SRI must match that asset');
    assert.match(asset.cdnUrl, /@\d+\.\d+\.\d+\//);
  });
}
