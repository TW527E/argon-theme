<?php
$protected = post_password_required();
$title = get_the_title() ?: __('Untitled', 'liquid-glass');
$thumbnail = argon_has_post_thumbnail() ? argon_get_post_thumbnail() : '';
$limit = (int) get_option('argon_trim_words_count', 175);
$meta = explode('|', get_option('argon_article_meta', 'time|views|comments|categories'));
if (is_sticky() && is_home() && !is_paged()) {
	array_unshift($meta, 'sticky');
}
if ($protected) {
	array_unshift($meta, 'needpassword');
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class('card liquid-entry' . ($thumbnail ? ' has-entry-image' : '') . (get_option('argon_enable_into_article_animation') === 'true' ? ' post-preview' : '')); ?>>
	<?php if ($thumbnail) : ?>
		<div class="liquid-entry-media">
			<?php liquid_glass_thumbnail($thumbnail, 'large'); ?>
		</div>
	<?php endif; ?>
	<div class="liquid-entry-copy">
		<header>
			<h2><a class="post-title" href="<?php the_permalink(); ?>"><?php echo esc_html($title); ?></a></h2>
			<div class="liquid-entry-meta">
				<?php
				foreach ($meta as $field) {
					if ($field === 'time') {
						printf('<time datetime="%s">%s</time>', esc_attr(get_the_date(DATE_W3C)), esc_html(get_the_date()));
					} else {
						echo get_article_meta($field);
					}
				}
				?>
			</div>
		</header>
		<?php if ($limit > 0) : ?>
			<?php if ($protected) : ?>
				<p><?php esc_html_e('这篇文章受密码保护，输入密码才能阅读', 'argon'); ?></p>
			<?php else : ?>
				<?php
				$excerpt = has_excerpt() ? get_the_excerpt() : get_the_content('…');
				if (get_option('argon_hide_shortcode_in_preview') === 'true') {
					$excerpt = strip_shortcodes($excerpt);
				}
				?>
				<?php if ($excerpt) : ?><p><?php echo esc_html(wp_trim_words($excerpt, $limit)); ?></p><?php endif; ?>
			<?php endif; ?>
		<?php endif; ?>
		<?php if (!$protected && get_option('argon_show_readingtime') !== 'false' && !is_readingtime_meta_hidden()) : ?>
			<div class="liquid-entry-meta liquid-reading-time"><?php echo get_article_reading_time_meta(get_the_content()); ?></div>
		<?php endif; ?>
		<?php the_tags('<div class="liquid-entry-tags">', '', '</div>'); ?>
		<a class="liquid-read-link" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(sprintf(__('Read %s', 'liquid-glass'), $title)); ?>"><?php esc_html_e('Read article', 'liquid-glass'); ?> <i class="fa fa-long-arrow-right" aria-hidden="true"></i></a>
	</div>
</article>
