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
class OnePica_ImageCdn_Helper_Image extends Mage_Catalog_Helper_Image
{	
	/**
	 * In older versions of Magento (<1.1.3) this method was used to get an image URL.
	 * However, 1.1.3 now uses the getUrl() method in the product > image model. This code
	 * was added for backwards compatibility.
	 *
	 * @return string
	 */
    public function __toString()
    {
        parent::__toString();
        return $this->_getModel()->getUrl();
    }
}