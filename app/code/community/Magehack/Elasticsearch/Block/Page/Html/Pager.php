<?php

/**
 * @category   MageHack
 * @package    MageHack_Elasticsearch
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Magehack_Elasticsearch_Block_Page_Html_Pager extends Mage_Page_Block_Html_Pager
{
	protected function _construct()
    {
        parent::_construct();
        $this->setData('show_amounts', true);
        $this->setData('use_container', true);
        $this->setTemplate('elasticsearch/page/html/pager.phtml');
    }

	public function setCollection($collection)
    {
		$this->_collection = $collection
            ->setCurPage($this->getCurrentPage());
        // If not int - then not limit
        if ((int) $this->getLimit()) {
            $this->_collection->setPageSize($this->getLimit());
        }

        $this->_setFrameInitialized(false);

        return $this;
    }
	
	public function getPages()
    {
        $collection = $this->getCollection();
        $pages = array();
        if ($collection->getLastPageNumber() <= $this->_displayPages) {
            $pages = range(1, $collection->getLastPageNumber());
        }
        else {
            $half = ceil($this->_displayPages / 2);
            if ($collection->getCurPage() >= $half && $collection->getCurPage() <= $collection->getLastPageNumber() - $half) {
                $start  = ($collection->getCurPage() - $half) + 1;
                $finish = ($start + $this->_displayPages) - 1;
            }
            elseif ($collection->getCurPage() < $half) {
                $start  = 1;
                $finish = $this->_displayPages;
            }
            elseif ($collection->getCurPage() > ($collection->getLastPageNumber() - $half)) {
                $finish = $collection->getLastPageNumber();
                $start  = $finish - $this->_displayPages + 1;
            }

            $pages = range($start, $finish);
        }
        return $pages;
    }
}