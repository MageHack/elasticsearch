<?php

class Magehack_Elasticsearch_Model_Mysql4_Etype extends Mage_Core_Model_Mysql4_Abstract{
    public function _construct(){    
        $this->_init('elasticsearch/etype', 'etype_id');
    }
}