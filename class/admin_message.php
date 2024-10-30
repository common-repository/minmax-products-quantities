<?php
if( ! class_exists( 'AdvertikonAdminMessage' ) ) :
/**
* Admin notification handler class
*
* @author Advertikon
* @version 1.0
*/
class AdvertikonAdminMessage {

	/**
	* Class constructor
	*/
	public function __construct() {
		add_action( 'admin_notices', array( $this , 'show' ) );
		if( ! session_id() ) {
			session_start();
		}
	}

	/**
	* Adds error notification
	*
	* @param String $msg Error message
	*/
	public function error( $msg ) {
		$this->addMessage( 'error' , $msg );
	}

	/**
	* Add notice
	*
	* @param String $type Notice type (error,update,update-nag)
	* @param String $msg Notification message
	*/
	protected function addMessage( $type , $msg ) {
		if( ! isset( $_SESSION[ 'adk_notices' ] ) ) {
			$_SESSION[ 'adk_notices' ] = array();
		}
		$_SESSION[ 'adk_notices' ][] = sprintf( '<div class="notice %s"><p>%s</p></div>' , $type , htmlentities( $msg ) );
	}

	/**
	* Show print out admin notices
	*/
	public function show() {
		if( empty( $_SESSION[ 'adk_notices' ] ) ) {
			return;
		}
		foreach( $_SESSION[ 'adk_notices' ] as $mes ) {
			echo $mes;
		}
		unset( $_SESSION[ 'adk_notices' ] );
	}
}

endif;
?>