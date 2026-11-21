<?php get_header(); ?>

<div class="page-information-card-container">
	<div class="page-information-card card bg-gradient-secondary shadow-lg border-0" <?php if (isset($_GET['post_type'])){echo 'style="animation: none;"';}?>>
		<div class="card-body">
			<h1><?php printf(esc_html__('Search results for “%s”', 'liquid-glass'), get_search_query()); ?></h1>
			<?php 
				if (get_option('argon_enable_search_filters', 'true') == 'true'){ ?>
					<div class="search-filters">
						<?php
							$all_post_types= get_post_types(array(
								'public'   => true,
							), 'objects');
							$search_filters_type = explode(',', get_option('argon_search_filters_type', '*post,*page,shuoshuo'));
							$current_filters_type = argon_get_search_post_type_array();
							foreach ($search_filters_type as $filter_type) {
								if ($filter_type[0] == '*'){ $filter_type = substr($filter_type, 1); }
								$checked = in_array($filter_type, $current_filters_type);
								if (isset($all_post_types[$filter_type])){
									$filter_name = $all_post_types[$filter_type] -> labels -> name;
								?>
									<div class="custom-control custom-checkbox search-filter-wrapper">
										<input class="custom-control-input search-filter" name="<?php echo $filter_type; ?>" id="search_filter_<?php echo $filter_type; ?>" type="checkbox" <?php echo $checked ? 'checked="true"' : ''; ?>>
										<label class="custom-control-label" for="search_filter_<?php echo $filter_type; ?>"><?php echo $filter_name; ?></label>
									</div>
								<?php
								}
							}
						?>
					</div>
			<?php } ?>
			<p class="text-black mt-3 mb-0 opacity-8">
				<i class="fa fa-file-o mr-1"></i>
				<?php global $wp_query; echo $wp_query -> found_posts; ?> <?php _e('个结果', 'argon');?>
			</p>
		</div>
	</div>
</div>

<?php get_sidebar(); ?>

<div id="primary" class="content-area">
	<main id="main" tabindex="-1" data-columns="<?php echo esc_attr(liquid_glass_feed_columns()); ?>" class="site-main article-list search-result" role="main">
	<?php if (have_posts()) : ?>
		<?php get_template_part('template-parts/feed-items'); ?>
	<?php else : ?>
		<?php get_template_part('template-parts/content', 'none'); ?>
	<?php endif; ?>

<?php get_footer(); ?>
