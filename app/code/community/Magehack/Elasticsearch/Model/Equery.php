<?php

Class Magehack_Elasticsearch_Model_Equery extends Mage_Core_Model_Abstract{
	
	public function _construct($model = NULL) {
		$this->_init('elasticsearch/equery');
		
	}
}