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
 * CDN adapter for Amazon S3
 */
class OnePica_ImageCdn_Model_Adapter_AmazonS3 extends OnePica_ImageCdn_Model_Adapter_Abstract
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
	private $s3;
	
	/**
	 * Creates a singleton connection handle
	 *
	 * @return OnePica_ImageCdn_Model_Adapter_AmazonS3_Wrapper
	 */
	private function auth() { 
		if(is_null($this->s3)) {
	    	$params['key'] = Mage::getStoreConfig('imagecdn/amazons3/access_key_id');
	    	$params['private_key'] = Mage::getStoreConfig('imagecdn/amazons3/secret_access_key');
	    	$params['bucket'] = Mage::getStoreConfig('imagecdn/amazons3/bucket');
	    	$this->s3 = Mage::getModel('imagecdn/adapter_amazons3_wrapper', $params);
			$test = $this->s3->listBuckets();
			return empty($test) ? false : $this->s3;
		} else {
			return $this->s3;
		}
	}
	
	/**
	 * Saves the image to the remote server
	 *
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @param string $tempfile		temp file name to upload
	 * @return bool
	 */
    protected function _save($relFilename, $tempfile) {   
    	$bucket = Mage::getStoreConfig('imagecdn/amazons3/bucket');
    	if(!$this->auth()) return false;
	    $result = $this->auth()->uploadFile($bucket, substr($relFilename, 1), $tempfile, true);
        return $result ? true : false;    	
    }
	
	/**
	 * Deletes the image from the remote server
	 *
	 * @param string $relFilename	path (with filename) from the CDN root
	 * @return bool
	 */
    protected function _remove($relFilename) {
    	$bucket = Mage::getStoreConfig('imagecdn/amazons3/bucket');
    	if(!$this->auth()) return false;
	    $result = $this->auth()->deleteObject($bucket, substr($relFilename, 1));
        return $result ? true : false;    	
    }
    
	/**
	 * Clears all if the images from the remote server
	 *
	 * @return bool
	 */
    protected function _clearCache() {
    	$bucket = Mage::getStoreConfig('imagecdn/amazons3/bucket');
    	if(!$this->auth()) return false;
		$files = $this->auth()->getBucketContents($bucket);
		if(count($files)) {
			foreach($files as $file=>$info) {
				$this->auth()->deleteObject($bucket, $file);
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
    	$type = Mage::app()->getStore()->isCurrentlySecure() ? 'url_base_secure' : 'url_base';
    	$base_url = Mage::getStoreConfig('imagecdn/amazons3/' . $type);
	    $filename = $base_url . $this->getRelative($filename);
	    return str_replace('\\', '/', $filename);
    }
    
	/**
	 * Observer function to check log in credentials and checks to see if the bucket exists.
	 *
	 * @return bool
	 */
	protected function _onConfigChange() {
		$login = $this->auth();
		if($login === false) {
			$session = Mage::getSingleton('adminhtml/session');
			$session->addWarning('The access identifiers you provided for Amazon S3 were denied. You must enter new credentials to use Amazon S3 with this site.');
			return false;
		}
		
		$bucket = Mage::getStoreConfig('imagecdn/amazons3/bucket');
		$buckets = $this->auth()->listBuckets();
		if(empty($bucket) || !in_array($bucket, $buckets)) {
			if(!$this->auth()->createBucket($bucket)) {
				$session = Mage::getSingleton('adminhtml/session');
				$session->addWarning('The bucket name your requested (' . $bucket . ') was not available. You must enter a new bucket name to use Amazon S3 with this site.');
				return false;
			}
		}
		return true;
	}
    
}