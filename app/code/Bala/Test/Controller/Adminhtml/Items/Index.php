<?php
/**
 * Copyright © 2015 Bala. All rights reserved.
 */

namespace Bala\Test\Controller\Adminhtml\Items;

class Index extends \Bala\Test\Controller\Adminhtml\Items
{
    /**
     * Items list.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Bala_Test::test');
        $resultPage->getConfig()->getTitle()->prepend(__('Bala Items'));
        $resultPage->addBreadcrumb(__('Bala'), __('Bala'));
        $resultPage->addBreadcrumb(__('Items'), __('Items'));
        return $resultPage;
    }
}
