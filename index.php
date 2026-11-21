<?php get_header(); ?>

<div class="page-information-card-container"></div>

<?php get_sidebar(); ?>

<div id="primary" class="content-area">
	<?php if (is_home()) :
		$shows_notes = liquid_glass_home_shows_notes();
		$toggle_url = add_query_arg('liquid_notes', $shows_notes ? 'hide' : 'show', get_pagenum_link(1, false));
		?>
		<header id="posts-feed" class="liquid-feed-heading liquid-surface" tabindex="-1">
			<h<?php echo get_option('argon_banner_size') === 'hide' ? '1' : '2'; ?>><?php esc_html_e('Latest posts', 'liquid-glass'); ?></h<?php echo get_option('argon_banner_size') === 'hide' ? '1' : '2'; ?>>
			<a class="liquid-notes-toggle" href="<?php echo esc_url($toggle_url . '#posts-feed'); ?>">
				<i class="fa <?php echo $shows_notes ? 'fa-eye-slash' : 'fa-eye'; ?>" aria-hidden="true"></i>
				<?php echo esc_html($shows_notes ? __('Hide notes', 'liquid-glass') : __('Show notes', 'liquid-glass')); ?>
			</a>
		</header>
	<?php endif; ?>
	<main id="main" tabindex="-1" data-columns="<?php echo esc_attr(liquid_glass_feed_columns()); ?>" class="site-main article-list article-list-home" role="main">
	<?php if (have_posts()) : ?>
		<?php get_template_part('template-parts/feed-items'); ?>
	<?php else : ?>
		<?php get_template_part('template-parts/content', 'none'); ?>
	<?php endif; ?>

<?php get_footer(); ?>
