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
 */

//require_once 'Customweb/Payment/Authorization/Recurring/IAdapter.php';
//require_once 'Customweb/Ogone/Authorization/AbstractAdapter.php';
//require_once 'Customweb/Ogone/Authorization/Transaction.php';
//require_once 'Customweb/Payment/Exception/RecurringPaymentErrorException.php';
//require_once 'Customweb/Ogone/Authorization/Recurring/ParameterBuilder.php';


/**
 *
 * @author Thomas Hunziker
 * @Bean
 *
 */
class Customweb_Ogone_Authorization_Recurring_Adapter extends Customweb_Ogone_Authorization_AbstractAdapter implements Customweb_Payment_Authorization_Recurring_IAdapter
{
	
	public function getAuthorizationMethodName() {
		return self::AUTHORIZATION_METHOD_NAME;
	}
	
	public function getAdapterPriority() {
		return 2000;
	}
	
	public function isPaymentMethodSupportingRecurring(Customweb_Payment_Authorization_IPaymentMethod $paymentMethod) {
		$paymentMethod = $this->getPaymentMethodFactory()->getPaymentMethod($paymentMethod, self::AUTHORIZATION_METHOD_NAME);
		return $paymentMethod->isRecurringPaymentSupported();
	}
	
	public function createTransaction(Customweb_Payment_Authorization_Recurring_ITransactionContext $transactionContext) {
		$transaction = new Customweb_Ogone_Authorization_Transaction($transactionContext);
		$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		return $transaction;
	}
	
	public function process(Customweb_Payment_Authorization_ITransaction $transaction) {
		$builder = new Customweb_Ogone_Authorization_Recurring_ParameterBuilder($transaction, $this->getContainer());
		$parameters = $builder->buildParameters();
		$response = $this->sendDirectRequest($this->getDirectOrderUrl(), $parameters);
		
		// In any case dont save the CVC         	 	 	  	 	  	 
		unset($parameters['CVC']);
		
		$transaction->setDirectLinkCreationParameters($parameters);
		$transaction->setAuthorizationParameters($response);
			
		// Check whether a 3D secure redirection is required or not.
		$this->setTransactionAuthorizationState($transaction, $response);
		
		if ($transaction->isAuthorizationFailed()) {
			throw new Customweb_Payment_Exception_RecurringPaymentErrorException(end($transaction->getErrorMessages()));
		}
	}
	
}