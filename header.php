<!DOCTYPE html>
<?php
	$htmlclasses = "";
	if (get_option('argon_page_layout', 'double-reverse') == "single"){
		$htmlclasses .= "single-column ";
	}
	if (get_option('argon_page_layout', 'double-reverse') == "triple"){
		$htmlclasses .= "triple-column ";
	}
	if (get_option('argon_page_layout', 'double-reverse') == "double-reverse"){
		$htmlclasses .= "double-column-reverse ";
	}
	if (get_option('argon_enable_immersion_color') == "true"){
		$htmlclasses .= "immersion-color ";
	}
	if (get_option('argon_enable_amoled_dark') == "true"){
		$htmlclasses .= "amoled-dark ";
	}
	if (get_option('argon_card_shadow') == 'big'){
		$htmlclasses .= 'use-big-shadow ';
	}
	if (get_option('argon_font') == 'serif'){
		$htmlclasses .= 'use-serif ';
	}
	if (get_option('argon_disable_codeblock_style') == 'true'){
		$htmlclasses .= 'disable-codeblock-style ';
	}
	$toolbar_behavior = get_option('argon_enable_headroom');
	if ($toolbar_behavior === 'true' || $toolbar_behavior === 'false') {
		$htmlclasses .= 'toolbar-sticky ';
	} elseif ($toolbar_behavior === 'absolute') {
		$htmlclasses .= 'navbar-absolute ';
	}
	$banner_size = get_option('argon_banner_size', 'full');
	if ($banner_size != 'full'){
		if ($banner_size == 'mini'){
			$htmlclasses .= 'banner-mini ';
		}else if ($banner_size == 'hide'){
			$htmlclasses .= 'no-banner ';
		}else if ($banner_size == 'fullscreen'){
			$htmlclasses .= 'banner-as-cover ';
		}
	}
	if (get_option('argon_toolbar_blur', 'true') == 'true'){
		$htmlclasses .= 'toolbar-blur ';
	}
	$htmlclasses .= get_option('argon_article_header_style', 'article-header-style-default') . ' ';
	if(strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'Safari') !== false && strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'Chrome') === false){
		$htmlclasses .= ' using-safari';
	}
?>
<html <?php language_attributes(); ?> class="no-js <?php echo esc_attr(trim($htmlclasses)); ?>" data-liquid-glass-mode="lite" data-liquid-palette="<?php echo get_option('liquid_glass_default_palette') === 'pink' ? 'pink' : 'warm'; ?>">
<?php
	$themecolor = liquid_glass_theme_color();
	$themecolor_origin = $themecolor;
	if (isset($_COOKIE["argon_custom_theme_color"])){
		if (checkHEX($_COOKIE["argon_custom_theme_color"]) && get_option('argon_show_customize_theme_color_picker') != 'false'){
			$themecolor = $_COOKIE["argon_custom_theme_color"];
		}
	}
	if (hex2gray($themecolor) < 50){
		echo '<script>document.getElementsByTagName("html")[0].classList.add("themecolor-toodark");</script>';
	}
?>
<?php
	$cardradius_origin = liquid_glass_card_radius();
	$cardradius = is_numeric($_COOKIE['argon_card_radius'] ?? null)
		? min(30, max(0, (float) $_COOKIE['argon_card_radius']))
		: $cardradius_origin;
