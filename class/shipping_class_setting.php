<?php
/**
* Class to get recurcively product setting based on shipping class
*
* @version 1.0.0
* @author Advertikon
*/
require_once( 'abstract_setting.php' );

class ShippingClassSetting extends ClassSetting {

	/**
	* @see ClassSetting::$taxonomy
	*/
	protected $taxonomy = 'product_shipping_class';

}

?>