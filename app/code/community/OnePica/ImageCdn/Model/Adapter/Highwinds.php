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


set_include_path(dirname(__FILE__).DIRECTORY_SEPARATOR.'Highwinds'.PATH_SEPARATOR.get_include_path());
require_once 'api.php';

/**
 * CDN adapter for Highwinds CDN
 */
class OnePica_ImageCdn_Model_Adapter_Highwinds extends OnePica_ImageCdn_Model_Adapter_Abstract
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
	 * Connection handle
	 */
	private $conn;
	
	/**
	 * Creates a singleton connection handle
	 *
	 * @return hwCDN
	 */
	private function auth() { 
		if(is_null($this->conn)) {
	    	$this->conn = new hwCDN();	    	
			$this->conn->setApiKey(Mage::getStoreConfig('imagecdn/highwinds/apikey'));
			$this->conn->setUsername(Mage::getStoreConfig('imagecdn/highwinds/username'));
			$this->conn->setPassword(Mage::getStoreConfig('imagecdn/highwinds/password'));
		}
		return $this->conn;
	}
	
	/**
	 * Saves the image to the remote server. If the folder structure doesn't exist, create it.
	 *
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @param string $tempfile		temp file name to upload
	 * @return bool
	 */
    protected function _save($relFilename, $tempfile) {
    	$base_dir = Mage::getStoreConfig('imagecdn/highwinds/base_dir');
    	$directory = 'cds' . ($base_dir ? '/'.$base_dir : '') . $relFilename;    	
    	$directory = str_replace('\\', '/', str_replace('//', '/', $directory));
    	
		$dir = explode('/', $directory);
		$filename = array_pop($dir);
        
        $currdir = '';
        for($x=0; $x<count($dir); $x++) {
        	if($dir[$x] && $currdir) {
        		$this->auth()->execute('CD', array('path'=>$currdir, 'name'=>$dir[$x]));
        	}
        	$currdir .= '/' . $dir[$x];
        }
        
        //Rename the file locally with the corrent file name so we don't have
        //to make a second API call to rename it remotely
        $temp = sys_get_temp_dir() . $filename;
        copy($tempfile, $temp);        
    	$result = $this->auth()->upload($currdir.'/', $temp);
    	@unlink($temp);
    	
        return $result['result'] ? true : false;    	
    }
	
	/**
	 * Deletes the image from the remote server
	 *
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @return bool
	 */
    protected function _remove($relFilename) {
    	$base_dir = Mage::getStoreConfig('imagecdn/highwinds/base_dir');
    	$directory = '/cds' . ($base_dir ? '/'.$base_dir : '') . $relFilename;
    	$directory = str_replace('\\', '/', str_replace('//', '/', $directory));
    	
    	$pos = strrpos($directory, '/');
    	$tPath = substr($directory, 0, $pos);
    	$file = substr($directory, $pos+1);
    	
    	$fields = array(
    		'tPath' => $tPath,
    		'file' => $file
    	);    	
    	$result = $this->auth()->execute('DF', $fields);
        return $result['result'] ? true : false;
    }
    
	/**
	 * Clears all if the images from the remote server
	 *
	 * @return bool
	 */
    protected function _clearCache() {
    	$base_dir = Mage::getStoreConfig('imagecdn/highwinds/base_dir');
    	$directory = '/cds' . ($base_dir ? '/'.$base_dir : '') . '/catalog';    	
    	$directory = str_replace('\\', '/', str_replace('//', '/', $directory));
    	$this->auth()->execute('DD', array('path'=>$directory));
    }
	
	/**
	 * Creates a full URL to the image on the remote server
	 *
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @return string
	 */
    public function getUrl($filename) {
    	$base_url = Mage::getStoreConfig('imagecdn/highwinds/base_url');
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
		
		if($login === false) {
			$session = Mage::getSingleton('adminhtml/session');	
			$session->addWarning('The API credentials you provided for Highwinds were denied. You must enter new credentials to use Highwinds with this site.');
		}
	}
	
	/**
	 * If currently over HTTPS do not use the CDN to serve images since Highwinds doesn't support it
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