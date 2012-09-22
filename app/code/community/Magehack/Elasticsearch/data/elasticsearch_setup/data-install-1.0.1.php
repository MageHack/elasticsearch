<?php

$installer = $this;
/** @var $installer Mage_Sales_Model_Entity_Setup */

/**
 * Install default types from config
 */
$data = array();
$options = Mage::getConfig()->getNode('global/elasticsearch/types')->asArray();
foreach ($options as $type) {
    $data[] = array(
 	    'model_class' => $type['model_class'],
	    'name' => $type['name'],
	    'enabled' => $type['enabled'],
		'feed_class' => $type['feed_class'],
    );
}
$installer->getConnection()->insertArray(
    $installer->getTable('elasticsearch_etype'),
    array('model_class', 'name', 'enabled', 'feed_class'),
    $data
);