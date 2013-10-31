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
    	$files = $cont->get_objects();
    	if(count($files)) {
			foreach($files as $file) {
				$cont->delete_object($file);
			}
		}
    }
	
	/**
	 * Creates a full URL to the image on the remote server
	 *
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @return string
	 */
    public function getUrl($filename) {
    	$base_url = Mage::getStoreConfig('imagecdn/rackspace/base_url');
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
	 * If currently over HTTPS do not use the CDN to serve images since Rackspace doesn't support it
	 * 
	 * @return bool
	 */
	public function useCdn() {
    	if(Mage::app()->getStore()->isCurrentlySecure()) {
    		return false;
    	}
		return parent::useCdn();
	}
    
}