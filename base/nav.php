<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
$siteUrl = isset(Helper::options()->siteUrl) ? (string)Helper::options()->siteUrl : '/';
$siteUrl = App::escapeUrlAttribute($siteUrl, true, array('http', 'https'));
if ($siteUrl === '') {
    $siteUrl = '/';
}

$heroStyle = App::buildBackgroundImageStyle(isset($this->options->heroimg) ? (string)$this->options->heroimg : '');
$boyAvatar = App::escapeUrlAttribute(isset($this->options->boy) ? (string)$this->options->boy : '', true, array('http', 'https'));
$girlAvatar = App::escapeUrlAttribute(isset($this->options->girl) ? (string)$this->options->girl : '', true, array('http', 'https'));
$boyName = App::escapeHtml(isset($this->options->boyname) ? (string)$this->options->boyname : '');
$girlName = App::escapeHtml(isset($this->options->girlname) ? (string)$this->options->girlname : '');
$navSay = App::escapeHtml(isset($this->options->navsay) ? (string)$this->options->navsay : '');
$enableDarkMode = isset(Helper::options()->enableDarkMode) && (string)Helper::options()->enableDarkMode === '1';
?>
<div class="container-fluid position-relative">
    <nav class="navbar navbar-expand-lg navbar-dark  text-white bg-transparent">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $siteUrl; ?>"><?php $this->options->title() ?></a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarText"
                    aria-controls="navbarText" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarText">
                <ul class="navbar-nav mr-auto">
                </ul>
                <span class="navbar-text d-inline-flex align-items-center">
                    <?php echo $navSay; ?>
                </span>
                <?php if ($enableDarkMode) : ?>
                    <button type="button" class="brave-theme-toggle ml-2" data-theme-toggle aria-label="切换暗色模式" title="切换暗色模式">
                        <svg class="brave-theme-toggle__icon brave-theme-toggle__icon--sun" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="4"></circle>
                            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path>
                        </svg>
                        <svg class="brave-theme-toggle__icon brave-theme-toggle__icon--moon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"></path>
                        </svg>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <section class="lover-background" <?php if ($heroStyle !== '') : ?>style="<?php echo htmlspecialchars($heroStyle, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>></section>
    <section class="container lover-container d-flex flex-column align-content-center justify-content-center">
        <div class="row align-items-center pb-5 lover">
            <div class="col">
                <div class="d-flex flex-column">
                    <img class="mx-auto avatar-img rounded-circle" src="<?php echo $boyAvatar; ?>"
                         alt="<?php echo $boyName; ?>">
                    <h4 class="mx-auto text-white pt-2"><?php echo $boyName; ?></h4>
                </div>
            </div>
            <div class="col">
                <div class="d-flex justify-content-center">
                    <div class="heart"></div>
                </div>
            </div>
            <div class="col">
                <div class="d-flex flex-column">
                    <img class="mx-auto avatar-img rounded-circle" src="<?php echo $girlAvatar; ?>"
                         alt="<?php echo $girlName; ?>">
                    <h4 class="mx-auto text-white pt-2"><?php echo $girlName; ?></h4>
                </div>
            </div>
        </div>
    </section>
    <section class="main-hero-waves-area waves-area">
        <svg class="waves-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
             viewBox="0 24 150 28" preserveAspectRatio="none" shape-rendering="auto">
            <defs>
                <path id="gentle-wave"
                      d="M -160 44 c 30 0 58 -18 88 -18 s 58 18 88 18 s 58 -18 88 -18 s 58 18 88 18 v 44 h -352 Z"></path>
            </defs>
            <g class="parallax">
                <use xlink:href="#gentle-wave" x="48" y="0"></use>
                <use xlink:href="#gentle-wave" x="48" y="3"></use>
                <use xlink:href="#gentle-wave" x="48" y="5"></use>
                <use xlink:href="#gentle-wave" x="48" y="7"></use>
            </g>
        </svg>
    </section>
</div>
<div id="pjax-container">
