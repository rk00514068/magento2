<?php
/**
 * Copyright © 2015 Bala. All rights reserved.
 */

namespace Bala\Test\Controller\Adminhtml\Items;

class NewAction extends \Bala\Test\Controller\Adminhtml\Items
{

    public function execute()
    {
        $this->_forward('edit');
    }
}
