<!-- Woocommerce -->
<div class="propoza-iframe-container">
	<iframe id="propoza-dashboard-iframe" src="<?php echo Propoza::get_dashboard_propoza_login_url( null, apply_filters( 'propoza_dashboard_token', null ) ); ?>" frameborder="0"></iframe>
</div>

<!-- Calculate iframe height. Adminmenuback - body height -->
<script>
	jQuery(document).ready(function ($) {
		var sidebarHeight = $('#adminmenuback').outerHeight();
		var totalHeight = sidebarHeight - 152;
		$('#propoza-dashboard-iframe').css('height', totalHeight);
		$('#propoza-dashboard-iframe').css('width', '100%');
	});
</script>
