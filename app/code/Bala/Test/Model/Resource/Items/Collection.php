<?php
/**
 * Copyright © 2015 Bala. All rights reserved.
 */

namespace Bala\Test\Model\Resource\Items;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Bala\Test\Model\Items', 'Bala\Test\Model\Resource\Items');
    }
}
