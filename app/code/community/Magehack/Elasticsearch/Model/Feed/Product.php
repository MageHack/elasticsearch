<?php

/**
 * Magento
 *
 *
 * @category    GPMD
 * @package     Magehack_Elasticsearch
 * @author      Carlo Tasca
 */

/**
 * Catalog layer category filter
 *
 * @category    GPMD
 * @package     Magehack_Elasticsearch
 * @author      Carlo Tasca
 */
class Magehack_Elasticsearch_Model_Feed_Product extends Magehack_Elasticsearch_Model_Feed_Abstract
{

	protected static $attributes = array();

	protected $_type = 'product';

	protected $_attributeMapOverrides = array();

	/**
	 * Allows overriding of any default attribute mappings,
	 * key on attribute code see product feed attribute for value examples.
	 *
	 * e.g. $name = 'sku', $values = array('_getType' => 'integer', '_nullValue' => 000000)
	 *
	 * @see Magehack_Elasticsearch_Model_Feed_Product_Attribute
	 *
	 * @param string $name
	 * @param array $values
	 */
	public function setAttributeMapOverride($name, $values)
	{
		$this->_attributeMapOverrides[$name] = $values;
	}

	public function getAttributeMapOverrides()
	{
		return $this->_attributeMapOverrides;
	}

	/**
	 * Prefix of model events names
	 *
	 * @var string
	 */
	protected $_eventPrefix = 'elasticsearch_feed_product';

	public function getMapping()
	{
		$attributes = Mage::getResourceModel('catalog/product_attribute_collection');
		$this->getMap()->setParam('type', 'object');
		$props = array();

		Mage::dispatchEvent($this->_eventPrefix . '_generate_default_map_before', array("feed_product" => $this, "data" => $props, "attributes" => $attributes));

		$this->_publishOverrides();

		foreach ($attributes as $attribute) {
			$this->_formatAttribute($props, $attribute, 'type');
		}

		Mage::dispatchEvent($this->_eventPrefix . '_generate_default_map_after', array("feed_product" => $this, "data" => $props));

		return $props;
	}

	protected function _publishOverrides()
	{
		Mage::unregister("elasticsearch_feed_product_attr_overrides");
		Mage::register("elasticsearch_feed_product_attr_overrides", $this->getAttributeMapOverrides());
	}

	protected function _organiseData($items)
	{
		$this->_feedArray = array();
		if ($this->_getHelper()->getStaticMembersReset()) {
			Mage::helper('elasticsearch')->log('Resetting static attributes');

			self::$attributes = array();
		}
		foreach ($items as $item) {
			$doc = new Elastica_Document($item->getData('model_id'), array(), $this->getTypeName(), $this->getIndexName());
			// If we are updating we have to delete the item first, delete's don't throw errors if ID doesn't exist
			$this->_feedArray[] = array(
				"delete" => $doc->toArray()
			);
			switch ($item->getData('message')) {
				case Magehack_Elasticsearch_Model_Queue_Item::MESSAGE_CHANGED:
					$this->_feedArray[] = array(
						"create" => $doc->toArray()
					);
					$this->_feedArray[] = $this->_prepareProductAttributes($item);
					break;
				default:
					$this->_addSkipped($item->getData('queue_item_id'));
					Mage::helper('elasticsearch')->log(get_class($this) . "::_organiseData() Skipping queue item '{$item->getData('queue_item_id')}' with type '{$item->getData('model_class')}' and message '{$item->getData('message')}'");
					break;
			}
			$item->markAsProcessed();
		}

		Mage::dispatchEvent($this->_eventPrefix . '_organise_data', array("feed_product" => $this, "feed_array" => $this->_feedArray));
	}

	protected function _prepareProductAttributes(Magehack_Elasticsearch_Model_Queue_Item $item)
	{
		$data = array();
		$product = $item->getModel();

		$static_key = $this->_eventPrefix . '_' . $product->getData('entity_type_id') . '_' . $product->getData('attribute_set_id') . '_' . $product->getData('type_id');

		if (array_key_exists($static_key, self::$attributes)) {
			$attributes = self::$attributes[$static_key];
		} else {
			$attributes = $product->getAttributes();
			self::$attributes[$static_key] = $attributes;
		}
		Mage::dispatchEvent($this->_eventPrefix . '_prepare_product_attributes_before', array("feed_product" => $this, "product" => $product, "data" => $data));

		foreach ($attributes as $attribute) {
			$this->_formatAttribute($data, $attribute, "create", $product);
		}

		// Inject stock info
		$data['store_id'] = $product->getStockItem()->getData('store_id');
		//$data['website_id'] = Mage::app()->getStore()->getWebsiteId();
		$data['stock_qty'] = (int) $product->getStockItem()->getData('qty');
		$data['url'] = $product->getProductUrl();
		$data['url_path'] = $product->getUrlPath();
		$data['categories'] = $product->getCategoryIds();
		$data['visibility'] = $product->getData('visibility');

		Mage::dispatchEvent($this->_eventPrefix . '_prepare_product_attributes_after', array("feed_product" => $this, "product" => $product, "data" => $data));
		//$this->_getHelper()->log('Data:' . var_export(json_encode($data), true));
		return $data;
	}

	protected function _formatAttribute(&$array, $attribute, $for = "type", $product = null)
	{
		$current_front = $attribute->getData('frontend_input');
		$current_back = $attribute->getData('backend_type');
		$current_name = $attribute->getAttributeCode();

		$product_attribute = new Magehack_Elasticsearch_Model_Feed_Product_Attribute($attribute);

		if (!$product_attribute->isIndexable($attribute)) {
			//Mage::helper('elasticsearch')->log(get_class($this)."::_formatAttribute() Attribute '{$current_name}' ignored");
			return NULL;
		}

		if ($product_attribute->getType()) {
			$map_function = "to" . ucwords($for) . "Map";
			if (method_exists($product_attribute, $map_function)) {
				if ($product) {
					$product_attribute->setProduct($product);
				}
				$array = $product_attribute->$map_function($array);
			}
		} else {
			Mage::helper('elasticsearch')->log(get_class($this) . "::_formatAttribute() Attribute '{$current_name}' has no type match back: '{$current_back}' front: '{$current_front}'");
			return NULL;
		}
	}

	protected function _getQueueItems()
	{
		$items = Mage::getModel('elasticsearch/queue')->getUnprocessedItems('product');
		return $items;
	}

	public function getAllItems()
	{
		$visibilities = Mage::getSingleton('catalog/product_visibility')->getVisibleInSiteIds();

		$products = Mage::getModel('catalog/product')->getCollection()
				->addAttributeToFilter('visibility', array('in' => $visibilities))
				->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
				->addAttributeToFilter('type_id', array('in' => $this->_getHelper()->getSupportedProductTypes()));

		return $products;
	}

}