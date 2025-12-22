<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('base/head.php');
$this->need('base/nav.php');
?>

	<div class="list-content mx-auto mt-5">
	    <div id="article" class="list-top">
	        <h5 class="list-text">「<?php $this->title() ?>」</h5>
	        <time datetime="<?php $this->date('c'); ?>" itemprop="datePublished" class="d-block text-center text-muted small mb-4"><?php $this->date('Y-m-d'); ?></time>
	        <article>
	            <?php $this->content(); ?>
	        </article>
	    </div>
	</div>

<?php $this->need('base/footer.php'); ?>