?>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script>
		try {
			const palette = localStorage.getItem('liquid-glass-palette');
			if (palette === 'pink' || palette === 'warm') {
				document.documentElement.dataset.liquidPalette = palette;
			}
		} catch {}
	</script>
	<meta property="og:site_name" content="<?php echo esc_attr(get_bloginfo('name'));?>">
	<meta property="og:title" content="<?php echo esc_attr(wp_get_document_title());?>">
	<meta property="og:type" content="article">
	<meta property="og:url" content="<?php echo esc_url(home_url(add_query_arg(array(), $wp->request)));?>">
	<?php
		$seo_description = get_seo_description();
		if ($seo_description != ''){ ?>
			<meta name="description" content="<?php echo esc_attr($seo_description)?>">
			<meta property="og:description" content="<?php echo esc_attr($seo_description)?>">
	<?php } ?>

	<?php
		$seo_keywords = get_seo_keywords();
		if ($seo_keywords != ''){ ?>
			<meta name="keywords" content="<?php echo esc_attr($seo_keywords);?>">
	<?php } ?>

	<?php
		if (is_single() || is_page()){
			$og_image = get_og_image();
			if ($og_image != ''){ ?>
				<meta property="og:image" content="<?php echo esc_url($og_image)?>" />
	<?php 	}
		} ?>

	<meta name="theme-color" content="<?php echo $themecolor; ?>">
	<meta name="theme-color-rgb" content="<?php echo hex2str($themecolor); ?>">
	<meta name="theme-color-origin" content="<?php echo $themecolor_origin; ?>">
	<meta name="argon-enable-custom-theme-color" content="<?php echo (get_option('argon_show_customize_theme_color_picker') != 'false' ? 'true' : 'false'); ?>">


	<meta name="theme-card-radius" content="<?php echo $cardradius; ?>">
	<meta name="theme-card-radius-origin" content="<?php echo $cardradius_origin; ?>">

	<meta name="theme-version" content="<?php echo $GLOBALS['theme_version']; ?>">

	<link rel="profile" href="http://gmpg.org/xfn/11">
	<?php if ( is_singular() && pings_open( get_queried_object() ) ) : ?>
	<link rel="pingback" href="<?php echo esc_url( get_bloginfo( 'pingback_url' ) ); ?>">
	<?php endif; ?>
	<?php wp_head(); ?>
	<?php
		$GLOBALS['wp_path'] = trailingslashit(get_option('argon_wp_path') ?: home_url('/'));
		$zoomify = get_option('argon_enable_zoomify') === 'true';
		wp_print_inline_script_tag(
			'document.documentElement.classList.remove("no-js"); var argonConfig = ' . wp_json_encode([
				'wp_path' => $GLOBALS['wp_path'],
				'ajax_url' => admin_url('admin-ajax.php'),
				'home_url' => home_url('/'),
				'language' => argon_get_locate(),
				'dateFormat' => get_option('argon_dateformat', 'YMD'),
				'zoomify' => $zoomify ? [
					'duration' => (float) get_option('argon_zoomify_duration', 200),
					'easing' => get_option('argon_zoomify_easing', 'cubic-bezier(0.4,0,0,1)'),
					'scale' => (float) get_option('argon_zoomify_scale', 0.9),
				] : false,
				'pangu' => get_option('argon_enable_pangu', 'false'),
				'lazyload' => ['threshold' => 500],
				'fold_long_comments' => get_option('argon_fold_long_comments') === 'true',
				'fold_long_shuoshuo' => get_option('argon_fold_long_shuoshuo') === 'true',
				'disable_pjax' => get_option('argon_pjax_disabled') === 'true',
				'pjax_animation_durtion' => get_option('argon_disable_pjax_animation') === 'true' ? 0 : 600,
				'headroom' => get_option('argon_enable_headroom', 'false'),

				'code_highlight' => [
					'enable' => get_option('argon_enable_code_highlight') === 'true',
					'hide_linenumber' => get_option('argon_code_highlight_hide_linenumber') === 'true',
					'transparent_linenumber' => get_option('argon_code_highlight_transparent_linenumber') === 'true',
					'break_line' => get_option('argon_code_highlight_break_line') === 'true',
				],
			], JSON_HEX_TAG) . ';'
		);
	?>
	<script>
		var darkmodeAutoSwitch = "<?php echo (esc_js(get_option('argon_darkmode_autoswitch', 'system')));?>";
		function setDarkmode(enable){
			if (enable == true){
				$("html").addClass("darkmode");
			}else{
				$("html").removeClass("darkmode");
			}
			$(window).trigger("scroll");
		}
		function toggleDarkmode(){
			if ($("html").hasClass("darkmode")){
				setDarkmode(false);
				sessionStorage.setItem("Argon_Enable_Dark_Mode", "false");
			}else{
				setDarkmode(true);
				sessionStorage.setItem("Argon_Enable_Dark_Mode", "true");
			}
		}
		if (sessionStorage.getItem("Argon_Enable_Dark_Mode") == "true"){
			setDarkmode(true);
		}
		function toggleDarkmodeByPrefersColorScheme(media){
			if (sessionStorage.getItem('Argon_Enable_Dark_Mode') == "false" || sessionStorage.getItem('Argon_Enable_Dark_Mode') == "true"){
				return;
			}
			if (media.matches){
				setDarkmode(true);
			}else{
				setDarkmode(false);
			}
		}
		function toggleDarkmodeByTime(){
			if (sessionStorage.getItem('Argon_Enable_Dark_Mode') == "false" || sessionStorage.getItem('Argon_Enable_Dark_Mode') == "true"){
				return;
			}
			let hour = new Date().getHours();
			if (<?php echo apply_filters("argon_darkmode_time_check", "hour < 7 || hour >= 22")?>){
				setDarkmode(true);
			}else{
				setDarkmode(false);
			}
		}
		if (darkmodeAutoSwitch == 'system'){
			var darkmodeMediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
			darkmodeMediaQuery.addEventListener('change', toggleDarkmodeByPrefersColorScheme);
			toggleDarkmodeByPrefersColorScheme(darkmodeMediaQuery);
		}
		if (darkmodeAutoSwitch == 'time'){
			toggleDarkmodeByTime();
		}
		if (darkmodeAutoSwitch == 'alwayson' && sessionStorage.getItem('Argon_Enable_Dark_Mode') === null){
			setDarkmode(true);
		}

		function toggleAmoledDarkMode(){
			$("html").toggleClass("amoled-dark");
			if ($("html").hasClass("amoled-dark")){
				localStorage.setItem("Argon_Enable_Amoled_Dark_Mode", "true");
			}else{
				localStorage.setItem("Argon_Enable_Amoled_Dark_Mode", "false");
			}
		}
		if (localStorage.getItem("Argon_Enable_Amoled_Dark_Mode") == "true"){
			$("html").addClass("amoled-dark");
		}else if (localStorage.getItem("Argon_Enable_Amoled_Dark_Mode") == "false"){
			$("html").removeClass("amoled-dark");
		}
	</script>
	<script>
		if (navigator.userAgent.indexOf("Safari") !== -1 && navigator.userAgent.indexOf("Chrome") === -1){
			$("html").addClass("using-safari");
		}
	</script>

	<?php if (get_option('argon_enable_smoothscroll_type', 'disabled') == '2') { /*平滑滚动*/?>
		<script src="<?php echo $GLOBALS['assets_path']; ?>/assets/vendor/smoothscroll/smoothscroll2.js"></script>
	<?php }else if (get_option('argon_enable_smoothscroll_type') == '3'){?>
		<script src="<?php echo $GLOBALS['assets_path']; ?>/assets/vendor/smoothscroll/smoothscroll3.min.js"></script>
	<?php }else if (get_option('argon_enable_smoothscroll_type') == '1_pulse'){?>
		<script src="<?php echo $GLOBALS['assets_path']; ?>/assets/vendor/smoothscroll/smoothscroll1_pulse.js"></script>
	<?php }else if (get_option('argon_enable_smoothscroll_type', 'disabled') != 'disabled'){?>
		<script src="<?php echo $GLOBALS['assets_path']; ?>/assets/vendor/smoothscroll/smoothscroll1.js"></script>
	<?php }?>
