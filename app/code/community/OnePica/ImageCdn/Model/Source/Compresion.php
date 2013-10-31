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

class OnePica_ImageCdn_Model_Source_Compresion
{
    /**
	 * Creates compression options for the admin config dropdown
	 *
	 * @return array
	 */
    public function toOptionArray()
    {
    	$return = array();
    	$return[0] = '-- Use default --';
    	for($x=1; $x<=9; $x++) $return[$x] = $x;
        return $return;
    }
}
