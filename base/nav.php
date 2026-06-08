<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
$siteUrl = App::siteUrl(false);

$heroStyle = App::buildBackgroundImageStyle(App::optionValue('heroimg', ''));
$boyAvatarRaw = trim(App::optionValue('boy', ''));
$girlAvatarRaw = trim(App::optionValue('girl', ''));
$boyAvatar = App::escapeUrlAttribute($boyAvatarRaw, true, array('http', 'https'));
$girlAvatar = App::escapeUrlAttribute($girlAvatarRaw, true, array('http', 'https'));

$boyNameRaw = trim(App::optionValue('boyname', ''));
$girlNameRaw = trim(App::optionValue('girlname', ''));
$boyName = App::escapeHtml($boyNameRaw);
$girlName = App::escapeHtml($girlNameRaw);

$boyInitial = ($boyNameRaw === '') ? '他' : (function_exists('mb_substr') ? mb_substr($boyNameRaw, 0, 1, 'UTF-8') : '?');
$girlInitial = ($girlNameRaw === '') ? '她' : (function_exists('mb_substr') ? mb_substr($girlNameRaw, 0, 1, 'UTF-8') : '?');
$boyInitial = App::escapeHtml($boyInitial);
$girlInitial = App::escapeHtml($girlInitial);

$navSay = App::escapeHtml(App::optionValue('navsay', ''));
$siteTitle = App::escapeHtml(App::optionValue('title', ''));
$enableDarkMode = App::optionFlag('enableDarkMode', false);
?>
<div class="container-fluid position-relative">
    <nav class="navbar navbar-expand-lg navbar-dark  text-white bg-transparent">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $siteUrl; ?>"><?php echo $siteTitle; ?></a>
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
                    <button type="button"
                            class="brave-theme-toggle ml-2"
                            data-theme-toggle
                            role="switch"
                            aria-checked="false"
                            aria-label="暗色模式"
                            title="切换暗色模式">
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
	                    <?php if ($boyAvatar !== '') : ?>
	                        <img class="mx-auto avatar-img rounded-circle" src="<?php echo $boyAvatar; ?>"
	                             alt="<?php echo ($boyName !== '') ? $boyName : '头像'; ?>">
	                    <?php else : ?>
	                        <div class="mx-auto avatar-img rounded-circle brave-avatar-fallback" role="img" aria-label="<?php echo ($boyName !== '') ? $boyName : '头像'; ?>"><?php echo $boyInitial; ?></div>
	                    <?php endif; ?>
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
	                    <?php if ($girlAvatar !== '') : ?>
	                        <img class="mx-auto avatar-img rounded-circle" src="<?php echo $girlAvatar; ?>"
	                             alt="<?php echo ($girlName !== '') ? $girlName : '头像'; ?>">
	                    <?php else : ?>
	                        <div class="mx-auto avatar-img rounded-circle brave-avatar-fallback" role="img" aria-label="<?php echo ($girlName !== '') ? $girlName : '头像'; ?>"><?php echo $girlInitial; ?></div>
	                    <?php endif; ?>
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
