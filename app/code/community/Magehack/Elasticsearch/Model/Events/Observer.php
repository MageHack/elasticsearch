<?php

/**
 * @category   MageHack
 * @package    MageHack_Elasticsearch
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author      Carlo Tasca <dev@gpmd.net>
 */
class Magehack_Elasticsearch_Model_Events_Observer
{
	/**
	 * Inject 'Use in Autosuggest' field to add/edit attribute form
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function adminAttributeEdit(Varien_Event_Observer $observer)
	{
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
	 *
	 * @return Magehack_Elasticsearch_Model_Api_Elasticsearch
	 */
	protected function _getElasticsearchApi()
	{
		if(!isset($this->_elasticsearch)){
			$this->_elasticsearch = Mage::getModel('elasticsearch/api_elasticsearch');
		}

		return $this->_elasticsearch;
	}

	/**
	 *
	 * @return Magehack_Elasticsearch_Helper_Data
	 */
	protected function _getHelper()
	{
		if(!isset($this->_helper)){
			$this->_helper = Mage::helper('elasticsearch');
		}
		return $this->_helper;
	}

	/**
	 * Adds deleted products to the feed update queue
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function catalogProductDeleteBefore(Varien_Event_Observer $observer)
	{
		$product = $observer->getEvent()->getProduct();

		$message = Magehack_Elasticsearch_Model_Queue_Item::MESSAGE_UNPUBLISHED;
		Mage::getModel('elasticsearch/queue')->addItem($product, $message);

		// Don't re-index everything everytime something is deleted
//		if($this->_getHelper()->isRealtime()){
//			Mage::getModel('elasticsearch/feed_product')->generateAndPush();
//		}
	}

	/**
	 * Adds products to the feed update queue on stock item save
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function catalogInventoryStockItemSaveAfter(Varien_Event_Observer $observer)
	{
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

//			if($this->_getHelper()->isRealtime()){
//				Mage::getModel('elasticsearch/feed_product')->generateAndPush();
//			}
		}
	}

	/**
	 * Updates the Elasticsearch Schema after an attribute is deleted
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function catalogEntityAttributeDeleteAfter(Varien_Event_Observer $observer)
	{
		$this->catalogEntityAttributeSaveBefore($observer);

		return $this;
	}

	/**
	 * Queue product if any of its attrbitue trigger fields are changed
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function catalogEntityAttributeSaveBefore(Varien_Event_Observer $observer)
	{
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
	 * Re-index elasticsearch when any other reindex process occurs
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function reindex(Varien_Event_Observer $observer)
	{
		$helper = Mage::helper('elasticsearch')->remapReindexAll();
	}
}