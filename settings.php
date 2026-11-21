<?php

function liquid_glass_settings_sections() {
	return require __DIR__ . '/inc/settings-fields.php';
}

function liquid_glass_settings_label($text) {
	return translate($text, preg_match('/\p{Han}/u', $text) ? 'argon' : 'liquid-glass');
}

function liquid_glass_settings_url($section = 'appearance', $notice = '') {
	$args = ['page' => 'functions.php', 'section' => $section];
	if ($notice !== '') {
		$args['liquid_notice'] = $notice;
	}
	return add_query_arg($args, admin_url('admin.php'));
}

function liquid_glass_settings_menu() {
	$screen = add_menu_page(
		__('Liquid Glass Settings', 'liquid-glass'),
		'Liquid Glass',
		'edit_theme_options',
		'functions.php',
		'liquid_glass_render_settings',
		'dashicons-admin-appearance'
	);
	add_action("load-$screen", function () {
		add_action('admin_enqueue_scripts', 'liquid_glass_settings_assets');
	});
}
add_action('admin_menu', 'liquid_glass_settings_menu');

function liquid_glass_settings_assets() {
	$uri = get_template_directory_uri() . '/assets/liquid-glass/';
	$version = $GLOBALS['theme_version'];
	wp_enqueue_style('liquid-glass-settings', $uri . 'settings.css', [], $version);
	wp_enqueue_script('liquid-glass-settings', $uri . 'settings.js', [], $version, true);
	if (current_user_can('upload_files')) {
		wp_enqueue_media();
	}
}

function liquid_glass_settings_value($name, $field) {
	$value = get_option($name, null);
	if (!is_scalar($value)) {
		return $field[2];
	}
	if (in_array($field[1], ['text', 'textarea', 'image'], true)) {
		return htmlspecialchars_decode((string) $value, ENT_QUOTES);
	}
	return (string) $value;
}

function liquid_glass_highlight_themes() {
	$path = get_template_directory() . '/assets/vendor/highlight/styles/';
	$files = array_merge(glob($path . '*.css') ?: [], glob($path . 'base16/*.css') ?: []);
	$names = array_map(static function ($file) use ($path) {
		return substr($file, strlen($path), -4);
	}, $files);
	sort($names, SORT_NATURAL);
	return array_combine($names, $names);
}

function liquid_glass_render_ordered_setting($name, $value, $choices, $filters) {
	$separator = $filters ? ',' : '|';
	$selected = array_filter(explode($separator, $value), 'strlen');
	$keys = array_map(static function ($entry) {
		return ltrim($entry, '*');
	}, $selected);
	if ($filters) {
		$choices = [];
		foreach (get_post_types(['public' => true], 'objects') as $type) {
			if ($type->name !== 'attachment') {
				$choices[$type->name] = $type->label;
			}
		}
	}
	$rows = array_unique(array_merge($keys, array_keys($choices)));
	?>
	<div class="liquid-order" data-order="<?php echo esc_attr($separator); ?>">
		<input type="text" id="<?php echo esc_attr($name); ?>" name="liquid_options[<?php echo esc_attr($name); ?>]" value="<?php echo esc_attr($value); ?>" class="regular-text" data-order-value>
		<ul data-order-list hidden>
			<?php foreach ($rows as $key) :
				$label = $choices[$key] ?? $key;
				$label = $filters ? $label : liquid_glass_settings_label($label);
				?>
				<li data-order-key="<?php echo esc_attr($key); ?>">
					<label class="liquid-order-entry">
						<input type="checkbox" data-order-enabled <?php checked(in_array($key, $keys, true)); ?>>
						<?php echo esc_html($label); ?>
					</label>
					<?php if ($filters) : ?>
						<label class="liquid-order-default">
							<input type="checkbox" data-order-default <?php checked(in_array('*' . $key, $selected, true)); ?>>
							<?php esc_html_e('Selected by default', 'liquid-glass'); ?>
						</label>
					<?php endif; ?>
					<button type="button" class="button" data-order-move="-1" aria-label="<?php echo esc_attr(sprintf(__('Move %s up', 'liquid-glass'), $label)); ?>">↑</button>
					<button type="button" class="button" data-order-move="1" aria-label="<?php echo esc_attr(sprintf(__('Move %s down', 'liquid-glass'), $label)); ?>">↓</button>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}

