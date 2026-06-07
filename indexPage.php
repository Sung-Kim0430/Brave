<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 主题首页
 * @package custom
 * Editor: Sung Kim
 * Creator: Veen Zhao
 * CreateTime: 2021/2/6 22:32
 * UpdateTime: 2026/1/1 00:54
 */
$siteUrl = App::siteUrl(true);
$blessingPageHref = App::safeCardLink(App::optionValue('blessingPageLink', ''), '#');
$blessingPageIcon = App::escapeUrlAttribute(App::optionValue('blessingPageIcon', ''), true, array('http', 'https'));
$timePageHref = App::safeCardLink(App::optionValue('timePageLink', ''), $siteUrl . 'blog/');
$timePageIcon = App::escapeUrlAttribute(App::optionValue('timePageIcon', ''), true, array('http', 'https'));
$loveListPageHref = App::safeCardLink(App::optionValue('loveListPageLink', ''), '#');
$loveListPageIcon = App::escapeUrlAttribute(App::optionValue('loveListPageIcon', ''), true, array('http', 'https'));
$this->need('base/head.php');
$this->need('base/nav.php');
?>
	<div class="container">
	    <blockquote class="blockquote text-center my-5 py-2">
	        <h5 class="card-title lover-card-title">已相伴</h5>
	        <h5 id="site_runtime"></h5>
	    </blockquote>
	    <?php
		    $introHomeHtml = App::pageIntroHtml(
		        App::optionFlag('introHomeEnable', false),
		        App::optionValue('introHomeText', '')
		    );
		    ?>
	    <?php if ($introHomeHtml !== '') : ?>
	        <h5 class="list-text page-quote"><?php echo $introHomeHtml; ?></h5>
	        <hr class="quote-divider">
	    <?php endif; ?>
		    <div class="row indexPlate">
		        <div class="col-md-4">
		            <a href="<?php echo $blessingPageHref; ?>" class="card ">
		                <div class="card-body">
		                    <div class="row align-items-center">
	                        <div class="col-auto">
	                            <div class="avatar avatar-md">
	                                <?php if ($blessingPageIcon !== '') : ?>
	                                    <img src="<?php echo $blessingPageIcon; ?>" alt="祝愿墙" class="avatar-img rounded-circle">
	                                <?php else : ?>
	                                    <div class="brave-card-icon-fallback" role="img" aria-label="祝愿墙">祝</div>
	                                <?php endif; ?>
	                            </div>
	                        </div>
	                        <div class="col">
	                            <p class="h5">祝愿墙</p>
	                            <p class="small text-muted mb-1">写下一句祝愿</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
		        <div class="col-md-4">
		            <a href="<?php echo $timePageHref; ?>" class="card">
	                <div class="card-body">
	                    <div class="row align-items-center">
	                        <div class="col-auto">
	                            <div class="avatar avatar-md">
	                                <?php if ($timePageIcon !== '') : ?>
	                                    <img src="<?php echo $timePageIcon; ?>" alt="点滴时光" class="avatar-img rounded-circle">
	                                <?php else : ?>
	                                    <div class="brave-card-icon-fallback" role="img" aria-label="点滴时光">点</div>
	                                <?php endif; ?>
	                            </div>
	                        </div>
	                        <div class="col">
	                            <p class="h5">点滴时光</p>
	                            <p class="small text-muted mb-1">把瞬间收进岁月</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
	        <div class="col-md-4">
		            <a href="<?php echo $loveListPageHref; ?>" class="card ">
	                <div class="card-body">
	                    <div class="row align-items-center">
	                        <div class="col-auto">
	                            <div class="avatar avatar-md">
	                                <?php if ($loveListPageIcon !== '') : ?>
	                                    <img src="<?php echo $loveListPageIcon; ?>" alt="恋爱清单" class="avatar-img rounded-circle">
	                                <?php else : ?>
	                                    <div class="brave-card-icon-fallback" role="img" aria-label="恋爱清单">恋</div>
	                                <?php endif; ?>
	                            </div>
	                        </div>
	                        <div class="col">
	                            <p class="h5">恋爱清单</p>
	                            <p class="small text-muted mb-1">把喜欢写成清单</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
<?php $this->need('base/footer.php'); ?>
