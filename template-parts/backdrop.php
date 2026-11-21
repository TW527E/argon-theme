<?php
$light = apply_filters('argon_page_background_url', get_option('argon_page_background_url'));
$dark = apply_filters('argon_page_background_dark_url', get_option('argon_page_background_dark_url'));
$opacity = get_option('argon_page_background_opacity', 1);
$style = '--wallpaper-opacity:' . (is_numeric($opacity) ? min(1, max(0, (float) $opacity)) : 1) . ';';
$style .= liquid_glass_background_style($light, '--wallpaper-light');
$style .= liquid_glass_background_style($dark, '--wallpaper-dark');
?>
<div class="liquid-backdrop<?php echo $dark ? ' has-dark-wallpaper' : ''; ?>" aria-hidden="true" style="<?php echo esc_attr($style); ?>"></div>
