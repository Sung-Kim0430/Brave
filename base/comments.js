// Editor: Sung Kim
// 评论交互使用外部脚本：Typecho 1.3 的 header() 内联初始化只运行于首次整页加载，
// PJAX 不加载新的 head；同时，严格 CSP 默认禁止 onclick 等内联事件处理器。
(function () {
    'use strict';

    // 使用捕获阶段的委托事件，先于内核 reply()/cancelReply() 生成的 onclick 执行。
    // 继续使用内核输出链接，保留最大层数/是否允许评论及插件的链接生成逻辑；
    // 没有 JS 时仍可通过链接的 replyTo 参数完成回复。
    document.addEventListener('click', function (event) {
        if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        var target = event.target;
        var link = target && target.closest ? target.closest('a') : null;
        if (!link) return;
        var reply = link.closest('[data-brave-reply-to]');
        var cancel = link.closest('[data-brave-cancel-reply]');
        if (!reply && !cancel) return;

        var area = link.closest('.comment-area');
        var form = area && area.querySelector('form.comment-form');
        var response = form && form.closest('.respond');
        if (!response) return;

        var holderId = response.id + '-holder';
        var holder = document.getElementById(holderId);
        var parent = form.querySelector('input[name="parent"]');
        var cancelLink = response.querySelector('[data-brave-cancel-reply] a');

        if (reply) {
            var coid = reply.getAttribute('data-brave-reply-to');
            var comment = document.getElementById(reply.getAttribute('data-brave-comment-id'));
            // 从 DOM 读取的数据仍需校验；不得拼成 selector/HTML 或直接执行 onclick 内容。
            if (!/^[1-9][0-9]*$/.test(coid || '') || !comment || !area.contains(comment)) return;
            if (!holder) {
                holder = document.createElement('div');
                holder.id = holderId;
                response.parentNode.insertBefore(holder, response);
            }
            if (!parent) {
                parent = document.createElement('input');
                parent.type = 'hidden';
                parent.name = 'parent';
                form.appendChild(parent);
            }
            parent.value = coid;
            // 插到回复按钮之后，避免追加到所有子评论末尾；不复制表单或 CSRF 令牌。
            reply.parentNode.parentNode.insertBefore(response, reply.parentNode.nextSibling);
            if (cancelLink) cancelLink.style.display = '';
            var textarea = form.querySelector('textarea[name="text"]');
            if (textarea) textarea.focus();
        } else {
            if (parent) parent.value = '0';
            if (holder) {
                holder.parentNode.insertBefore(response, holder);
                holder.parentNode.removeChild(holder);
            }
            if (cancelLink) cancelLink.style.display = 'none';
        }
        event.preventDefault();
        event.stopPropagation();
    }, true);
})();
