<?php

// Keep Argon's data migrations tied to their original release series.
const LIQUID_GLASS_ARGON_VERSION = '1.3.5';

function liquid_glass_theme_color() {
	$color = sanitize_hex_color(get_option('argon_theme_color')) ?: '#a3502b';
	if (strlen($color) === 4) {
		return '#' . str_repeat($color[1], 2) . str_repeat($color[2], 2) . str_repeat($color[3], 2);
	}
	return $color;
}

function liquid_glass_card_radius() {
	$radius = get_option('argon_card_radius', 28);
	return is_numeric($radius) ? min(30, max(0, (float) $radius)) : 28;
}

function liquid_glass_enqueue_assets() {
	$version = $GLOBALS['theme_version'];
	$dir = get_template_directory_uri();
	$vendor = $GLOBALS['assets_path'];

	wp_enqueue_style('argon_css_merged', $vendor . '/assets/argon_css_merged.css', [], $version);
	wp_enqueue_style('argon-compat', $dir . '/assets/liquid-glass/argon-compat.css', ['argon_css_merged'], $version);
	wp_enqueue_style('style', $dir . '/style.css', ['argon-compat'], $version);
	if (is_child_theme()) {
		wp_enqueue_style('liquid-glass-child', get_stylesheet_uri(), ['style'], wp_get_theme()->get('Version'));
	}
	if (get_option('argon_disable_googlefont', 'true') === 'false') {
		wp_enqueue_style('googlefont', 'https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700|Noto+Serif+SC:300,600&display=swap', [], null);
	}

	wp_enqueue_script('argon_js_merged', $vendor . '/assets/argon_js_merged.js', [], $version);
	wp_enqueue_script('argonjs', $vendor . '/assets/js/argon.min.js', ['argon_js_merged'], $version);
	wp_enqueue_script('argontheme', $dir . '/argontheme.js', ['argonjs'], $version, true);
	wp_enqueue_script('liquid-glass', $dir . '/assets/liquid-glass/presentation.js', ['argontheme'], $version, true);
	wp_enqueue_script('liquid-glass-renderer', $dir . '/assets/liquid-glass/glass.js', ['liquid-glass'], $version, true);
	wp_enqueue_script('liquid-glass-feed', $dir . '/assets/liquid-glass/feed.js', ['liquid-glass'], $version, true);
}
add_action('wp_enqueue_scripts', 'liquid_glass_enqueue_assets');

function liquid_glass_feed_columns() {
	$columns = (string) get_option('argon_article_list_waterflow', '1');
	return in_array($columns, ['2', '3', '2and3'], true) ? $columns : '1';
}

function liquid_glass_preview() {
	if (get_post_type() === 'shuoshuo') {
		get_template_part('template-parts/content-shuoshuo-preview');
		return;
	}

	$layout = get_option('argon_article_list_layout', 'glass');
	if (!in_array($layout, ['1', '2', '3'], true)) {
		$layout = 'glass';
	}
	get_template_part('template-parts/content-preview', $layout);
}

function liquid_glass_background_style($url, $property = 'background-image') {
	$url = esc_url_raw($url);
	if ($url === '') {
		return '';
	}
	return $property . ':url("' . addcslashes($url, "\\\"\n\r\f") . '");';
}

function liquid_glass_thumbnail($url, $size, $attributes = []) {
	$attachment = get_post_thumbnail_id();
	if ($attachment && $url === wp_get_attachment_image_url($attachment, 'full')) {
		echo wp_get_attachment_image($attachment, $size, false, $attributes);
		return;
	}
	$attributes = array_merge(['alt' => '', 'decoding' => 'async'], $attributes);
	printf('<img src="%s"', esc_url($url));
	foreach ($attributes as $name => $value) {
		printf(' %s="%s"', esc_attr($name), esc_attr($value));
	}
	echo '>';
}

function liquid_glass_post_list_style() {
	wp_enqueue_block_style('core/latest-posts', [
		'handle' => 'liquid-glass-post-list',
		'src' => get_theme_file_uri('assets/liquid-glass/post-list.css'),
		'path' => get_theme_file_path('assets/liquid-glass/post-list.css'),
		'ver' => $GLOBALS['theme_version'],
	]);
}
add_action('init', 'liquid_glass_post_list_style');

function liquid_glass_post_list_variation() {
	$variation = [
		'name' => 'liquid-glass-post-list',
		'title' => __('Article list', 'liquid-glass'),
		'description' => __('List posts with optional dates, excerpts, and featured images.', 'liquid-glass'),
		'icon' => 'list-view',
		'attributes' => [
			'className' => 'liquid-post-list',
			'postsToShow' => 5,
			'displayPostDate' => true,
		],
		'isActive' => ['className'],
		'scope' => ['inserter'],
	];
	wp_add_inline_script('wp-blocks', 'wp.blocks.registerBlockVariation("core/latest-posts", ' . wp_json_encode($variation, JSON_HEX_TAG) . ');');
}
add_action('enqueue_block_editor_assets', 'liquid_glass_post_list_variation');
