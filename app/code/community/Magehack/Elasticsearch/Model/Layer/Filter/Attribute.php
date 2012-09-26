<?php

/**
 * @category   MageHack
 * @package    MageHack_Elasticsearch
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magehack_Elasticsearch_Model_Layer_Filter_Attribute extends Mage_Catalog_Model_Layer_Filter_Attribute
{
	/**
	 *
	 * @return Magehack_Elasticsearch_Helper_Data
	 */
	protected function _getHelper()
	{
		return Mage::helper('elasticsearch');
	}

	/**
	 * Apply attribute option filter to product collection
	 *
	 * @param   Zend_Controller_Request_Abstract $request
	 * @param   Varien_Object $filterBlock
	 * @return  Mage_Catalog_Model_Layer_Filter_Attribute
	 */
	public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
	{
		$filter = $request->getParam($this->_requestVar);
		if (is_array($filter)) {
			return $this;
		}
		$text = $this->_getOptionText($filter);
		if ($filter && $text) {
			// set elastic collection
			$attributeFilter = $this->_getHelper()->getElasticaFilterTerm($this->_requestVar, $this->getAttributeOptionValueById($filter));
			$this->_getHelper()->log('Searched attribute value in filter: ' . $this->getAttributeOptionValueById($filter));
			$collection = $this->_getHelper()->search(array($attributeFilter));
			$this->getLayer()->setElasticCollection($collection);
			$this->getLayer()->getState()->addFilter($this->_requestVar, $this->_createItem($text, $filter));
			$this->getLayer()->getState()->addElasticFilter ($this->_requestVar, $attributeFilter);
			$this->_items = array();
		}
		return $this;
	}

	/**
	 * Get data array for building attribute filter items
	 *
	 * @return array
	 */
	protected function _getItemsData()
	{
		$attribute = $this->getAttributeModel();
		$this->_requestVar = $attribute->getAttributeCode();

		$key = $this->getLayer()->getStateKey() . '_' . $this->_requestVar;
		$data = $this->getLayer()->getAggregator()->getCacheData($key);

		if ($data === null) {
			$options = $attribute->getFrontend()->getSelectOptions();
			$optionsCount = $this->_getResource()->getCount($this);
			$data = array();

			foreach ($options as $option) {
				if (is_array($option['value'])) {
					continue;
				}
				if (Mage::helper('core/string')->strlen($option['value'])) {
					// Check filter type
					if ($this->_getIsFilterableAttribute($attribute) == self::OPTIONS_ONLY_WITH_RESULTS) {
						if (!empty($optionsCount[$option['value']])) {
							$data[] = array(
								'label' => $option['label'],
								'value' => $option['value'],
								'count' => $optionsCount[$option['value']],
							);
						}
					} else {
						$data[] = array(
							'label' => $option['label'],
							'value' => $option['value'],
							'count' => isset($optionsCount[$option['value']]) ? $optionsCount[$option['value']] : 0,
						);
					}
				}
			}

			$tags = array(
				Mage_Eav_Model_Entity_Attribute::CACHE_TAG . ':' . $attribute->getId()
			);

			$tags = $this->getLayer()->getStateTags($tags);
			$this->getLayer()->getAggregator()->saveCacheData($data, $key, $tags);
		}
		return $data;
	}
	
	/**
	 * Gets option attribute value by id.
	 *
	 * Takes attribute id as argument and loops through options value
	 * to find a match.
	 *
	 * If found, it returns it.
	 *
	 * @param type $arg_id
	 * @return type
	 */
	public function getAttributeOptionValueById($arg_id)
	{
		$attribute = $this->getAttributeModel();
		$values = array();
		$valuesCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
				->setAttributeFilter($attribute->getId())
				->setStoreFilter(0, false)
				->load();
		foreach ($valuesCollection as $item) {
			if ($item->getId() == $arg_id) {
				return $item->getValue();
			}
		}
		return false;
	}

}
