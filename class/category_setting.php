<?php
if( ! class_exists( 'CategorySetting' ) ) : 
/**
* Class to get recurcively product setting based on category
*
* @version 1.0.0
* @author Advertikon
*/
require_once( 'abstract_setting.php' );

class CategorySetting extends ClassSetting {

	/**
	* @see ClassSetting::$taxonomy
	*/
	protected $taxonomy = 'product_cat';

}

endif;
?>