<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Author: Veen Zhao
 * CreateTime: 2020/9/5 18:26
 */

class App
{
    public static function escapeHtml($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    public static function parseShortCode($content)
    {
        $content = do_shortcode($content);
        return $content;
    }

    public static function avatarQQ($ctx)
    {
        if ($ctx) {
            if (strpos($ctx, "@qq.com") !== false) {
                $email = str_replace('@qq.com', '', $ctx);
                if (is_numeric($email)) {
                    return "//q1.qlogo.cn/g?b=qq&nk=" . $email . "&";
                } else {
                    $str = $email . '@qq.com';
                    $email = md5($str);
                    return "//sdn.geekzu.org/avatar/" . $email . "?";
                }
            } else {
                $email = md5($ctx);
                return "//sdn.geekzu.org/avatar/" . $email . "?";
            }
        } else {
            return "//sdn.geekzu.org/avatar/null?";
        }
    }

    public static function normalizeUrl($url, $allowRelative, $allowedSchemes)
    {
        if (!is_string($url)) {
            return '';
        }

        $url = trim($url);
        // Remove ASCII control chars to avoid browser/parser discrepancies.
        $url = preg_replace('/[\\x00-\\x1F\\x7F]+/', '', $url);
        if ($url === '') {
            return '';
        }

        // Normalize for scheme checks: decode entities and remove ASCII control/space chars.
        $decoded = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = preg_replace('/[\\x00-\\x20]+/', '', $decoded);

        // Block dangerous schemes even if obfuscated with entities/whitespace.
        if (preg_match('#^(?:javascript|data|vbscript):#i', $decoded)) {
            return '';
        }

        // Allow protocol-relative URLs (e.g. //example.com/a.png).
        if (strpos($decoded, '//') === 0) {
            return $url;
        }

        if (preg_match('#^([a-z][a-z0-9+.-]*):#i', $decoded, $m)) {
            $scheme = strtolower($m[1]);
            if (!in_array($scheme, $allowedSchemes, true)) {
                return '';
            }
            return $url;
        }

        if ($allowRelative) {
            $firstChar = substr($url, 0, 1);
            if ($firstChar === '/' || $firstChar === '#') {
                return $url;
            }
            if (strpos($url, './') === 0 || strpos($url, '../') === 0) {
                return $url;
            }
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
        if (!is_string($value)) {
            $value = (string)$value;
        }

        $json = json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        return ($json !== false) ? $json : '""';
    }

    public static function escapeInlineScriptSnippet($js)
    {
        $js = (string)$js;
        // Prevent closing the <script> element early (e.g. via </script> inside user-provided snippets).
        return str_ireplace('</script', '<\\/script', $js);
    }

    public static function escapeInlineStyleSnippet($css)
    {
        $css = (string)$css;
        // Prevent closing the <style> element early (e.g. via </style> inside user-provided snippets).
        return str_ireplace('</style', '<\\/style', $css);
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

    private static function sanitizeHtmlFragment($html, $allowedTags, $allowedAttrsByTag)
    {
        $html = (string)$html;
        if ($html === '') {
            return '';
        }

        if (!class_exists('DOMDocument')) {
            // Safe fallback: render as plain text.
            return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        // Defense-in-depth: avoid external entity resolution / network loads.
        $dom->resolveExternals = false;
        $dom->substituteEntities = false;
        $dom->validateOnParse = false;
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
            return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
        }

        $allowedTagMap = array();
        foreach ($allowedTags as $tag) {
            $allowedTagMap[strtolower($tag)] = true;
        }

        $walk = function ($node) use (&$walk, $allowedTagMap, $allowedAttrsByTag) {
            $children = array();
            foreach ($node->childNodes as $child) {
                $children[] = $child;
            }

            foreach ($children as $child) {
                if ($child->nodeType === XML_COMMENT_NODE) {
                    $node->removeChild($child);
                    continue;
                }

                if ($child->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }

                $tag = strtolower($child->nodeName);

                if (!isset($allowedTagMap[$tag])) {
                    // Remove dangerous blocks entirely; otherwise unwrap to keep inner text.
                    if ($tag === 'script' || $tag === 'style' || $tag === 'iframe' || $tag === 'object' || $tag === 'embed') {
                        $node->removeChild($child);
                    } else {
                        self::unwrapNode($child);
                    }
                    continue;
                }

                $kept = self::sanitizeElementAttributes($child, $allowedAttrsByTag);
                if ($kept) {
                    $walk($child);
                }
            }
        };

        $walk($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
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

function loveListAcc($atts, $content = '')
{
    if (!preg_match_all("/(.?)\[(item)\b(.*?)(?:(\/))?\](?:(.+?)\[\/item\])?(.?)/s", $content, $matches)) {
        return do_shortcode($content);
    } else {
        for ($i = 0; $i < count($matches[0]); $i++) {
            $matches[3][$i] = shortcode_parse_atts($matches[3][$i]);
        }
        $out = '<div class="accordion mx-auto mt-5" id="loveList">';

        $allowTitleHtml = false;
        $options = Helper::options();
        if (isset($options->loveListTitleAllowHtml) && (string)$options->loveListTitleAllowHtml === '1') {
            $allowTitleHtml = true;
        }

        foreach ($matches[3] as $key => $value){
            if (!is_array($value)) {
                $value = array();
            }

            $status = isset($value['status']) ? (string)$value['status'] : '0';
            $isTodo = ($status === '0');

            $rawTitle = isset($matches[5][$key]) ? (string)$matches[5][$key] : '';
            $safeTitle = App::sanitizeLoveListTitle($rawTitle, $allowTitleHtml);

            $rawImg = isset($value['img']) ? (string)$value['img'] : '';
            $style = '';
            if ($rawImg !== '') {
                $safeImg = App::normalizeUrl($rawImg, true, array('http', 'https'));
                if ($safeImg !== '') {
                    $safeImg = str_replace(array("\\", "\r", "\n"), array("\\\\", '', ''), $safeImg);
                    $safeImg = str_replace("'", "\\'", $safeImg);
                    $style = "background-image: url('{$safeImg}')";
                }
            }

            $out .= '<div class="card">';
            $out .= '<div class="card-header p-1 bg-white" id="heading'.$key.'"><h2 class="mb-0">';
            $out .= '<span class="btn collapsed ml-auto d-flex align-items-center" type="button" data-toggle="collapse" data-target="#collapse'.$key.'" aria-expanded="false" aria-controls="collapse'.$key.'">';
            if ($isTodo)
                $out .= '<img class="statusIcon" src="'.Helper::options()->themeUrl.'/svg/todo.svg">';
            else
                $out .= '<img class="statusIcon" src="'.Helper::options()->themeUrl.'/svg/ok.svg">';
            $out .= '<strong>'.$safeTitle.'</strong>';
            $out .= '</span></h2></div>';
            $out .= '<div id="collapse'.$key.'" class="collapse" aria-labelledby="heading'.$key.'" data-parent="#loveList">';
            $out .= '<div class="card-body p-0">';
            if ($style !== '') {
                $out .= '<section style="'.htmlspecialchars($style, ENT_QUOTES, 'UTF-8').'"></section>';
            } else {
                $out .= '<section></section>';
            }
            $out .= '</div></div></div>';
        }
        $out .= '</div>';
        return $out;
    }
}
add_shortcode('loveList', 'loveListAcc');
