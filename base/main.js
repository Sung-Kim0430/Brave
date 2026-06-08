// 请保留版权说明，祝99
if (window.console && window.console.log) {
    console.log("%c Brave 主题 v1.2 %c https://blog.zwying.com ","color: #fff; margin: 1em 0; padding: 5px 0; background: #673ab7;","margin: 1em 0; padding: 5px 0; background: #efefef;");
}

(function() {
    var THEME_STORAGE_KEY = 'brave-theme';

    function isDarkModeEnabled() {
        try {
            return document.documentElement && document.documentElement.getAttribute('data-darkmode') === '1';
        } catch (e) {
            return false;
        }
    }

    function safeStorageGet(key) {
        try {
            return localStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }

    function safeStorageSet(key, value) {
        try {
            localStorage.setItem(key, value);
        } catch (e) {}
    }

    function safeStorageRemove(key) {
        try {
            localStorage.removeItem(key);
        } catch (e) {}
    }

    function getSavedTheme() {
        var v = safeStorageGet(THEME_STORAGE_KEY);
        return (v === 'dark' || v === 'light') ? v : null;
    }

    function getSystemTheme() {
        var mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
        return (mql && mql.matches) ? 'dark' : 'light';
    }

    function getCurrentTheme() {
        var t = document.documentElement ? document.documentElement.getAttribute('data-theme') : '';
        return (t === 'dark' || t === 'light') ? t : null;
    }

    function applyTheme(theme) {
        if (!document.documentElement) return;
        if (theme !== 'dark' && theme !== 'light') return;
        document.documentElement.setAttribute('data-theme', theme);
        updateThemeToggle(theme);
    }

    function updateThemeToggle(theme) {
        var btn = document.querySelector('[data-theme-toggle]');
        if (!btn) return;

        var isDark = (theme === 'dark');
        btn.setAttribute('aria-checked', isDark ? 'true' : 'false');
        btn.setAttribute('aria-label', isDark ? '暗色模式已开启' : '暗色模式已关闭');
        btn.title = '切换暗色模式（Shift+点击跟随系统）';
    }

    function bindThemeToggle() {
        var btn = document.querySelector('[data-theme-toggle]');
        if (!btn) return;
        if (btn.dataset.braveThemeBound === '1') return;
        btn.dataset.braveThemeBound = '1';

        btn.addEventListener('click', function(e) {
            if (!isDarkModeEnabled()) return;

            if (e && e.shiftKey) {
                safeStorageRemove(THEME_STORAGE_KEY);
                applyTheme(getSystemTheme());
                return;
            }

            var current = getCurrentTheme() || getSystemTheme();
            var next = (current === 'dark') ? 'light' : 'dark';
            safeStorageSet(THEME_STORAGE_KEY, next);
            applyTheme(next);
        });
    }

    function bindSystemThemeListener() {
        if (!window.matchMedia) return;
        window.BraveTheme = window.BraveTheme || {};
        if (window.BraveTheme._themeMql) return;

        var mql = window.matchMedia('(prefers-color-scheme: dark)');
        var handler = function(e) {
            if (!isDarkModeEnabled()) return;
            if (getSavedTheme()) return;
            applyTheme(e.matches ? 'dark' : 'light');
        };

        if (mql.addEventListener) {
            mql.addEventListener('change', handler);
        } else if (mql.addListener) {
            mql.addListener(handler);
        }

        window.BraveTheme._themeMql = mql;
        window.BraveTheme._themeMqlHandler = handler;
    }

    function initTheme() {
        if (!isDarkModeEnabled()) return;

        var theme = getCurrentTheme() || getSavedTheme() || getSystemTheme();
        applyTheme(theme);
        bindThemeToggle();
        bindSystemThemeListener();
    }

    function bindBackLinks() {
        var links = document.querySelectorAll('[data-brave-back]');
        if (!links || !links.length) return;

        for (var i = 0; i < links.length; i++) {
            var link = links[i];
            if (!link) continue;
            if (link.dataset.braveBackBound === '1') continue;
            link.dataset.braveBackBound = '1';

            link.addEventListener('click', function(e) {
                if (!e) return;
                if (e.defaultPrevented) return;
                if (e.button != null && e.button !== 0) return;
                if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

                if (window.history && window.history.length > 1) {
                    e.preventDefault();
                    window.history.back();
                }
            });
        }
    }

    function hashString(str) {
        var h = 5381;
        for (var i = 0; i < str.length; i++) {
            h = ((h << 5) + h) + str.charCodeAt(i);
            h = h & 0xffffffff;
        }
        return (h >>> 0).toString(36);
    }

    function slugify(text) {
        var t = String(text || '').trim().toLowerCase();
        // Try to create ASCII-safe slug
        t = t.replace(/\s+/g, '-');
        var ascii = t.replace(/[^a-z0-9-]/g, '');
        ascii = ascii.replace(/-+/g, '-');
        ascii = ascii.replace(/^-+|-+$/g, '');

        // If we have a reasonable ASCII slug (3+ chars), use it
        if (ascii.length >= 3) {
            return ascii;
        }

        // For non-ASCII content (e.g., Chinese), return empty to trigger hash fallback
        return '';
    }

    function ensureHeadingId(heading, used) {
        if (!heading) return '';
        var id = (heading.getAttribute('id') || '').trim();
        if (!id) {
            var raw = heading.textContent || '';
            id = slugify(raw);
            if (!id || id.length < 3) {
                id = 'h-' + hashString(raw || String(Date.now()));
            }
        }
        var base = id;
        var i = 2;
        while (used[id]) {
            id = base + '-' + i;
            i++;
        }
        used[id] = true;
        heading.setAttribute('id', id);
        return id;
    }

    function getArticleRoot() {
        return document.querySelector('#article article');
    }

    function removeExistingToc() {
        if (window.BraveTheme && window.BraveTheme._tocObserver) {
            try {
                // Properly unobserve all nodes before disconnect
                if (window.BraveTheme._tocObservedNodes) {
                    for (var i = 0; i < window.BraveTheme._tocObservedNodes.length; i++) {
                        window.BraveTheme._tocObserver.unobserve(window.BraveTheme._tocObservedNodes[i]);
                    }
                }
                window.BraveTheme._tocObserver.disconnect();
            } catch (e) {}
            window.BraveTheme._tocObserver = null;
            window.BraveTheme._tocObservedNodes = null;
        }
        var existing = document.getElementById('brave-article-toc');
        if (existing && existing.parentNode) {
            existing.parentNode.removeChild(existing);
        }
    }

    function buildArticleToc() {
        removeExistingToc();
        var root = getArticleRoot();
        if (!root) return;

        var headings = root.querySelectorAll('h1, h2, h3, h4');
        if (!headings || !headings.length) {
            return;
        }

        var used = {};
        var items = [];
        for (var i = 0; i < headings.length; i++) {
            var h = headings[i];
            var text = (h.textContent || '').trim();
            if (!text) continue;
            var tag = (h.tagName || '').toUpperCase();
            if (tag === 'H1') {
                // 内容里偶尔会出现 H1，保留但作为最高级别目录项。
            }
            var id = ensureHeadingId(h, used);
            if (!id) continue;
            items.push({ id: id, level: tag, text: text, node: h });
        }

        if (!items.length) {
            return;
        }

        var tocWrap = document.createElement('details');
        tocWrap.id = 'brave-article-toc';
        tocWrap.className = 'article-toc-wrap';
        tocWrap.open = true;

        var summary = document.createElement('summary');
        summary.className = 'article-toc-summary';
        summary.textContent = '目录';
        tocWrap.appendChild(summary);

        var nav = document.createElement('nav');
        nav.className = 'article-toc';
        nav.setAttribute('aria-label', '目录');

        var list = document.createElement('ol');
        list.className = 'article-toc-list';

        for (var j = 0; j < items.length; j++) {
            var item = items[j];
            var li = document.createElement('li');
            li.className = 'toc-item toc-' + item.level.toLowerCase();
            li.setAttribute('data-target', item.id);

            var a = document.createElement('a');
            a.className = 'toc-link';
            a.href = '#' + item.id;
            a.textContent = item.text;
            li.appendChild(a);
            list.appendChild(li);
        }

        nav.appendChild(list);
        tocWrap.appendChild(nav);

        var articleContainer = document.getElementById('article');
        var timeEl = articleContainer ? articleContainer.querySelector('time') : null;
        if (timeEl && timeEl.parentNode) {
            timeEl.insertAdjacentElement('afterend', tocWrap);
        } else {
            root.insertAdjacentElement('beforebegin', tocWrap);
        }

        // 高亮当前章节（IntersectionObserver 优先；不支持则跳过）。
        // Note: Observer cleanup is handled by removeExistingToc() before this point

        if ('IntersectionObserver' in window) {
            var setActive = function(id) {
                var links = tocWrap.querySelectorAll('.toc-item');
                for (var k = 0; k < links.length; k++) {
                    var link = links[k].querySelector('.toc-link');
                    if (links[k].getAttribute('data-target') === id) {
                        links[k].classList.add('is-active');
                        if (link) link.setAttribute('aria-current', 'location');
                    } else {
                        links[k].classList.remove('is-active');
                        if (link) link.removeAttribute('aria-current');
                    }
                }
            };

            var observer = new IntersectionObserver(function(entries) {
                for (var k = 0; k < entries.length; k++) {
                    if (entries[k].isIntersecting && entries[k].target && entries[k].target.id) {
                        setActive(entries[k].target.id);
                        break;
                    }
                }
            }, {
                root: null,
                rootMargin: '-20% 0px -70% 0px',
                threshold: [0, 1]
            });

            for (var k = 0; k < items.length; k++) {
                observer.observe(items[k].node);
            }

            window.BraveTheme = window.BraveTheme || {};
            window.BraveTheme._tocObserver = observer;
            window.BraveTheme._tocObservedNodes = items.map(function(item) { return item.node; });
        }
    }

    function copyTextToClipboard(text) {
        if (text == null || text === '') return Promise.reject(new Error('empty'));

        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        return new Promise(function(resolve, reject) {
            try {
                var textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', 'readonly');
                textarea.style.position = 'fixed';
                textarea.style.left = '-9999px';
                textarea.style.top = '0';
                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();
                var ok = document.execCommand('copy');
                document.body.removeChild(textarea);
                if (ok) resolve();
                else reject(new Error('copy failed'));
            } catch (e) {
                reject(e);
            }
        });
    }

    function enhanceCodeBlocks() {
        var root = getArticleRoot();
        if (!root) return;

	        var pres = root.querySelectorAll('pre');
	        for (var i = 0; i < pres.length; i++) {
	            var pre = pres[i];
	            if ((pre.closest && pre.closest('.brave-codeblock')) || (pre.parentNode && pre.parentNode.classList && pre.parentNode.classList.contains('brave-codeblock'))) continue;

	            var wrapper = document.createElement('div');
	            wrapper.className = 'brave-codeblock';

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'brave-codecopy';
            btn.textContent = '复制';

            (function(preEl, btnEl) {
                btnEl.addEventListener('click', function() {
                    var codeEl = preEl.querySelector('code');
                    var text = codeEl ? codeEl.textContent : preEl.textContent;
                    copyTextToClipboard(text).then(function() {
                        btnEl.textContent = '已复制';
                        btnEl.classList.add('is-ok');
                        window.setTimeout(function() {
                            btnEl.textContent = '复制';
                            btnEl.classList.remove('is-ok');
                        }, 1200);
                    }).catch(function() {
                        btnEl.textContent = '失败';
                        window.setTimeout(function() {
                            btnEl.textContent = '复制';
                        }, 1200);
                    });
                });
            })(pre, btn);

            pre.parentNode.insertBefore(wrapper, pre);
            wrapper.appendChild(btn);
            wrapper.appendChild(pre);
        }
    }

    function ensureLightbox() {
        var existing = document.getElementById('brave-lightbox');
        if (existing) return existing;

        var overlay = document.createElement('div');
        overlay.id = 'brave-lightbox';
        overlay.className = 'brave-lightbox';

        var inner = document.createElement('div');
        inner.className = 'brave-lightbox-inner';
        inner.setAttribute('role', 'dialog');
        inner.setAttribute('aria-modal', 'true');
        inner.setAttribute('aria-label', '图片预览');

        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'brave-lightbox-close';
        closeBtn.setAttribute('aria-label', '关闭图片预览');
        closeBtn.textContent = '×';

        var lightboxImg = document.createElement('img');
        lightboxImg.className = 'brave-lightbox-img';
        lightboxImg.alt = '';

        inner.appendChild(closeBtn);
        inner.appendChild(lightboxImg);
        overlay.appendChild(inner);
        document.body.appendChild(overlay);

        var close = function() {
            overlay.classList.remove('is-open');
            document.body.classList.remove('brave-lightbox-open');

            // 焦点恢复到触发元素
            if (window.BraveTheme._lightboxTrigger && window.BraveTheme._lightboxTrigger.focus) {
                window.BraveTheme._lightboxTrigger.focus();
            }
            window.BraveTheme._lightboxTrigger = null;
        };

        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) close();
        });
        closeBtn.addEventListener('click', close);

        // Tab 焦点陷阱
        overlay.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                close();
                return;
            }

            if (e.key === 'Tab') {
                var focusableElements = overlay.querySelectorAll('button, [href], [tabindex]:not([tabindex="-1"])');
                if (focusableElements.length === 0) return;

                var firstFocusable = focusableElements[0];
                var lastFocusable = focusableElements[focusableElements.length - 1];

                if (e.shiftKey && document.activeElement === firstFocusable) {
                    e.preventDefault();
                    lastFocusable.focus();
                } else if (!e.shiftKey && document.activeElement === lastFocusable) {
                    e.preventDefault();
                    firstFocusable.focus();
                }
            }
        });

        return overlay;
    }

    function openLightbox(src, alt) {
        if (!src) return;
        var overlay = ensureLightbox();
        var img = overlay.querySelector('.brave-lightbox-img');
        var closeBtn = overlay.querySelector('.brave-lightbox-close');
        if (!img) return;

        // 保存触发元素用于焦点恢复
        window.BraveTheme._lightboxTrigger = document.activeElement;

        img.src = src;
        img.alt = alt || '';
        overlay.classList.add('is-open');
        document.body.classList.add('brave-lightbox-open');

        // 聚焦到关闭按钮
        if (closeBtn) {
            setTimeout(function() {
                closeBtn.focus();
            }, 100);
        }
    }

    function enhanceArticleImages() {
        var root = getArticleRoot();
        if (!root) return;

        // Mark images as zoomable without individual event listeners
        var imgs = root.querySelectorAll('img');
        for (var i = 0; i < imgs.length; i++) {
            var img = imgs[i];
            if (img.dataset.braveZoomBound === '1') continue;

            var link = (img.closest ? img.closest('a') : null);
            if (link) {
                var href = (link.getAttribute('href') || '').trim();
                // 避免干扰普通链接：仅对”图片链接”启用预览。
                var isImageLink = /\.(png|jpe?g|gif|webp|svg)(\?.*)?$/i.test(href);
                if (!isImageLink) continue;
            }

            img.classList.add('brave-zoomable');
            img.dataset.braveZoomBound = '1';
        }

        // Use event delegation on article root instead of individual listeners
        if (!root.dataset.braveLightboxBound) {
            root.addEventListener('click', function(e) {
                var target = e.target;
                if (target.tagName === 'IMG' && target.classList.contains('brave-zoomable')) {
                    e.preventDefault();
                    e.stopPropagation();
                    openLightbox(target.currentSrc || target.src, target.alt || '');
                }
            });
            root.dataset.braveLightboxBound = '1';
        }
    }

    function setPageReadyState() {
        try {
            document.body.classList.add('is-page-ready');
        } catch (e) {}
    }

    function init() {
        setPageReadyState();
        initTheme();
        bindBackLinks();
        buildArticleToc();
        enhanceCodeBlocks();
        enhanceArticleImages();
    }

    // Expose reinit function for pjax
    window.BraveTheme = window.BraveTheme || {};
    window.BraveTheme.reinitAfterPjax = function() {
        bindBackLinks();
        buildArticleToc();
        enhanceCodeBlocks();
        enhanceArticleImages();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    if (window.jQuery) {
        window.jQuery(document).on('pjax:complete', function() {
            if (window.BraveTheme && window.BraveTheme.reinitAfterPjax) {
                window.BraveTheme.reinitAfterPjax();
            }
        });
    }
})();
