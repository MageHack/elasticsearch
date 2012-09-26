<?php

/**
 * This class extends Mage_CatalogSearch_Model_Resource_Fulltext_Collection
 * Provides custom functionality for Mage_CatalogSearch_Model_Resource_Fulltext_Collection
 *
 * Mage_CatalogSearch_Model_Resource_Fulltext_Collection extends Mage_Catalog_Model_Resource_Product_Collection
 * which extends Mage_Catalog_Model_Resource_Collection_Abstract
 *
 * @category   MageHack
 * @package    MageHack_Elasticsearch
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author      Carlo Tasca
 */
class Magehack_Elasticsearch_Model_Mysql4_Fulltext_Collection extends Mage_CatalogSearch_Model_Resource_Fulltext_Collection
{
	protected $_elasticCollectionSize = false;
	protected $_elasticCollectionHitTotal = false;

	/**
	 * Set elastic collection size
	 *
	 * @param int $size
	 */
	public function setElasticCollectionSize($size)
	{
		$this->_elasticCollectionSize = $size;
	}

	public function getElasticCollectionSize()
	{
		return (int) $this->_elasticCollectionSize;
	}

	/**
	 * Sets elastic collection hits total
	 *
	 * @param type $size
	 */
	public function setElasticCollectionHitTotal($size)
	{
		$this->_elasticCollectionHitTotal = $size;
	}

	public function getElasticCollectionHitTotal()
	{
		return (int) $this->_elasticCollectionHitTotal;
	}


	/**
     * Retrieve query model object
     *
     * @return Mage_CatalogSearch_Model_Query
     */
    protected function _getQuery()
    {
        return Mage::helper('elasticsearch')->getQuery();
    }

    /**
     * Add search query filter
	 * @deprecated
     *
     * @param string $query
     * @return Mage_CatalogSearch_Model_Resource_Fulltext_Collection
     */
    public function addSearchFilter($query)
    {
        return $this;
		/*
		Mage::helper('elasticsearch')->log(get_class($this) . '::addSearchFilter() invoked');
		Mage::getSingleton('elasticsearch/fulltext')->prepareResult();
        $this->getSelect()->joinInner(
            array('search_result' => $this->getTable('catalogsearch/result')),
            $this->getConnection()->quoteInto(
                'search_result.product_id=e.entity_id AND search_result.query_id=?',
                $this->_getQuery()->getId()
            ),
            array('relevance' => 'relevance')
        );

        return $this;*/
    }

	/**
     * Get collection size
     *
     * @return int
     */
    public function getSize()
    {
			if ($this->getElasticCollectionSize() !== false)
			{
				Mage::helper('elasticsearch')->log(get_class($this) . '::getSize(); returning elastic collection size ' . $this->getElasticCollectionSize());
				return intval($this->getElasticCollectionHitTotal());
			}

			return 0;
    }

		/**
     * Retrieve collection last page number
     *
     * @return int
     */
    public function getLastPageNumber()
    {
			$collectionSize = (int) $this->getElasticCollectionHitTotal();
        if (0 === $collectionSize) {
            return 1;
        }
        elseif($this->_pageSize) {
            return ceil($collectionSize/$this->_pageSize);
        }
        else{
            return 1;
        }
    }

		/**
     * Specify category filter for product collection
     *
     * @param Mage_Catalog_Model_Category $category
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function addCategoryFilter(Mage_Catalog_Model_Category $category)
    {
		$this->_productLimitationFilters['category_id'] = $category->getId();
        if ($category->getIsAnchor()) {
            unset($this->_productLimitationFilters['category_is_anchor']);
        } else {
            $this->_productLimitationFilters['category_is_anchor'] = 1;
        }

        if ($this->getStoreId() == Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID) {
            $this->_applyZeroStoreProductLimitations();
        } else {
            $this->_applyProductLimitations();
        }

        return $this;
    }
}
