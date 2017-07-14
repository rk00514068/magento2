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

//require_once 'Customweb/Core/Exception/CastException.php';
//require_once 'Customweb/Ogone/Authorization/PaymentPage/ParameterBuilder.php';
//require_once 'Customweb/Ogone/Authorization/AbstractAdapter.php';
//require_once 'Customweb/Util/Url.php';
//require_once 'Customweb/Ogone/Authorization/Transaction.php';
//require_once 'Customweb/Payment/Authorization/ITransactionHistoryItem.php';
//require_once 'Customweb/Core/Url.php';
//require_once 'Customweb/Payment/Authorization/PaymentPage/IAdapter.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Payment/Authorization/DefaultTransactionHistoryItem.php';


/**
 * This class implements the Customweb_Payment_Authorization_PaymentPage_IAdapter interface 
 * with the Ogone payment page service.
 *         	 	 	  	 	  	 
 * @author Thomas Hunziker
 * @Bean
 *
 */
class Customweb_Ogone_Authorization_PaymentPage_Adapter extends Customweb_Ogone_Authorization_AbstractAdapter 
	implements Customweb_Payment_Authorization_PaymentPage_IAdapter {
	
	private static $cache = array();
	
	public function getAuthorizationMethodName() {
		return self::AUTHORIZATION_METHOD_NAME;
	}

	public function getAdapterPriority() {
		return 100;
	}
	
	public function createTransaction(Customweb_Payment_Authorization_PaymentPage_ITransactionContext $transactionContext, $failedTransaction) {
		$transaction = new Customweb_Ogone_Authorization_Transaction($transactionContext);
		$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
		$this->handleFailedTransaction($transaction, $failedTransaction);
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		return $transaction;
	}
	
	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext) {
		$paymentMethod = $this->getPaymentMethodFactory()->getPaymentMethod($orderContext->getPaymentMethod(), self::AUTHORIZATION_METHOD_NAME);
		return $paymentMethod->getFormFields($orderContext, $aliasTransaction, $failedTransaction, self::AUTHORIZATION_METHOD_NAME, false, $customerPaymentContext);
	}
	
	public function isHeaderRedirectionSupported(Customweb_Payment_Authorization_ITransaction $transaction, array $formData) {
		$url = $this->getRedirectionUrl($transaction, $formData);
		
		/* 
		 * Due to inconsistency in Ogone, Amazon Checkout always requires POST,
		 * whereby other payment methods don't.
		 */
		if ($transaction->getPaymentMethod()->getPaymentMethodName() == 'AmazonCheckout'){
			return false;
		}
		
		// The max length can be up to 2000 chars. The limiting factor is here the user's browser and not
		// the server on which this API runs on!
		if (strlen($url) > 2000) {
			return false;
		}
		else {
			return true;
		}
	}
	
	public function getRedirectionUrl(Customweb_Payment_Authorization_ITransaction $transaction, array $formData) {
		if (!($transaction instanceof Customweb_Ogone_Authorization_Transaction)) {
			throw new Customweb_Core_Exception_CastException('Customweb_Ogone_Authorization_Transaction');
		}

		$item = new Customweb_Payment_Authorization_DefaultTransactionHistoryItem(
				Customweb_I18n_Translation::__("Redirection Parameters generated."),
				Customweb_Payment_Authorization_ITransactionHistoryItem::ACTION_LOG
		);
		$transaction->addHistoryItem($item);
		
		try {
			$url = $this->getPaymentPageUrl($transaction) . '?';
			$builder = new Customweb_Ogone_Authorization_PaymentPage_ParameterBuilder($transaction, $this->getContainer(), $formData);
			$parameters = $builder->buildParameters();
						
			foreach ($parameters as $key => $value) {
				$url .= $key . '=' . urlencode($value) . '&';
			}
			$url = utf8_encode($url);
			return $url;
		}
		catch(Exception $e) {
			$transaction->setAuthorizationFailed($e->getMessage());
			return $transaction->getFailedUrl();
		}
	}
	
	public function getParameters(Customweb_Payment_Authorization_ITransaction $transaction, array $formData) {
		$url = new Customweb_Core_Url($this->getRedirectionUrl($transaction, $formData));
		return $url->getQueryAsArray();
	}
	
	public function getFormActionUrl(Customweb_Payment_Authorization_ITransaction $transaction, array $formData) {
		$url = new Customweb_Core_Url($this->getRedirectionUrl($transaction, $formData));
		return $url->setQuery(array())->toString();
	}
	
	public function processAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters) {
		
		// In case the authorization failed, we stop processing here
		if ($transaction->isAuthorizationFailed()) {
			return $this->finalizeAuthorizationRequest($transaction);
		}
		
		// In case the transaction is authorized, we do not have to do anything here.         	 	 	  	 	  	 
		if ($transaction->isAuthorized()) {
			return $this->finalizeAuthorizationRequest($transaction);
		}
		
		if (isset($parameters['cuctr']) && $parameters['cuctr'] == 't') {
			$transaction->setAuthorizationFailed(
					Customweb_I18n_Translation::__(
							'The transaction is cancelled.'
					)
			);
			return $this->finalizeAuthorizationRequest($transaction);
		}
		
		$transaction->appendAuthorizationParameters($parameters);
		$parameters = array_change_key_case($parameters, CASE_UPPER);
		if (!$this->validateResponse($parameters)) {
			$transaction->setAuthorizationFailed(
				Customweb_I18n_Translation::__(
					'The notification failed because the SHA signature seems not to be valid.'
				)
			);
		}
		else {
			$this->setTransactionAuthorizationState($transaction, $parameters);
		}
		return $this->finalizeAuthorizationRequest($transaction);
	}
	
	public function finalizeAuthorizationRequest(Customweb_Payment_Authorization_ITransaction $transaction) {
		if ($transaction->isAuthorized()) {
			$url = $transaction->getTransactionContext()->getSuccessUrl();
		}
		else {
			$url = $transaction->getTransactionContext()->getFailedUrl();
		}
		
		$url = Customweb_Util_Url::appendParameters(
				$url,
				$transaction->getTransactionContext()->getCustomParameters()
		);
		
		return 'redirect: ' . $url;
	}
	
}


