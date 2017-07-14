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

namespace Customweb\OgoneCw\Controller;

abstract class Checkout extends \Magento\Framework\App\Action\Action
{
	/**
	 * @var \Magento\Framework\View\Result\PageFactory
	 */
	protected $_resultPageFactory;

	/**
	 * @var \Magento\Checkout\Model\Session
	 */
	protected $_checkoutSession;

	/**
	 * @var \Magento\Quote\Api\CartRepositoryInterface
	 */
	protected $_quoteRepository;

	/**
	 * @var \Customweb\OgoneCw\Model\Authorization\TransactionFactory
	 */
	protected $_transactionFactory;

	/**
	 * @var \Customweb\OgoneCw\Model\Authorization\Method\Factory
	 */
	protected $_authorizationMethodFactory;
	const CHECKOUT_SESSION_LOG = '/var/log/checkout_session.log';

	/**
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
	 * @param \Magento\Checkout\Model\Session $checkoutSession
	 * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
	 * @param \Customweb\OgoneCw\Model\Authorization\TransactionFactory $transactionFactory
	 * @param \Customweb\OgoneCw\Model\Authorization\Method\Factory $authorizationMethodFactory
	 */
	public function __construct(
			\Magento\Framework\App\Action\Context $context,
			\Magento\Framework\View\Result\PageFactory $resultPageFactory,
			\Magento\Checkout\Model\Session $checkoutSession,
			\Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
			\Customweb\OgoneCw\Model\Authorization\TransactionFactory $transactionFactory,
			\Customweb\OgoneCw\Model\Authorization\Method\Factory $authorizationMethodFactory
	) {
		parent::__construct($context);
		$this->_resultPageFactory = $resultPageFactory;
		$this->_checkoutSession = $checkoutSession;
		$this->_quoteRepository = $quoteRepository;
		$this->_transactionFactory = $transactionFactory;
		$this->_authorizationMethodFactory = $authorizationMethodFactory;
	}

	/**
	 * @param \Customweb\OgoneCw\Model\Authorization\Transaction $transaction
	 */
	protected function handleSuccess(\Customweb\OgoneCw\Model\Authorization\Transaction $transaction)
	{
		$transaction->getQuote()->setIsActive(false)->save();
	}

	/**
	 * @param \Customweb\OgoneCw\Model\Authorization\Transaction $transaction
	 * @param string $errorMessage
	 * @return \Magento\Framework\Controller\Result\Redirect
	 */
	protected function handleFailure(\Customweb\OgoneCw\Model\Authorization\Transaction $transaction, $errorMessage)
	{
		$this->_checkoutSession->setLastRealOrderId($transaction->getOrder()->getRealOrderId());
		$this->restoreQuote();
		
		$om = \Magento\Framework\App\ObjectManager::getInstance();
		$registychecksession = $om->get('Magento\Checkout\Model\Session');
	    $backurl = $registychecksession->getBackurl();
		$registychecksession->setBackurl('2');
		
        if ($errorMessage !=__("The transaction is cancelled.") || $backurl != 1) {
			$this->messageManager->addError($errorMessage);
		}
// 		$this->_checkoutSession->setOgoneCwFailureMessage($errorMessage);
        if ($backurl == 1) {
			return $this->resultRedirectFactory->create()->setPath('checkout');
		} else {
			return $this->resultRedirectFactory->create()->setPath('checkout/cart');
		}
	}

	/**
	 * @param int $transactionId
	 * @return \Customweb\OgoneCw\Model\Authorization\Transaction
	 * @throws \Exception
	 */
	protected function getTransaction($transactionId)
	{
		$transaction = $this->_transactionFactory->create()->load($transactionId);
		if (!$transaction->getId()) {
			throw new \Exception('The transaction has not been found.');
		}
		return $transaction;
	}

	/**
	 * Restore last active quote
	 *
	 * @return bool True if quote restored successfully, false otherwise
	 */
	private function restoreQuote()
	{
		/** @var \Magento\Sales\Model\Order $order */
		$order = $this->_checkoutSession->getLastRealOrder();
		if ($order->getId()) {
			try {
				
				$this->_orangeHelper =$this->_objectManager->create('Orange\Upload\Helper\Data');
				$this->_oldSession = $this->_objectManager->create('Orange\Abandonexport\Model\Items');

				$this->_orangeHelper->logCreate(self::CHECKOUT_SESSION_LOG,'AFTER OGONE FAILURE:'.$this->_checkoutSession->getSessionId());		
				$this->_orangeHelper->logCreate(self::CHECKOUT_SESSION_LOG,$this->_checkoutSession->getNewcheckout());//LOG Session data
				$this->_orangeHelper->logCreate(self::CHECKOUT_SESSION_LOG,'SESSION DB DATA');
				$oldSessionModel = $this->_oldSession;
				$oldSessionCollection = $oldSessionModel->getCollection()->addFieldToFilter('quote_id',$order->getQuoteId());
				$oldSessionStepData = array();
				foreach($oldSessionCollection as $oldSessionData) {
					$this->_orangeHelper->logCreate(self::CHECKOUT_SESSION_LOG,'---------------------------------');
					$this->_orangeHelper->logCreate(self::CHECKOUT_SESSION_LOG,$oldSessionData->getData('quote_id'));//LOG Session data
					$this->_orangeHelper->logCreate(self::CHECKOUT_SESSION_LOG,$oldSessionData->getData('stepfirst'));//LOG Session data
					$this->_orangeHelper->logCreate(self::CHECKOUT_SESSION_LOG,$oldSessionData->getData('stepsecond'));//LOG Session data
					$oldSessionStepData = unserialize($oldSessionData->getStepsecond());
					$this->_orangeHelper->logCreate(self::CHECKOUT_SESSION_LOG,unserialize($oldSessionData->getStepsecond()));//LOG Session data					
					$this->_orangeHelper->logCreate(self::CHECKOUT_SESSION_LOG,'---------------------------------');
				}
				
				$quote = $this->_quoteRepository->get($order->getQuoteId());
				$quote->setIsActive(1)->setReservedOrderId(null);
				$this->_quoteRepository->save($quote);
				$this->_checkoutSession->replaceQuote($quote)->unsLastRealOrderId();
				$this->_checkoutSession->setNewcheckout($oldSessionStepData);
// 				$this->_eventManager->dispatch('restore_quote', ['order' => $order, 'quote' => $quote]);
				return true;
			} catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
			}
		}
		return false;
	}
}
