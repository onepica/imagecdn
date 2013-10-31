<?php

/**
 * Catalog product media gallery attribute backend model
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Wojciech Naruniec
 */

class OnePica_ImageCdn_Model_Catalog_Product_Attribute_Backend_Media extends Mage_Catalog_Model_Product_Attribute_Backend_Media
{

    /**
     * Move image from temporary directory to normal
     *
     * @param string $file
     * @return string
     */
    protected function _moveImageFromTmp($file)
    {
        $ioObject = new Varien_Io_File();
        $destDirectory = dirname($this->_getConfig()->getMediaPath($file));
        try {
            $ioObject->open(array('path'=>$destDirectory));
        } catch (Exception $e) {
            $ioObject->mkdir($destDirectory, 0777, true);
            $ioObject->open(array('path'=>$destDirectory));
        }

        if (strrpos($file, '.tmp') == strlen($file)-4) {
            $file = substr($file, 0, strlen($file)-4);
        }
        $destFile = $this->_getUniqueFileName($file, $ioObject->dirsep());

        /** @var $storageHelper Mage_Core_Helper_File_Storage_Database */
        $storageHelper = Mage::helper('core/file_storage_database');

        if ($storageHelper->checkDbUsage()) {
            $storageHelper->renameFile(
                $this->_getConfig()->getTmpMediaShortUrl($file),
                $this->_getConfig()->getMediaShortUrl($destFile));

            $ioObject->rm($this->_getConfig()->getTmpMediaPath($file));
            $ioObject->rm($this->_getConfig()->getMediaPath($destFile));
        } else {
            $ioObject->mv(
                $this->_getConfig()->getTmpMediaPath($file),
                $this->_getConfig()->getMediaPath($destFile)
            );

            // add image to CDN
            $cds = Mage::Helper('imagecdn')->factory();
            if($cds->useCdn()) {
                $curpath = $this->_getConfig()->getMediaPath($destFile);
                $newpath = $cds->getRelative($this->_getConfig()->getMediaPath($destFile));

                // ommit file if it already exist in cdn
                if (!$cds->fileExists($newpath)) {
                    // save the image to the CDN
                    $result = $cds->save($newpath, $curpath); 
                }
            }
        }

        return str_replace($ioObject->dirsep(), '/', $destFile);
    }

} // Class Mage_Catalog_Model_Product_Attribute_Backend_Media End
