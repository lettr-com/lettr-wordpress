<?php
if ( ! isset( $dashboard ) ) {
	$dashboard = false;
}
?>
<div class="lettr-logo-container">
	<div style="display: flex;">
		<img class="lettr-logo" src="<?php echo esc_url( plugins_url( '../public/img/lettr-wordmark-black.png', __FILE__ ) ); ?>" srcset="<?php echo esc_url( plugins_url( '../public/img/lettr-wordmark-black.svg', __FILE__ ) ); ?>" alt="Lettr logo" />
	</div>
	<?php if ( $dashboard ) : ?>
		<a href="https://lettr.com" target="_blank" rel="noopener noreferrer" class="lettr-button">
			<span>Lettr Dashboard</span>
			<?php Lettr::view( 'icon', array( 'type' => 'arrow-top-right-on-square' ) ); ?>
		</a>
	<?php endif; ?>
</div>
