<?php

Class Magehack_Elasticsearch_Model_Queue extends Varien_Object {

	const LOCK_TIME = 3600;
	const LOCK_DIR = 'var/locks';

	protected $modelTypes = array(
		'product' => 'product',
		'page' => 'cmspage'
	);

	protected function _construct() {
		if (!file_exists($this->_getLockDir())) {
			if (!mkdir($this->_getLockDir(), 0777, TRUE)) {
				Mage::throwException("Could not create a lock directory: " . $this->_getLockDir());
			}
		}
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
	 * Add an item to the queue 
	 * 
	 * @param class $item
	 * @param string $message 
	 * @return bool
	 */
	public function addItem(Varien_Object $model, $message = NULL, $type = NULL) {
		$result = FALSE;

		// Determine the message if not provided
		if (!$message) {
			$message = $this->_getMessageForModel($model);
		}

		$item = Mage::getModel('elasticsearch/queue_item');
		$item->setData('model_id', $model->getId());
		$item->setData('message', $message);

		if (!$type) {
			$model_class = $this->_getHelper()->generateClassString($model);
			$type = $this->_guessModelType($model_class);
		}

		if (is_int($type)) {
			$type = Mage::getModel('elasticsearch/etype')->load($type);
		} elseif (is_string($type)) {
			if (strpos($type, '/')) {
				$type = Mage::getModel('elasticsearch/etype')->load($type, 'model_class');
			} else {
				$type = Mage::getModel('elasticsearch/etype')->load($type, 'name');
			}
		}

		$type_id = $type->getId();
		if ($type_id) {
			$item->setData('etype_id', $type_id);
		} else {
			$this->_getHelper()->log("No type found via: " . var_export($type, TRUE));
			Mage::throwException("No type found via: " . var_export($type, TRUE));
		}

		if ($item->save()) {
			$result = TRUE;
		}

		return $result;
	}

	/**
	 * Guess model type from magento class string
	 * 
	 * e.g. 'catalog/product' returns 'product'
	 * 
	 * @param string $model_class
	 * @return string 
	 */
	protected function _guessModelType($model_class) {
		$return_type = 'unknown';

		foreach ($this->modelTypes as $class_frag => $type) {
			if (strpos($model_class, $class_frag) !== FALSE) {
				$return_type = $type;
				break;
			}
		}

		return $return_type;
	}

	/**
	 * Add all supported products to the feed queue
	 * 
	 * Returns false if any of the products were not added
	 * 
	 * @return bool 
	 */
	public function addAllSupportedProducts() {
		$result = TRUE;

		$visibilities = Mage::getSingleton('catalog/product_visibility')->getVisibleInSiteIds();

		$products = Mage::getModel('catalog/product')->getCollection()
				->addAttributeToFilter('visibility', array('in' => $visibilities))
				->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
				->addAttributeToFilter('type_id', array('in' => $this->_getHelper()->getSupportedProductTypes()));

		foreach ($products as $product) {
			$added = $this->addItem($product);
			if (!$added) {
				$result = FALSE;
			}
		}

		return $result;
	}
	
	public function addAllItems(Varien_Data_Collection $collection){
		$result = TRUE;
		foreach ($collection as $item) {
			$added = $this->addItem($item, Magehack_Elasticsearch_Model_Queue_Item::MESSAGE_CHANGED);
			if (!$added) {
				$result = FALSE;
			}
		}
		
		return $result;
	}

	/**
	 * Determine a queue item message for a product being added to the queue
	 * 
	 * @TODO This only supports products at the moment!
	 * 
	 * @param Mage_Catalog_Model_Product $product
	 * @return string 
	 */
	protected function _getMessageForModel(Varien_Object $item) {
		$changed = TRUE;

		// Status
		if ($item->getStatus() != Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
			$changed = FALSE;
		}

		// Visibility
		if (!$item->isVisibleInSiteVisibility()) {
			$changed = FALSE;
		}

		// Stock
		$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($item);

		if (!$stockItem->getIsInStock() && !Mage::helper('cataloginventory')->isShowOutOfStock()) {
			$changed = FALSE;
		}

		if ($changed) {
			$message = Magehack_Elasticsearch_Model_Queue_Item::MESSAGE_CHANGED;
		} else {
			$message = Magehack_Elasticsearch_Model_Queue_Item::MESSAGE_UNPUBLISHED;
		}

		return $message;
	}

	/**
	 * Check if the product can be added to the queue
	 * 
	 * @param Mage_Catalog_Model_Product $product
	 * @return boolean 
	 */
	protected function _validateItem(Mage_Catalog_Model_Product $product) {
		$valid = TRUE;

		// Only allow supported product types
		if (in_array($product->getData('type_id'), Mage::helper('elasticsearch')->getSupportedProductTypes()) === FALSE) {
			$valid = FALSE;
		}

		return $valid;
	}

	/**
	 * Get the queue items collection
	 * 
	 * @return Magehack_Elasticsearch_Model_Mysql4_Queue_Item_Collection 
	 */
	public function getItems() {
		return Mage::getModel('elasticsearch/queue_item')->getCollection();
	}

	/**
	 * Delete queue items marked as processed
	 */
	public function deleteProcessedItems($type = NULL) {
		$items = $this->getItems()->addFieldToFilter('processed', 1);
		if ($type) {
			$items->addFieldToFilter('elasticsearch_etype.name', $type);
		}
		$items->walk('delete');
	}

	/**
	 * Get the collection of processed queue items
	 * 
	 * @return type 
	 */
	public function getProcessedItems($type = NULL) {
		$items = $this->getItems()->addFieldToFilter('processed', 1);
		if ($type) {
			$items->addFilter('elasticsearch_etype.name', $type);
		}
		return $items;
	}

	/**
	 * Get the collection of unprocessed queue items
	 * 
	 * @return type 
	 */
	public function getUnprocessedItems($type = NULL) {
		$items = $this->getItems()->addFieldToFilter('processed', 0);
		if ($type) {
			$items->addFilter('elasticsearch_etype.name', $type);
		}
		return $items;
	}

	/**
	 * Checks to see if queue is locked
	 * @return boolean
	 */
	public function isLocked() {
		return file_exists($this->_getLockFilePath());
	}

	/**
	 * Lock queue to prevent more than one handler running
	 * @return boolean
	 */
	public function lock() {
		if (!file_exists($this->_getLockFilePath())) {
			Mage::helper('elasticsearch')->log(get_class($this) . '::lock() Locking the queue');
			return touch($this->_getLockFilePath());
		} else {
			// Unlock if queue is locked for too long
			$lockTime = filemtime($this->_getLockFilePath());
			if ($lockTime < (time() - self::LOCK_TIME)) {
				Mage::helper('elasticsearch')->log(get_class($this)
						. '::lock() Detected a stale lock. Unlocking.', Zend_Log::WARN);
				$this->unlock();
				return $this->lock();
			} else {
				Mage::helper('elasticsearch')->log(get_class($this)
						. '::lock() The queue is locked by another process.', Zend_Log::WARN);
				return FALSE;
			}
		}
	}

	/**
	 * Unlock queue
	 * @return boolean
	 */
	public function unlock() {
//        Mage::helper('elasticsearch')->log(get_class($this) . '::unlock() Unlocking the queue');
		return @unlink($this->_getLockFilePath());
	}

	/**
	 * Get full path to the lock directory
	 * 
	 * @return type 
	 */
	protected function _getLockDir() {
		return Mage::getBaseDir() . '/' . self::LOCK_DIR;
	}

	/**
	 * Get full path to the lock file
	 * 
	 * @return type 
	 */
	protected function _getLockFilePath() {
		return $this->_getLockDir() . '/elasticsearch_queue.lock';
	}

}