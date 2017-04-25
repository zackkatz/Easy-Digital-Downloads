<?php

media_upload_header();
wp_enqueue_style( 'media' );

$page     = isset( $_GET['p'] ) ? $_GET['p'] : 1;
$per_page = 30;
$offset   = $per_page * ( $page - 1 );
$offset   = $offset < 1 ? 30 : $offset;
$start    = isset( $_GET['start'] )  ? rawurldecode( $_GET['start'] )  : '';
$bucket   = $this->get_bucket();
$files    = $this->get_files();
echo '<pre>';
//print_r( $files );
echo '</pre>';?>
<div style="padding:20px; background:#fff;">
<?php if( ! $this->is_authenticated() ) : ?>
	<div class="error"><p><?php printf( __( 'Please authenticate your EDD Cloud Files account.', 'easy-digital-downloads' ), admin_url( 'edit.php?post_type=download&page=edd-settings&tab=extensions' ) ); ?></p></div>
<?php else : ?>
	<div class="media-frame-content">
		<ul>
			<?php foreach( $files['Contents'] as $key => $object ) : ?>

				<?php if( false !== strpos( $key, '/' ) ) : ?>

					<li class="edd-cloud-folder">
						<a href="#" data-id="<?php echo $key; ?>"><?php echo $name; ?> &rarr;</a>
					</li>

				<?php else : ?>

					<li class="edd-cloud-file">
						<a href="#" data-id="<?php echo $key; ?>"><?php echo $object['Key']; ?></a>
					</li>

				<?php endif; ?>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>
</div>
<?php