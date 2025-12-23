<?php
use Typecho\Widget\Helper\Form\Element\Text;
use Typecho\Widget\Helper\Form\Element\Textarea;
use Typecho\Widget\Helper\Form\Element\Radio;
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
require_once("core/shortcodes.php");
require_once("core/App.php");
function themeInit($archive = null)
{
    $options = Helper::options();

    // 评论安全相关：提供可配置开关（默认偏安全，但尽量避免影响原有可用性）。
    $commentAntiSpam = true;
    if (isset($options->commentAntiSpam) && (string)$options->commentAntiSpam === '0') {
        $commentAntiSpam = false;
    }

    // Referer 检查在部分站点/代理/隐私策略下可能导致无法评论，因此默认关闭，提供手动开启。
    $commentCheckReferer = false;
    if (isset($options->commentCheckReferer) && (string)$options->commentCheckReferer === '1') {
        $commentCheckReferer = true;
    }

    $commentMaxNestingLevels = 10;
    if (isset($options->commentMaxNestingLevels) && is_numeric($options->commentMaxNestingLevels)) {
        $commentMaxNestingLevels = (int)$options->commentMaxNestingLevels;
    }
    if ($commentMaxNestingLevels < 1) {
        $commentMaxNestingLevels = 1;
    }
    if ($commentMaxNestingLevels > 50) {
        $commentMaxNestingLevels = 50;
    }

    Helper::options()->commentsAntiSpam = $commentAntiSpam;
    Helper::options()->commentsCheckReferer = $commentCheckReferer;
    Helper::options()->commentsRequireURL = false;
    Helper::options()->commentsMaxNestingLevels = (string)$commentMaxNestingLevels;
    Helper::options()->commentsPageDisplay = 'first'; //强制评论第一页
    Helper::options()->commentsOrder = 'DESC'; //将最新的评论展示在前

    $allowCommentImg = false;
    if (isset($options->commentAllowImg) && (string)$options->commentAllowImg === '1') {
        $allowCommentImg = true;
    }

    // 仅放行评论中必要的基础标签与属性（额外净化在模板输出阶段处理）。
    $allowedTags = '<a href="" title="" rel="" target=""> <code> <pre> <del> <strong> <em> <blockquote> <p> <br> <ul> <ol> <li> <hr>';
    if ($allowCommentImg) {
        $allowedTags .= ' <img src="" alt="" title="" class="">';
    }
    Helper::options()->commentsHTMLTagAllowed = $allowedTags;
    Helper::options()->commentsMarkdown = true;
}
/**
 * 主题后台设置
 */
