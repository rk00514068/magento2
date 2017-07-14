<?php
/**
 * Copyright © 2015 Bala. All rights reserved.
 */
namespace Bala\Test\Block\Adminhtml\Items\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('bala_test_items_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Item'));
    }
}
