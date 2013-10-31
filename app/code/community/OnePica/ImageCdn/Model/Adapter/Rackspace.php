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


set_include_path(dirname(__FILE__).DS.'Rackspace'.PS.get_include_path());
require_once 'cloudfiles.php';

/**
 * CDN adapter for Rackspace Could Files
 */
class OnePica_ImageCdn_Model_Adapter_Rackspace extends OnePica_ImageCdn_Model_Adapter_Abstract
{	
	/**
	 * Sets the value for cURL's CURLOPT_FOLLOWLOCATION. This has been an issue
	 * with servers that use the open_basedir PHP config setting. Not all adapters
	 * need this functionality, so we can turn it off one-by-one with this value.
	 *
	 * @return bool
	 */
	protected $curlFollowLocation = false;
	
	/**
	 * Connection handle
	 */
	private $conn;
	
	/**
	 * Creates a singleton connection handle
	 *
	 * @return CF_Connection
	 */
	private function auth() { 
		if(is_null($this->conn)) {
	    	$username = Mage::getStoreConfig('imagecdn/rackspace/username');
	    	$api_key = Mage::getStoreConfig('imagecdn/rackspace/api_key');
	    	$auth = new CF_Authentication($username, $api_key);
	    	$auth->ssl_use_cabundle();
	    	$auth->authenticate();
	    	if($auth->authenticated()) { 
		    	$this->conn = new CF_Connection($auth);
		    	$this->conn->ssl_use_cabundle();
				return $this->conn;
	    	} else {
	    		return false;
	    	}
		} else {
			return $this->conn;
		}
	}
	
	/**
	 * Saves the image to the remote server.
	 *
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @param string $tempfile		temp file name to upload
	 * @return bool
	 */
    protected function _save($relFilename, $tempfile) {
    	$container = Mage::getStoreConfig('imagecdn/rackspace/container');
    	$cont = $this->auth()->get_container($container);
    	$file = $cont->create_object(substr($relFilename, 1));
    	$result = $file->load_from_filename($tempfile);
        return $result ? true : false;    	
    }
	
	/**
	 * Deletes the image from the remote server
	 *
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @return bool
	 */
    protected function _remove($relFilename) {
    	$container = Mage::getStoreConfig('imagecdn/rackspace/container');
    	$cont = $this->auth()->get_container($container);
    	$file = $cont->get_object(substr($relFilename, 1));
    	$result = $cont->delete_object($file);
        return $result ? true : false;    	
    }
    
	/**
	 * Clears all if the images from the remote server
	 *
	 * @return bool
	 */
    protected function _clearCache() { 
      	$container = Mage::getStoreConfig('imagecdn/rackspace/container');
      	$cont = $this->auth()->get_container($container);
     	// it's changed to delete only cache files,
     	// not full size images now (wojtek)
     	$files = $cont->get_objects(0, null, 'catalog/product/cache');
      	if(count($files)) {
  			foreach($files as $file) {
  				$cont->delete_object($file);
			}
		}
    }
	
	/**
	 * Creates a full URL to the image on the remote server
	 *
	 * Added ability to use SSL for rackspace CDN as they started supporting
	 * that feature. I've also removed useCdn() method from the end of this class. (wojtek) 
	 *
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @return string
	 */
	public function getUrl($filename) {
		$type = Mage::app()->getStore()->isCurrentlySecure() ? 'secure_base_url' : 'base_url';
		$base_url = Mage::getStoreConfig('imagecdn/rackspace/' . $type);
		$filename = $base_url . $this->getRelative($filename);
		return str_replace('\\', '/', $filename);
	}
    
	/**
	 * Observer function to check log in credentials.
	 *
	 * @return bool
	 */
	protected function _onConfigChange() {
		try {
			$login = $this->auth();
		} catch(Exception $e) {
			$login = false;
		}
		
		$session = Mage::getSingleton('adminhtml/session');	
		
		if(!function_exists('finfo_open')) {
			$session->addNotice('The Rackspace Cloud Files API suggests the use of PEAR\'s FileInfo module (for Content-Type detection). You may be able to continue without it, but it is recommended.');
		}
		if(!function_exists('mb_substr')) {
			$session->addWarning('The Rackspace Cloud Files API requires PHP enabled with mbstring (multi-byte string) support. You must enable it to use Rackspace Cloud Files with this site.');
		}
		
		if($login === false) {
			$session->addWarning('The API credentials you provided for Rackspace Cloud Files were denied. You must enter new credentials to use Rackspace Cloud Files with this site.');
		
		} else {
			try {
				$container = Mage::getStoreConfig('imagecdn/rackspace/container');
				$cont = $this->auth()->get_container($container);
				
				if(!$cont->is_public()) {
					try {
						$session->addNotice('The container you entered is not a public container. Attempting to make it public...');
						if($cont->make_public()) {
							$session->addNotice('The container you entered was successfully made public.');
						} else {
							throw new Exception('The container you entered could not be make public');
						}
					} catch(Exception $e) {
						$session->addWarning('The container you entered could not be make public. A public container is required to use Rackspace Cloud Files with this site.');
					}
				}
			} catch(NoSuchContainerException $e) {
				$session->addWarning('The container name you entered is not found. You must enter a valid container name to use Rackspace Cloud Files with this site.');
			}
		}
	}
	
	/**
	 * Download the image from the remote server.
	 * 
	 * @param string $filename	filename
	 * @return bool
	 */
	public function download($filename) {
		$filename = $this->getRelative($filename);
		try {
			$result = $this->_download($filename);
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}

	/**
	 * Download the image from the remote server.
	 *
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @return bool
	 */
	protected function _download($relFilename) {
		$container = Mage::getStoreConfig('imagecdn/rackspace/container');
		$cont = $this->auth()->get_container($container);
		$file = $cont->get_object(substr($relFilename, 1));
		$base = str_replace('\\', '/', Mage::getBaseDir('media'));
		if (!file_exists(dirname($base . '/' . $file->name))) {
			mkdir(dirname($base . '/' . $file->name), 0777, true);
		}
		$result = $file->save_to_filename($base . '/' . $file->name);
		return $result ? true : false;
	}
}