function themeConfig($form)
{
    $navsay = new Text('navsay', NULL, NULL, _t('导航栏右侧文字设置'), _t('直接书写文字即可，不建议过长。也可使用相关随机api'));
    $form->addInput($navsay);
    $heroimg = new Text('heroimg', NULL, NULL, _t('头部大图设置'), _t('在这里输入图片链接'));
    $form->addInput($heroimg);
    $lovetime = new Text('lovetime', NULL, NULL, _t('恋爱起始日期设定'), _t('格式“YYYY/MM/DD”，例“2021/06/26”'));
    $form->addInput($lovetime);
    $boy = new Text('boy', NULL, NULL, _t('男生头像设置'), _t('在这里输入头像链接'));
    $form->addInput($boy);
    $girl = new Text('girl', NULL, NULL, _t('女生头像设置'), _t('在这里输入头像链接'));
    $form->addInput($girl);
    $boyname = new Text('boyname', NULL, NULL, _t('男生昵称设置'), _t('在这里输入昵称'));
    $form->addInput($boyname);
    $girlname = new Text('girlname', NULL, NULL, _t('女生昵称设置'), _t('在这里输入昵称'));
    $form->addInput($girlname);


    $loveListPageIcon = new Text('loveListPageIcon', NULL, NULL, _t('首页Love List页面图标'), _t('在此输入图标直链，将显示在首页Love List小版块中'));
    $form->addInput($loveListPageIcon);
    $loveListPageLink = new Text('loveListPageLink', NULL, NULL, _t('Love List页面链接'), _t('在此输入Love List页面链接'));
    $form->addInput($loveListPageLink);

    $blessingPageIcon = new Text('blessingPageIcon', NULL, NULL, _t('首页祝福板页面图标'), _t('在此输入图标直链，将显示在首页祝福板小版块中'));
    $form->addInput($blessingPageIcon);
    $blessingPageLink = new Text('blessingPageLink', NULL, NULL, _t('祝福页面链接'), _t('在此输入祝福页面链接'));
    $form->addInput($blessingPageLink);

    $timePageIcon = new Text('timePageIcon', NULL, NULL, _t('首页点点滴滴图标'), _t('在此输入图标直链，将显示在首页点点滴滴小版块中'));
    $form->addInput($timePageIcon);

    $commentAntiSpam = new Radio(
        'commentAntiSpam',
        array(
            '1' => _t('开启（推荐）'),
            '0' => _t('关闭（兼容）'),
        ),
        '1',
        _t('评论反垃圾'),
        _t('开启可降低垃圾评论风险；若与插件/站点策略冲突可关闭。')
    );
    $form->addInput($commentAntiSpam);

    $commentCheckReferer = new Radio(
        'commentCheckReferer',
        array(
            '0' => _t('关闭（默认）'),
            '1' => _t('开启（更安全）'),
        ),
        '0',
        _t('评论 Referer 检查'),
        _t('开启可减少跨站投递评论；但在部分 HTTPS/代理/隐私策略下可能导致无法评论。')
    );
    $form->addInput($commentCheckReferer);

    $commentMaxNestingLevels = new Text(
        'commentMaxNestingLevels',
        NULL,
        '10',
        _t('评论最大嵌套层数'),
        _t('建议 3~10；过大可能被滥用导致性能问题（已在代码中限制最大为 50）。')
    );
    $form->addInput($commentMaxNestingLevels);

    $commentAllowImg = new Radio(
        'commentAllowImg',
        array(
            '0' => _t('不允许（推荐）'),
            '1' => _t('允许（兼容旧版本）'),
        ),
        '0',
        _t('评论允许图片'),
        _t('开启后评论内容中的 <img> 会被保留；关闭可减少追踪像素与外链风险。')
    );
    $form->addInput($commentAllowImg);

    $loveListTitleAllowHtml = new Radio(
        'loveListTitleAllowHtml',
        array(
            '0' => _t('仅纯文本（推荐）'),
            '1' => _t('允许少量 HTML（兼容）'),
        ),
        '0',
        _t('Love List 标题允许 HTML'),
        _t('开启后 [item] 标题允许 <del><code><strong><em><br> 等少量标签；关闭则全部按纯文本输出。')
    );
    $form->addInput($loveListTitleAllowHtml);

    $assetsSource = new Radio(
        'assetsSource',
        array(
            'local' => _t('本地（推荐）'),
            'cdn' => _t('CDN（兼容）'),
        ),
        'local',
        _t('静态资源加载方式'),
        _t('本地模式将从主题目录加载 jQuery/Bootstrap/pjax/nprogress，降低供应链风险；CDN 模式保持原有网络加载方式。')
    );
    $form->addInput($assetsSource);

    $fontSource = new Radio(
        'fontSource',
        array(
            'local' => _t('本地/系统字体（推荐）'),
            'remote' => _t('在线字体（兼容）'),
        ),
        'local',
        _t('字体加载方式'),
        _t('本地模式不引入第三方字体链接（若系统未安装 Inter 会自动回退）；在线模式会从 https://gfonts.ctfile.com 加载 Inter 字体，存在供应链/可用性风险。')
    );
    $form->addInput($fontSource);

    $cdnEnableSRI = new Radio(
        'cdnEnableSRI',
        array(
            '1' => _t('开启（默认）'),
            '0' => _t('关闭'),
        ),
        '1',
        _t('CDN 模式启用 SRI'),
        _t('仅在静态资源加载方式为「CDN」时生效；开启后会为外部脚本/样式添加 integrity 校验，降低 CDN 被篡改风险。')
    );
    $form->addInput($cdnEnableSRI);

    $cdnEnableCSP = new Radio(
        'cdnEnableCSP',
        array(
            '1' => _t('开启（默认）'),
            '0' => _t('关闭'),
        ),
        '1',
        _t('CDN 模式启用 CSP'),
        _t('仅在静态资源加载方式为「CDN」时生效；开启后会在 <head> 输出 CSP（Content-Security-Policy）以降低 XSS/供应链风险。若使用了额外外链脚本，可通过自定义策略放行或关闭。')
    );
    $form->addInput($cdnEnableCSP);

    $cspPolicy = new Textarea(
        'cspPolicy',
        NULL,
        NULL,
        _t('自定义 CSP 策略（可选）'),
        _t('留空则使用主题内置默认策略；仅在「CDN 模式 + 启用 CSP」时生效。示例：default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://cdn.staticfile.org;')
    );
    $form->addInput($cspPolicy);

    $enableCustomCode = new Radio(
        'enableCustomCode',
        array(
            '1' => _t('开启（兼容）'),
            '0' => _t('关闭（更安全）'),
        ),
        '1',
        _t('输出自定义 HTML/CSS/JS'),
        _t('控制是否在前台输出「头部自定义 / Css自定义 / 底部自定义 / pjax回调」等高权限字段；关闭可降低被误用或后台被劫持后的风险。')
    );
    $form->addInput($enableCustomCode);

    $CustomContenth = new Textarea('头部自定义', NULL, NULL, _t('头部自定义内容'), _t('位于头部，head内，适合放置一些链接引用或自定义内容'));
    $form->addInput($CustomContenth);
    $stylemyself = new Textarea('Css自定义', NULL, NULL, _t('自定义Css样式'), _t('已包含&lt;style&gt;标签，请直接书写样式'));
    $form->addInput($stylemyself);
    $CustomContent = new Textarea('底部自定义', NULL, NULL, _t('底部自定义内容'), _t('位于底部，footer之后body之前，适合放置一些js或自定义内容，如网站统计代码等，（注意：如果您开启了Pjax，暂时只支持百度统计、Google统计，其余统计代码可能会不准确；没开请忽略）'));
    $form->addInput($CustomContent);
    $pjaxContent = new Textarea('pjax回调', NULL, NULL, _t('Pjax回调函数'), _t('在这里可以书写回调函数内容。如果您不知道这项如何使用请忽略'));
    $form->addInput($pjaxContent);

}




