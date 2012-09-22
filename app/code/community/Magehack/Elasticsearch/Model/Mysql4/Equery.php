<?php

class Magehack_Elasticsearch_Model_Mysql4_Equery extends Mage_Core_Model_Mysql4_Abstract {

	public function _construct() {
		$this->_init('elasticsearch/equery', 'equery_id');
	}
}