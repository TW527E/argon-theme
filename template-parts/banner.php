<?php
$size = $args['size'];
if ($size === 'hide') {
	return;
}

$image = '';
if (get_option('argon_page_background_banner_style') !== 'transparent' && get_option('argon_banner_background_url')) {
	$image = get_banner_background_url();
}
if ((is_single() || is_page()) && get_option('argon_show_thumbnail_in_banner_in_content_page') === 'true' && argon_has_post_thumbnail()) {
	$image = argon_get_post_thumbnail() ?: $image;
}
$style = liquid_glass_background_style($image);

if (!is_front_page() && !is_home()) {
	if ($style) {
		printf('<div class="liquid-inner-banner liquid-frame" aria-hidden="true" style="%s"></div>', esc_attr($style));
	}
	return;
}

$title = apply_filters('argon_banner_title_html', get_option('argon_banner_title') ?: get_bloginfo('name'));
$subtitle = get_option('argon_banner_subtitle', get_bloginfo('description'));
$typing = get_option('argon_enable_banner_title_typing_effect') === 'true';
$heading = is_home() ? 'h1' : 'p';
?>
<section id="banner" class="liquid-banner liquid-frame<?php echo $size === 'fullscreen' ? ' liquid-cover' : ''; ?><?php echo $style ? ' has-banner-image' : ''; ?>" style="<?php echo esc_attr($style); ?>">
	<div id="banner_container" class="liquid-hero liquid-surface">
		<div class="liquid-hero-heading<?php echo $typing ? ' banner-title' : ''; ?>"<?php if ($typing) : ?> data-interval="<?php echo (int) get_option('argon_banner_typing_effect_interval', 100); ?>"<?php endif; ?>>
			<<?php echo $heading; ?> class="liquid-hero-title banner-title-inner"<?php if ($typing) : ?> data-text="<?php echo esc_attr(wp_strip_all_tags($title)); ?>"<?php endif; ?>><?php echo wp_kses_post($title); ?></<?php echo $heading; ?>>
			<?php if ($subtitle) : ?>
				<p class="liquid-hero-description banner-subtitle"<?php if ($typing) : ?> data-text="<?php echo esc_attr(wp_strip_all_tags($subtitle)); ?>"<?php endif; ?>><?php echo wp_kses_post($subtitle); ?></p>
			<?php endif; ?>
		</div>
		<?php if (is_home()) : ?>
			<a class="liquid-hero-link" href="#main" no-pjax><?php esc_html_e('Browse posts', 'liquid-glass'); ?> <i class="fa fa-arrow-down" aria-hidden="true"></i></a>
		<?php endif; ?>
	</div>
</section>