function liquid_glass_render_setting($name, $field) {
	[$label, $kind] = $field;
	$value = liquid_glass_settings_value($name, $field);
	$choices = $field[3] ?? [];
	$description = $field[4] ?? '';
	$markup = in_array($kind, ['textarea', 'textarea-html', 'html', 'script'], true);
	$disabled = $kind === 'script' && !current_user_can('unfiltered_html');
	$field_name = 'liquid_options[' . $name . ']';
	$label = liquid_glass_settings_label($label);
	?>
	<div class="liquid-setting-row">
		<label class="liquid-setting-label" for="<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?></label>
		<div class="liquid-field">
			<?php if ($kind === 'select' || $kind === 'highlight') :
				if ($kind === 'highlight') {
					$choices = liquid_glass_highlight_themes();
				}
				if (!array_key_exists($value, $choices)) {
					$choices[$value] = $value;
				}
				?>
				<select id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($field_name); ?>">
					<?php foreach ($choices as $choice => $caption) : ?>
						<option value="<?php echo esc_attr($choice); ?>" <?php selected((string) $choice, $value); ?>><?php echo esc_html(liquid_glass_settings_label($caption)); ?></option>
					<?php endforeach; ?>
				</select>
			<?php elseif ($markup) : ?>
				<textarea id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($field_name); ?>" rows="<?php echo $kind === 'script' ? 7 : 4; ?>" <?php disabled($disabled); ?>><?php echo esc_textarea($disabled ? '' : $value); ?></textarea>
			<?php elseif ($kind === 'order' || $kind === 'filters') : ?>
				<?php liquid_glass_render_ordered_setting($name, $value, $choices, $kind === 'filters'); ?>
			<?php else :
				$type = $kind === 'number' || $kind === 'color' ? $kind : 'text';
				if ($kind === 'color') {
					$value = liquid_glass_theme_color();
				}
				?>
				<div<?php if ($kind === 'image') echo ' class="liquid-media-field"'; ?>>
					<input type="<?php echo esc_attr($type); ?>" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($field_name); ?>" value="<?php echo esc_attr($value); ?>"<?php
						if ($kind === 'image' || $kind === 'url') echo ' inputmode="url"';
						if ($kind === 'number') {
							foreach ($choices as $bound => $limit) {
								printf(' %s="%s"', esc_attr($bound), esc_attr($limit));
							}
						}
					?>>
					<?php if ($kind === 'image' && current_user_can('upload_files')) : ?>
						<button type="button" class="button" data-select-image><?php esc_html_e('Choose from media library', 'liquid-glass'); ?></button>
					<?php endif; ?>
				</div>
				<?php if ($kind === 'image') : ?>
					<img class="liquid-qr-preview"<?php if ($value) echo ' src="' . esc_url($value) . '"'; ?> alt="<?php esc_attr_e('Donation QR code', 'liquid-glass'); ?>" <?php if (!$value) echo 'hidden'; ?>>
				<?php endif; ?>
			<?php endif; ?>
			<?php if ($description !== '' || $disabled) : ?>
				<p class="description">
					<?php echo $disabled ? esc_html__('Only administrators with permission to edit unfiltered HTML can change this field.', 'liquid-glass') : wp_kses_post(liquid_glass_settings_label($description)); ?>
				</p>
			<?php endif; ?>
			<?php if ($name === 'argon_hide_categories') : ?>
				<details class="liquid-taxonomy-ids">
					<summary><?php esc_html_e('View category and tag IDs', 'liquid-glass'); ?></summary>
					<ul>
						<?php foreach (get_terms(['taxonomy' => ['category', 'post_tag'], 'hide_empty' => false]) as $term) : ?>
							<li><?php echo esc_html($term->name . ' · ' . $term->term_id); ?></li>
						<?php endforeach; ?>
					</ul>
				</details>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

function liquid_glass_render_settings() {
	if (!current_user_can('edit_theme_options')) {
		return;
	}
	$sections = liquid_glass_settings_sections();
	$section = $_GET['section'] ?? 'appearance';
	if (!is_string($section) || (!isset($sections[$section]) && $section !== 'backup')) {
		$section = 'appearance';
	}
	$navigation = array_merge($sections, ['backup' => ['Backups', []]]);
	$notice = $_GET['liquid_notice'] ?? '';
	?>
	<div class="wrap liquid-settings" data-save-error="<?php esc_attr_e('Settings could not be saved. Your changes are still in the form.', 'liquid-glass'); ?>">
		<header class="liquid-settings-header">
			<h1><?php esc_html_e('Liquid Glass Settings', 'liquid-glass'); ?></h1>
			<a class="button" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('View site', 'liquid-glass'); ?></a>
		</header>
		<?php if ($notice === 'saved') : ?>
			<div class="liquid-setting-message" role="status"><p><?php esc_html_e('Settings saved.', 'liquid-glass'); ?></p></div>
		<?php elseif ($notice === 'imported') : ?>
			<div class="liquid-setting-message" role="status"><p><?php esc_html_e('Settings imported and saved.', 'liquid-glass'); ?></p></div>
		<?php elseif ($notice === 'invalid') : ?>
			<div class="liquid-setting-message is-error" role="alert"><p><?php esc_html_e('Check the values or JSON format and try again.', 'liquid-glass'); ?></p></div>
		<?php endif; ?>
		<div class="liquid-settings-layout">
			<nav class="liquid-settings-nav" aria-label="<?php esc_attr_e('Settings sections', 'liquid-glass'); ?>">
				<?php foreach ($navigation as $slug => [$label]) : ?>
					<a href="<?php echo esc_url(liquid_glass_settings_url($slug)); ?>" data-settings-tab="<?php echo esc_attr($slug); ?>"<?php if ($section === $slug) echo ' aria-current="page"'; ?>><?php echo esc_html(liquid_glass_settings_label($label)); ?></a>
				<?php endforeach; ?>
			</nav>
			<?php foreach ($navigation as $slug => [$label, $fields]) :
				$title_id = 'liquid-section-title-' . $slug;
				$form_id = 'liquid-settings-form-' . $slug;
				?>
				<section class="liquid-settings-panel" data-settings-panel="<?php echo esc_attr($slug); ?>" aria-labelledby="<?php echo esc_attr($title_id); ?>" <?php if ($section !== $slug) echo 'hidden'; ?>>
					<div class="liquid-settings-panel-head">
						<h2 id="<?php echo esc_attr($title_id); ?>" tabindex="-1"><?php echo esc_html(liquid_glass_settings_label($label)); ?></h2>
						<?php if ($slug !== 'backup') : ?>
							<button class="button button-primary" type="submit" form="<?php echo esc_attr($form_id); ?>"><?php esc_html_e('Save changes', 'liquid-glass'); ?></button>
						<?php endif; ?>
					</div>
					<?php if ($slug === 'backup') : ?>
						<?php liquid_glass_render_setting_backups($sections); ?>
					<?php else : ?>
						<form id="<?php echo esc_attr($form_id); ?>" data-settings-form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
							<?php wp_nonce_field('liquid_glass_save_settings', '_wpnonce_' . $slug); ?>
							<input type="hidden" name="action" value="liquid_glass_save_settings">
							<input type="hidden" name="section" value="<?php echo esc_attr($slug); ?>">
							<?php foreach ($fields as $name => $field) liquid_glass_render_setting($name, $field); ?>
						</form>
					<?php endif; ?>
				</section>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

function liquid_glass_render_setting_backups($sections) {
	$values = [];
	foreach ($sections as [, $fields]) {
		foreach ($fields as $name => $field) {
			if ($field[1] !== 'script' || current_user_can('unfiltered_html')) {
				$values[$name] = liquid_glass_settings_value($name, $field);
			}
		}
	}
	?>
	<div class="liquid-backup">
		<h3><label for="liquid-settings-export"><?php esc_html_e('Export settings', 'liquid-glass'); ?></label></h3>
		<textarea id="liquid-settings-export" rows="6" readonly><?php echo esc_textarea(wp_json_encode($values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></textarea>
		<button type="button" class="button" data-download-settings><?php esc_html_e('Download JSON', 'liquid-glass'); ?></button>
	</div>
	<form class="liquid-backup" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<h3><label for="liquid-settings-import"><?php esc_html_e('Import settings', 'liquid-glass'); ?></label></h3>
		<p class="description"><?php esc_html_e('Paste a Liquid Glass or Argon JSON backup. Settings included in the backup will be updated.', 'liquid-glass'); ?></p>
		<?php wp_nonce_field('liquid_glass_save_settings'); ?>
		<input type="hidden" name="action" value="liquid_glass_save_settings">
		<input type="hidden" name="section" value="backup">
		<textarea id="liquid-settings-import" name="liquid_backup" rows="7" required></textarea>
		<button class="button button-primary" type="submit"><?php esc_html_e('Import and save', 'liquid-glass'); ?></button>
	</form>
	<?php
}

function liquid_glass_validate_setting($name, $field, $value) {
	if (!is_scalar($value)) {
		return null;
	}
	$value = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
	$type = $field[1];
	if ($type === 'select' || $type === 'highlight') {
		$choices = $type === 'highlight' ? liquid_glass_highlight_themes() : $field[3];
		if ($name === 'argon_assets_path' && in_array($value, ['jsdelivr', 'fastgit', 'sourcegcdn', 'jsdelivr_gcore', 'jsdelivr_fastly', 'jsdelivr_cf'], true)) {
			return 'default';
		}
		return array_key_exists($value, $choices) ? $value : null;
	}
	if ($type === 'number') {
		$limits = $field[3] ?? [];
		return is_numeric($value) && $value >= ($limits['min'] ?? -INF) && $value <= ($limits['max'] ?? INF) ? $value : null;
	}
	if ($type === 'color') {
		return sanitize_hex_color($value) ?: null;
	}
	if ($type === 'image' || $type === 'url') {
		$url = esc_url_raw($value, ['http', 'https']);
		return $url === '' && trim($value) !== '' ? null : $url;
	}
	if ($name === 'argon_hide_categories' && $value !== '' && !preg_match('/^\s*\d+(?:\s*,\s*\d+)*\s*$/D', $value)) {
		return null;
	}
	if ($type === 'order' || $type === 'filters') {
		$separator = $type === 'order' ? '|' : ',';
		$entries = $value === '' ? [] : explode($separator, $value);
		foreach ($entries as $entry) {
			if ($type === 'order' ? !array_key_exists($entry, $field[3]) : !preg_match('/^\*?[a-z0-9_-]+$/D', $entry)) {
				return null;
			}
		}
		return implode($separator, array_unique($entries));
	}
	if (in_array($type, ['html', 'textarea-html', 'script'], true)) {
		return current_user_can('unfiltered_html') ? $value : wp_kses_post($value);
	}
	// Argon templates consume these options as already escaped strings.
	return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function liquid_glass_save_settings() {
	if (!current_user_can('edit_theme_options')) {
		wp_die(esc_html__('You are not allowed to change theme settings.', 'liquid-glass'));
	}
	$sections = liquid_glass_settings_sections();
	$section = $_POST['section'] ?? '';
	if (!is_string($section) || (!isset($sections[$section]) && $section !== 'backup')) {
		wp_safe_redirect(liquid_glass_settings_url('appearance', 'invalid'));
		exit;
	}
	$import = $section === 'backup';
	check_admin_referer('liquid_glass_save_settings', $import ? '_wpnonce' : '_wpnonce_' . $section);
	if ($import) {
		$raw = $_POST['liquid_backup'] ?? '';
		$decoded = is_string($raw) ? json_decode(wp_unslash($raw)) : null;
		$values = is_object($decoded) ? (array) $decoded : [];
		$fields = array_merge(...array_column($sections, 1));
	} else {
		$raw = $_POST['liquid_options'] ?? [];
		$values = is_array($raw) ? wp_unslash($raw) : [];
		$fields = $sections[$section][1];
	}

	$updates = [];
	$valid = false;
	foreach (array_intersect_key($values, $fields) as $name => $value) {
		$field = $fields[$name];
		if ($field[1] === 'script' && !current_user_can('unfiltered_html')) {
			continue;
		}
		$valid = true;
		if ($value === liquid_glass_settings_value($name, $field)) {
			continue;
		}
		$value = liquid_glass_validate_setting($name, $field, $value);
		if ($value === null) {
			$valid = false;
			break;
		}
		$updates[$name] = $value;
	}
	if ($valid) {
		// Only one image viewer can own the article's image click handler.
		if (($updates['argon_enable_fancybox'] ?? '') === 'true') {
			$updates['argon_enable_zoomify'] = 'false';
		} elseif (($updates['argon_enable_zoomify'] ?? '') === 'true') {
			$updates['argon_enable_fancybox'] = 'false';
		}
		foreach ($updates as $name => $value) {
			update_option($name, $value);
		}
	}
	wp_safe_redirect(liquid_glass_settings_url($section, $valid ? ($import ? 'imported' : 'saved') : 'invalid'));
	exit;
}
add_action('admin_post_liquid_glass_save_settings', 'liquid_glass_save_settings');
