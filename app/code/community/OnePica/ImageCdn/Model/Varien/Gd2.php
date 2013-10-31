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
 * Extends normal GD lib
 */
class OnePica_ImageCdn_Model_Varien_Gd2 extends Varien_Image_Adapter_Gd2
{	
	/**
	 * Hijacks the normal GD2 save method to add ImageCDN hooks. Fails back to parent method
	 * as appropriate.
	 *
	 * @param string $destination
	 * @param string $newName
	 * @return none
	 */
    public function save($destination=null, $newName=null) {
    	$cds = Mage::Helper('imagecdn')->factory();
    	$compression = Mage::getStoreConfig('imagecdn/general/compression');
    	    	
    	if($cds->useCdn()) {
    		$temp = tempnam(sys_get_temp_dir(), 'cds');    		
	        parent::save($temp);
	        
	        //Compress images?
	        if($this->_fileType == IMAGETYPE_JPEG && $compression > 0) {
	        	$convert = round((9-$compression)*(100/8)); //convert to imagejpeg's scale
	        	call_user_func('imagejpeg', $this->_imageHandler, $temp, $convert);
	        } elseif($this->_fileType == IMAGETYPE_PNG && $compression > 0) {
	        	$convert = round(($compression-1)*(9/8)); //convert to imagepng's scale
	        	call_user_func('imagepng', $this->_imageHandler, $temp, $convert);
	        }
    		
	        $filename = ( !isset($destination) ) ? $this->_fileName : $destination;
	        if( isset($destination) && isset($newName) ) {
	            $filename = $destination . "/" . $filename;
	        } elseif( isset($destination) && !isset($newName) ) {
	            $info = pathinfo($destination);
	            $filename = $destination;
	            $destination = $info['dirname'];
	        } elseif( !isset($destination) && isset($newName) ) {
	            $filename = $this->_fileSrcPath . "/" . $newName;
	        } else {
	            $filename = $this->_fileSrcPath . $this->_fileSrcName;
	        }
			
			if($cds->save($filename, $temp)) {
				@unlink($temp);
			} else {
				if(!is_writable($destination)) {
					try {
						$io = new Varien_Io_File();
						$io->mkdir($destination);
					} catch (Exception $e) {
						throw new Exception("Unable to write file into directory '{$destinationDir}'. Access forbidden.");
					}
				}
				@rename($temp, $filename);
				@chmod($filename, 0644);
			}
			
    	} else {
			return parent::save($destination, $newName);
    	}    	
    	
    }
}