<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   Propoza
 * @author    Propoza <support@propoza.com>
 * @license   GPL-2.0+
 * @link      https://propoza.com
 * @copyright 2015 Propoza
 */
?>

<?php if ( ! Propoza::is_valid_api_key( WC_Propoza_Integration::get_option( 'api_key', null ) ) ) : ?>
	<div class="error">
		<p>
			<strong>
				<?php echo __( 'Your Propoza API key is outdated. For security reasons please generate a new API key and save it to your dashboard and module.', 'propoza' ); ?>
				<a href="<?php echo Propoza::get_dashboard_propoza_url( '%s' ); ?>" target="_blank" id="propoza_dashoard_link">
					<span><?php echo __( 'Dashboard', 'propoza' ); ?></span>
				</a>
			</strong>
		</p>
	</div>
<?php endif; ?>
