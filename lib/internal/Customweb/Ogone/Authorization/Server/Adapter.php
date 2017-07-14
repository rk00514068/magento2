<?php

/**
 *  * You are allowed to use this API in your web application.
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
 */

//require_once 'Customweb/Payment/Authorization/Server/IAdapter.php';
//require_once 'Customweb/Ogone/Authorization/AbstractAdapter.php';
//require_once 'Customweb/Ogone/Authorization/Transaction.php';



/**
 *
 * @author Thomas Hunziker
 * @Bean
 *
 */
class Customweb_Ogone_Authorization_Server_Adapter extends Customweb_Ogone_Authorization_AbstractAdapter implements 
		Customweb_Payment_Authorization_Server_IAdapter {

	public function getAuthorizationMethodName(){
		return self::AUTHORIZATION_METHOD_NAME;
	}

	public function getAdapterPriority(){
		return 200;
	}

	public function createTransaction(Customweb_Payment_Authorization_Server_ITransactionContext $transactionContext, $failedTransaction){
		$transaction = new Customweb_Ogone_Authorization_Transaction($transactionContext);
		$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
		$this->handleFailedTransaction($transaction, $failedTransaction);
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		return $transaction;
	}

	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext){
		$paymentMethod = $this->getPaymentMethodFactory()->getPaymentMethod($orderContext->getPaymentMethod(), self::AUTHORIZATION_METHOD_NAME);
		return $paymentMethod->getFormFields($orderContext, $aliasTransaction, $failedTransaction, self::AUTHORIZATION_METHOD_NAME, false, 
				$customerPaymentContext);
	}

	public function processAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters){
		$this->authorize($transaction, $parameters);
		return $this->finalizeAuthorizationRequest($transaction);
	}

	public function getProcessControllerName(){
		return 'process';
	}

	public function getProcessActionName(){
		return 'index';
	}
}
