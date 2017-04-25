<?php
wp_enqueue_style( 'media' );

$form_action_url = esc_url( add_query_arg( array( 'edd_action' => 'upload_to_edd_cloud' ), admin_url() ) );
?>
<div style="padding:20px; background:#fff;">
<?php if( ! $this->is_authenticated() ) : ?>
	<div class="error"><p><?php printf( __( 'Please authenticate your EDD Cloud Files account.', 'easy-digital-downloads' ), admin_url( 'edit.php?post_type=download&page=edd-settings&tab=extensions' ) ); ?></p></div>
<?php else : ?>

	<?php
	if( ! empty( $_GET['edd-cf-upload-success'] ) && '1' == $_GET['edd-cf-upload-success'] ) {
		echo '<div class="edd_errors"><p class="edd_success">' . sprintf( __( 'Success! <a href="#" class="edd-cf-insert">Insert uploaded file into %s</a>.', 'easy-digital-downloads' ), edd_get_label_singular() ) . '</p></div>';
	}
	?>
	<form enctype="multipart/form-data" method="post" action="<?php echo esc_attr( $form_action_url ); ?>" class="edd-cf-upload">
		<p>
			<input type="file" name="edd_cf_file"/>
		</p>
		<p>
			<input type="submit" class="button-secondary" value="<?php esc_attr_e( 'Upload to Cloud', 'easy-digital-downloads' ); ?>"/>
		</p>
	</form>
<?php endif; ?>
</div>