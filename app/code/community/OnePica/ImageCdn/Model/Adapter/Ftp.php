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
 * CDN adapter for FTP
 */
class OnePica_ImageCdn_Model_Adapter_Ftp extends OnePica_ImageCdn_Model_Adapter_Abstract
{
	/**
	 * Connection handle
	 */
	private $conn;
	
	/**
	 * Constructor
	 *
	 * @return none
	 */
	public function __construct() {
		$this->curlFollowLocation = Mage::getStoreConfig('imagecdn/ftp/url_is_direct') ? true : false;
	}
	
	/**
	 * Automatically closes the FTP connection
	 *
	 * @return none
	 */
	public function __destruct() {
		if(!is_null($this->conn)) {
			$this->conn->close();
		}
	}
	
	/**
	 * Creates a singleton connection handle
	 *
	 * @return OnePica_ImageCdn_Model_Varien_Ftp
	 */
	private function auth() { 
		if(is_null($this->conn)) {
	    	$ftp_host = Mage::getStoreConfig('imagecdn/ftp/host');
	    	$ftp_port = Mage::getStoreConfig('imagecdn/ftp/port');
	    	$ftp_user = Mage::getStoreConfig('imagecdn/ftp/user');
	    	$ftp_pass = Mage::getStoreConfig('imagecdn/ftp/pass');
	    	$ftp_passive = Mage::getStoreConfig('imagecdn/ftp/passive');
	    	$this->conn = Mage::getModel('imagecdn/varien_ftp');
	    	$this->conn->open(array('host'=>$ftp_host,'user'=>$ftp_user,'password'=>$ftp_pass,'port'=>$ftp_port,'passive'=>$ftp_passive));
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
    	$cds_ftp_base = Mage::getStoreConfig('imagecdn/ftp/base') . '/';        
		$relFilename = str_replace('\\', '/', str_replace('//', '/', $cds_ftp_base.$relFilename));		
		
		$dir = explode('/', $relFilename);
		$filename = array_pop($dir);
        
        $currdir = '';
        for($x=0; $x<count($dir); $x++) {
        	if($dir[$x] == '') continue;
        	$currdir .= '/' . $dir[$x];
        	if($this->auth()->cd($currdir) === false) {
        		$this->auth()->mkdir($currdir);
        	}
        }
        
        $this->auth()->cd($currdir);
        $result = $this->auth()->write($filename, $tempfile);
        return $result ? true : false;    	
    }
	
	/**
	 * Deletes the image from the remote server
	 *
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @return bool
	 */
    protected function _remove($relFilename) {
    	$cds_ftp_base = Mage::getStoreConfig('imagecdn/ftp/base') . '/';
		$relFilename = str_replace('\\', '/', str_replace('//', '/', $cds_ftp_base.$relFilename));
		
		$dir = explode('/', $relFilename);
		$filename = array_pop($dir);
        
        $this->auth()->cd($currdir);
        $result = $this->auth()->rm($filename);
        return $result ? true : false;
    }
    
	/**
	 * Clears all if the images from the remote server
	 *
	 * @return bool
	 */
    protected function _clearCache() {
    	$cds_ftp_base = Mage::getStoreConfig('imagecdn/ftp/base');
		$cds_ftp_base = str_replace('\\', '/', str_replace('//', '/', $cds_ftp_base));
	    $this->auth()->rmdir($cds_ftp_base, true);
    }
	
	/**
	 * Creates a full URL to the image on the remote server
	 *
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @return string
	 */
    public function getUrl($filename) {
	    $filename = $this->getRelative($filename);
    	$var = Mage::app()->getStore()->isCurrentlySecure() ? 'imagecdn/ftp/url_base_secure' : 'imagecdn/ftp/url_base';
	    return str_replace('\\', '/', Mage::getStoreConfig($var).$filename);    	
    }
    
	/**
	 * Observer function to check log in credentials and to verify that the PHP FTP module is installed.
	 *
	 * @return bool
	 */
	protected function _onConfigChange() {
		if(!function_exists('ftp_connect')) {
			$session = Mage::getSingleton('adminhtml/session');	
			$session->addWarning('This server does not support FTP. You must change your settings or enable the FTP modules on your server. See the <a href="http://us.php.net/manual/en/ftp.installation.php" target="_blank">PHP manual</a> for more infomation.');
			return false;		
		}
		
		try {
			$login = $this->auth();
		} catch(Exception $e) {
			$login = false;
		}
		
		if($login == false) {
			$session = Mage::getSingleton('adminhtml/session');	
			$session->addWarning('The credentials you provided for FTP were denied. You must enter new credentials to use FTP with this site.');
		}
	}
	
	/**
	 * If there is no secure base URL do not use the CDN to serve images
	 * 
	 * @return bool
	 */
	public function useCdn() {
    	if(Mage::app()->getStore()->isCurrentlySecure()) {
    		$url_base_secure = Mage::getStoreConfig('imagecdn/ftp/url_base_secure');
    		if(empty($url_base_secure)) {
    			return false;
    		}
    	}
		return parent::useCdn();
	}
    
}