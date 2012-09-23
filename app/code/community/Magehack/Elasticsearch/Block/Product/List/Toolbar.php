<?php

class Magehack_Elasticsearch_Block_Product_List_Toolbar extends Mage_Catalog_Block_Product_List_Toolbar
{
	/**
     * Init Toolbar
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('elasticsearch/product/list/toolbar.phtml');
    }
	
	
	/**
     * Set collection to pager
     *
     * @param Varien_Data_Collection $collection
     * @return Mage_Catalog_Block_Product_List_Toolbar
     */
    public function setCollection($collection)
    {
		$this->_collection = $collection;

        $this->_collection->setCurPage($this->getCurrentPage());

        // we need to set pagination only if passed value integer and more that 0
        $limit = (int)$this->getLimit();
        if ($limit) {
			$this->_collection->setPageSize($limit);
        }

        if ($this->getCurrentOrder()) {
           $this->_collection->setOrder($this->getCurrentOrder(), $this->getCurrentDirection());
        }
        return $this;
    }
	
	
	/**
     * Return products collection instance
     *
     * @return Mage_Core_Model_Mysql4_Collection_Abstract
     */
    public function getCollection()
    {
        return $this->_collection;
    }
	
	/**
	 * Return pager block 
	 * @return Mage_Core_Block_Abstract 
	 */
	public function getPager () 
	{
		return $this->getChild('product_list_toolbar_pager');
	}
	
	/**
	 * Return the size of loaded collection
	 * @return int 
	 */
	
	public function getTotalNum()
    {
		return $this->getCollection()->getSize();
    }
	
	/**
	 * Gets last pagination number
	 * @return type 
	 */
	public function getLastNum()
    {
		$collection = $this->getCollection();
        return $collection->getPageSize()*($collection->getCurPage()-1)+$collection->count();
    }
	/**
	 * Gets pagination first number
	 * @return int 
	 */
	public function getFirstNum()
    {
        return $this->getLimit()*($this->getCurrentPage()-1)+1;
    }
	
	/**
	 * Gets pagination last page number
	 * @return type 
	 */
	public function getLastPageNum()
    {
        return $this->getCollection()->getLastPageNumber();
    }
	
	/**
     * Render pagination HTML
     *
     * @return string
     */
    public function getPagerHtml()
    {
        
		$pagerBlock = $this->getChild('product_list_toolbar_pager');

        if ($pagerBlock instanceof Varien_Object) {

            /* @var $pagerBlock Mage_Page_Block_Html_Pager */
            $pagerBlock->setAvailableLimit($this->getAvailableLimit());
            $pagerBlock->setUseContainer(false)
                ->setShowPerPage(false)
                ->setShowAmounts(false)
                ->setLimitVarName($this->getLimitVarName())
                ->setPageVarName($this->getPageVarName())
                ->setLimit($this->getLimit())
                ->setFrameLength(Mage::getStoreConfig('design/pagination/pagination_frame'))
                ->setJump(Mage::getStoreConfig('design/pagination/pagination_frame_skip'))
                ->setCollection($this->getCollection());

            return $pagerBlock->toHtml();
        }

        return '';
    }

}