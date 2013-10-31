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
 * Extends various methods to use ImageCDN
 */
class OnePica_ImageCdn_Model_Catalog_Product_Image extends Mage_Catalog_Model_Product_Image
{

	/**
	 * Sets the images processor to the ImageCDN version of varien_image and calls the parent
	 * method to return it.
	 *
	 * @return OnePica_ImageCdn_Model_Varien_Image
	 */
    public function getImageProcessor()
    {
        if(!$this->_processor ) {
            $this->_processor = Mage::getModel('imagecdn/varien_image', $this->getBaseFile());
        }
        return parent::getImageProcessor();
    }

	/**
	 * Checks to see if the image has been verified lately by checking in the cache or fails
	 * back to the parent method as appropriate.
	 *
	 * @return bool
	 */
    public function isCached()
    {
    	$cds = Mage::Helper('imagecdn')->factory();
    	if($cds->useCdn()) {	    
	    	return $cds->fileExists($this->_newFile);
    	} else {
    		return parent::isCached();
    	}
    }

	/**
	 * Provides the URL to the image on the CDN or fails back to the parent method as appropriate.
	 *
	 * @return string
	 */
    public function getUrl()
    {
    	$cds = Mage::Helper('imagecdn')->factory();   	    	
    	if($cds->useCdn()) {	    
	    	$url = $cds->getUrl($this->_newFile);
	    	if($url) {
	    		return $url;
	    	}
    	} 
    	
    	return parent::getUrl();
    }

	/**
	 * Clears the images on the CDN and the local cache.
	 *
	 * @return string
	 */
    public function clearCache()
    {
    	parent::clearCache();    	
    	$cds = Mage::Helper('imagecdn')->factory();
    	if($cds->useCdn()) {
    		$cds->clearCache();
    	}
    }
}
