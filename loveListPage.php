<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Love List
 * @package custom
 *
 * Editor: Sung Kim
 * Creator: Veen Zhao
 * CreateTime: 2020/9/4 22:37
 * UpdateTime: 2026/6/7 23:05
 * Love list page
 */

$siteUrl = App::siteUrl(true);

$this->need('base/head.php');
$this->need('base/nav.php');?>
<div class="container text-center my-5">
	<div class="brave-page-actions">
		<a class="brave-back-link" href="<?php echo $siteUrl; ?>" data-brave-back>
			<span class="brave-back-link__icon" aria-hidden="true">←</span>
			<span class="brave-back-link__text">返回</span>
		</a>
	</div>
	<?php
		$introLoveListHtml = App::pageIntroHtml(
			App::optionFlag('introLoveListEnable', true),
			App::optionValue('introLoveListText', ''),
			"你要是愿意，我就永远爱你；你要是不愿意，我就永远相思。\n我活在世上，无非想要明白些道理，遇见些有趣的事。倘能如我所愿，我的一生就算成功。\n把这些有趣的事写成恋爱清单，完成一项，就点亮一枚小小的勾。"
		);
	?>
	<?php if ($introLoveListHtml !== '') : ?>
		<h5 class="list-text page-quote"><?php echo $introLoveListHtml; ?></h5>
		<hr class="quote-divider">
	<?php endif; ?>
		<?php
		ob_start();
		$this->content();
		$contentHtml = ob_get_clean();
		echo App::parseShortCode($contentHtml);
	?>
</div>
<?php $this->need('base/footer.php'); ?>
