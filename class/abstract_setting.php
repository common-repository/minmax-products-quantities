<?php
if( ! class_exists( 'ClassSetting' ) ) :
/**
* Abstract class for product seting with recurcive nature
*
* @version 1.0.0
* @author Advertikon
*/
abstract class ClassSetting {

	/**
	* @var Array $tree List of parents for all the elements
	*/
	protected $tree = array();

	/**
	* @var Array $hierarchy Hierarchical structure
	*/
	protected $hierarchy = array();

	/**
	* @var Array $settings Flat settings list
	*/
	protected $settings = array();

	/**
	* @var Array $rawSettings List of taxonomies (duplicating) for all products
	*/
	protected $rawSettings = array();

	/**
	* @var String $taxonomy Taxonomy name for specific setting
	*/
	protected $taxonomy;

	/**
	* @var Object $traversableSettings Traversable settings
	*/
	protected $traversableSettings = null;

	/**
	* Class constructor
	*/
	public function __construct() {
		$this->build();
	}

	protected function build() {
		global $wpdb , $table_prefix;

		//Get all the pruduct's settings form DB
		$res = $wpdb->get_results( sprintf( 'SELECT `t`.`slug`, `t`.`name`, `tt`.`parent`, `tt`.`term_id` as "id", `tr`.`object_id` as "product_id" FROM `%1$sterm_taxonomy` `tt` LEFT JOIN `%1$sterm_relationships` `tr` USING( `term_taxonomy_id` ) INNER JOIN `%1$sterms` `t` USING( `term_id` ) WHERE `tt`.`taxonomy` ="%2$s" ' , $table_prefix , $this->taxonomy ) , ARRAY_A );

		$parents = array();
		$dependency = array();

		//Fill intermediate data
		$this->rawSettings = $res;
		foreach( $res as $class ) {
			$this->settings[ $class[ 'id' ] ] = $class;
			$dependency[ $class[ 'id' ] ] = $class[ 'parent' ];
			if( $class[ 'parent' ] ) {
				$parents[] = $class[ 'parent' ];
			}
		}

		//Fill in first level elements
		foreach( $dependency as $term => $parent ) {
			if( ! $parent ) {
				$this->tree[ $term ] = array();
			}
		}

		foreach( $dependency as $term => $parent ) {
			//Get a tail
			if( ! in_array( $term, $parents ) && $parent ) {
				$this->tree[ $term ] = array( $parent );
				$currentParent = $parent;
				while( $dependency[ $currentParent ] ) {
					$currentParent = $dependency[ $currentParent ];
					$this->tree[ $term ][] = $currentParent;
				}
				//Create dependency for each level up to top level
				$p = $this->tree[ $term ];
				while( count( $p ) > 1 ) {
					$this->tree[ array_shift( $p ) ] = $p;
				}
			}
		}

		//Get head
		$pointer = array();
		$top = array();
		foreach( $dependency as $term => $parent ) {
			if( ! $parent ) {
				$this->hierarchy[ $term ] = array();
				unset( $dependency[ $term ] );
				$cParent = $term;
				$pointer = & $this->hierarchy[ $cParent ];
				$top[] = $cParent;
				while( false !== $cChild = array_search( $cParent , $dependency ) ) {
					$pointer[ $cChild ] = array();
					$pointer = & $pointer[ $cChild ];
					$top[] = $cChild;
					unset( $dependency[ $cChild ] );
					$prevL = $cChild;
					while( false !== $curL = array_search( $prevL , $dependency ) ) {
						$pointer[ $curL ] = array();
						$pointer = & $pointer[ $curL ];
						$top[] = $curL;
						unset( $dependency[ $curL ] );
						$prevL = $curL;
					}
					array_pop( $top );
					$pointer = & $this->getParent( $this->hierarchy , end( $top ) );
					//Rewind stack
					while( $top && false === $curL = array_search( end( $top ) , $dependency ) ) {
						array_pop( $top );	
						$pointer = & $this->getParent( $this->hierarchy , end( $top ) );
					}
					$cParent = $top ? current( $top ) : null;
				}
			}
		}

		$this->traversableSettings = new TraversableSettings();
		foreach( $this->rawSettings as $rs ) {
			$this->traversableSettings->add( new SettingUnit( $rs ) );
		}
	}

	/**
	* Get parent array
	*
	* @param Array $array Array to search for where
	* @param Integer $index Parent array index
	* @return Array
	*/
	protected function & getParent( & $array , $index ) {
		$parent = null;
		foreach( $array as $i => & $a ) {
			if( $i == $index ) {
				$parent = & $a;
				break;
			}
			if( is_array( $a ) ) {
				$parent = & $this->getParent( $a , $index );
				if( ! is_null( $parent ) ) {
					break;
				}
			}
		}
		return $parent;
	}

	/**
	* Get flat array with names, formatted accordingly to thiers hierarchy
	*
	* @param Array $hierarchy Hierarchy structure
	* @param Integer $indent Indent value
	* @param String $symbol Indention symbol
	* @return Array
	*/
	public function getFormattedNames( $hierarchy = null , $indent = 0, $symbol = '&mdash;' ) {
		$hierarchy = ! is_null( $hierarchy ) ? $hierarchy : $this->hierarchy;
		$tree = array();
		foreach( $hierarchy as $id => $item ) {
			$tree[ $this->settings[ $id ][ 'slug' ] ] = str_repeat( $symbol , $indent ) . ' ' . $this->settings[ $id ][ 'name' ];
			$indent++;
			$tree = array_merge( $tree , $this->getFormattedNames( $item , $indent , $symbol ) );
			$indent--;
		}
		return $tree;
	}

