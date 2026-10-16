/**
 * 可选真浏览器回归，目标必须是临时 SQLite fixture（不能对生产站点运行，会提交评论）。
 * BRAVE_TEST_ORIGIN=http://127.0.0.1:8133；PLAYWRIGHT_MODULE 可指向外部 playwright 安装。
 * CHROMIUM_EXECUTABLE 可指向本机 Chrome；不使用用户配置目录或现有浏览器会话。
 */
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import test from 'node:test';

const require = createRequire(import.meta.url);
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const origin = process.env.BRAVE_TEST_ORIGIN || 'http://127.0.0.1:8133';
assert.match(origin, /^http:\/\/127\.0\.0\.1:\d+$/, 'Only a loopback fixture is allowed');

test('Typecho 1.3: strict CSP, PJAX, threaded replies and comment CSRF', async () => {
  const browser = await chromium.launch({ headless: true, executablePath: process.env.CHROMIUM_EXECUTABLE || undefined });
  try {
    const page = await browser.newPage();
    const errors = [];
    const remoteRequests = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('request', request => { if (!request.url().startsWith(origin)) remoteRequests.push(request.url()); });
    const response = await page.goto(origin + '/', { waitUntil: 'networkidle' });
    assert.equal(response.headers()['x-brave-test-fixture'], 'typecho13', 'Refuse to submit comments to a site without the fixture marker');
    const csp = response.headers()['content-security-policy'];
    assert.ok(csp.includes("'nonce-"));
    assert.ok(!/script-src[^;]*unsafe-inline/.test(csp));
    await page.evaluate(() => { window.__bravePjaxSentinel = 'preserved'; });
    const pjaxResponse = page.waitForResponse(r => r.url().includes('/archives/1/') && !!r.request().headers()['x-pjax']);
    await page.locator('#pjax-container a[href="' + origin + '/archives/1/"]').first().click();
    await pjaxResponse;
    await page.waitForFunction(() => location.pathname === '/archives/1/' && document.querySelector('form.comment-form'));
    assert.equal(await page.evaluate(() => window.__bravePjaxSentinel), 'preserved', 'navigation must really use PJAX');
    const token = await page.locator('form.comment-form input[name="_"]').inputValue();
    assert.match(token, /^[a-f0-9]{32}$/);
    assert.equal(await page.locator('#cancel-comment-reply-link').count(), 1);
    await page.locator('[data-brave-reply-to="1"] a').click();
    assert.equal(await page.locator('form.comment-form input[name="parent"]').inputValue(), '1');
    assert.ok(await page.locator('#comment-1 .respond').count());
    await page.locator('[data-brave-cancel-reply] a').click();
    assert.equal(await page.locator('form.comment-form input[name="parent"]').inputValue(), '0');
    assert.equal(await page.locator('#comment-1 .respond').count(), 0);

    // 模拟注入的内联事件；nonce 默认策略应阻止它，且不依赖净化器测试来证明 CSP 生效。
    await page.evaluate(() => {
      window.__inlineAttack = false;
      const button = document.createElement('button');
      button.setAttribute('onclick', 'window.__inlineAttack = true');
      document.body.appendChild(button);
      button.click();
      button.remove();
    });
    assert.equal(await page.evaluate(() => window.__inlineAttack), false);

    const rejected = 'rejected-csrf-' + Date.now();
    const formAction = await page.locator('form.comment-form').getAttribute('action');
    const invalidResponse = await page.request.post(formAction, {
      form: { author: 'Browser guest', mail: 'guest@example.test', text: rejected, _: 'invalid' },
      headers: { Referer: origin + '/archives/1/' }, maxRedirects: 0,
    });
    assert.ok([302, 303].includes(invalidResponse.status()), 'native security rejects bad token with goBack');
    assert.ok(!(await (await page.request.get(origin + '/archives/1/')).text()).includes(rejected));

    const accepted = 'accepted-pjax-' + Date.now();
    await page.locator('input[name="author"]').fill('Browser guest');
    await page.locator('input[name="mail"]').fill('guest@example.test');
    await page.locator('textarea[name="text"]').fill(accepted);
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.locator('form.comment-form button[type="submit"]').click()]);
    assert.ok((await page.locator('.comment-text').allTextContents()).some(text => text.includes(accepted)), 'PJAX-generated token must pass native Feedback validation');
    assert.deepEqual(errors, []);
    assert.deepEqual(remoteRequests, [], 'local font/assets defaults must not send any third-party request');
    if (process.env.BRAVE_SCREENSHOT) await page.screenshot({ path: process.env.BRAVE_SCREENSHOT, fullPage: true });
  } finally {
    await browser.close();
  }
});
