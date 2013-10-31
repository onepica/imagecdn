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

/**
 * Helper methods
 */
class OnePica_ImageCdn_Helper_Data extends Mage_Core_Helper_Abstract
{	
	/**
	 * Factory method for creating the current CDN adapter. Since the adapter class changes
	 * based on the admin config settings, the class can't be hard coded. 
	 *
	 * @return OnePica_ImageCdn_Model_Adapter_Abstract
	 */
	public function factory() {
		$adapter = Mage::getStoreConfig('imagecdn/general/status');
		if($adapter) {
			return Mage::getSingleton($adapter);
		} else {
			return Mage::getSingleton('imagecdn/adapter_disabled');
		}		
	}	
}