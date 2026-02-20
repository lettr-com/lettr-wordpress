<div class="lettr-plugin-container">
	<div class="lettr-start-container">
		<?php Lettr::view( 'logo' ); ?>

		<div id="lettr_alerts"></div>

		<div>
			<h3 class="lettr-h3"><?php esc_html_e( 'Connect your site to Lettr', 'lettr' ); ?></h3>
			<p class="lettr-setup-steps-desc"><?php esc_html_e( 'Follow these steps to send emails from your site using Lettr.', 'lettr' ); ?></p>
		</div>

		<div class="lettr-setup-steps-container">
			<div class="lettr-setup-steps-gradient"></div>

			<div class="lettr-setup-step-create-key lettr-setup-steps-step-container">
				<div class="lettr-setup-steps-spot">
					<div></div>
				</div>
				<div class="lettr-setup-steps-content">
					<div>
						<div>
							<div style="display: flex; align-items: center; margin-bottom: 8px; gap: 8px;">
								<h3 class="lettr-h3" style="margin-bottom: 0;">Create an API key</h3>
								<svg class="only-completed" xmlns="http://www.w3.org/2000/svg" fill="none" width="22" viewBox="0 0 24 24" stroke-width="2" stroke="#00713f">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
								</svg>
							</div>
							<p class="lettr-setup-steps-desc">Create an API key with <strong>"Sending access"</strong> permissions to use it in the next step.</p>
							<div class="lettr-setup-steps-actions" style="display: flex; align-items: center; gap: 16px;">
								<a id="lettr-create-key" class="lettr-button is-primary" href="https://lettr.com/api-keys" onclick="lettrCreateKey()" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Create API key', 'lettr' ); ?></a>
								<span>OR</span>
								<a id="lettr-use-existing-key" href="#"><?php esc_html_e( 'Use existing API key', 'lettr' ); ?></a>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="lettr-setup-step-enter-key lettr-setup-steps-step-container is-disabled">
				<div class="lettr-setup-steps-spot">
					<div></div>
				</div>
				<div class="lettr-setup-steps-content">
					<div>
						<div>
							<h3 class="lettr-h3"><?php esc_html_e( 'Enter your API key', 'lettr' ); ?></h3>
							<p class="lettr-setup-steps-desc"><?php esc_html_e( 'Copy your API key from the Lettr dashboard, and paste it into the field below.', 'lettr' ); ?></p>
							<form id="lettr-api-key-form" autocomplete="off" method="post">
								<div>
									<input id="lettr_api_key" class="lettr-input" name="key" type="password" value="" placeholder="<?php esc_attr_e( 'lttr_xxxxxxxxx', 'lettr' ); ?>" style="width: 100%;" autocomplete="off" data-1p-ignore data-lpignore="true" data-protonpass-ignore="true">
								</div>
								<div style="margin-top: 16px;">
									<input type="submit" class="lettr-button" value="<?php esc_attr_e( 'Connect with API key', 'lettr' ); ?>">
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