	/**
	* Get list of names for all the parents
	*
	* @param Integer $productId Product ID
	* @return Array
	*/
	public function getParentNames( $productId ) {
		$names = array();
		foreach( $this->rawSettings as $setting ) {
			if( $productId == $setting[ 'product_id' ] ) {
				$names[] = $setting[ 'name' ];
				foreach( $this->tree[ $setting[ 'id' ] ] as $termId ) {
					$names[] = $this->settings[ $termId ][ 'name' ];
				}
			}
		}
		return $names;
	}

	/**
	* Get names by thiers slugs
	*
	* @param Array $slugs List of slugs
	* @return Array
	*/
	public function getNameBySlug( $slugs ) {
		$names = array();
		foreach( (array)$slugs as $slug ) {
			foreach( $this->settings as $setting ) {
				if( $setting[ 'slug' ] == $slug ) {
					$names[] = $setting[ 'name' ];
				}
			}
		}
		return $names;
	}

	/**
	* Get two dimentional array of items's IDs, product belongs to
	*
	* @param Integer $productId Product ID
	* @return Array
	*/
	public function getIds( $productId ) {
		return $this->traversableSettings->get( $productId , 'id' );
	}

	/**
	* Get two dimentional array of items's names, product belongs to
	*
	* @param Integer $productId Product ID
	* @return Array
	*/
	public function getNames( $productId ) {
		return $this->traversableSettings->get( $productId , 'name' );
	}
	
}

endif;

if( ! class_exists( 'TraversableSettings' ) ) :
/**
* Class to container for particular setting
*/
class TraversableSettings {

	/**
	* @var Array $storage Inner storage array
	*/
	protected $storage = array();
	/**
	* @var Boolean $linked Flag, whether settings element have been linked together
	*/
	protected $linked = false;

	/**
	* Add setting element to iner storage
	*
	* @param Object $item Setting element
	*/
	public function add( $item ) {
		if( ! isset( $this->storage[ $item->id ] ) ) {
			$item->product_id = (array)$item->product_id;
			$this->storage[ $item->id ] = $item;
		}
		else {
			$a =  $this->storage[ $item->id ]->product_id;
			$a[] = $item->product_id;
			$this->storage[ $item->id ]->product_id =  $a;
		}
	}

	/**
	* Link settings elements by thiers parents
	*/
	protected function link() {
		if( ! $this->linked ) {
			foreach( $this->storage as $item ) {
				if( array_key_exists( $item->parent , $this->storage ) ) {
					$item->parent = $this->storage[ $item->parent ];
				}
			}
			$this->linked = true;
		}
	}

	/**
	* Get prioritized list of settings for specific product
	*
	* @param Integer $productId Product ID
	* @param String $output Which settings attributes to be included in the list. Default - IDs
	* @return Array
	*/
	public function get( $productId , $output = 'id' ) {
		$this->link();
		$data = array();
		foreach( $this->storage as $item ) {
			if( in_array( $productId , $item->product_id ) ) {
				$item->get( $data , $output );
			}
		}

		//Here we should get array with settings, prioritized by keys, eg $data[1] - own settings, $data[2] - first parents settings and so forth
		//Filter repeated ocurrences
		for( $i = 1, $len = count( $data ); $i <= $len; $i++ ) {
			for( $x = $i + 1; $x <= $len; $x++ ) {
				$arr = &$data[ $x ];
				for( $y = 0; $y < count( $data[ $x ] ); $y++ ) {
					if( in_array( $data[ $x ][ $y ] , $data[ $i ] ) ) {
						array_splice( $arr, $y );
						$y--;
					}
				}
			}
		}
		return $data;
	}
}

endif;

if( ! class_exists( 'SettingUnit' ) ) :
/**
* Setting instance class
*/
class SettingUnit {

	/**
	* @var Array $data Inner storage
	*/
	protected $data = array();

	/**
	* @var Integer $level Current recurrence level
	*/
	static $level = 0;

	/**
	* Class constructor
	*/
	public function __construct( $item ) {
		if( ! isset( $item[ 'id' ] ) ) {
			trigger_error( 'Setting item should have ID' );
			return;
		}
		$this->data = $item;
	}

	/**
	* Class getter
	*
	* @param String $key Value's key
	* @return Mixed
	*/
	public function __get( $key ) {
		if( isset( $this->data[ $key ] ) ) {
			return $this->data[ $key ];
		}
		return null;
	}

	/**
	* Class setter
	*
	* @param String $key Value's key
	* @param Mixed $val Value
	*/
	public function __set( $key , $val ) {
		$this->data[ $key ] = $val;
	}

	/**
	* Defines whether inner storage contains value with specific key 
	*
	* @param String $key Value's key
	* @return Boolean
	*/
	public function has( $key ) {
		return isset( $this->data[ $key ] );
	}

	/**
	* Recurrsively get prioritaized list of seettings
	*
	* @param Array $data List to be filled in
	* @param String $output Settings' attributes to be fetched
	*/
	public function get( & $data , $output ) {
		if( ! in_array( $output , array( 'id' , 'name' , 'slug' ) ) ) {
			$output = 'id';
		}
		self::$level++;
		if( ! isset( $data[ self::$level ] ) ) {
			$data[ self::$level ] = array();
		}
		$data[ self::$level ][] = $this->{$output};
		if( $this->parent ) {
			$this->parent->get( $data , $output );
		}
		self::$level--;
	}
}

endif;
?>