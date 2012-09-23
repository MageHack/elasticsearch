<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Elasticsearch
 *
 * @author Carlo Tasca
 */
class Magehack_Elasticsearch_Block_Elasticsearch extends Mage_Core_Block_Template {
	
	
	protected $_eHelper;
	
	public function __construct() {
		parent::__construct();
		$this->_eHelper = Mage::helper('elasticsearch');
	}
	public function _prepareLayout() {
		return parent::_prepareLayout();
	}
	
	public function getElasticsearch() {
		if (!$this->hasData('elasticsearch')) {
			$this->setData('elasticsearch', Mage::registry('elasticsearch'));
		}
		return $this->getData('elasticsearch');
	}
	
	protected function getEhelper () {
		return $this->_eHelper;
	}
}
