<?php

/**
 * 勇敢爱 - Typecho情侣主题
 * @package     Brave
 * @author      Veen Zhao
 * @version     1.2
 * @link        https://blog.zwying.com
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('base/head.php');
$this->need('base/nav.php');
?>

<div class="list-content mx-auto mt-5">
    <div class="list-top">
        <h5 class="list-text">你要是愿意，我就永远爱你；你要是不愿意，我就永远相思。<br>我活在世上，无非想要明白些道理，遇见些有趣的事。倘能如我所愿，我的一生就算成功。</h5>
        <?php if ($this->have()) : ?>
            <?php while ($this->next()) : ?>
                <article style="padding: 20px;border-bottom: 1px solid rgba(0,123,255,.2);text-align: center;" class="post">
                    <h4 class="post-title" itemprop="name headline"><a class=" list-wbc" itemprop="url" href="<?php $this->permalink() ?>"><?php $this->title() ?></a></h4>
                    <time datetime="<?php $this->date('c'); ?>" itemprop="datePublished"><?php $this->date('Y-m-d'); ?></time>
                </article>
            <?php endwhile; ?>
        <?php else : ?>
            <article class="post">
                <h2 class="post-title"><?php _e('没有找到内容'); ?></h2>
            </article>
        <?php endif; ?>
        <?php $this->pageNav('&laquo; 上一页', '下一页 &raquo;'); ?>
    </div>
</div>

<?php $this->need('base/footer.php'); ?>
