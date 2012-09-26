<?php

/**
 * @category   MageHack
 * @package    MageHack_Elasticsearch
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magehack_Elasticsearch_Model_Options_Schedule
{
	const TYPE_SCHEDULED = 'scheduled';
	const TYPE_REALTIME = 'realtime';

	public function toOptionArray()
	{
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