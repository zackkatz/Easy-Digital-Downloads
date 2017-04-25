<?php
/**
 * Cloud Files
 *
 * @package     EDD
 * @subpackage  Classes/Tools/Cloud Files
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.8
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use Aws\S3\S3Client;

/**
 * EDD_Cloud_Files Class
 *
 * This class handles uploading files to the hosted EDD Files services
 *
 * @since 2.8
 */
class EDD_Cloud_Files {

	public $client;
	public $region;
	public $version;
	public $bucket;

	public function __construct() {

		//$this->region  = $_region;
		//$this->version = $_version;

		$this->bucket = 'eddtest';

		$this->includes();

		// Instantiate an Amazon S3 client.
		$this->client = new S3Client( array(
			'version' => 'latest', //$this->version,
			'region'  => 'us-west-2', //$this->region,
			'credentials' => array(
				'key'    => 'AKIAIAB3D2RT23PC377A',
				'secret' => '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'
			)
		) );

		add_filter( 'media_upload_tabs', array( $this, 'register_media_tab' ) );
		add_action( 'media_upload_edd_cloud_upload', array( $this, 'register_upload_file_media_frame' ) );
		add_action( 'media_upload_edd_cloud_files', array( $this, 'register_view_files_frame' ) );
		add_action( 'edd_upload_to_edd_cloud', array( $this, 'upload_handler' ) );

	}

	public function includes() {
		require EDD_PLUGIN_DIR . 'includes/libraries/aws/aws-autoloader.php';
	}

	public function register_media_tab( $tabs ) {

		$tabs['edd_cloud_upload'] = __( 'Upload to EDD Cloud', 'easy-digital-downloads' );
		$tabs['edd_cloud_files']  = __( 'View Cloud Files', 'easy-digital-downloads' );

		return $tabs;
	}

	public function register_upload_file_media_frame() {

		if ( ! empty( $_POST ) ) {
			$return = media_upload_form_handler();
			if ( is_string( $return ) )
				return $return;
		}

		wp_iframe( array( $this, 'upload_file_frame' ) );
	}

	public function upload_file_frame( $type = 'file', $errors = null, $id = null ) {

		include EDD_PLUGIN_DIR . 'includes/admin/tools/cloud-files-upload.php';

	}

	public function register_view_files_frame() {

		if ( ! empty( $_POST ) ) {
			$return = media_upload_form_handler();
			if ( is_string( $return ) )
				return $return;
		}

		wp_iframe( array( $this, 'view_files_frame' ) );
	}

	public function view_files_frame() {

		include EDD_PLUGIN_DIR . 'includes/admin/tools/cloud-files-list.php';

	}

	public function is_authenticated() {
		return true;
	}

	public function upload_handler() {

		if( ! is_admin() ) {
			return;
		}

		if( ! current_user_can( 'edit_products' ) ) {
			wp_die( __( 'You do not have permission to upload files to EDD Cloud', 'easy-digital-downloads' ) );
		}

		if( empty( $_FILES['edd_cf_file'] ) || empty( $_FILES['edd_cf_file']['name'] ) ) {
			wp_die( __( 'Please select a file to upload', 'easy-digital-downloads' ), __( 'Error', 'easy-digital-downloads' ), array( 'back_link' => true ) );
		}

		$file = array(
			'name'   => $_FILES['edd_cf_file']['name'],
			'file'   => $_FILES['edd_cf_file']['tmp_name'],
			'type'   => $_FILES['edd_cf_file']['type']
		);

		if( $this->send_to_destination( $file['name'], $file['file'] ) ) {
			EDD()->session->set( 'edd_cf_file_name', $file['name'] );
			EDD()->session->set( 'edd_cf_file_bucket', $file['bucket'] );
			wp_safe_redirect( add_query_arg( 'edd-cf-upload-success', '1', $_SERVER['HTTP_REFERER'] ) ); exit;
		} else {
			wp_die( __( 'Something went wrong during the upload process', 'easy-digital-downloads' ), __( 'Error', 'easy-digital-downloads' ), array( 'back_link' => true ) );
		}
	}

	public function send_to_destination( $filename, $file ) {
		$success = $this->client->putObject( array(
			'Bucket'     => $this->bucket,
   			'Key'        => $filename,
    		'SourceFile' => $file,
		) );
		return $success;
	}

	public function get_file_url( $file ) {

	}

	private function get_destination() {

	}

	private function get_key() {

	}

	public function get_bucket() {
		return 'eddtest';
	}

	public function get_files() {
		return $this->client->listObjects( array(
			'Bucket' => $this->bucket
		) ) ;
		/*
		$files = array(
			'folder-test/' => 'Folder Test',
			'test'  => 'Test File',
			'test2' => 'Another Test File',
		);

		return $files;*/
	}


}