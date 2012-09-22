<?php

Class Magehack_Elasticsearch_Model_Source_Schedule{
	
	const TYPE_SCHEDULED = 'scheduled';
	const TYPE_REALTIME = 'realtime';
	
	public function toOptionArray(){
        if (!$this->_options) {
            $this->_options = array(
				array(
                    'value' => self::TYPE_REALTIME,
                    'label' => Mage::helper('elasticsearch')->__('Realtime'),
				),
                array(
                    'value' => self::TYPE_SCHEDULED,
                    'label' => Mage::helper('elasticsearch')->__('Scheduled'),
                )
            );
        }
        return $this->_options;
	}
}