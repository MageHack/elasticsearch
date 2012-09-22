<?php

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$installer->getConnection()->addColumn($installer->getTable('cms/page'), 'use_in_elasticsearch', 
		"tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER `meta_description`");

if (!$conn->tableColumnExists($this->getTable('catalog/eav_attribute'), 'use_in_autosuggest')) {
    $conn->addColumn($this->getTable('catalog/eav_attribute'), 'use_in_autosuggest', 'tinyint(1) unsigned NOT NULL DEFAULT 0');
}
if (!$conn->tableColumnExists($this->getTable('catalog/eav_attribute'), 'elasticsearch_query_boost')) {
    $conn->addColumn($this->getTable('catalog/eav_attribute'), 'elasticsearch_query_boost', 'float(8,4) signed NOT NULL DEFAULT 0.0000');
}
if (!$conn->tableColumnExists($this->getTable('catalog/eav_attribute'), 'elasticsearch_custom_map')) {
    $conn->addColumn($this->getTable('catalog/eav_attribute'), 'elasticsearch_custom_map', 'text NULL');
}


// query setup

$installer->run("

DROP TABLE IF EXISTS `{$this->getTable('elasticsearch_equery')}`;
CREATE TABLE `{$this->getTable('elasticsearch_equery')}` (
  `equery_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ejson` varchar(255) NOT NULL DEFAULT '',
  `query_id` int(11) unsigned NOT NULL,
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`equery_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

");

// Queue setup

$installer->run("

DROP TABLE IF EXISTS `{$this->getTable('elasticsearch_queue_item')}`;
CREATE TABLE `{$this->getTable('elasticsearch_queue_item')}` (
  `queue_item_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `model_id` int(11) unsigned NOT NULL,
  `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `processed` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `message` varchar(64) NOT NULL DEFAULT '',
  `etype_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`queue_item_id`),
  FOREIGN KEY (etype_id) REFERENCES elasticsearch_etype(etype_id) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

");

// Type table

$installer->run("

DROP TABLE IF EXISTS `{$this->getTable('elasticsearch_etype')}`;
CREATE TABLE `{$this->getTable('elasticsearch_etype')}` (
  `etype_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `feed_class` varchar(255) NOT NULL DEFAULT '',
  `model_class` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(128) NOT NULL DEFAULT '',
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`etype_id`),
  UNIQUE (`model_class`, `name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;");





//$installer->run ("
//ALTER TABLE {$this->getTable('elasticsearch_equery')} ADD CONSTRAINT `FK_CATALOGSEARCH_QUERY_ID` FOREIGN KEY (`query_id`) REFERENCES `{$this->getTable('catalogsearch_query')}` (`query_id`) ON DELETE CASCADE ON UPDATE CASCADE;
//");

$installer->endSetup();
//$installer->installEntities();