<?php
$title = get_the_title();
$upvotes = get_shuoshuo_upvotes(get_the_ID());
$comments = get_comments_number();
?>
<article id="post-<?php the_ID(); ?>" <?php post_class('shuoshuo-preview-container shuoshuo-foldable card'); ?>>
	<?php if ($title !== '') : ?>
		<h2 class="shuoshuo-title"><a href="<?php the_permalink(); ?>"><?php echo esc_html($title); ?></a></h2>
	<?php endif; ?>

	<div class="shuoshuo-content"><?php the_content(); ?></div>

	<footer class="shuoshuo-preview-meta">
		<span><?php esc_html_e('说说', 'argon'); ?></span>
		<time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>">
			<?php echo esc_html(get_the_date() . ' · ' . get_the_time()); ?>
		</time>
		<?php if (is_sticky()) : ?>
			<span><i class="fa fa-thumb-tack" aria-hidden="true"></i> <?php _ex('置顶', 'pinned', 'argon'); ?></span>
		<?php endif; ?>
		<?php if ($upvotes > 0) : ?>
			<span><i class="fa fa-thumbs-o-up" aria-hidden="true"></i> <?php echo esc_html(number_format_i18n($upvotes)); ?></span>
		<?php endif; ?>
		<?php if ($comments > 0) : ?>
			<span><i class="fa fa-comments-o" aria-hidden="true"></i> <?php echo esc_html(number_format_i18n($comments)); ?></span>
		<?php endif; ?>
		<a class="shuoshuo-preview-link" href="<?php the_permalink(); ?>">
			<?php esc_html_e('Read note', 'liquid-glass'); ?>
			<i class="fa fa-long-arrow-right" aria-hidden="true"></i>
		</a>
	</footer>
</article>
