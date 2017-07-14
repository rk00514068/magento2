<?php
/**
 * Copyright © 2015 Bala. All rights reserved.
 */

namespace Bala\Test\Model;

class Items extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Bala\Test\Model\Resource\Items');
    }
}
