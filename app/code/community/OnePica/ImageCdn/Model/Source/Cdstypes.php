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

class OnePica_ImageCdn_Model_Source_CdsTypes
{    
    /**
	 * Gets the list of CDNs for the admin config dropdown. Allowing $types to
	 * be passed in enables third-paries to easily add adapters to the results
	 *
	 * @param array $types
	 * @return array
	 */
    public function toOptionArray($types = array())
    {    	
    	//List commercial CDNs
        $types[] = array('value'=>'imagecdn/adapter_amazons3', 'label'=>'Amazon S3/CloudFront');
        $types[] = array('value'=>'imagecdn/adapter_rackspace', 'label'=>'Rackspace Cloud Files');
        $types[] = array('value'=>'imagecdn/adapter_highwinds', 'label'=>'Highwinds');
        
        //Sort commercial CDNs alphabetically
        usort($types, array('OnePica_ImageCdn_Model_Source_CdsTypes', 'sort'));
         	
    	if(function_exists('ssh2_connect')) {
    		array_unshift($types, array('value'=>'imagecdn/adapter_sftp', 'label'=>'SFTP'));
    	} else {
    		array_unshift($types, array('value'=>'imagecdn/adapter_sftp', 'label'=>'SFTP (not available)', 'style'=>'color:#aaa;'));
    	}   
    	if(function_exists('ftp_ssl_connect')) {
    		array_unshift($types, array('value'=>'imagecdn/adapter_ftps', 'label'=>'FTPS'));
    	} else {
    		array_unshift($types, array('value'=>'imagecdn/adapter_ftps', 'label'=>'FTPS (not available)', 'style'=>'color:#aaa;'));
    	}   
    	if(function_exists('ftp_connect')) {
    		array_unshift($types, array('value'=>'imagecdn/adapter_ftp', 'label'=>'FTP'));
    	} else {
    		array_unshift($types, array('value'=>'imagecdn/adapter_ftp', 'label'=>'FTP (not available)', 'style'=>'color:#aaa;'));
    	}    	 	
        
    	if(function_exists('curl_init')) {
    		array_unshift($types, array('value'=>'', 'label'=>'-- Disabled --'));
    	} else {
    		$types[] = array('value'=>'', 'label'=>'Disabled - Enable PHP cURL for more options');
    		return $types;
    	}
    	
        $types[] = array('value'=>'imagecdn/adapter_coralcdn', 'label'=>'DNS-based CDN (like CoralCDN)');
        return $types;
    }
    
    /**
	 * Custom comparison function for usort
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
    public static function sort($a, $b)
    {
        if($a['label'] == $b['label']) {
            return 0;
        }
        return ($a['label'] > $b['label']) ? +1 : -1;
    }
}
