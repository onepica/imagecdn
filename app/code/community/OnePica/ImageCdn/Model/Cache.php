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
 * This class keeps track of which images have been verified on the CDN. This class also
 * acts as a driver or controller for the database version of this class.
 */
class OnePica_ImageCdn_Model_Cache
{
	private static $instance;
	private static $cds_cache_method;
	private static $cds_cache_ttl;
	private static $cachefile;
	private static $cache;
	
	
	/**
	 * Loads all of the config variables and calls init()
	 *
	 * @return OnePica_ImageCdn_Model_Cache
	 */
	public function __construct() 
	{
    	self::$cds_cache_method = intval(Mage::getStoreConfig('imagecdn/general/cache_method'));
    	self::$cds_cache_ttl = floatval(Mage::getStoreConfig('imagecdn/general/cache_ttl'));
    	self::$cachefile = Mage::getBaseDir('cache') . DS . 'cdn.cache';
		$this->init();
  	}  
	
	/**
	 * Loads the current cache into memory
	 *
	 * @return none
	 */
	protected function init()
	{		
		//file system
		if (self::$cds_cache_method == 2) {
			if (file_exists(self::$cachefile) && $contents = file_get_contents(self::$cachefile)) {
	    		self::$cache = unserialize($contents);
			}
	    	if (!is_array(self::$cache)) {
				self::$cache = array();
			}
		}
	}
	
	/**
	 * Checks the cache for a URL and determines if it has expired
	 *
	 * @param string $url
	 * @return bool
	 */
    public function checkCache($url) 
	{
    	$ttlSeconds = self::$cds_cache_ttl * 60;
		$cache_ttl = rand(intval($ttlSeconds*0.9), intval($ttlSeconds*1.1));
    	
		//database
		if(self::$cds_cache_method == 1) {			
			try {
				$db = Mage::getModel('imagecdn/cachedb')->load($url, 'url');
				$lastchecked = strtotime($db->getLastChecked());
			} catch(Exception $e) {
				return false;
			}
			
        	$maxtime = intval($lastchecked) + ($cache_ttl * 60);
        	return (time()<$maxtime) ? true : false;
		}
		
		//file system
		if(self::$cds_cache_method == 2) {
			if(array_key_exists($url, self::$cache)) {
				$maxtime = self::$cache[$url] + ($cache_ttl * 60);
		    	return (time()<$maxtime) ? true : false;
			} else {
				return false;
			}
		}
    }
	
	/**
	 * Records a newly verified URL by write to the database or file
	 *
	 * @param string $url
	 * @return bool
	 */
    public function updateCache($url) 
	{    	
		//database
		if(self::$cds_cache_method == 1) {					
			try {
				$db = Mage::getModel('imagecdn/cachedb')->load($url, 'url');
				$db->setUrl($url);
				$db->setLastChecked(date('Y-m-d H:i:s'));
				$db->save();
			} catch(Exception $e) {
				throw new Exception("Could not access caching database table.");
				return false;
			}
        	return true;
		}
    	
    	//file system
    	if (self::$cds_cache_method == 2) {
	    	self::init();
			self::$cache[$url] = time();
			file_put_contents(self::$cachefile, serialize(self::$cache));
    	}		
    }
	
	/**
	 * Walk through the entire cache to find and delete all expired entries
	 *
	 * @return bool
	 */
    public function cleanCache() 
	{
		$useCdn = Mage::Helper('imagecdn')->factory()->useCdn();
    	if(!$useCdn) {
    		return true;
    	}
    	
		//database
		if(self::$cds_cache_method == 1) {
			$mintime = time() - (self::$cds_cache_ttl * 60);
			$collection = Mage::getModel('imagecdn/cachedb')->getCollection()
							->addFieldToFilter('last_checked', array('lt'=>date('Y-m-d H:i:s', $mintime)))
							->load();
			if($collection->getSize() > 0) {			
		        foreach ($collection->getIterator() as $record) {
		            $record->delete();
		        }
			}
		}
    	
    	//file system
    	if(self::$cds_cache_method == 2) {
    		if(count(self::$cache)) {
    			foreach(self::$cache as $url=>$timestamp) {
					$maxtime = self::$cache[$url] + (self::$cds_cache_ttl * 60);
		    		if(time() > $maxtime) {
		    			unset(self::$cache[$url]);
		    		}
    			}
				file_put_contents(self::$cachefile, serialize(self::$cache));
    		}
    	}		
    }
	
	/**
	 * Delete the entire cache
	 *
	 * @return none
	 */
    public function clearCache()
	{
		file_put_contents(self::$cachefile, '');
		Mage::getModel('imagecdn/cachedb')->getCollection()->truncate();
    }
}
