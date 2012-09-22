<?php

Class Magehack_Elasticsearch_Model_Etype extends Mage_Core_Model_Abstract {

	protected $_feedModel;

	public function _construct($model = NULL) {
		$this->_init('elasticsearch/etype');
		if ($model) {
			$this->setFeedModel($model);
			$this->setName(NULL);
		}
	}

	public function setFeedModel(Magehack_Elasticsearch_Model_Feed_Abstract $model) {
		$this->_feedModel = $model;
		return $this;
	}

	/**
	 *
	 * @return Magehack_Elasticsearch_Helper_Data
	 */
	protected function _getHelper() {
		if (!isset($this->_helper)) {
			$this->_helper = Mage::helper('elasticsearch');
		}
		return $this->_helper;
	}

	/**
	 * Get real model associated with this item
	 * 
	 * @return Magehack_Elasticsearch_Model_Feed_Abstract 
	 */
	public function getFeedModel() {
		if (!isset($this->_feedModel)) {
			$modelClass = (string) $this->getData('feed_class');
			$this->_feedModel = Mage::getModel($modelClass);
		}

		return $this->_feedModel;
	}

	/**
	 * Set the database field 'feed_class' for this item. Classname is converted
	 * to getModel() format
	 * 
	 * e.g. 
	 * 'Magehack_Elasticsearch_Model_Feed_Abstract' would set 'elasticsearch/feed_abstract'
	 * 
	 * @param Magehack_Elasticsearch_Model_Feed_Abstract $model
	 * @param boolean $save
	 * @return Magehack_Elasticsearch_Model_Etype 
	 */
	public function setFeedClass(Magehack_Elasticsearch_Model_Feed_Abstract $model = NULL, $save = TRUE) {
		if ($model) {
			$this->setFeedModel($model);
		}

		$this->setData('feed_class', $this->_getHelper()->generateClassString($this->getFeedModel()));

		if ($save) {
			$this->save();
		}

		return $this;
	}

	public function setName($name = NULL, $save = TRUE) {
		if ($name) {
			$this->setData('name', $name);
		} else {
			$this->setData('name', $this->getFeedModel()->getIndexName());
		}

		if ($save) {
			$this->save();
		}

		return $this;
	}

	public function setEnabled($save = TRUE) {
		$this->setData('enabled', 1);
		if ($save) {
			$this->save();
		}
		return $this;
	}

	public function setDisabled($save = TRUE) {
		$this->setData('enabled', 0);
		if ($save) {
			$this->save();
		}
		return $this;
	}

}