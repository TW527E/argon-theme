<?php $input_id = wp_unique_id('liquid-search-input-'); ?>
<form class="liquid-search-form" method="get" action="<?php echo esc_url(home_url('/')); ?>" role="search">
	<label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php esc_html_e('Search', 'liquid-glass'); ?></label>
	<input id="<?php echo esc_attr($input_id); ?>" name="s" class="form-control" type="search" value="<?php echo get_search_query(); ?>" placeholder="<?php esc_attr_e('搜索什么...', 'argon'); ?>" required>
	<button class="btn btn-primary" type="submit"><?php esc_html_e('Search', 'liquid-glass'); ?></button>
</form>
