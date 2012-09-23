<?php

Class Magehack_Elasticsearch_Model_Feed_Product_Attribute{

	const ATTRIBUTE_TYPE_FRONTEND = 'frontend';
	const ATTRIBUTE_TYPE_BACKEND = 'backend';
	const DATA_ARRAY_BACKEND = '_backend';

	protected $_searchTypes;
	protected $_elasticFrontType;
	protected $_elasticBackType;

	protected $_magBackType;
	protected $_magFrontType;

	protected $_name;

	protected $_attribute;

	protected $_product;

	protected $_customMap = false;

	protected $_attributeMap = array(
		"text" => array(
			"textarea" => array(
				"_getValue" => "TextArea"
			),
			"multiselect" => array(
				"_getValue" => "MultiSelect"
			),
			"_default" => array(
				"_getType" => "string",
				"_canMulti" => 1,
				"_nullValue" => NULL
			)
		),
		"varchar" => array(
			"multiselect" => array(
				"_getValue" => "MultiSelect",
			),
			"select" => array(
				"_getValue" => "Select",
			),
			"_default" => array(
				"_getType" => "string",
				"_canMulti" => 1,
				"_nullValue" => NULL
			),
			"_ignore" => array(
				"media_image",
				"gallery",
			)
		),
		"int" => array(
			"select" => array(
				"_getValue" => "Select",
				"_getType" => "string",
				"_canMulti" => 1,
				"_nullValue" => NULL
			),
			"boolean" => array(
				"_getType" => "boolean",
				"_canMulti" => 1,
			),
			"_default" => array(
				"_getType" => "integer",
				"_canMulti" => 0,
				"_nullValue" => 0
			)
		),
		"decimal" => array(
			"_default" => array(
				"_getType" => "float",
				"_canMulti" => 0,
				"_nullValue" => 0
			)
		),
		"datetime" => array(
			"_default" => array(
				"_getType" => "date",
				"_canMulti" => 0,
				"_format" => "YYYY-MM-dd HH:mm:ss"
			)
		),
		"static" => array(
			"select" => array(
				"_getValue" => "Select",
				"_canMulti" => 1
			),
			"date" => array(
				"_getType" => "date",
				"_format" => "YYYY-MM-dd HH:mm:ss"
			),
			"_default" => array(
				"_getType" => "string",
				"_canMulti" => 0
			)
		)
	);


	function __construct(Mage_Catalog_Model_Resource_Eav_Attribute $attribute) {

		$this->_attribute = $attribute;

		$searchable = ($attribute->getData('is_searchable')) ? 1 : 0;

		$filterable = 0;
		if($attribute->getData('is_filterable_in_search') || $attribute->getData('is_filterable')){
			$filterable = 1;
		}

		$autosuggest = ($attribute->getData('use_in_autosuggest')) ? 1 : 0;

		$sorted = ($attribute->getData('used_for_sort_by')) ? 1 : 0;

		$this->_searchTypes = array(
			"searchable" => $searchable,
			"filterable" => $filterable,
			"autosuggest" => $autosuggest,
			"sorted" => $sorted
		);

		$this->_name = $attribute->getAttributeCode();

		$this->_magBackType = $attribute->getData('backend_type');
		$this->_magFrontType = $attribute->getData('frontend_input'); // Can be NULL

		$this->_elasticFrontType = $this->_elasticMapType();
		$this->_elasticBackType = "string";

		if($attribute->getData('elasticsearch_custom_map')){
			$this->_customMap = json_decode($attribute->getData('elasticsearch_custom_map'), TRUE);
		}
	}

	protected function _getHelper(){
		if(!isset($this->_helper)){
			$this->_helper = Mage::helper('elasticsearch');
		}

		return $this->_helper;
	}

	public function getType(){
		return $this->_elasticFrontType;
	}

	public function setProduct(Mage_Catalog_Model_Product $product){
		$this->_product = $product;
	}

	public function getProduct(){
		return $this->_product;
	}

	protected function _getId () {
		$id = $this->_attribute->getId();
		if (isset ($id)) {
			return $id;
		}
		return FALSE;
	}

	protected function _elasticMapType(){
		return $this->_getAttributeMapValue('_getType');
	}

	protected function _isSearchable(){
		return (isset($this->_searchTypes['searchable'])) ? $this->_searchTypes['searchable'] : FALSE;
	}

	protected function _isFilterable(){
		return (isset($this->_searchTypes['filterable'])) ? $this->_searchTypes['filterable'] : FALSE;
	}

	protected function _isAutoSuggestable(){
		return (isset($this->_searchTypes['autosuggest'])) ? $this->_searchTypes['autosuggest'] : FALSE;
	}

	protected function _isSortable(){
		return (isset($this->_searchTypes['sorted'])) ? $this->_searchTypes['sorted'] : FALSE;
	}

	protected function _isMultiField(){
		$this->_isMulti = FALSE;

		$attribute_can_multi = $this->_getAttributeMapValue('_canMulti');
		if($attribute_can_multi && ($this->_isFilterable() || $this->_isAutoSuggestable())){
			$this->_isMulti = TRUE;
		}

		return $this->_isMulti;
	}

	protected function _getTextAreaValue($type){
		if($value = $this->_attribute->getFrontend()->getValue($this->getProduct())){
			return strip_tags($value);
		}

		return NULL;
	}

	protected function _getSelectValue($type){
		if($type == self::ATTRIBUTE_TYPE_FRONTEND && $value = $this->_attribute->getFrontend()->getValue($this->getProduct())){
			return trim($value);
		}
		if ($type == self::ATTRIBUTE_TYPE_BACKEND){
			$attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
			$attribute_table = $attribute_options_model->setAttribute($this->_attribute);
			$options = $attribute_options_model->getAllOptions(false);
			foreach($options as $option) {
				if ($option['label'] == $this->_attribute->getFrontend()->getValue($this->getProduct())){
					return $option['value'];
				}

			}

		}
		return NULL;
	}

	protected function _getMultiSelectValue($type){
		if($type == self::ATTRIBUTE_TYPE_FRONTEND && $value = $this->_attribute->getFrontend()->getValue($this->getProduct())){
			// Convert value to array, trim and dedupe
			$val = array_unique(array_map('trim', explode(',', $value)));
			//Mage::helper('elasticsearch')->log(var_export($val, true));
			return $val;
		}
		if ($type == self::ATTRIBUTE_TYPE_BACKEND && $value = $this->_attribute->getFrontend()->getValue($this->getProduct())){
			$attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');
			$attribute_table = $attribute_options_model->setAttribute($this->_attribute);
			$options = $attribute_options_model->getAllOptions(false);
			$val = array_unique(array_map('trim', explode(',', $value)));
			$result = array();
			foreach($options as $option) {
				if (in_array($option['label'], $val)){
					$result[] = $option['value'];
				}

			}
			if(!empty($result)){
				return $result;
			}
		}
		return NULL;
	}

	public function getFrontendValue(){
		$method = $this->_getAttributeMapValue('_getValue');

		if($method !== FALSE){
			$method = "_get".$method."Value";
			if(method_exists($this, $method)){
				return $this->$method(self::ATTRIBUTE_TYPE_FRONTEND);
			}
		}else{
			/**
			 *@todo We'll need to be able to determine boolean types. At the mo, getting only frontend values
			 */
			if($value = $this->_attribute->getFrontend()->getValue($this->getProduct())){
				return $value;
			}
		}

		return NULL;
	}


	public function getBackendValue(){
		$method = $this->_getAttributeMapValue('_getValue');
		$aId = $this->_getId();
		if($method !== FALSE){
			$method = "_get".$method."Value";
			if(method_exists($this, $method)){
				return $this->$method(self::ATTRIBUTE_TYPE_BACKEND);
			}
		}else{
			/**
			 *@todo We'll need to be able to determine boolean types. At the mo, getting only frontend values
			 */
			return $this->_attribute->getFrontend()->getValue($this->getProduct());	
		}
		
		return NULL;
	}

	public function toCreateMap(&$parent_array){
		$parent_array[$this->_name] = $this->getFrontendValue();
		$parent_array[$this->_name . self::DATA_ARRAY_BACKEND] = $this->getBackendValue();
		return $parent_array;
	}

	/**
	 * Takes a referenced array as argument and return formatted key element with
	 * attribute data.
	 *
	 * Foreach attribute frontend value, also a backend value is created in ES index.
	 *
	 * @param array &$parent_array
	 * @return array
	 */
	public function toTypeMap(&$parent_array){

		$data = array(
			"type" => ($this->_isMultiField()) ? "multi_field" : $this->_elasticFrontType
		);

		if($this->_isMultiField()){
			$data['fields'] = array();

			$data['fields'][$this->_name] = array(
				'type' => $this->_elasticFrontType,
				'index' => ($this->_searchTypes['searchable']) ? "analyzed" : "not_analyzed"
			);

			$data['fields'][$this->_name . self::DATA_ARRAY_BACKEND] = array(
				'type' => $this->_elasticBackType,
				'index' => "not_analyzed"
			);


			if($this->_isFilterable()){
				$data['fields']['f_'.$this->_name] = array(
					'type' => $this->_elasticFrontType,
					'index' => 'not_analyzed',
					'include_in_all' => FALSE
				);

				$data['fields']['f_'. $this->_name . self::DATA_ARRAY_BACKEND] = array(
					'type' => $this->_elasticBackType,
					'index' => 'not_analyzed',
					'include_in_all' => FALSE
				);
			}


			if($this->_isAutoSuggestable()){
				// Edge N-Gram
				$data['fields']['eng_'.$this->_name] = array(
					"type" => $this->_elasticFrontType,
					"include_in_all" => FALSE,
					"analyzer" => "edgengram"
				);
				// N-Gram
				$data['fields']['ng_'.$this->_name] = array(
					"type" => $this->_elasticFrontType,
					"include_in_all" => FALSE,
					"analyzer" => "ngram"
				);
				// Snowball
				$data['fields']['sb_'.$this->_name] = array(
					"type" => $this->_elasticFrontType,
					"include_in_all" => FALSE,
					"analyzer" => "snowball"
				);
			}
		}else{
			if($this->_elasticFrontType == "string" && !$this->_isSearchable()){
				$data['index'] = "not_analyzed";
			}

			$format = $this->_getAttributeMapValue('_format');

			if($format){
				$data['format'] = $format;
			}

			$null = $this->_getAttributeMapValue('_nullValue');

			if($null !== FALSE){
				$data['null_value'] = $null;
			}
		}

		if($this->_customMap){
			$data = $this->_processCustomMapping($data);
		}

		$parent_array[$this->_name] = $data;

		return $parent_array;
	}

	protected function _processCustomMapping($data){
		foreach($this->_customMap as $name => $options){
			// Remove field if name is pre-fixed with '-'
			if($string = $this->_getHelper()->stringCheckForUnset($name)){
				Mage::helper('elasticsearch')->log(var_export($string, true));
				unset($data[$string]);
				continue;
			}
			// Allow users to override individual sub-options on default custom analyzers
			if((isset($this->_customMap['type']) && $this->_customMap['type'] == 'multi_field') || $this->_isMultiField()){
				if($name == 'fields'){
					foreach($options as $opt_name => $opt_data){
						if($string = $this->_getHelper()->stringCheckForUnset($opt_name)){
							Mage::helper('elasticsearch')->log(var_export($string, true));
							unset($data[$name][$string]);
							continue;
						}
						foreach($opt_data as $sub_name => $sub_data){
							if($string = $this->_getHelper()->stringCheckForUnset($sub_name)){
								unset($data[$name][$opt_name][$string]);
								continue;
							}
							$data[$name][$opt_name][$sub_name] = $sub_data;
						}
					}
					continue;
				}
			}

			$data[$name] = $options;
		}

		return $data;
	}

	protected function _getAttributeMapValue($option){
		$backend_type = $this->_magBackType;
		$frontend_type = $this->_magFrontType;

		$value = FALSE;
		$overrides = $this->_getAttributeMapOverrides();

		// Check overrides from Feed_Product in the form of array('attribute_code' => array('option_name' => 'option_value'))
		if($overrides && isset($overrides[$this->_name][$option])){
			$value = $overrides[$this->_name][$option];

		}elseif($frontend_type && isset($this->_attributeMap[$backend_type][$frontend_type][$option])){
			$value = $this->_attributeMap[$backend_type][$frontend_type][$option];

		}elseif(isset($this->_attributeMap[$backend_type]['_default'][$option])){
			$value = $this->_attributeMap[$backend_type]['_default'][$option];
		}

		return $value;
	}

	protected function _getAttributeMapOverrides(){
		if(!isset($this->_attributeMapOverrides)){
			$this->_attributeMapOverrides = Mage::registry("elasticsearch_feed_product_attr_overrides");
		}

		return $this->_attributeMapOverrides;
	}


	public function isIndexable($attribute){
		$overrides = $this->_getAttributeMapOverrides();
		if(isset($overrides[$this->_name]['_ignore'])){
			return $overrides[$this->_name]['_ignore'];
		}elseif(in_array(1, array_values($this->_searchTypes)) && !isset($this->_attributeMap[$this->_magBackType]["_ignore"][$this->_magFrontType])){
			return TRUE;
		}

		return FALSE;
	}
}