</head>

<?php echo get_option('argon_custom_html_head'); ?>

<style id="themecolor_css">
	<?php
		$themecolor_rgbstr = hex2str($themecolor);
		$RGB = hexstr2rgb($themecolor);
		$HSL = rgb2hsl($RGB['R'], $RGB['G'], $RGB['B']);
	?>
	:root{
		--themecolor: <?php echo $themecolor; ?>;
		--themecolor-R: <?php echo $RGB['R']; ?>;
		--themecolor-G: <?php echo $RGB['G']; ?>;
		--themecolor-B: <?php echo $RGB['B']; ?>;
		--themecolor-H: <?php echo $HSL['H']; ?>;
		--themecolor-S: <?php echo $HSL['S']; ?>;
		--themecolor-L: <?php echo $HSL['L']; ?>;
	}
</style>
<style id="theme_cardradius_css">
	:root{
		--card-radius: <?php echo $cardradius; ?>px;
	}
</style>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="liquid-skip" href="#main" no-pjax><?php esc_html_e('Skip to content', 'liquid-glass'); ?></a>
<?php get_template_part('template-parts/backdrop'); ?>
<?php get_template_part('template-parts/toolbar'); ?>
<div id="liquid-banner-slot">
	<?php get_template_part('template-parts/banner', null, ['size' => $banner_size]); ?>
