<?php get_header(); ?>

<div class="page-information-card-container">
	<div class="page-information-card card bg-gradient-secondary shadow-lg border-0">
		<div class="card-body">
			<h1><?php the_archive_title(); ?></h1>
			<?php the_archive_description('<div class="liquid-archive-description">', '</div>'); ?>
			<p class="text-black mt-3 mb-0 opacity-8">
				<i class="fa fa-file-o mr-1"></i>
				<?php echo $wp_query -> found_posts; ?> <?php _e('篇文章', 'argon');?>
			</p>
		</div>
	</div>
</div>

<?php get_sidebar(); ?>

<div id="primary" class="content-area">
	<main id="main" tabindex="-1" data-columns="<?php echo esc_attr(liquid_glass_feed_columns()); ?>" class="site-main article-list" role="main">
	<?php if (have_posts()) : ?>
		<?php get_template_part('template-parts/feed-items'); ?>
	<?php else : ?>
		<?php get_template_part('template-parts/content', 'none'); ?>
	<?php endif; ?>

<?php get_footer(); ?>
