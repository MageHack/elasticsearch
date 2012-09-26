<?php

/**
 * @category   MageHack
 * @package    MageHack_Elasticsearch
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magehack_Elasticsearch_Model_Mysql4_Equery extends Mage_Core_Model_Mysql4_Abstract
{
	public function _construct()
	{
		$this->_init('elasticsearch/equery', 'equery_id');
	}
}