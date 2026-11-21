<?php get_header(); ?>

<div class="page-information-card-container"></div>
<?php get_sidebar(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" tabindex="-1">
		<div class="liquid-empty card">
			<h1><?php esc_html_e('Page not found', 'liquid-glass'); ?></h1>
			<p><?php esc_html_e('Use search to find a post, or head back to the homepage.', 'liquid-glass'); ?></p>
			<div class="liquid-empty-actions">
				<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#argon_search_modal"><?php esc_html_e('Search', 'liquid-glass'); ?></button>
				<a class="liquid-read-link" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'liquid-glass'); ?> <i class="fa fa-long-arrow-right" aria-hidden="true"></i></a>
			</div>
			<?php echo apply_filters('argon_404page_extra_html', ''); ?>
		</div>

<?php get_footer(); ?>
