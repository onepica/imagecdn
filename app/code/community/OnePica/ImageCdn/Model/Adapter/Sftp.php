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


class OnePica_ImageCdn_Model_Adapter_Sftp extends OnePica_ImageCdn_Model_Adapter_Abstract
{	
	/**
	 * Connection handle
	 */
	private $conn;
	
	/**
	 * Connection handle
	 */
	private $sftp;
	
	/**
	 * Creates a singleton connection handle
	 *
	 * @return PHP resource
	 */
	private function auth() { 
		if(is_null($this->conn)) {
	    	$host = Mage::getStoreConfig('imagecdn/ftp/host');
	    	$user = Mage::getStoreConfig('imagecdn/ftp/user');
	    	$pass = Mage::getStoreConfig('imagecdn/ftp/pass');
	    	$port = Mage::getStoreConfig('imagecdn/ftp/port');
	    	$port = intval($port) ? intval($port) : 22;
	    	
	    	$this->conn = ssh2_connect($host, $port);
		    if(ssh2_auth_password($this->conn, $user, $pass)) {
		        return $this->conn;
		    } else {
	    		return false;
	    	}
		} else {
			return $this->conn;
		}
	}
	
	/**
	 * Saves the image to the remote server. If the folder structure doesn't exist, create it.
	 *
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @param string $tempfile		temp file name to upload
	 * @return bool
	 */
    protected function _save($relFilename, $tempfile) {
    	$base = Mage::getStoreConfig('imagecdn/ftp/base');
    	$remotePath = str_replace('\\', '/', str_replace('//', '/', '/'.$base.'/'.$relFilename));
    	
        ssh2_sftp_mkdir(ssh2_sftp($this->auth()), substr($remotePath, 0, strrpos($remotePath, '/')), 0777, true);        
		$result = ssh2_scp_send($this->auth(), $tempfile, $remotePath, 0644);		
        return $result ? true : false;    	
    }
	
	/**
	 * Deletes the image from the remote server
	 *
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @return bool
	 */
    protected function _remove($relFilename) {
    	$base = Mage::getStoreConfig('imagecdn/ftp/base');
    	$remotePath = str_replace('\\', '/', str_replace('//', '/', '/'.$base.'/'.$relFilename)); 
    	$result = ssh2_exec($this->auth(), 'rm -r ' . $remotePath);
        return $result ? true : false;
    }
    
	/**
	 * Clears all if the images from the remote server
	 *
	 * @return bool
	 */
    protected function _clearCache() {
    	$base = Mage::getStoreConfig('imagecdn/ftp/base');
    	$remotePath = str_replace('\\', '/', str_replace('//', '/', '/'.$base.'/catalog')); 
    	$result = ssh2_exec($this->auth(), 'rm -r ' . $remotePath);
    	return $result ? true : false;
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
	 * Observer function to check log in credentials and to verify that the SSH2 module is installed.
	 *
	 * @return bool
	 */
	protected function _onConfigChange() {  
		if(!function_exists('ssh2_connect')) {
			$session = Mage::getSingleton('adminhtml/session');	
			$session->addWarning('This server does not support SFTP. You must change your settings or enable the SSH2 modules on your server. See the <a href="http://us.php.net/manual/en/ssh2.installation.php" target="_blank">PHP manual</a> for more infomation.');
			return false;		
		}
		
		try {
			$login = $this->auth();
		} catch(Exception $e) {
			$login = false;
		}
		
		if($login === false) {
			$session = Mage::getSingleton('adminhtml/session');	
			$session->addWarning('The credentials you provided for the SFTP were denied. You must enter new credentials to use SFTP with this site.');
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