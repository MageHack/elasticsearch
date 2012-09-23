<?php

Class Magehack_Elasticsearch_Model_Feed_Cmspage extends Magehack_Elasticsearch_Model_Feed_Abstract {

	protected $_type = 'cmspage';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'elasticsearch_feed_cmspage';

	/**
	 * Returns queue items for this feed to process
	 */
	protected function _getQueueItems(){
        $items =  Mage::getModel('elasticsearch/queue')->getUnprocessedItems('cmspage');
        return $items;
	}

	/**
	 * Sets $this->_feedArray to a assoc array key'd on ID
	 *
	 */
	protected function _organiseData($items){
		$this->_feedArray = array();

		foreach($items as $item){
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
					$this->_feedArray[] = $this->_prepareCmspageData($item);
					break;
				default:
					$this->_addSkipped($item->getData('queue_item_id'));
					Mage::helper('elasticsearch')->log(get_class($this)."::_organiseData() Skipping Queue Item:{$item->getData('queue_item_id')} with type '{$item->getData('model_class')}' and message '{$item->getData('message')}'");
					break;
			}
			$item->markAsProcessed();
		}

		Mage::dispatchEvent($this->_eventPrefix.'_organise_data', array("feed_cmspage" => $this, "feed_array" => $this->_feedArray));
	}

	protected function _prepareCmspageData($item){
		$data = array();
		$page = $item->getModel();

		Mage::dispatchEvent($this->_eventPrefix.'_prepare_cmspage_before', array("feed_cmspage" => $this, "cmspage" => $page, "data" => $data));

		$data['title'] = $page->getTitle();
		$data['content_heading'] = $page->getContentHeading();
		$data['content'] = strip_tags($page->getContent());
		$data['url'] = $page->getUrl();

		Mage::dispatchEvent($this->_eventPrefix.'_prepare_cmspage_before', array("feed_cmspage" => $this, "cmspage" => $page, "data" => $data));
		return $data;
	}

	/**
	 * Returns array for type mapping
	 */
	public function getMapping(){
		$props = array();

		Mage::dispatchEvent($this->_eventPrefix.'_generate_default_map_before', array("feed_cmspage" => $this, "data" => $props));

		$props['title'] = array(
			'type' => 'string'
		);

		$props['content'] = array(
			'type' => 'string'
		);

		$props['content_heading'] = array(
			'type' => 'string'
		);

		$props['url'] = array(
			'type' => 'string',
			'index' => 'not_analyzed'
		);

		Mage::dispatchEvent($this->_eventPrefix.'_generate_default_map_after', array("feed_cmspage" => $this, "data" => $props));

		return $props;
	}

	public function getAllItems(){
		$pages = Mage::getModel('cms/page')->getCollection()
				->addFieldToFilter('is_active', Mage_Cms_Model_Page::STATUS_ENABLED)
				->addFieldToFilter('identifier', array('neq' => Mage_Cms_Model_Page::NOROUTE_PAGE_ID));

		return $pages;
	}
}