<?php
/*
Template Name: 说说
*/
get_header();
$notes = new WP_Query([
	'post_type' => 'shuoshuo',
	'post_status' => 'publish',
	'posts_per_page' => -1,
	'no_found_rows' => true,
]);
?>
<div class="page-information-card-container">
	<div class="page-information-card card">
		<div class="card-body">
			<h1><?php esc_html_e('说说', 'argon'); ?></h1>
			<p class="mt-3 mb-0">
				<?php echo esc_html(number_format_i18n($notes->post_count)); ?> <?php esc_html_e('条说说', 'argon'); ?>
			</p>
		</div>
	</div>
</div>
<?php get_sidebar(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" tabindex="-1">
		<?php if ($notes->have_posts()) : ?>
			<?php while ($notes->have_posts()) : $notes->the_post(); ?>
				<?php get_template_part('template-parts/content', 'shuoshuo'); ?>
			<?php endwhile; ?>
			<?php wp_reset_postdata(); ?>
		<?php else : ?>
			<div class="liquid-empty card">
				<h2><?php esc_html_e('No notes yet', 'liquid-glass'); ?></h2>
			</div>
		<?php endif; ?>

<?php get_footer(); ?>
