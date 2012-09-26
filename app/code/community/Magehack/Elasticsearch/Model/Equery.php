<?php

/**
 * @category   MageHack
 * @package    MageHack_Elasticsearch
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magehack_Elasticsearch_Model_Equery extends Mage_Core_Model_Abstract
{
	public function _construct($model = NULL)
	{
		$this->_init('elasticsearch/equery');

	}
}