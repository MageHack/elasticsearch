<?php

class Magehack_Elasticsearch_Model_Mysql4_Queue_Item_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    public function _construct(){
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