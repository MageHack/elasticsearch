<?php

/**
 * This is a wrapper for Elastica PHP library
 *
 * @category   MageHack
 * @package    MageHack_Elasticsearch
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magehack_Elasticsearch_Model_Api_Elasticsearch
{
	/**
	 * Prefix of model events names
	 *
	 * @var string
	 */
	protected $_eventPrefix = 'elasticsearch_api';
	protected $eClient;
	/* @var $_eClient Elastica_Client */
	protected $eIndex;
	/* @var $_eIndex Elastica_Index */
	protected $_defaultAnalysis = array(
		"analyzer" => array(
			"edgengram" => array(
				"type" => "custom",
				"tokenizer" => "left_tokenizer",
				"filter" => array("standard", "lowercase", "stop")
			),
			"ngram" => array(
				"type" => "custom",
				"tokenizer" => "n_tokenizer",
				"filter" => array("standard", "lowercase", "stop")
			)
		),
		"tokenizer" => array(
			"left_tokenizer" => array(
				"type" => "edgeNGram",
				"side" => "front",
				"max_gram" => 20
			),
			"n_tokenizer" => array(
				"type" => "nGram",
				"max_gram" => 20
			)
		)
	);

	/**
	 *
	 * @return Elastica_Client
	 */
	public function __construct()
	{
		return $this->getElasticaClient();
	}

	/**
	 * Gets an elastica client
	 *
	 * @return Elastica_Client
	 */
	public function getElasticaClient()
	{
		if (!isset($this->eClient)) {
			$this->eClient = new Elastica_Client();
			if ($host = Mage::getStoreConfig(Magehack_Elasticsearch_Helper_Data::XML_PATH_ELASTIC_HOST)) {
				$this->eClient->setConfigValue('host', $host);
			}
			if ($port = Mage::getStoreConfig(Magehack_Elasticsearch_Helper_Data::XML_PATH_ELASTIC_PORT)) {
				$this->eClient->setConfigValue('port', $port);
			}
			if ($transport = Mage::getStoreConfig(Magehack_Elasticsearch_Helper_Data::XML_PATH_ELASTIC_TRANSPORT)) {
				$this->eClient->setConfigValue('transport', $transport);
			}
		}

		return $this->eClient;
	}

	/**
	 *
	 * @return Magehack_Elasticsearch_Helper_Data
	 */
	public function _getHelper()
	{
		if (!isset($this->_helper)) {
			$this->_helper = Mage::helper('elasticsearch');
		}
		return $this->_helper;
	}

	/**
	 * Gets the current index. If it doesnt exist, creates it and applies settings
	 *
	 * @return Elastica_Index
	 * @throws Elastica_Exception_Client
	 * @throws Mage_Core_Exception
	 */
	public function getElasticaIndex()
	{
		$index_name = Mage::getStoreConfig(Magehack_Elasticsearch_Helper_Data::XML_PATH_INDEX_NAME);
		if (!$index_name) {
			$this->_getHelper()->log(get_class($this) . '::getElasticaIndex() Global index name not defined');
			Mage::throwException('Elasticsearch index name not set');
		}

		$this->eIndex = $this->getElasticaClient()->getIndex($index_name);

		// Check index exists, else create it and set default settings
		try {
			if (!$this->eIndex->exists()) {
				$this->eIndex->create($this->getIndexSettings());
			}
		} catch (Elastica_Exception_Client $e) {
			$this->_getHelper()->log(get_class($this) . "::getElasticaIndex() Elastica client exception. Exception:\n\t" . $e->getMessage());
			throw new Elastica_Exception_Client($e->getError());
		} catch (Exception $e) {
			$this->_getHelper()->log(get_class($this) . "::getElasticaIndex() Exception:\n\t" . $e->getMessage());
			Mage::throwException($e->getMessage());
		}

		return $this->eIndex;
	}

	/**
	 * @return Elastica_Response
	 * @throws Elastica_Exception_Client
	 * @throws Mage_Core_Exception
	 */
	public function refreshIndex()
	{
		$index = $this->getElasticaIndex();
		try {
			$response = $index->refresh();
		} catch (Elastica_Exception_Client $e) {
			$this->_getHelper()->log(get_class($this) . "::refreshIndex() Elastica client exception. Exception:\n\t" . $e->getMessage());
			throw new Elastica_Exception_Client($e->getError());
		} catch (Exception $e) {
			$this->_getHelper()->log(get_class($this) . "::refreshIndex() Exception:\n\t" . $e->getMessage());
			Mage::throwException($e->getMessage());
		}
		return $response;
	}

	/**
	 * Sets the current indexes settings, use $data to complete override
	 * otherwise uses getIndexSettings()
	 *
	 * @see getIndexSettings()
	 *
	 * @param array $data
	 * @return Elastica_Response
	 * @throws Elastica_Exception_Client
	 * @throws Mage_Core_Exception
	 */
	public function setSettings($data = NULL)
	{
		$index = $this->getElasticaIndex();
		if ($data && is_array($data)) {
			try {
				$response = $index->setSettings($data);
			} catch (Elastica_Exception_Client $e) {
				$this->_getHelper()->log(get_class($this) . "::setSettings() Elastica client exception. Exception:\n\t" . $e->getMessage());
				throw new Elastica_Exception_Client($e->getError());
			} catch (Exception $e) {
				$this->_getHelper()->log(get_class($this) . "::setSettings() Exception:\n\t" . $e->getMessage());
				Mage::throwException($e->getMessage());
			}
		} else {
			try {
				$response = $index->setSettings($this->getIndexSettings());
			} catch (Elastica_Exception_Client $e) {
				$this->_getHelper()->log(get_class($this) . "::setSettings() Elastica client exception. Exception:\n\t" . $e->getMessage());
				throw new Elastica_Exception_Client($e->getError());
			} catch (Exception $e) {
				$this->_getHelper()->log(get_class($this) . "::setSettings() Exception:\n\t" . $e->getMessage());
				Mage::throwException($e->getMessage());
			}
		}

		return $response;
	}

	/**
	 * Deletes current index
	 *
	 * @return Elastica_Response
	 * @throws Elastica_Exception_Client
	 * @throws Mage_Core_Exception
	 */
	public function deleteIndex()
	{
		$index = $this->getElasticaIndex();
		try {
			$response = $index->delete();
		} catch (Elastica_Exception_Client $e) {
			$this->_getHelper()->log(get_class($this) . "::deleteIndex() Elastica client exception. Exception:\n\t" . $e->getMessage());
			throw new Elastica_Exception_Client($e->getError());
		} catch (Exception $e) {
			$this->_getHelper()->log(get_class($this) . "::deleteIndex() Exception:\n\t" . $e->getMessage());
			Mage::throwException($e->getMessage());
		}
		return $response;
	}

	/**
	 * Recreates a type inside the current index. When recreating it will set the
	 * mapping for the type
	 *
	 * @param Magehack_Elasticsearch_Model_Feed_Abstract $feed_type
	 * @return Elastica_Response
	 *
	 * @throws Elastica_Exception_Client
	 * @throws Mage_Core_Exception
	 */
	public function recreateType(Magehack_Elasticsearch_Model_Feed_Abstract $feed_type)
	{
		$responses = array();

		try {
			$responses[] = $feed_type->getType()->delete();
		} catch (Elastica_Exception_Client $e) {
			$this->_getHelper()->log(get_class($this) . "::recreateType() Elastica client exception. Exception:\n\t" . $e->getMessage());
			throw new Elastica_Exception_Client($e->getError());
		} catch (Exception $e) {
			$this->_getHelper()->log(get_class($this) . "::recreateType() Exception:\n\t" . $e->getMessage());
			Mage::throwException($e->getMessage());
		}

		$new_type = $this->getIndexType($feed_type->getTypeName());

		$new_emap = new Elastica_Type_Mapping($new_type);

		$new_emap->setProperties($feed_type->getMapping());

		try {
			$responses[] = $new_emap->send();
		} catch (Elastica_Exception_Client $e) {
			$this->_getHelper()->log(get_class($this) . "::recreateType() Elastica client exception. Exception:\n\t" . $e->getMessage());
			throw new Elastica_Exception_Client($e->getError());
		} catch (Exception $e) {
			$this->_getHelper()->log(get_class($this) . "::recreateType() Exception:\n\t" . $e->getMessage());
			Mage::throwException($e->getMessage());
		}

		return $responses;
	}

	/**
	 * Get type in current index by name
	 *
	 * @param string $name
	 * @return Elastica_Type
	 * @throws Elastica_Exception_Client
	 * @throws Mage_Core_Exception
	 */
	public function getIndexType($name)
	{
		$index = $this->getElasticaIndex();

		try {
			$type = $index->getType((string) $name);
		} catch (Elastica_Exception_Client $e) {
			$this->_getHelper()->log(get_class($this) . "::recreateType() Elastica client exception. Exception:\n\t" . $e->getMessage());
			throw new Elastica_Exception_Client($e->getError());
		} catch (Exception $e) {
			$this->_getHelper()->log(get_class($this) . "::recreateType() Exception:\n\t" . $e->getMessage());
			Mage::throwException($e->getMessage());
		}

		return $type;
	}

	/**
	 * Gets settings for the index. Uses a combination of the default settings in
	 * $_defaultAnalysis and also processes any custom settings from admin.
	 *
	 * @see _processCustomSettings()
	 *
	 * @return array
	 */
	public function getIndexSettings()
	{
		$this->_settings = array(
			"analysis" => $this->_defaultAnalysis
		);

		if (Mage::getStoreConfig(Magehack_Elasticsearch_Helper_Data::XML_PATH_INDEX_CUSTOM_SETTINGS)) {
			$this->_processCustomSettings();
		}

		$this->_settings = array("settings" => $this->_settings);

		Mage::dispatchEvent($this->_eventPrefix . '_get_index_settings', array("settings" => $this->_settings));

		return $this->_settings;
	}

	/**
	 * Search in the set indices, types
	 *
	 * @deprecated
	 *
	 * @param mixed $query
	 * @param int   $limit OPTIONAL
	 * @return Elastica_ResultSet
	 */
	public function search2($filterString, $from = null, $limit = null)
	{
		return $this;
		$filter = new Elastica_Filter_Terms();
		$filter->setTerms('categories', array($filterString));
		$facet = new Elastica_Facet_Terms('categories');
		$facet->setField('categories');
		$queryObject = new Elastica_Query();
		$queryObject = $queryObject->create(Mage::helper('elasticsearch')->getQueryText());
		$queryObject->addFacet($facet);
		$queryObject->setFilter($filter);

		//$equery->setRawQuery(json_decode($query, true));

		if (!is_null($limit)) {
			if ($limit == 'all') {
				$limit = $this->_getHelper()->getSearchQueryLimit();
			}
			$queryObject->setLimit($limit);
		}
		if (!is_null($from)) {
			$from = $from * $limit;
			$queryObject->setFrom($from);
		}

		$queryObject->setSort(array('_score' => 'desc'));

		try {
			$path = $this->_getHelper()->getEqueryPath();
			$this->_getHelper()->log('Search params: ' . var_export($queryObject->toArray(), true));
			$response = $this->getElasticaClient()->request($path, Elastica_Request::GET, $queryObject->toArray());
			$rset = new Elastica_ResultSet($response);
			return $this->processResultSet($rset);
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * Search in the set indices, types
	 *
	 * @deprecated
	 *
	 * @param mixed $query
	 * @param int   $limit OPTIONAL
	 * @return Elastica_ResultSet
	 */
	public function search($query, $from = null, $limit = null)
	{
		return $this;
		$equery = new Elastica_Query();
		$queryObject = $equery->create(Mage::helper('elasticsearch')->getQueryText());

		if (!is_null($limit)) {
			if ($limit == 'all') {
				$limit = $this->_getHelper()->getSearchQueryLimit();
			}
			$queryObject->setLimit($limit);
		}
		if (!is_null($from)) {
			$from = $from * $limit;
			$queryObject->setFrom($from);
		}

		$queryObject->setSort(array('_score' => 'desc'));

		try {
			$path = $this->_getHelper()->getEqueryPath();
			$this->_getHelper()->log('Search params: ' . var_export($queryObject->toArray(), true));
			$response = $this->getElasticaClient()->request($path, Elastica_Request::GET, $queryObject->toArray());
			$rset = new Elastica_ResultSet($response);
			return $this->processResultSet($rset);
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * Makes ES query
	 *
	 * Takes a query string, filters, facets, from, limit and sort as arguments and
	 * processes ES request
	 *
	 * @param string $query
	 * @param array $filters
	 * @param array $facets
	 * @param string/int $from
	 * @param string/int $limit
	 * @param array $sort
	 * @return Magehack_Elasticsearch_Model_Mysql4_Fulltext_Collection
	 */
	public function doSearch($query, $filters = array(), $facets = array(), $from = false, $limit = false, $sort = array())
	{
		$queryObject = false;

		if (count($filters) > 0) {
			$andFilter = new Elastica_Filter_And();

			foreach ($filters as $filter) {
				$andFilter->addFilter($filter);
			}

			$filteredQuery = new Elastica_Query_Filtered(new Elastica_Query_QueryString($query), $andFilter);
			$queryObject = new Elastica_Query($filteredQuery);
		} else {
			$equery = new Elastica_Query();
			$queryObject = $equery->create($this->_getHelper()->getQueryText());
		}

		if (count($facets) > 0) {
			/**
			 * @todo implement facets?
			 */
		}

		if (!is_null($limit)) {
			if ($limit == 'all') {
				$limit = $this->_getHelper()->getSearchQueryLimit();
			}
			$queryObject->setLimit($limit);
		}
		if (!is_null($from)) {
			$from = $from * $limit;
			$queryObject->setFrom($from);
		}

		if (count($sort) > 0) {
			$queryObject->setSort($sort);
		}
		try {
			$path = $this->_getHelper()->getEqueryPath();
			$response = $this->getElasticaClient()->request($path, Elastica_Request::GET, $queryObject->toArray());
			$this->_getHelper()->log(json_encode($queryObject->toArray()));
			$rSet = new Elastica_ResultSet($response);
			return $this->processResultSet($rSet);
		} catch (Exception $e) {
			echo $e->getMessage();
			// have to stop everything here
			exit;
		}
	}

	/**
	 * Gets Catalog Product Collection
	 * @return type
	 */
	public function getCatalogProductCollection()
	{
		return Mage::getResourceModel('elasticsearch/fulltext_collection');
	}

	/**
	 *
	 * Processes Elastica Result Set.
	 *
	 * Set gets converted into magento fulltext collection for ES 'product' type.
	 *
	 * This method provides sorting functionality to product collection.
	 *
	 * @param Elastica_ResultSet $set
	 * @return Magehack_Elasticsearch_Model_Mysql4_Fulltext_Collection
	 */
	public function processResultSet(Elastica_ResultSet $set)
	{
		Mage::helper('elasticsearch')->log('Magehack_Elasticsearch_Model_Api_Elasticsearch::processResultSet() invoked');
		$productIds = array();
		$cmspages = array();
		$collection = $this->getCatalogProductCollection();
		// here have to process additional types (e.g. cmspage, blogpost, etc)
		foreach ($set->getResults() as $hit) {
			$type = $hit->getType();
			$id = $hit->getId();
			if ($type === 'product' && $id > 0) {
				$productIds[] = $id;
			}

			if ($type === 'cmspage' && $id > 0) {
				//Mage::helper('elasticsearch')->log(var_export($hit, TRUE));
				$cmspages[] = $id;
				//echo 'sadfasd';exit;
			}
		}

		$collection->addAttributeToSelect('*');
		$collection->addFieldToFilter('entity_id', array('in' => $productIds));
		$collection->setElasticCollectionSize($set->getTotalHits());
		$collection->setElasticCollectionHitTotal($set->getTotalHits());
		// NOTE: ordering is set in block Toolbar.php setCollection method
		Mage::helper('elasticsearch')->log('after processing collection size is ' . $collection->getSize());
		Mage::helper('elasticsearch')->log('product ids ' . var_export($productIds, true));
		return $collection;
	}

	/**
	 * Processes any custom settings set in the admin backend. If you prefix any key
	 * with a '-' it will unset it (useful for removing default settings
	 * like in $_defaultAnalysis) or if you define the same key it will override it
	 *
	 * @see Magehack_Elasticsearch_Helper_Data::XML_PATH_INDEX_CUSTOM_SETTINGS
	 *
	 * e.g. if your custom settings contained:
	 *
	 * {
	 * 	 "analyzer" : {
	 *     "ngram" : {
	 *       "filter" : ["customFilter", "stop", "standard"]
	 *     }
	 *   },
	 *   "tokenizer" : {
	 *     "-left_tokenizer"
	 *   },
	 *   "newSetting" : {...}
	 * }
	 *
	 * "ngram"'s setting "filter" inside $_defaultAnalysis would get overriden
	 * leaving all its other settings intact
	 *
	 * The setting "left_tokenizer" inside $_defaultAnalysis would be completely
	 * unset leaving the rest of the settings inside "tokenizer" intact
	 *
	 * "newSettings" would be added including any subsettings
	 *
	 */
	protected function _processCustomSettings()
	{
		$custom_settings = json_decode(Mage::getStoreConfig(Magehack_Elasticsearch_Helper_Data::XML_PATH_INDEX_CUSTOM_SETTINGS), TRUE);

		foreach ($custom_settings as $name => $options) {
			// Remove field if name is pre-fixed with '-'
			if ($string = $this->_getHelper()->stringCheckForUnset($name)) {
				unset($this->_settings[$string]);
				continue;
			}
			// Prevent default analysis from getting nuked if user wants to add custom options in analysis
			if ($name == 'analysis') {
				foreach ($options as $opt_name => $opt_settings) {
					if ($string = $this->_getHelper()->stringCheckForUnset($opt_name)) {
						unset($this->_settings[$name][$string]);
						continue;
					}
					foreach ($opt_settings as $sub_name => $sub_options) {
						if ($string = $this->_getHelper()->stringCheckForUnset($sub_name)) {
							unset($this->_settings[$name][$opt_name][$string]);
							continue;
						}
						$this->_settings[$name][$opt_name][$sub_name] = $sub_options;
					}
				}
			} else {
				$this->_settings[$name] = $options;
			}
		}
	}

}