<?php
/**
 * Love List
 * @package custom
 *
 * Author: Veen Zhao
 * CreateTime: 2020/9/4 22:37
 * Love list page
 */

$this->need('base/head.php');
$this->need('base/nav.php');?>
<div class="container text-center my-5">
<h5 class="list-text">你要是愿意，我就永远爱你；你要是不愿意，我就永远相思。<br>我活在世上，无非想要明白些道理，遇见些有趣的事。倘能如我所愿，我的一生就算成功。<br>把这些有趣的事写成恋爱清单，完成一项，就点亮一枚小小的勾。</h5>
<?php echo App::parseShortCode($this->content) ?>
</div>
<?php $this->need('base/footer.php'); ?>