</div>

<div id="float_action_buttons" class="float-action-buttons fabtns-unloaded">
	<button id="fabtn_toggle_sides" class="btn btn-icon btn-neutral fabtn shadow-sm" type="button" aria-hidden="true" tooltip-move-to-left="<?php _e('移至左侧', 'argon'); ?>" tooltip-move-to-right="<?php _e('移至右侧', 'argon'); ?>">
		<span class="btn-inner--icon fabtn-show-on-right"><i class="fa fa-caret-left"></i></span>
		<span class="btn-inner--icon fabtn-show-on-left"><i class="fa fa-caret-right"></i></span>
	</button>
	<button id="fabtn_back_to_top" class="btn btn-icon btn-neutral fabtn shadow-sm" type="button" aria-label="Back To Top" tooltip="<?php _e('回到顶部', 'argon'); ?>">
		<span class="btn-inner--icon"><i class="fa fa-angle-up"></i></span>
	</button>
	<button id="fabtn_go_to_comment" class="btn btn-icon btn-neutral fabtn shadow-sm d-none" type="button" <?php if (get_option('argon_fab_show_gotocomment_button') != 'true') echo " style='display: none;'";?> aria-label="Comment" tooltip="<?php _e('评论', 'argon'); ?>">
		<span class="btn-inner--icon"><i class="fa fa-comment-o"></i></span>
	</button>
	<button id="fabtn_toggle_darkmode" class="btn btn-icon btn-neutral fabtn shadow-sm" type="button" <?php if (get_option('argon_fab_show_darkmode_button') != 'true') echo " style='display: none;'";?> aria-label="Toggle Darkmode" tooltip-darkmode="<?php _e('夜间模式', 'argon'); ?>" tooltip-blackmode="<?php _e('暗黑模式', 'argon'); ?>" tooltip-lightmode="<?php _e('日间模式', 'argon'); ?>">
		<span class="btn-inner--icon"><i class="fa fa-moon-o"></i><i class='fa fa-lightbulb-o'></i></span>
	</button>
	<button id="fabtn_toggle_blog_settings_popup" aria-controls="fabtn_blog_settings_popup" aria-expanded="false" class="btn btn-icon btn-neutral fabtn shadow-sm" type="button" <?php if (get_option('argon_fab_show_settings_button') == 'false') echo " style='display: none;'";?> aria-label="<?php esc_attr_e('Appearance settings', 'liquid-glass'); ?>" tooltip="<?php _e('设置', 'argon'); ?>">
		<span class="btn-inner--icon"><i class="fa fa-cog"></i></span>
	</button>
	<div id="fabtn_blog_settings_popup" class="card shadow-sm" aria-hidden="true" inert>
		<button id="close_blog_settings" type="button" aria-label="<?php esc_attr_e('Close', 'liquid-glass'); ?>"><i class="fa fa-close" aria-hidden="true"></i></button>
		<div class="blog-setting-item mt-3">
			<div style="transform: translateY(-4px);"><div id="blog_setting_toggle_darkmode_and_amoledarkmode" tooltip-switch-to-darkmode="<?php _e('切换到夜间模式', 'argon'); ?>" tooltip-switch-to-blackmode="<?php _e('切换到暗黑模式', 'argon'); ?>"><span><?php _e('夜间模式', 'argon');?></span><span><?php _e('暗黑模式', 'argon');?></span></div></div>
			<div style="flex: 1;"></div>
			<label id="blog_setting_darkmode_switch" class="custom-toggle">
				<span class="custom-toggle-slider rounded-circle"></span>
			</label>
		</div>
		<div class="blog-setting-item mt-3">
			<div style="flex: 1;"><?php _e('字体', 'argon');?></div>
			<div>
				<button id="blog_setting_font_sans_serif" type="button" class="blog-setting-font btn btn-outline-primary blog-setting-selector-left">Sans Serif</button><button id="blog_setting_font_serif" type="button" class="blog-setting-font btn btn-outline-primary blog-setting-selector-right">Serif</button>
			</div>
		</div>
		<div class="blog-setting-item mt-3">
			<div style="flex: 1;"><?php _e('阴影', 'argon');?></div>
			<div>
				<button id="blog_setting_shadow_small" type="button" class="blog-setting-shadow btn btn-outline-primary blog-setting-selector-left"><?php _e('浅阴影', 'argon');?></button><button id="blog_setting_shadow_big" type="button" class="blog-setting-shadow btn btn-outline-primary blog-setting-selector-right"><?php _e('深阴影', 'argon');?></button>
			</div>
		</div>
		<div class="blog-setting-item mt-3 mb-3">
			<div style="flex: 1;"><?php _e('滤镜', 'argon');?></div>
			<div id="blog_setting_filters" class="ml-3">
				<button id="blog_setting_filter_off" type="button" class="blog-setting-filter-btn ml-0" filter-name="off"><?php _e('关闭', 'argon');?></button>
				<button id="blog_setting_filter_sunset" type="button" class="blog-setting-filter-btn" filter-name="sunset"><?php _e('日落', 'argon');?></button>
				<button id="blog_setting_filter_darkness" type="button" class="blog-setting-filter-btn" filter-name="darkness"><?php _e('暗化', 'argon');?></button>
				<button id="blog_setting_filter_grayscale" type="button" class="blog-setting-filter-btn" filter-name="grayscale"><?php _e('灰度', 'argon');?></button>
			</div>
		</div>
		<div class="blog-setting-item mb-3">
			<div id="blog_setting_card_radius_to_default" style="cursor: pointer;" tooltip="<?php _e('恢复默认', 'argon'); ?>"><?php _e('圆角', 'argon');?></div>
			<div style="flex: 1;margin-left: 20px;margin-right: 8px;transform: translateY(2px);">
				<div id="blog_setting_card_radius"></div>
			</div>
		</div>
		<?php if (get_option('argon_show_customize_theme_color_picker') != 'false') {?>
			<div class="blog-setting-item mt-1 mb-3">
				<div style="flex: 1;"><?php _e('主题色', 'argon');?></div>
				<div id="theme-color-picker" class="ml-3"></div>
			</div>
		<?php }?>
	</div>
	<button id="fabtn_reading_progress" class="btn btn-icon btn-neutral fabtn shadow-sm" type="button" aria-hidden="true" tooltip="<?php _e('阅读进度', 'argon'); ?>">
		<div id="fabtn_reading_progress_bar" style="width: 0%;"></div>
		<span id="fabtn_reading_progress_details">0%</span>
	</button>
</div>

<div id="content" class="site-content">
