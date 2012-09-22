<?php
/**
 * Observer model
 *
 * @category    GPMD
 * @package     Magehack_Elasticsearch
 * @copyright   
 * @license     
 */

/**
 *
 * @category    GPMD
 * @package     Magehack_Elasticsearch
 * @author      Carlo Tasca <dev@gpmd.net>
 */
class Magehack_Elasticsearch_Model_Observer {

	/**
	 *
	 * @return Magehack_Elasticsearch_Helper_Data
	 */
	protected function _getHelper(){
		if(!isset($this->_helper)){
			$this->_helper = Mage::helper('elasticsearch');
		}
		return $this->_helper;
	}
	
	/**
	 *
	 * @return Magehack_Elasticsearch_Model_Api_Elasticsearch 
	 */
	protected function _getElasticsearchApi(){
		if(!isset($this->_elasticsearch)){
			$this->_elasticsearch = Mage::getModel('elasticsearch/api_elasticsearch');
		}
		
		return $this->_elasticsearch;
	}
	
	public function __call($name, $arguments) {
		if($this->_getHelper()->isEnabled()){
			if(!method_exists($this, $name)){
				$this->_getHelper()->log(get_class($this)."::__call() Method '$name' does not exist on this class");
				Mage::throwException("Method '$name' does not exist on this class");
			}
			return call_user_func_array(array($this, $name), $arguments);
		}
	}

	/**
	 * Adds deleted products to the feed update queue
	 * 
	 * @param Varien_Event_Observer $observer 
	 */
	protected function catalogProductSaveBefore(Varien_Event_Observer $observer) {
		$product = $observer->getEvent()->getProduct();

		/** @TODO: Use a haschanged to work out if we need to add product to queue */
		
		$message = Magehack_Elasticsearch_Model_Queue_Item::MESSAGE_CHANGED;
		Mage::getModel('elasticsearch/queue')->addItem($product, $message);

		if($this->_getHelper()->isRealtime()){
			Mage::getModel('elasticsearch/feed_product')->generateAndPush();
		}
	}
	
	/**
	 * Adds deleted products to the feed update queue
	 * 
	 * @param Varien_Event_Observer $observer 
	 */
	protected function catalogProductDeleteBefore(Varien_Event_Observer $observer) {
		$product = $observer->getEvent()->getProduct();

		$message = Magehack_Elasticsearch_Model_Queue_Item::MESSAGE_UNPUBLISHED;
		Mage::getModel('elasticsearch/queue')->addItem($product, $message);

		if($this->_getHelper()->isRealtime()){
			Mage::getModel('elasticsearch/feed_product')->generateAndPush();
		}
	}

	/**
	 * Adds products to the feed update queue on stock item save
	 * 
	 * @param Varien_Event_Observer $observer 
	 */
	protected function catalogInventoryStockItemSaveAfter(Varien_Event_Observer $observer) {
		$stockItem = $observer->getEvent()->getItem();
		
        $trigger_fields = array(
            'product_id',
            'qty',
            'is_in_stock',
            'stock_id',
        );
		
		if($this->_getHelper()->hasChanged($stockItem, $trigger_fields)){
			$product = Mage::getModel('catalog/product')->load($stockItem->getProductId());
			$queue = Mage::getModel('elasticsearch/queue');

			$queue->addItem($product, Magehack_Elasticsearch_Model_Queue_Item::MESSAGE_CHANGED);

			// Add parent products as well
			$product->loadParentProductIds();
			foreach ($product->getParentProductIds() as $parentProductId) {
				$parentProduct = Mage::getModel('catalog/product')->load($parentProductId);
				$queue->addItem($parentProduct, Magehack_Elasticsearch_Model_Queue_Item::MESSAGE_CHANGED);
			}

			if($this->_getHelper()->isRealtime()){
				Mage::getModel('elasticsearch/feed_product')->generateAndPush();
			}
		}
	}

	/**
	 * Executed before an attribute save
	 * 
	 * @param Varien_Event_Observer $observer 
	 */
	protected function catalogEntityAttributeSaveBefore(Varien_Event_Observer $observer) {
		$attribute = $observer->getEvent()->getAttribute();

        $trigger_fields = array(
            'is_filterable',
            'is_searchable',
            'is_filterable_in_search',
            'used_for_sort_by',
            'is_visible_in_advanced_search',
			'use_in_autosuggest',
			'elasticsearch_query_boost',
			'elasticsearch_custom_map',
			'attribute_code',
			'backend_type',
			'frontend_input'
        );
		
		if($this->_getHelper()->hasChanged($attribute, $trigger_fields)){
			$feed_product = Mage::getModel('elasticsearch/feed_product');
			$this->_getElasticsearchApi()->recreateType($feed_product);
			
			$queue = Mage::getModel('elasticsearch/queue');
			$queue->addAllSupportedProducts();
			
			if($this->_getHelper()->isRealtime()){
				Mage::getModel('elasticsearch/feed_product')->generateAndPush();
			}
		}
	}

	/**
	 * Updates the Elasticsearch Schema after an attribute is deleted
	 * 
	 * @param Varien_Event_Observer $observer 
	 */
	protected function catalogEntityAttributeDeleteAfter(Varien_Event_Observer $observer) {
		$this->catalogEntityAttributeSaveBefore($observer);

		return $this;
	}
	
	/**
	 * Inject 'Use in Autosuggest' field to add/edit attribute form
	 * 
	 * @param Varien_Event_Observer $observer 
	 */
	protected function adminAttributeEdit(Varien_Event_Observer $observer){
		$form = $observer->getEvent()->getForm();
		if($form){
			$fieldset = $form->getElement('front_fieldset');
			$fieldset->addField('use_in_autosuggest', 'select', array(
				'name'     => 'use_in_autosuggest',
				'label'    => Mage::helper('catalog')->__('Use in Autosuggest'),
				'title'    => Mage::helper('catalog')->__('Use in Autosuggest'),
				'values'   => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
				'note'	   => Mage::helper('catalog')->__('Used in Elasticsearch, attribute is used for search box auto-suggestions'),
			));
		}
	}
	
	/**
	 * Catch system config changes so we can perform appropriate actions
	 * 
	 * @deprecated in favor of index button 
	 * 
	 * @param Varien_Event_Observer $observer 
	 */
	protected function adminSystemConfigChange(Varien_Event_Observer $observer){
		/** @TODO: Should be updated to check only fields that have changed and then perform re-index
		 * Will require a pre-dispatch event on the controller to gather old settings
		 * and then in this method get new settings and compare to old (via singleton?) 
		 */
		$config = Mage::getModel('adminhtml/config_data')->setSection('elasticsearch')->load();

		$this->_getHelper()->remap();
		
		if($this->_getHelper()->isScheduled($config) && $this->_getHelper()->getCronExpr($config)){
			$job_code = Magehack_Elasticsearch_Helper_Data::CRON_JOB_CODE;
			try {
				$schedule = Mage::getModel('cron/schedule');
				$schedule->setJobCode($job_code)
					->setCronExpr($this->_getHelper()->getCronExpr($config))
					->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)
					->save();
			} catch (Exception $e) {
				$this->_getHelper()->log(get_class($this)."::adminSystemConfigChange() Error saving cron. Exception:\n\t".$e->getMessage());
				Mage::throwException($e);
			}
			
			Mage::getConfig()->setNode('crontab/jobs/elasticsearch/schedule/cron_expr/', $this->_getHelper()->getCronExpr($config), TRUE);
			Mage::getConfig()->saveCache();
		}
		
		$this->_getHelper()->reindexAll();
		
	}

}