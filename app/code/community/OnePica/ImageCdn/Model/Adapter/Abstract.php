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
 * Abstract class for all adapters to follow
 */
abstract class OnePica_ImageCdn_Model_Adapter_Abstract extends Varien_Object
{
	/**
	 * Sets the value for cURL's CURLOPT_FOLLOWLOCATION. This has been an issue
	 * with servers that use the open_basedir PHP config setting. Not all adapters
	 * need this functionality, so we can turn it off one-by-one with this value.
	 *
	 * @return bool
	 */
	protected $curlFollowLocation = true;
	
	/**
	 * Saves the image to the remote server
	 *
	 * @abstract
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @param string $tempfile		temp file name to upload
	 * @return bool
	 */
	abstract protected function _save($relFilename, $tempfile);
	
	/**
	 * Deletes the image from the remote server (not currently used)
	 *
	 * @abstract
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @return bool
	 */
	abstract protected function _remove($relFilename);
	
	/**
	 * Clears all if the images from the remote server
	 *
	 * @abstract
	 * @return bool
	 */
	abstract protected function _clearCache();
	
	/**
	 * Creates a full URL to the image on the remote server
	 *
	 * @abstract
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @return string
	 */
	abstract public function getUrl($filename);
	
	/**
	 * Observer function to perform some action when an admin config setting related to the
	 * this extension is changed. Typically, this will test log in credentials and create a
	 * notice message if the are incorrect.
	 *
	 * @abstract
	 * @return bool
	 */
	abstract protected function _onConfigChange();
	
	
	/**
	 * Calls the adapter-specific save method and updates the cache
	 *
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @param string $tempfile		temp file name to upload
	 * @return bool
	 */
	public function save($filename, $tempfile) {
	    $filename = $this->getRelative($filename);
	    try {
			$result = $this->_save($filename, $tempfile);
	    } catch (Exception $e) {
	    	$result = false;
	    }
		if($result) {
			$url = $this->getUrl($filename);
			Mage::getSingleton('imagecdn/cache')->updateCache($url);
		}
		return $result;
	}
	
	/**
	 * Calls the adapter-specific remove method and updates the cache
	 *
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @return bool
	 */
	public function remove($filename) {
	    $filename = $this->getRelative($filename);
	    try {
			$result = $this->_remove($filename);
	    } catch (Exception $e) {
	    	$result = false;
	    }
		if($result) {
			$url = $this->getUrl($filename);
			Mage::getSingleton('imagecdn/cache')->updateCache($url, true);
		}
		return $result;
	}
		
	/**
	 * Clears the cache
	 *
	 * @return none
	 */
	public function clearCache() {
		Mage::getSingleton('imagecdn/cache')->clearCache();
	    try {
			$this->_clearCache();
	    } catch (Exception $e) { }
	}
	
	/**
	 * Simple test to see if this ImageCDN extension is enabled
	 *
	 * @return bool
	 */
	public function useCdn() {
		return Mage::getStoreConfig('imagecdn/general/status') ? true : false;
	}
	
	/**
	 * Tests to see if an image on the current CDN is available. These results are cached for
	 * a configurable amount of time to reduce the number of cURLs needed.
	 *
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @return bool
	 */
	public function fileExists($filename) {
	    $filename = $this->getRelative($filename);
		$url = $this->getUrl($filename);
		
		$cached = Mage::getSingleton('imagecdn/cache')->checkCache($url);
		if($cached) {
			return true;
		}
        
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_HEADER, true);
		curl_setopt($c, CURLOPT_NOBODY, true);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, $this->curlFollowLocation);
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
		curl_exec($c);
		$httpcode = curl_getinfo($c, CURLINFO_HTTP_CODE);
		$size = curl_getinfo($c, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		curl_close($c);
		
		//should we test to see if file size greater than zero?
		$verifySize = Mage::getStoreConfig('imagecdn/general/cache_check_size');
		
		if ($httpcode == 200 && (!$verifySize || $size)) {
			Mage::getSingleton('imagecdn/cache')->updateCache($url);
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Takes a fairly raw file path (typically including the default media folder) and 
	 * converts it to a relative path. Also reduces double slashes to just one.
	 *
	 * @param string $filename
	 * @return string
	 */
	public function getRelative($filename) {
        $base = str_replace('\\', '/', Mage::getBaseDir('media'));
		$filename = str_replace('\\', '/', $filename);
	    return str_replace($base, '', $filename);
	}
	
	/**
	 * Observer function to perform some action when an admin config setting related to the
	 * this extension is changed. Typically, this will test log in credentials and create a
	 * notice message if the are incorrect.
	 *
	 * @abstract
	 * @return bool
	 */
	public function onConfigChange() {
		if($this->curlFollowLocation && ini_get('open_basedir')) {
			$session = Mage::getSingleton('adminhtml/session');
			$session->addWarning('You have an open_basedir restriction set in PHP. You must turn off this restriction or use a different CDN.');
		}
		
		return $this->_onConfigChange();
	}
}