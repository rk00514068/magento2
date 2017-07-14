<?php
/**
 * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2016 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 *
 * @category	Customweb
 * @package		Customweb_OgoneCw
 *
 */

namespace Customweb\OgoneCw\Controller\Checkout;

class Success extends \Customweb\OgoneCw\Controller\Checkout
{
	/**
	 * @var \Customweb\OgoneCw\Model\Authorization\Notification
	 */
	protected $_notification;
	protected $_catalogHelper;

	/**
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
	 * @param \Magento\Checkout\Model\Session $checkoutSession
	 * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
	 * @param \Customweb\OgoneCw\Model\Authorization\TransactionFactory $transactionFactory
	 * @param \Customweb\OgoneCw\Model\Authorization\Method\Factory $authorizationMethodFactory
	 * @param \Customweb\OgoneCw\Model\Authorization\Notification $notification
	 */
	public function __construct(
			\Magento\Framework\App\Action\Context $context,
			\Magento\Framework\View\Result\PageFactory $resultPageFactory,
			\Magento\Checkout\Model\Session $checkoutSession,
			\Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
			\Customweb\OgoneCw\Model\Authorization\TransactionFactory $transactionFactory,
			\Customweb\OgoneCw\Model\Authorization\Method\Factory $authorizationMethodFactory,
			\Customweb\OgoneCw\Model\Authorization\Notification $notification,
			\Orange\Catalog\Helper\CatalogUrl $catalogHelper
	) {
		parent::__construct($context, $resultPageFactory, $checkoutSession, $quoteRepository, $transactionFactory, $authorizationMethodFactory);
		$this->_notification = $notification;
		$this->_catalogHelper = $catalogHelper;	
	}

	public function execute()
	{
		$transactionId = $this->getRequest()->getParam('cstrxid');		
		try {
			/* Update cc_last_4 column from sales_order_payment start here */
			$cardno=$this->getRequest()->getParam('CARDNO');
			$payid=$this->getRequest()->getParam('PAYID');
			
			$orderId = $this->getRequest()->getParam('cw_transaction_id');
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();			
			$order = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId ($orderId);
			$payments = $order->getPaymentsCollection();
			foreach ($payments as $_payment) {
							$method = $_payment->getMethod();
							$addInfo = $_payment->setCcLast4($cardno);
							$addpid = $_payment->setCcTransId($payid);
							$_payment->save();
			}
			$this->_notification->waitForNotification($transactionId);
			$this->handleSuccess($this->getTransaction($transactionId));
			$store = $order->getStoreId();
			$customerGroupId = $order->getCustomerGroupId();
			$customerGroup = $objectManager->create('Magento\Customer\Api\GroupRepositoryInterface')->getById($customerGroupId)->getCode();//Get customer group name
			if($customerGroup == 'SOHO') {
				if($store == '2') {
					$urlPath = '/fr/checkout/onepage/success'; //SOHO URL PATH for FR
				}
				else {
					$urlPath = '/nl/checkout/onepage/success'; //SOHO URL PATH for NL
				}
				$RedirectUrl = $this->_catalogHelper->getFormattedURL($urlPath);//Format URL path for redirection	
				$objectManager->get('Psr\Log\LoggerInterface')->addDebug('Redirect URL: '.$RedirectUrl);
				return $this->resultRedirectFactory->create()->setUrl($RedirectUrl, ['_secure' => true]);
			}
						
			return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success', ['_secure' => true]);
		} catch (\Exception $e) {
			return $this->handleFailure($this->getTransaction($transactionId), $e->getMessage());
		}
	}
}