<?php

class Magehack_Elasticsearch_Model_Mysql4_Etype_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract{
    public function _construct()
    {
        parent::_construct();
        $this->_init('elasticsearch/etype');
    }
	
}