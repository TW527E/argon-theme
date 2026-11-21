<?php
$image = esc_url(get_option('argon_donate_qrcode_url'), ['http', 'https']);
if ($image === '') {
	return;
}
$link = esc_url(get_option('liquid_glass_donate_link'), ['http', 'https']);
?>
<details class="liquid-donate">
	<summary class="btn btn-outline-primary"><?php esc_html_e('赞赏', 'argon'); ?></summary>
	<div class="liquid-donate-code">
		<?php if ($link !== '') : ?>
			<a href="<?php echo $link; ?>" no-pjax aria-label="<?php esc_attr_e('Open donation link', 'liquid-glass'); ?>">
		<?php endif; ?>
		<img src="<?php echo $image; ?>" alt="<?php esc_attr_e('Donation QR code', 'liquid-glass'); ?>" loading="lazy" decoding="async">
		<?php if ($link !== '') : ?>
			</a>
		<?php endif; ?>
	</div>
</details>
