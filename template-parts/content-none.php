<div class="liquid-empty card">
	<?php if (is_search()) : ?>
		<h2><?php esc_html_e('没有搜索结果', 'argon'); ?></h2>
		<p><?php esc_html_e('换个关键词试试 ?', 'argon'); ?></p>
		<?php get_search_form(); ?>
	<?php elseif (is_archive()) : ?>
		<h2><?php esc_html_e('此分类没有文章', 'argon'); ?></h2>
	<?php else : ?>
		<h2><?php esc_html_e('No posts yet', 'liquid-glass'); ?></h2>
		<?php if (current_user_can('publish_posts')) : ?>
			<a class="liquid-read-link" href="<?php echo esc_url(admin_url('post-new.php')); ?>"><?php esc_html_e('Write a post', 'liquid-glass'); ?></a>
		<?php endif; ?>
	<?php endif; ?>
</div>
