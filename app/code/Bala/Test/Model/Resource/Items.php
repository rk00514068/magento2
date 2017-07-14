<?php
/**
 * Copyright © 2015 Bala. All rights reserved.
 */

namespace Bala\Test\Model\Resource;

class Items extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('bala_test_items', 'id');
    }
}
