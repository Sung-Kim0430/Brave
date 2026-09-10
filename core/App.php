<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Editor: Sung Kim
 * Creator: Veen Zhao
 * CreateTime: 2020/9/5 18:26
 * UpdateTime: 2026/6/8
 *
 * Note: This class depends on Typecho's Helper class, which is autoloaded by the framework.
 * All methods assume Typecho environment is properly initialized.
 */

class App
{
    /** 净化 HTML 片段时的硬上限（超过则截断后继续净化，避免 DOM 解析开销失控） */
    const MAX_HTML_LENGTH = 50000;

    /** 短代码解析的硬上限（超过则不解析，避免正则与渲染开销） */
    const MAX_SHORTCODE_LENGTH = 50000;

    /** 单个短代码属性串的硬上限 */
    const MAX_SHORTCODE_ATTR_LENGTH = 1000;

    public static function escapeHtml($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    public static function escapeTextWithBr($value)
    {
        $value = (string)$value;
        $value = str_replace(array("\r\n", "\r"), "\n", $value);
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $escaped = self::escapeHtml($value);
        return str_replace("\n", '<br>', $escaped);
    }

    public static function optionValue($name, $default = '')
    {
        $options = Helper::options();
        if (isset($options->{$name})) {
            return (string)$options->{$name};
        }

        return (string)$default;
    }

    public static function optionFlag($name, $default = false)
    {
        $options = Helper::options();
        if (!isset($options->{$name})) {
            return (bool)$default;
        }

        $value = (string)$options->{$name};
        if ($value === '1') {
            return true;
        }
        if ($value === '0') {
            return false;
        }

        // For safety, only return true for explicit '1', otherwise return default
        return (bool)$default;
    }

    public static function optionChoice($name, $default, $allowed)
    {
        $value = self::optionValue($name, $default);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    public static function optionIntRange($name, $default, $min, $max)
    {
        $min = (int)$min;
        $max = (int)$max;
        if ($max < $min) {
            $tmp = $min;
            $min = $max;
            $max = $tmp;
        }

        $value = self::optionValue($name, $default);
        $value = is_numeric($value) ? (int)$value : (int)$default;

        if ($value < $min) {
            return $min;
        }
        if ($value > $max) {
            return $max;
        }

        return $value;
    }

    public static function siteUrl($appendSlash = true)
    {
        $raw = self::optionValue('siteUrl', '/');
        if ($appendSlash) {
            $raw = rtrim($raw, '/') . '/';
        }

        $safe = self::escapeUrlAttribute($raw, true, array('http', 'https'));
        return ($safe !== '') ? $safe : '/';
    }

    public static function pageIntroHtml($enabled, $text, $fallback = '')
    {
        if (!$enabled) {
            return '';
        }

        $value = trim((string)$text);
        if ($value === '') {
            $value = trim((string)$fallback);
        }

        return self::escapeTextWithBr($value);
    }

    public static function safeCardLink($url, $fallback = '#')
    {
        $safeUrl = self::escapeUrlAttribute($url, true, array('http', 'https'));
        if ($safeUrl !== '') {
            return $safeUrl;
        }

        if ((string)$fallback === '') {
            return '';
        }

        $safeFallback = self::escapeUrlAttribute($fallback, true, array('http', 'https'));
        return ($safeFallback !== '') ? $safeFallback : '#';
    }

    public static function parseShortCode($content)
    {
        $content = (string)$content;

        if (stripos($content, '[loveList') === false) {
            return $content;
        }

        // Protect against ReDoS: limit content length for shortcode parsing
        // Use same limit as HTML sanitization for consistency
        if (strlen($content) > self::MAX_SHORTCODE_LENGTH) {
            return $content;
        }

        $listIndex = 0;
        // Use possessive quantifiers and atomic groups to prevent catastrophic backtracking
        $parsed = preg_replace_callback(
            '/\[loveList\b[^\]]*\]((?:[^\[]|\[(?!\/loveList\]))*+)\[\/loveList\]/i',
            function ($matches) use (&$listIndex) {
                return self::renderLoveListShortcode(isset($matches[1]) ? $matches[1] : '', $listIndex++);
            },
            $content
        );

        return is_string($parsed) ? $parsed : $content;
    }

    private static function parseLoveListAttributes($text)
    {
        $attrs = array();
        $text = (string)$text;
        if ($text === '') {
            return $attrs;
        }

        // Add length limit for attribute parsing specifically to prevent ReDoS
        if (strlen($text) > self::MAX_SHORTCODE_ATTR_LENGTH) {
            return $attrs;
        }

        // Use possessive quantifiers to prevent backtracking
        if (preg_match_all('/([A-Za-z0-9_-]++)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\']+))/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = strtolower($match[1]);
                // Limit number of attributes to prevent DoS
                if (count($attrs) >= 20) {
                    break;
                }
                if (isset($match[2]) && $match[2] !== '') {
                    $attrs[$name] = $match[2];
                } elseif (isset($match[3]) && $match[3] !== '') {
                    $attrs[$name] = $match[3];
                } elseif (isset($match[4])) {
                    $attrs[$name] = $match[4];
                }
            }
        }

        return $attrs;
    }

    private static function renderLoveListShortcode($content, $listIndex)
    {
        $content = (string)$content;

        // Protect against ReDoS: limit item count and content length
        if (strlen($content) > self::MAX_SHORTCODE_LENGTH) {
            return '<div class="alert alert-warning">Love List 内容过长</div>';
        }

        // Use possessive quantifiers and atomic groups to prevent backtracking.
        // 属性区必须是「引号感知」的：否则简单排除 `/` 会破坏 `img="https://…"`、`img="/img/a.png"`；
        // 而原来的 `[^\]]++` 会把自闭合写法 `[item …/]` 末尾的 `/` 一并吃掉，
        // 使 `(?:\/\]|…)` 分支永不匹配（历史遗留死分支）。
        // 各分支首字符互斥（非引号非斜杠 / 斜杠 / 双引号 / 单引号），因此不存在灾难性回溯。
        if (!preg_match_all(
            '/\[item\b((?:[^\]"\'\/]|\/[^\]"\'\/]|"[^"]*"|\'[^\']*\')*)\s*\/?\s*(?:\]((?:[^\[]|\[(?!\/item\]))*+)\[\/item\]|\])/i',
            $content,
            $items,
            PREG_SET_ORDER
        )) {
            // 无法解析时按纯文本输出（不把 `[item …]` 原样当标记放行）。
            return self::escapeHtml($content);
        }

        // Limit number of items to prevent excessive rendering
        if (count($items) > 200) {
            return '<div class="alert alert-warning">Love List 项目过多（最多200项）</div>';
        }

        $themeUrlRaw = rtrim(self::optionValue('themeUrl', ''), '/');
        $todoIcon = self::escapeUrlAttribute($themeUrlRaw . '/svg/todo.svg', true, array('http', 'https'));
        $okIcon = self::escapeUrlAttribute($themeUrlRaw . '/svg/ok.svg', true, array('http', 'https'));
        $allowTitleHtml = self::optionFlag('loveListTitleAllowHtml', false);

        $out = '<div class="accordion mx-auto mt-5 brave-love-list" id="loveList' . $listIndex . '">';
        foreach ($items as $key => $item) {
            $attrs = self::parseLoveListAttributes(isset($item[1]) ? $item[1] : '');
            $status = isset($attrs['status']) ? (string)$attrs['status'] : '0';
            $isCompleted = ($status === '1');

            $rawTitle = isset($item[2]) ? (string)$item[2] : '';
            $safeTitle = self::sanitizeLoveListTitle($rawTitle, $allowTitleHtml);

            $rawImg = isset($attrs['img']) ? (string)$attrs['img'] : '';
            $imgStyle = self::buildBackgroundImageStyle($rawImg);

            $out .= '<div class="card">';
            $out .= '<div class="card-header p-1" id="heading'.$listIndex.'-'.$key.'"><h2 class="mb-0">';
            $out .= '<button class="btn collapsed ml-auto d-flex align-items-center" type="button" data-toggle="collapse" data-target="#collapse'.$listIndex.'-'.$key.'" aria-expanded="false" aria-controls="collapse'.$listIndex.'-'.$key.'">';
            $statusIcon = $isCompleted ? $okIcon : $todoIcon;
            if ($statusIcon !== '') {
                $out .= '<img class="statusIcon" src="' . $statusIcon . '" alt="">';
            }
            $out .= '<strong>'.$safeTitle.'</strong>';
            $out .= '</button></h2></div>';
            $out .= '<div id="collapse'.$listIndex.'-'.$key.'" class="collapse" aria-labelledby="heading'.$listIndex.'-'.$key.'" data-parent="#loveList'.$listIndex.'">';
            if ($imgStyle !== '') {
                $out .= '<div class="card-body p-0">';
                $out .= '<section style="'.htmlspecialchars($imgStyle, ENT_QUOTES, 'UTF-8').'"></section>';
                $out .= '</div>';
            }
            $out .= '</div></div>';
        }
        $out .= '</div>';

        return $out;
    }

    public static function normalizeUrl($url, $allowRelative, $allowedSchemes)
    {
        $url = trim((string)$url);
        if ($url === '') {
            return '';
        }

        // Remove ASCII control chars to avoid browser/parser discrepancies.
        $url = preg_replace('/[\\x00-\\x1F\\x7F]+/', '', $url);
        if ($url === '') {
            return '';
        }

        // Normalize entities once, then use a whitespace-stripped copy for scheme checks.
        $decodedUrl = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decodedUrl = preg_replace('/[\\x00-\\x1F\\x7F]+/', '', $decodedUrl);
        if ($decodedUrl === '') {
            return '';
        }
        $schemeCheckUrl = preg_replace('/[\\x00-\\x20]+/', '', $decodedUrl);

        // Block dangerous schemes even if obfuscated with entities/whitespace.
        if (preg_match('#^(?:javascript|data|vbscript|file):#i', $schemeCheckUrl)) {
            return '';
        }

        // 拦截带用户信息的 URL（user:pass@host），避免凭据被写进可见属性。
        if (preg_match('#^[a-z][a-z0-9+.-]*://[^/@]*@#i', $schemeCheckUrl)) {
            return '';
        }

        // Allow protocol-relative URLs (e.g. //example.com/a.png), but verify they don't contain dangerous schemes.
        if (strpos($schemeCheckUrl, '//') === 0) {
            // Check that protocol-relative URL doesn't embed dangerous schemes after //
            $afterSlashes = substr($schemeCheckUrl, 2);
            if (preg_match('#^(?:javascript|data|vbscript|file):#i', $afterSlashes)) {
                return '';
            }
            // Block user info in protocol-relative URLs
            if (preg_match('#^[^/@]*@#', $afterSlashes)) {
                return '';
            }
            return $decodedUrl;
        }

        if (preg_match('#^([a-z][a-z0-9+.-]*):#i', $schemeCheckUrl, $m)) {
            $scheme = strtolower($m[1]);
            if (!in_array($scheme, $allowedSchemes, true)) {
                return '';
            }

            // 这里不做「禁止 localhost / 私有 IP」的 SSRF 黑名单：
            // 1) 该方法只用于生成 HTML 属性，PHP 端不会发起任何请求，黑名单不构成实际防护；
            // 2) 该分支原本被 `!$allowRelative` 保护，而所有调用点都传 true，属于永不生效的死代码；
            // 3) 真要做内网图片/内网地址拦截，应交由站点层（反向代理 / 出网策略）处理，
            //    否则会误伤内网部署时配置的内网图片地址。
            return $decodedUrl;
        }

        if ($allowRelative) {
            $firstChar = substr($decodedUrl, 0, 1);
            if ($firstChar === '/' || $firstChar === '#') {
                return $decodedUrl;
            }
            if (strpos($decodedUrl, './') === 0 || strpos($decodedUrl, '../') === 0) {
                return $decodedUrl;
            }
            // Allow ordinary relative URLs such as blog/ after scheme checks.
            return $decodedUrl;
        }

        return '';
    }

    public static function escapeUrlAttribute($url, $allowRelative = true, $allowedSchemes = array('http', 'https'))
    {
        $safeUrl = self::normalizeUrl($url, $allowRelative, $allowedSchemes);
        if ($safeUrl === '') {
            return '';
        }
        return htmlspecialchars($safeUrl, ENT_QUOTES, 'UTF-8');
    }

    public static function buildBackgroundImageStyle($url)
    {
        $safeUrl = self::normalizeUrl($url, true, array('http', 'https'));
        if ($safeUrl === '') {
            return '';
        }

        // Prevent breaking out of CSS url('...') and the HTML style attribute.
        $safeUrl = str_replace(array("\r", "\n", "\t"), '', $safeUrl);
        $safeUrl = str_replace(array("'", '"', '\\'), array('%27', '%22', '%5C'), $safeUrl);

        return "background-image: url('{$safeUrl}')";
    }

    public static function escapeJsString($value)
    {
        $value = (string)$value;
        $json = json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        return ($json !== false) ? $json : '""';
    }

    public static function guardInlineScriptSnippet($js)
    {
        $js = (string)$js;
        // This only prevents closing the script element early; it is not a JavaScript sanitizer.
        return str_ireplace('</script', '<\\/script', $js);
    }

    public static function escapeInlineScriptSnippet($js)
    {
        return self::guardInlineScriptSnippet($js);
    }

    public static function guardInlineStyleSnippet($css)
    {
        $css = (string)$css;
        // This only prevents closing the style element early; it is not a CSS sanitizer.
        return str_ireplace('</style', '<\\/style', $css);
    }

    public static function escapeInlineStyleSnippet($css)
    {
        return self::guardInlineStyleSnippet($css);
    }

    private static function normalizeClassList($class)
    {
        $class = (string)$class;
        $class = preg_replace('/[^A-Za-z0-9 _-]+/', '', $class);
        $class = trim(preg_replace('/\\s+/', ' ', $class));
        return $class;
    }

    private static function normalizeRelTokens($rel, $requiredTokens)
    {
        $rel = strtolower((string)$rel);
        $parts = preg_split('/\\s+/', trim($rel));
        $map = array();
        foreach ($parts as $p) {
            if ($p === '') continue;
            $map[$p] = true;
        }
        foreach ($requiredTokens as $token) {
            $map[$token] = true;
        }
        return implode(' ', array_keys($map));
    }

    private static function unwrapNode($node)
    {
        $parent = $node->parentNode;
        if (!$parent) {
            return;
        }

        while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
        }
        $parent->removeChild($node);
    }

    private static function sanitizeElementAttributes($element, $allowedAttrsByTag)
    {
        $tag = strtolower($element->nodeName);
        $allowed = isset($allowedAttrsByTag[$tag]) ? $allowedAttrsByTag[$tag] : array();
        $allowedMap = array();
        foreach ($allowed as $attrName) {
            $allowedMap[strtolower($attrName)] = true;
        }

        $removeElement = false;

        if ($element->hasAttributes()) {
            $toRemove = array();
            foreach ($element->attributes as $attr) {
                $name = strtolower($attr->nodeName);

                // Drop all event handler attributes like onclick/onerror...
                if (strpos($name, 'on') === 0) {
                    $toRemove[] = $name;
                    continue;
                }

                if (!isset($allowedMap[$name])) {
                    $toRemove[] = $name;
                    continue;
                }

                $value = $attr->nodeValue;

                if ($tag === 'a' && $name === 'href') {
                    $safeUrl = self::normalizeUrl($value, true, array('http', 'https', 'mailto'));
                    if ($safeUrl === '') {
                        $toRemove[] = $name;
                    } else {
                        $element->setAttribute('href', $safeUrl);
                    }
                    continue;
                }

                if ($tag === 'img' && $name === 'src') {
                    $safeUrl = self::normalizeUrl($value, true, array('http', 'https'));
                    if ($safeUrl === '') {
                        $removeElement = true;
                    } else {
                        $element->setAttribute('src', $safeUrl);
                    }
                    continue;
                }

                if (($tag === 'code' || $tag === 'pre') && $name === 'class') {
                    $safeClass = self::normalizeClassList($value);
                    if ($safeClass === '') {
                        $toRemove[] = $name;
                    } else {
                        $element->setAttribute('class', $safeClass);
                    }
                    continue;
                }

                if ($tag === 'img' && $name === 'class') {
                    $safeClass = self::normalizeClassList($value);
                    if ($safeClass === '') {
                        $toRemove[] = $name;
                    } else {
                        $element->setAttribute('class', $safeClass);
                    }
                    continue;
                }

                if ($tag === 'a' && $name === 'target') {
                    $target = strtolower(trim((string)$value));
                    if ($target !== '_blank' && $target !== '_self') {
                        $toRemove[] = $name;
                    } else {
                        $element->setAttribute('target', $target);
                    }
                    continue;
                }

                if ($tag === 'a' && $name === 'rel') {
                    // Normalized later (after attribute iteration).
                    continue;
                }

                if ($tag === 'img' && ($name === 'loading' || $name === 'referrerpolicy')) {
                    // Overwrite later with safer defaults.
                    continue;
                }
            }

            foreach ($toRemove as $name) {
                $element->removeAttribute($name);
            }
        }

        if ($tag === 'img' && !$element->hasAttribute('src')) {
            $removeElement = true;
        }

        if ($removeElement) {
            $parent = $element->parentNode;
            if ($parent) {
                $parent->removeChild($element);
            }
            return false;
        }

        // Post-process a/img attributes with safer defaults.
        if ($tag === 'a') {
            $rel = $element->getAttribute('rel');
            $element->setAttribute('rel', self::normalizeRelTokens($rel, array('nofollow', 'ugc', 'noopener', 'noreferrer')));
        }

        if ($tag === 'img') {
            $element->setAttribute('loading', 'lazy');
            $element->setAttribute('referrerpolicy', 'no-referrer');
        }

        return true;
    }

    private static function truncationNoticeText()
    {
        // 与 App 内其他提示文案保持一致：不依赖 Typecho 的 _t()，保证该类可独立单测。
        return '（内容过长，已截断）';
    }

    private static function truncationNoticeHtml()
    {
        return '<p class="brave-truncated-notice">' . self::truncationNoticeText() . '</p>';
    }

    private static function sanitizeHtmlFragment($html, $allowedTags, $allowedAttrsByTag)
    {
        $html = (string)$html;
        if ($html === '') {
            return '';
        }

        // Protect against DoS: limit HTML length to prevent memory exhaustion.
        // 超长内容改为「截断后继续净化」，而不是整体转义（后者会让长评论/长列表破版）。
        $truncated = false;
        if (strlen($html) > self::MAX_HTML_LENGTH) {
            $html = substr($html, 0, self::MAX_HTML_LENGTH);
            $truncated = true;
        }

        // Fast path: pure text (no HTML tags)
        if (strpos($html, '<') === false) {
            $escaped = htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
            return $truncated ? $escaped . self::truncationNoticeText() : $escaped;
        }

        // 注意：这里不要加"整体包裹在单个白名单标签内就直接 htmlspecialchars"的快速路径。
        // Typecho 的评论经 Markdown（HyperDown）渲染后必定是 `<p>…</p>` / `<p>…</p><p>…</p>`，
        // 那种快速路径会把合法标签一起转义，导致祝福板显示成原始标签文本。
        // 标签白名单判定统一交给下面的 DOM 分支。

        // DOMDocument is available in all standard PHP installations since PHP 5.
        // This check is kept for extreme edge cases (custom minimal builds).
        if (!class_exists('DOMDocument')) {
            $escaped = htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
            return $truncated ? $escaped . self::truncationNoticeText() : $escaped;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        // Defense-in-depth: avoid external entity resolution / network loads.
        $dom->resolveExternals = false;
        $dom->substituteEntities = false;
        $dom->validateOnParse = false;

        // Additional XXE protection
        if (function_exists('libxml_disable_entity_loader')) {
            @libxml_disable_entity_loader(true);
        }

        $prev = libxml_use_internal_errors(true);

        $wrapped = '<div>' . $html . '</div>';
        $flags = 0;
        if (defined('LIBXML_HTML_NOIMPLIED') && defined('LIBXML_HTML_NODEFDTD')) {
            $flags |= (LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        }
        if (defined('LIBXML_NONET')) {
            $flags |= LIBXML_NONET;
        }

        if ($flags !== 0) {
            $dom->loadHTML('<?xml encoding="UTF-8">' . $wrapped, $flags);
        } else {
            $dom->loadHTML('<?xml encoding="UTF-8">' . $wrapped);
        }

        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $dom->getElementsByTagName('div')->item(0);
        if (!$root) {
            $escaped = htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
            return $truncated ? $escaped . self::truncationNoticeText() : $escaped;
        }

        $allowedTagMap = array();
        foreach ($allowedTags as $tag) {
            $allowedTagMap[strtolower($tag)] = true;
        }

        $dangerousTags = array(
            'script' => true,
            'style' => true,
            'iframe' => true,
            'object' => true,
            'embed' => true,
        );

        $walk = function ($node) use (&$walk, $allowedTagMap, $allowedAttrsByTag, $dangerousTags) {
            for ($child = $node->firstChild; $child !== null; ) {
                $next = $child->nextSibling;

                if ($child->nodeType === XML_COMMENT_NODE) {
                    $node->removeChild($child);
                    $child = $next;
                    continue;
                }

                if ($child->nodeType !== XML_ELEMENT_NODE) {
                    $child = $next;
                    continue;
                }

                $tag = strtolower($child->nodeName);

                if (!isset($allowedTagMap[$tag])) {
                    // Remove dangerous blocks entirely; otherwise sanitize children then unwrap.
                    if (isset($dangerousTags[$tag])) {
                        $node->removeChild($child);
                    } else {
                        $walk($child);
                        self::unwrapNode($child);
                    }

                    $child = $next;
                    continue;
                }

                $kept = self::sanitizeElementAttributes($child, $allowedAttrsByTag);
                if ($kept) {
                    $walk($child);
                }

                $child = $next;
            }
        };

        $walk($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        if ($truncated) {
            $out .= self::truncationNoticeHtml();
        }

        return $out;
    }

    public static function sanitizeCommentHtml($html, $allowImages = false)
    {
        $allowedTags = array('a', 'p', 'br', 'strong', 'em', 'del', 'code', 'pre', 'blockquote', 'ul', 'ol', 'li', 'hr');
        if ($allowImages) {
            $allowedTags[] = 'img';
        }

        $allowedAttrsByTag = array(
            'a' => array('href', 'title', 'rel', 'target'),
            'code' => array('class'),
            'pre' => array('class'),
            'img' => array('src', 'alt', 'title', 'class', 'loading', 'referrerpolicy'),
        );

        return self::sanitizeHtmlFragment($html, $allowedTags, $allowedAttrsByTag);
    }

    public static function sanitizeCommentAuthorHtml($html)
    {
        $allowedTags = array('a');
        $allowedAttrsByTag = array(
            'a' => array('href', 'title', 'rel', 'target'),
        );

        return self::sanitizeHtmlFragment($html, $allowedTags, $allowedAttrsByTag);
    }

    public static function sanitizeLoveListTitle($title, $allowHtml = false)
    {
        $title = (string)$title;

        if (!$allowHtml) {
            return htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        }

        return self::sanitizeHtmlFragment(
            $title,
            array('del', 'code', 'strong', 'em', 'br'),
            array()
        );
    }
}
