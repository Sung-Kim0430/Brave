import assert from 'node:assert/strict';
import { existsSync, readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import test from 'node:test';

const root = fileURLToPath(new URL('../', import.meta.url));

function read(relativePath) {
  return readFileSync(path.join(root, relativePath), 'utf8');
}

test('custom code is explicit opt-in and custom CSS stays inside head', () => {
  const head = read('base/head.php');
  const footer = read('base/footer.php');
  const functions = read('functions.php');

  assert.match(head, /App::optionFlag\('enableCustomCode',\s*false\)/);
  assert.match(footer, /App::optionFlag\('enableCustomCode',\s*false\)/);
  assert.doesNotMatch(head, /!\s*isset\(Helper::options\(\)->enableCustomCode\)/);
  assert.doesNotMatch(footer, /!\s*isset\(Helper::options\(\)->enableCustomCode\)/);
  assert.match(functions, /'enableCustomCode'[\s\S]*'0'[\s\S]*关闭（推荐）[\s\S]*,\s*'0',/);

  const cssOutput = head.indexOf('Css自定义');
  const closeHead = head.indexOf('</head>');
  assert.notEqual(cssOutput, -1);
  assert.notEqual(closeHead, -1);
  assert.ok(cssOutput < closeHead, 'custom CSS output must be before </head>');
});

test('CSP is available in local mode and no longer tied only to CDN mode', () => {
  const head = read('base/head.php');
  const functions = read('functions.php');

  assert.match(head, /App::optionFlag\('enableCSP',\s*true\)/);
  assert.doesNotMatch(head, /\$enableCSP\s*=\s*\(\$assetsSource\s*===\s*'cdn'\s*&&/);
  assert.match(functions, /'enableCSP'/);
});

test('home cards use configurable safe links instead of empty hrefs or hard-coded blog path', () => {
  const indexPage = read('indexPage.php');
  const functions = read('functions.php');

  assert.match(functions, /'timePageLink'/);
  assert.match(indexPage, /App::optionValue\('timePageLink',\s*''\)/);
  assert.doesNotMatch(indexPage, /href="\/index\.php\/blog\/"/);
  assert.doesNotMatch(indexPage, /href="<\?php echo \$blessingPageLink; \?>"/);
  assert.doesNotMatch(indexPage, /href="<\?php echo \$loveListPageLink; \?>"/);
});

test('love time parsing rejects impossible calendar dates instead of normalizing them', () => {
  const footer = read('base/footer.php');

  assert.match(footer, /dt\.getFullYear\(\)\s*===\s*y/);
  assert.match(footer, /dt\.getMonth\(\)\s*===\s*m\s*-\s*1/);
  assert.match(footer, /dt\.getDate\(\)\s*===\s*d/);
});

test('shared helpers centralize option flags, site urls, intros, and safe card links', () => {
  const app = read('core/App.php');

  assert.match(app, /public static function optionFlag/);
  assert.match(app, /public static function siteUrl/);
  assert.match(app, /public static function pageIntroHtml/);
  assert.match(app, /public static function safeCardLink/);
});

test('documentation matches current security and link settings', () => {
  const usage = read('docs/USAGE.md');
  const security = read('docs/SECURITY.md');
  const readme = read('README.md');
  const docs = `${usage}\n${security}\n${readme}`;

  assert.match(usage, /timePageLink/);
  assert.match(usage, /enableCSP/);
  assert.match(security, /enableCSP/);
  assert.match(readme, /默认关闭/);
  assert.doesNotMatch(docs, /cdnEnableCSP/);
  assert.doesNotMatch(docs, /链接当前写死为 `\/index\.php\/blog\/`/);
});

test('Love List uses a focused parser without the WordPress shortcode compatibility layer', () => {
  const app = read('core/App.php');
  const usage = read('docs/USAGE.md');
  const security = read('docs/SECURITY.md');

  assert.match(app, /private static function renderLoveListShortcode/);
  assert.match(app, /private static function parseLoveListAttributes/);
  assert.doesNotMatch(app, /shortcodesLoaded|ensureShortcodesLoaded|do_shortcode|add_shortcode|avatarQQ/);
  assert.equal(existsSync(path.join(root, 'core/shortcodes.php')), false);
  assert.doesNotMatch(`${usage}\n${security}`, /shortcodes\.php|add_shortcode|WordPress/);
});

test('Love List generated DOM ids are scoped per shortcode instance', () => {
  const app = read('core/App.php');

  assert.match(app, /\$listIndex\s*=\s*0/);
  assert.match(app, /renderLoveListShortcode\([^,]+,\s*\$listIndex\+\+\)/);
  assert.match(app, /id="loveList'\s*\.\s*\$listIndex\s*\.\s*'"/);
  assert.match(app, /collapse'\s*\.\s*\$listIndex\s*\.\s*'-'\s*\.\s*\$key/);
  assert.match(app, /data-parent="#loveList'\s*\.\s*\$listIndex\s*\.\s*'"/);
});

test('comment nesting is capped to the documented safe maximum', () => {
  const functions = read('functions.php');

  assert.match(functions, /\$commentMaxNestingLevels\s*>\s*10/);
  assert.doesNotMatch(functions, /\$commentMaxNestingLevels\s*>\s*50/);
  assert.match(functions, /已在代码中限制最大为 10/);
});

test('front-end lightbox avoids raw HTML injection surfaces and data URI link promotion', () => {
  const main = read('base/main.js');

  assert.doesNotMatch(main, /innerHTML/);
  assert.doesNotMatch(main, /data:image/);
  assert.match(main, /document\.createElement\('button'\)/);
  assert.match(main, /document\.createElement\('img'\)/);
});

test('page intro display does not depend on undefined template variables', () => {
  const index = read('index.php');
  const loveListPage = read('loveListPage.php');

  assert.doesNotMatch(index, /\$introIndexEnabled/);
  assert.match(index, /if\s*\(\$introIndexHtml\s*!==\s*''\)/);
  assert.doesNotMatch(loveListPage, /\$introLoveListEnabled/);
  assert.match(loveListPage, /if\s*\(\$introLoveListHtml\s*!==\s*''\)/);
});

test('Love List unique DOM ids still receive the shared CSS styling', () => {
  const app = read('core/App.php');
  const style = read('base/style.css');

  assert.match(app, /class="accordion mx-auto mt-5 brave-love-list"/);
  assert.match(style, /\.brave-love-list/);
  assert.doesNotMatch(style, /#loveList(?:\s|\.|,|:|$)/);
});

test('Love List status only treats explicit 1 as completed', () => {
  const app = read('core/App.php');

  assert.match(app, /\$isCompleted\s*=\s*\(\$status\s*===\s*'1'\)/);
  assert.match(app, /\$statusIcon\s*=\s*\$isCompleted\s*\?\s*\$okIcon\s*:\s*\$todoIcon/);
  assert.doesNotMatch(app, /\$isTodo\s*=\s*\(\$status\s*===\s*'0'\)/);
});

test('runtime counter builds styled text without jQuery HTML insertion', () => {
  const footer = read('base/footer.php');

  assert.doesNotMatch(footer, /\.html\(/);
  assert.match(footer, /document\.createElement\('span'\)/);
  assert.match(footer, /document\.createTextNode/);
});

test('PJAX error fallback only navigates to validated same-origin URLs', () => {
  const footer = read('base/footer.php');

  assert.match(footer, /function getSafeSameOriginUrl/);
  assert.match(footer, /fallbackUrl\s*=\s*getSafeSameOriginUrl\(options\s*&&\s*options\.url\)/);
  assert.match(footer, /window\.location\.assign\(fallbackUrl\)/);
  assert.doesNotMatch(footer, /window\.location\.href\s*=\s*options\.url/);
});
