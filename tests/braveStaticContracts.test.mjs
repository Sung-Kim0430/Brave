import assert from 'node:assert/strict';
import { existsSync, readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import test from 'node:test';
import vm from 'node:vm';

const root = fileURLToPath(new URL('../', import.meta.url));

function read(relativePath) {
  return readFileSync(path.join(root, relativePath), 'utf8');
}

function loadParseLoveTime(overrides = {}) {
  const footer = read('base/footer.php');
  const start = footer.indexOf('window.parseLoveTime = function(value) {');
  const end = footer.indexOf('(function() {', start);
  assert.notEqual(start, -1, 'parseLoveTime definition should exist');
  assert.notEqual(end, -1, 'parseLoveTime definition should end before runtime bootstrap');

  const context = { window: {}, ...overrides };
  vm.runInNewContext(footer.slice(start, end), context);
  assert.equal(typeof context.window.parseLoveTime, 'function');
  return context.window.parseLoveTime;
}

function runFooterRuntime(loveTimeValue) {
  const footer = read('base/footer.php');
  const scriptMatch = footer.match(/<script>([\s\S]*?)<\/script>\s*<script src=/);
  assert.ok(scriptMatch, 'footer inline script should exist');

  let script = scriptMatch[1];
  script = script.replace(/<\?php echo App::escapeJsString[\s\S]*?\?>/g, JSON.stringify(loveTimeValue));
  script = script.replace(/<\?php[\s\S]*?\?>/g, '');

  const runtimeNode = {
    textContent: '',
    firstChild: null,
    appendChild(node) {
      this.textContent += node.textContent || '';
    },
    removeChild() {
      this.firstChild = null;
    },
  };

  const context = {
    Date,
    Math,
    Number,
    String,
    isNaN,
    window: {
      clearInterval() {},
      setInterval() {
        return 1;
      },
    },
    document: {
      body: { classList: { add() {}, remove() {} } },
      createElement() {
        return { className: '', textContent: '' };
      },
      createTextNode(text) {
        return { textContent: text };
      },
      getElementById(id) {
        return id === 'site_runtime' ? runtimeNode : null;
      },
    },
  };

  vm.runInNewContext(script, context);
  return runtimeNode.textContent;
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

test('document title escapes archive and site title text locally', () => {
  const head = read('base/head.php');

  assert.doesNotMatch(head, /<title>\s*<\?php\s+\$this->archiveTitle/);
  assert.doesNotMatch(head, /<\?php\s+\$this->options->title\(\);\s*\?>/);
  assert.match(head, /ob_start\(\);\s*\$this->archiveTitle/);
  assert.match(head, /\$archiveTitleText\s*=\s*ob_get_clean\(\)/);
  assert.match(head, /App::escapeHtml\(\$archiveTitleText\)/);
  assert.match(head, /App::escapeHtml\(App::optionValue\('title',\s*''\)\)/);
});

test('theme-owned site title text nodes are escaped locally', () => {
  const nav = read('base/nav.php');
  const footer = read('base/footer.php');

  assert.doesNotMatch(nav, /\$this->options->title\(\)/);
  assert.doesNotMatch(footer, /\$this->options->title\(\)/);
  assert.match(nav, /App::escapeHtml\(App::optionValue\('title',\s*''\)\)/);
  assert.match(footer, /App::escapeHtml\(App::optionValue\('title',\s*''\)\)/);
});

test('logged-in comment identity output is escaped locally', () => {
  const comments = read('base/comments.php');

  assert.doesNotMatch(comments, /\$this->user->screenName\(\)/);
  assert.doesNotMatch(comments, /\$this->options->profileUrl\(\)/);
  assert.doesNotMatch(comments, /\$this->options->logoutUrl\(\)/);
  assert.match(comments, /App::escapeHtml\(\$this->user->screenName\)/);
  assert.match(comments, /App::safeCardLink\(App::optionValue\('profileUrl',\s*''\),\s*'#'\)/);
  assert.match(comments, /App::safeCardLink\(App::optionValue\('logoutUrl',\s*''\),\s*'#'\)/);
});

test('comment form action and respond id use local escaping helpers', () => {
  const comments = read('base/comments.php');

  assert.doesNotMatch(comments, /\$this->respondId\(\)/);
  assert.doesNotMatch(comments, /\$this->commentUrl\(\)/);
  assert.match(comments, /App::escapeHtml\(\$this->respondId\)/);
  assert.match(comments, /App::escapeUrlAttribute\(\$this->commentUrl,\s*true,\s*array\('http',\s*'https'\)\)/);
});

test('post titles and list permalinks use local escaping helpers', () => {
  const index = read('index.php');
  const post = read('post.php');

  assert.doesNotMatch(index, /href="<\?php\s+\$this->permalink\(\)/);
  assert.doesNotMatch(index, />\s*<\?php\s+\$this->title\(\)\s*\?>\s*<\/a>/);
  assert.doesNotMatch(post, /「<\?php\s+\$this->title\(\)\s*\?>」/);
  assert.match(index, /ob_start\(\);\s*\$this->permalink\(\);\s*\$postPermalinkText\s*=\s*ob_get_clean\(\)/);
  assert.match(index, /App::escapeUrlAttribute\(\$postPermalinkText,\s*true,\s*array\('http',\s*'https'\)\)/);
  assert.match(index, /ob_start\(\);\s*\$this->title\(\);\s*\$postTitleText\s*=\s*ob_get_clean\(\)/);
  assert.match(index, /App::escapeHtml\(\$postTitleText\)/);
  assert.match(post, /ob_start\(\);\s*\$this->title\(\);\s*\$postTitleText\s*=\s*ob_get_clean\(\)/);
  assert.match(post, /App::escapeHtml\(\$postTitleText\)/);
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

test('custom page templates bootstrap App helper before using shared helpers', () => {
  for (const file of ['indexPage.php', 'loveListPage.php', 'commentPage.php']) {
    const template = read(file);
    const helperLoad = template.indexOf("require_once __DIR__ . '/core/App.php'");
    const firstAppUse = template.indexOf('App::');

    assert.notEqual(firstAppUse, -1, `${file} should use App helper contracts`);
    assert.notEqual(helperLoad, -1, `${file} must not assume themeInit loaded App first`);
    assert.ok(helperLoad < firstAppUse, `${file} helper load must happen before the first App:: call`);
  }
});

test('optional home cards render disabled instead of inert hash links', () => {
  const app = read('core/App.php');
  const indexPage = read('indexPage.php');
  const style = read('base/style.css');

  assert.match(app, /if\s*\(\(string\)\$fallback\s*===\s*''\)/);
  assert.match(indexPage, /App::safeCardLink\(App::optionValue\('blessingPageLink',\s*''\),\s*''\)/);
  assert.match(indexPage, /App::safeCardLink\(App::optionValue\('loveListPageLink',\s*''\),\s*''\)/);
  assert.match(indexPage, /\$blessingPageAvailable\s*=\s*\(\$blessingPageHref\s*!==\s*''\)/);
  assert.match(indexPage, /\$loveListPageAvailable\s*=\s*\(\$loveListPageHref\s*!==\s*''\)/);
  assert.match(indexPage, /brave-card-disabled/);
  assert.match(style, /\.brave-card-disabled/);
});

test('love time parsing rejects impossible calendar dates instead of normalizing them', () => {
  const footer = read('base/footer.php');

  assert.match(footer, /dt\.getFullYear\(\)\s*===\s*y/);
  assert.match(footer, /dt\.getMonth\(\)\s*===\s*m\s*-\s*1/);
  assert.match(footer, /dt\.getDate\(\)\s*===\s*d/);
});

test('love time parsing rejects malformed configured date and time values', () => {
  const parseLoveTime = loadParseLoveTime();

  assert.equal(parseLoveTime('2021-02-03abc'), null);
  assert.equal(parseLoveTime('2021-06-26 24:00:00'), null);
  assert.equal(parseLoveTime('2021-06-26 12:60:00'), null);
  assert.equal(parseLoveTime('2021-06-26 12:00:60'), null);

  const leapDay = parseLoveTime('2024-02-29 12:30:45');
  assert.equal(leapDay && Number.isNaN(leapDay.getTime()), false);
});

test('love time parsing does not fallback parse malformed configured dates', () => {
  const nativeDate = Date;
  const dateCalls = [];
  function LenientDate(...args) {
    dateCalls.push(args);
    if (args.length === 1 && typeof args[0] === 'string') {
      return new nativeDate(2000, 0, 1);
    }
    return new nativeDate(...args);
  }

  const parseLoveTime = loadParseLoveTime({
    Date: LenientDate,
    Number,
    String,
    isNaN,
  });

  assert.equal(parseLoveTime('2021-02-03abc'), null);
  assert.equal(dateCalls.length, 0);
});

test('runtime counter distinguishes missing and invalid love time config', () => {
  assert.equal(runFooterRuntime(''), '未设置');
  assert.equal(runFooterRuntime('2021-02-03abc'), '日期无效');
});

test('shared helpers centralize option flags, site urls, intros, and safe card links', () => {
  const app = read('core/App.php');

  assert.match(app, /public static function optionFlag/);
  assert.match(app, /return\s+\(bool\)\$default/);
  assert.match(app, /public static function optionIntRange/);
  assert.match(app, /public static function siteUrl/);
  assert.match(app, /public static function pageIntroHtml/);
  assert.match(app, /public static function safeCardLink/);
});

test('URL normalizer allows ordinary relative paths after scheme validation', () => {
  const app = read('core/App.php');

  assert.match(app, /Block dangerous schemes even if obfuscated/);
  assert.match(app, /Allow ordinary relative URLs such as blog\/ after scheme checks/);
  assert.match(app, /if\s*\(\$allowRelative\)\s*{[\s\S]*return\s+\$decodedUrl;[\s\S]*}\s*\n\s*return\s+'';/);
});

test('URL normalizer emits entity-decoded safe URLs before context escaping', () => {
  const app = read('core/App.php');

  assert.match(app, /\$decodedUrl\s*=\s*html_entity_decode\(\$url,\s*ENT_QUOTES\s*\|\s*ENT_HTML5,\s*'UTF-8'\)/);
  assert.match(app, /\$schemeCheckUrl\s*=\s*preg_replace\('\/\[\\\\x00-\\\\x20\]\+\/',\s*'',\s*\$decodedUrl\)/);
  assert.match(app, /return\s+\$decodedUrl;/);
  assert.doesNotMatch(app, /return\s+\$url;\s*\n\s*}\s*\n\s*return\s+'';/);
});

test('theme init uses shared option helpers for comment safety defaults', () => {
  const functions = read('functions.php');

  assert.match(functions, /App::optionFlag\('commentAntiSpam',\s*true\)/);
  assert.match(functions, /App::optionFlag\('commentCheckReferer',\s*true\)/);
  assert.match(functions, /App::optionIntRange\('commentMaxNestingLevels',\s*10,\s*1,\s*10\)/);
  assert.match(functions, /App::optionFlag\('commentAllowImg',\s*false\)/);
  assert.doesNotMatch(functions, /isset\(\$options->commentAntiSpam\)/);
  assert.doesNotMatch(functions, /isset\(\$options->commentMaxNestingLevels\)/);
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

  assert.match(functions, /App::optionIntRange\('commentMaxNestingLevels',\s*10,\s*1,\s*10\)/);
  assert.doesNotMatch(functions, /\$commentMaxNestingLevels\s*>\s*50/);
  assert.match(functions, /已在代码中限制最大为 10/);
});

test('comment sanitizer drops image tags without a safe src', () => {
  const app = read('core/App.php');

  assert.match(app, /if\s*\(\$tag\s*===\s*'img'\s*&&\s*!\$element->hasAttribute\('src'\)\)/);
  assert.match(app, /\$removeElement\s*=\s*true/);
});

test('blessing board shows existing comments even when new comments are closed', () => {
  const comments = read('base/comments.php');
  const section = comments.indexOf('<section id="comments"');
  const listComments = comments.indexOf('$comments->listComments()');
  const commentForm = comments.indexOf('<form method="post"');
  // 文档块里也出现过这几个字样，从表单之后开始找才算真正的分支位置。
  const closedMessage = comments.indexOf('留言暂已关闭', commentForm);

  // 输出顺序：先渲染已有评论，再是表单，最后才是「已关闭」分支。
  assert.notEqual(section, -1);
  assert.notEqual(listComments, -1);
  assert.notEqual(commentForm, -1);
  assert.notEqual(closedMessage, -1);
  assert.ok(section < listComments, 'comment section wrapper should precede the list');
  assert.ok(listComments < commentForm, 'existing comments should render before the form');
  assert.ok(commentForm < closedMessage, 'closed message should be the fallback branch');
  assert.match(comments, /if \(\$commentHasList\)/);
  assert.match(comments, /elseif \(\$commentShowClosedNotice\)/);
  assert.match(comments, /\$commentFormOpen = \$this->allow\('comment'\)/);
});

test('comment block lives in one shared partial used by both templates', () => {
  const comments = read('base/comments.php');
  const commentPage = read('commentPage.php');

  assert.match(comments, /require_once __DIR__ \. '\/\.\.\/core\/App\.php'/);
  assert.match(comments, /if \(!function_exists\('threadedComments'\)\)/);
  // 模板用 include 而不是 $this->need()，这样调用方预置的文案变量才传得进来。
  assert.match(commentPage, /include __DIR__ \. '\/base\/comments\.php'/);
  assert.doesNotMatch(commentPage, /function threadedComments\(/);
  assert.doesNotMatch(commentPage, /<form method="post"/);
});

test('article template gains prev/next navigation and its own comment block', () => {
  const post = read('post.php');

  // 上下篇：用内核渲染，两篇都缺时不渲染整块导航
  assert.match(post, /\$this->thePrev\('%s', ''\)/);
  assert.match(post, /\$this->theNext\('%s', ''\)/);
  assert.match(post, /post-near__prev/);
  assert.match(post, /post-near__next/);
  assert.match(post, /\$prevNavHtml !== '' \|\| \$nextNavHtml !== ''/);

  // 分类 / 标签：内核的 category()/tags() 直接拼 HTML 不转义，主题自己遍历走 App helper
  assert.doesNotMatch(post, /\$this->tags\(/);
  assert.doesNotMatch(post, /\$this->category\(/);
  assert.match(post, /App::escapeUrlAttribute\(\$tag\['permalink'\],\s*true,\s*array\('http',\s*'https'\)\)/);
  assert.match(post, /App::escapeHtml\(\$tag\['name'\]\)/);
  assert.match(post, /App::escapeHtml\(\$category\['name'\]\)/);

  // 文章页文案与祝福板区分；关闭评论时不输出「留言暂已关闭」
  assert.match(post, /\$commentCountLabels = array\(/);
  assert.match(post, /\$commentShowClosedNotice = false/);
});

test('article-only styles are shipped', () => {
  const style = read('base/style.css');

  for (const cls of ['.post-meta', '.post-near', '.post-near__col', '.comment-area']) {
    assert.ok(style.includes(cls), `${cls} should be styled`);
  }
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

test('comment sanitizer keeps markdown-generated markup', () => {
  const app = read('core/App.php');

  // 历史上这里有一条「整体包裹在单个白名单标签内就直接 htmlspecialchars」的快速路径，
  // Typecho 评论经 Markdown 渲染后必然是 <p>…</p>，会因此被整体转义。
  assert.ok(!app.includes("simple safe tags that don't need full parsing"), 'fast path comment should be gone');
  assert.ok(!app.includes('(p|br|strong|em|b|i)'), 'whole-fragment escaping whitelist should be gone');
  assert.match(app, /private static function truncationNoticeHtml/);
});

test('comment sanitizer truncates long content instead of escaping it wholesale', () => {
  const app = read('core/App.php');

  assert.match(app, /const MAX_HTML_LENGTH = 50000;/);
  assert.match(app, /\$truncated = true;/);
  assert.doesNotMatch(app, /内容过长，已截断\)', ENT_QUOTES, 'UTF-8'\);/);
});

test('every page template renders an h1 heading', () => {
  for (const file of ['index.php', 'post.php', 'indexPage.php', 'commentPage.php', 'loveListPage.php']) {
    const template = read(file);
    assert.match(template, /<h1\b/, `${file} should contain an h1`);
    assert.doesNotMatch(template, /<h1[^>]*>\s*<\/h1>/, `${file} should not ship an empty h1`);
  }
});

test('custom code guard extracts hosts instead of using a bypassable lookahead', () => {
  const head = read('base/head.php');
  const footer = read('base/footer.php');
  const app = read('core/App.php');

  for (const source of [head, footer]) {
    assert.match(source, /App::findUntrustedScriptHosts\(/);
    assert.ok(!source.includes('(?!https?:'), 'negative-lookahead domain check should be gone');
    assert.ok(!source.includes('preg_quote'), 'preg_quote(null) deprecation source should be gone');
  }

  assert.match(app, /public static function findUntrustedScriptHosts/);
  assert.match(app, /public static function describeUntrustedHosts/);
});

test('CSP header validation accepts hashes and nonces and keeps a meta fallback notice', () => {
  const head = read('base/head.php');

  assert.ok(!head.includes('a-z0-9\\s'), 'whitelist charset validation should be gone');
  assert.match(head, /strpbrk\(\$cspHeader, '<>'\) === false/);
  assert.match(head, /\$cspHeaderRejected = true;/);
  assert.match(head, /unsupported|不支持/);
});

test('stylesheet loading degrades to blocking when CSP forbids inline scripts', () => {
  const head = read('base/head.php');

  assert.match(head, /\$cspBlocksInlineScript = true;/);
  assert.match(head, /if\s*\(\$cspBlocksInlineScript\)\s*:/);
  assert.match(head, /stripos\(\$cspPolicy, "'unsafe-inline'"\) === false/);
});

test('bootstrap bundle (with Popper) replaces the plain bootstrap build', () => {
  const head = read('base/head.php');

  assert.match(head, /bootstrap-4\.6\.2\.bundle\.min\.js/);
  assert.match(head, /sha384-Fy6S3B9q64WdZWQUiU\+q4\/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct/);
  assert.equal(existsSync(path.join(root, 'base/vendor/bootstrap-4.6.2.bundle.min.js')), true);
  assert.equal(existsSync(path.join(root, 'base/vendor/bootstrap-4.6.2.min.js')), false);
});

test('dead hooks and unreachable URL blacklist stay removed', () => {
  const main = read('base/main.js');
  const app = read('core/App.php');
  const index = read('index.php');

  assert.ok(!main.includes('is-page-ready'), 'is-page-ready hook has no CSS and should stay removed');
  assert.ok(!index.includes('list-wbc'), 'list-wbc has no CSS and should stay removed');
  assert.ok(!app.includes('Block private IP ranges'), 'unreachable SSRF blacklist should stay removed');
});

test('App behavior suite is wired into the repository', () => {
  const testFile = path.join(root, 'tests/php/behavior.test.php');
  assert.equal(existsSync(testFile), true, 'tests/php/behavior.test.php should exist');

  const suite = readFileSync(testFile, 'utf8');
  assert.match(suite, /sanitizeCommentHtml/);
  assert.match(suite, /parseShortCode/);
  assert.match(suite, /findUntrustedScriptHosts/);
});

test('html lang is no longer hardcoded to zh-cn', () => {
  const head = read('base/head.php');

  assert.ok(!head.includes('lang="zh-cn"'), 'html lang should not be hardcoded');
  assert.match(head, /<html lang="<\?php echo App::escapeHtml\(App::htmlLang\(\)\); \?>/);
});

test('default CSP only allows http: images on plain HTTP requests', () => {
  const head = read('base/head.php');

  assert.match(head, /img-src " \. \$imgSrc \. ";/);
  assert.match(head, /if \(!App::isHttpsRequest\(\)\) \{\s*\n\s*\$imgSrc \.= ' http:';/);
  assert.ok(
    !/img-src 'self' data: blob: https: http:;/.test(head),
    'static img-src with http: should be gone'
  );
});

test('comment form can carry the nested-reply parent id', () => {
  const page = read('base/comments.php');

  assert.match(page, /<input type="hidden" name="parent" id="comment-parent" value="0">/);
  assert.match(page, /\$comments->reply\(/);
  assert.match(page, /\$comments->cancelReply\(/);
  assert.match(page, /comment-reply cp-<\?php \$comments->theId\(\); \?>/);
  assert.match(page, /cancel-comment-reply cl-<\?php \$comments->theId\(\); \?>/);
  assert.match(page, /App::optionFlag\('commentsThreaded', false\)/);
});

test('content length limit is configurable and the dead constant is gone', () => {
  const app = read('core/App.php');
  const functions = read('functions.php');

  assert.ok(!app.includes('MAX_SHORTCODE_LENGTH'), 'unused MAX_SHORTCODE_LENGTH should stay removed');
  assert.match(app, /public static function contentMaxLength\(\)/);
  assert.match(app, /self::optionIntRange\('contentMaxLength', self::MAX_HTML_LENGTH, 10000, 200000\)/);
  assert.match(functions, /new Text\(\s*\n\s*'contentMaxLength',/);
  assert.match(functions, /new Text\(\s*\n\s*'htmlLang',/);
});
