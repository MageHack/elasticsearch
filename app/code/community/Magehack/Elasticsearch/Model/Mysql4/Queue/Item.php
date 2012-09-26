<?php

/**
 * @category   MageHack
 * @package    MageHack_Elasticsearch
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magehack_Elasticsearch_Model_Mysql4_Queue_Item extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('elasticsearch/queue_item', 'queue_item_id');
    }

		protected function _getLoadSelect($field, $value, $object)
		{
				$select = parent::_getLoadSelect($field, $value, $object);
				if($object->getEtypeId())
				{
					$select->join(
						$this->getTable('elasticsearch/etype'),
						$this->getMainTable() . '.etype_id = elasticsearch_etype.etype_id'
						)->limit(1);
				}

		return $select;
	}
}