<?php
$title = get_option('argon_toolbar_title') ?: get_bloginfo('name');
$icon = esc_url(get_option('argon_toolbar_icon'));
$has_sidebar = get_option('argon_page_layout', 'double-reverse') !== 'single';
?>
<header id="toolbar" class="liquid-toolbar<?php echo get_option('argon_show_toolbar_mask') === 'true' ? ' has-toolbar-scrim' : ''; ?>">
	<div class="liquid-toolbar-inner liquid-frame">
		<div class="liquid-brand liquid-surface"<?php if (!$icon && $title === '--hidden--') echo ' hidden'; ?>>
			<?php if ($icon) : ?>
				<a class="liquid-brand-icon" href="<?php echo esc_url(get_option('argon_toolbar_icon_link') ?: home_url('/')); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
					<img src="<?php echo $icon; ?>" alt="" width="32" height="32">
				</a>
			<?php endif; ?>
			<?php if ($title !== '--hidden--') : ?>
				<a class="liquid-brand-name" href="<?php echo esc_url(home_url('/')); ?>">
					<?php if (!$icon) : ?><span class="liquid-mark" aria-hidden="true"></span><?php endif; ?>
					<span><?php echo wp_kses_post($title); ?></span>
				</a>
			<?php endif; ?>
		</div>

		<nav id="liquid-navigation" class="liquid-navigation collapse" aria-label="<?php esc_attr_e('Main navigation', 'liquid-glass'); ?>">
			<div id="liquid-nav-items" class="liquid-nav-panel liquid-surface">
				<?php if (has_nav_menu('toolbar_menu')) : ?>
					<?php wp_nav_menu(['theme_location' => 'toolbar_menu', 'container' => false, 'menu_class' => 'liquid-nav-list', 'fallback_cb' => false]); ?>
				<?php else : ?>
					<ul class="liquid-nav-list">
						<li><a href="<?php echo esc_url(home_url('/')); ?>"<?php if (is_front_page()) echo ' aria-current="page"'; ?>><?php esc_html_e('Home', 'liquid-glass'); ?></a></li>
						<?php wp_list_pages(['title_li' => '', 'depth' => 1, 'number' => 4]); ?>
					</ul>
				<?php endif; ?>
				<?php if ($has_sidebar) : ?>
					<button id="open_sidebar" type="button" class="liquid-side-search liquid-sidebar-toggle" aria-controls="leftbar" aria-expanded="false"><i class="fa fa-columns" aria-hidden="true"></i> <?php esc_html_e('Open sidebar', 'liquid-glass'); ?></button>
				<?php endif; ?>
			</div>
		</nav>

		<div class="liquid-tools liquid-surface">
			<button type="button" class="liquid-icon liquid-menu-toggle" data-toggle="collapse" data-target="#liquid-navigation" aria-controls="liquid-navigation" aria-expanded="false" title="<?php esc_attr_e('Menu', 'liquid-glass'); ?>" aria-label="<?php esc_attr_e('Menu', 'liquid-glass'); ?>"><i class="fa fa-bars" aria-hidden="true"></i></button>
			<button type="button" class="liquid-icon" data-toggle="modal" data-target="#argon_search_modal" aria-haspopup="dialog" title="<?php esc_attr_e('Search', 'liquid-glass'); ?>" aria-label="<?php esc_attr_e('Search', 'liquid-glass'); ?>"><i class="fa fa-search" aria-hidden="true"></i></button>
			<button type="button" class="liquid-icon liquid-palette-toggle" aria-pressed="false" title="<?php esc_attr_e('Pink mode', 'liquid-glass'); ?>" aria-label="<?php esc_attr_e('Pink mode', 'liquid-glass'); ?>"><i class="fa fa-tint" aria-hidden="true"></i></button>
			<button type="button" class="liquid-icon liquid-theme-toggle" aria-pressed="false" title="<?php esc_attr_e('Dark mode', 'liquid-glass'); ?>" aria-label="<?php esc_attr_e('Dark mode', 'liquid-glass'); ?>"><i class="fa fa-moon-o" aria-hidden="true"></i></button>
		</div>
	</div>
</header>
<div id="argon_search_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="liquid-search-title" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h2 class="modal-title" id="liquid-search-title"><?php esc_html_e('Search', 'liquid-glass'); ?></h2>
				<button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_attr_e('Close', 'liquid-glass'); ?>"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body"><?php get_search_form(); ?></div>
		</div>
	</div>
</div>
