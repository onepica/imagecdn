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
 * Feature enhancements of Varien_Io_Ftp
 */
class OnePica_ImageCdn_Model_Varien_Ftp extends Varien_Io_Ftp
{
    /**
     * Updates Varien's method that accepts the $recursive param but doesn't do anything
     * with it. This new method calls rmdirRecursive() if $recursive is true.
     *
     * @param string $dir
     * @param bool $recursive
     * @return boolean
     */
    public function rmdir($dir, $recursive=false)
    {
    	if($recursive) {
    		return $this->rmdirRecursive($dir);
    	} else
    		return @ftp_rmdir($this->_conn, $dir);
    }
    
    /**
     * Delete the provided directory and all its contents from the FTP-server.
     * Modified from http://www.php.net/ftp_rmdir
     *
     * @param string $dir
     * @return boolean
     */
	protected function rmdirRecursive($dir) {
	    $list = ftp_nlist($this->_conn, $dir);
	    if(empty($list)) {
	        $list = $this->rawListToNlist(ftp_rawlist($this->_conn, $dir), $dir . ( substr($dir, strlen($dir) - 1, 1) == "/" ? "" : "/" ) );
	    }
	        
	    if ($list[0] != $dir) {
	        $dir .= ( substr($dir, strlen($dir)-1, 1) == "/" ? "" : "/" );
	        foreach ($list as $item) {
		        if ($item != $dir.".." && $item != $dir.".") {
		            $r = $this->rmdirRecursive($dir.$item);
		            if($r === false) {
		            	return false;
		            }
		        }
	        }
	        return $this->rmdir($dir) ? true : false;
	    } else {	    	
	    	return $this->rm($dir) ? true : false;
	    }
	}
	
	/**
	* Convert a result from ftp_rawlist() to a result of ftp_nlist()
    * Modified from http://www.php.net/ftp_rmdir
	*
	* @param array $rawlist
	* @param string $dir
	* @return array
	*/
	protected function rawListToNlist($rawlist, $dir) {
	    $array = array();
	    foreach ($rawlist as $item) {
	        $filename = trim(substr($item, 55, strlen($item) - 55));
	        if ($filename != "." || $filename != "..") {
	        $array[] = $dir . $filename;
	        }
	    }
	    return $array;
	}
}
