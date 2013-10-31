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

class OnePica_ImageCdn_Model_Catalog_Category extends Mage_Catalog_Model_Category
{
	/**
	 * Provides the URL to the image on the CDN or fails back to the parent method as appropriate
	 *
	 * @return string
	 */
	public function getImageUrl()
    {
    	$path = false;
        if ($image = $this->getImage()) {
            $path = Mage::getBaseDir('media').'/catalog/category/'.$image;
        }
        
	    $cds = Mage::Helper('imagecdn')->factory(); 
	    
        if($path && $cds->useCdn()) {
        	$fileExists = $cds->fileExists($path);
        	if(!$fileExists) {
        		$cds->save($path, $path);
        	}
	    	$url = $cds->getUrl($path);
	    	if($url) {
	    		return $url;
	    	}
        }
    	return parent::getImageUrl();
    }
}