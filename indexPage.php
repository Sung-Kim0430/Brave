<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 主题首页
 * @package custom
 * Author: Veen Zhao
 * CreateTime: 2021/2/6 22:32
 */
$this->need('base/head.php');
$this->need('base/nav.php');
?>
<div class="container">
    <blockquote class="blockquote text-center my-5 py-2">
        <h5 class="card-title lover-card-title">已相伴</h5>
        <h5 id="site_runtime"></h5>
    </blockquote>
    <div class="row indexPlate">
        <div class="col-md-4">
            <a href="<?php $this->options->blessingPageLink() ?>" class="card ">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="avatar avatar-md">
                                <img src="<?php $this->options->blessingPageIcon() ?>" alt="..." class="avatar-img rounded-circle">
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
            <a href="/index.php/blog/" class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="avatar avatar-md">
                                <img src="<?php $this->options->timePageIcon() ?>" alt="..." class="avatar-img rounded-circle">
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
            <a href="<?php $this->options->loveListPageLink() ?>" class="card ">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="avatar avatar-md">
                                <img src="<?php $this->options->loveListPageIcon() ?>" alt="..." class="avatar-img rounded-circle">
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
