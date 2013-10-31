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
 * CDN adapter for Coral CDN.
 * All the methods in the class are not used since Coral is am on-demand service.
 */
class OnePica_ImageCdn_Model_Adapter_CoralCdn extends OnePica_ImageCdn_Model_Adapter_Abstract
{
    public function save($relFilename, $tempfile) {
        return false;
    }	
    protected function _save($relFilename, $tempfile) {
        return false;    	
    }
	
    public function remove($relFilename) {
    	return false;
    }
    protected function _remove($relFilename) {
    	return false;
    }
    
    public function clearCache() {
    	return true;
    }
    protected function _clearCache() {
    	return true;
    }
    
    public function fileExists($filename) {
    	return file_exists($filename);
    }
	
	/**
	 * Creates a full URL to the image on the remote server
	 *
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @return string
	 */
    public function getUrl($filename) {
	    $filename = '/media' . $this->getRelative($filename);
	    $var = Mage::app()->getStore()->isCurrentlySecure() ? 'imagecdn/coralcdn/url_base_secure' : 'imagecdn/coralcdn/url_base';
	    return Mage::getStoreConfig($var) . $filename;  
    }
	
	/**
	 * If there is no secure base URL do not use the CDN to serve images
	 * 
	 * @return bool
	 */
	public function useCdn() {
    	if(Mage::app()->getStore()->isCurrentlySecure()) {
    		$url_base_secure = Mage::getStoreConfig('imagecdn/coralcdn/url_base_secure');
    		if(empty($url_base_secure)) {
    			return false;
    		}
    	}
		return parent::useCdn();
	}
    
	protected function _onConfigChange() {
		return true;
	}
    
}