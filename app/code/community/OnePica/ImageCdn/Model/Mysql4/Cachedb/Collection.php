<?php
/**
 * OnePica_ImageCdn
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0), a
 * copy of which is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   OnePica
 * @package    OnePica_ImageCdn
 * @author     OnePica Codemaster <codemaster@onepica.com>
 * @copyright  Copyright (c) 2009 One Pica, Inc.
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class OnePica_ImageCdn_Model_Mysql4_Cachedb_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	public function _construct() {
		parent::_construct();
		$this->_init('imagecdn/cachedb');
	}
	
	/**
	 * Custom method to truncate the cache table. Tests for recently created truncate method (added Magento 1.3.1)
	 *
	 * @return none
	 */
	public function truncate() {
		if(method_exists($this->getConnection(), 'truncate')) {
			$this->getConnection()->truncate($this->getTable('imagecdn/cachedb'));
		} else {
			$sql = 'TRUNCATE ' . $this->getConnection()->quoteIdentifier($this->getTable('imagecdn/cachedb'));
        	$this->getConnection()->raw_query($sql);
		}
	}
}
