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
 * CDN adapter for FTPS
 */
class OnePica_ImageCdn_Model_Adapter_Ftps extends OnePica_ImageCdn_Model_Adapter_Ftp
{
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
	    	$this->conn = Mage::getModel('imagecdn/varien_ftp');
	    	$this->conn->open(array('host'=>$ftp_host,'user'=>$ftp_user,'password'=>$ftp_pass,'ssl'=>'1','port'=>$ftp_port));
		}
		return $this->conn;
	}
    
	/**
	 * Observer function to check log in credentials and to verify that the PHP FTP and OpenSSL modules are installed.
	 *
	 * @return bool
	 */
	protected function _onConfigChange() {
		if(!function_exists('ftp_ssl_connect')) {
			$session = Mage::getSingleton('adminhtml/session');	
			$session->addWarning('This server does not support FTPS. You must change your settings or enable the FTP and OpenSSL modules on your server. See the <a href="http://us.php.net/manual/en/function.ftp-ssl-connect.php" target="_blank">PHP manual</a> for more infomation.');
			return false;		
		}
		
		try {
			$login = $this->auth();
		} catch(Exception $e) {
			$login = false;
		}
		
		if($login == false) {
			$session = Mage::getSingleton('adminhtml/session');	
			$session->addWarning('The credentials you provided for FTPS were denied. You must enter new credentials to use FTPS with this site.');
		}
	}
    
}