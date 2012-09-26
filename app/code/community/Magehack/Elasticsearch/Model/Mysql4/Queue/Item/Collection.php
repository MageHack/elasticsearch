<?php

/**
 * @category   MageHack
 * @package    MageHack_Elasticsearch
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magehack_Elasticsearch_Model_Mysql4_Queue_Item_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
		{
        parent::_construct();
        $this->_init('elasticsearch/queue_item');
    }

    protected function _initSelect()
    {
        return parent::_initSelect()->getSelect()
            ->join(
                $this->getTable('elasticsearch/etype'),
                'main_table.etype_id = elasticsearch_etype.etype_id'
            );
    }
}