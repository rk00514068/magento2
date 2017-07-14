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

//require_once 'Customweb/Payment/Authorization/Moto/IAdapter.php';
//require_once 'Customweb/Core/Http/Response.php';
//require_once 'Customweb/Ogone/Authorization/Hidden/Adapter.php';
//require_once 'Customweb/Ogone/Authorization/AbstractAdapter.php';
//require_once 'Customweb/I18n/Translation.php';


/**
 *
 * @author Thomas Hunziker
 * @Bean
 *
 */
class Customweb_Ogone_Authorization_Moto_Adapter extends Customweb_Ogone_Authorization_AbstractAdapter implements Customweb_Payment_Authorization_Moto_IAdapter{
	
	public function getAuthorizationMethodName() {
		return self::AUTHORIZATION_METHOD_NAME;
	}
	
	public function getAdapterPriority() {
		return 1000;
	}
	
	public function createTransaction(Customweb_Payment_Authorization_Moto_ITransactionContext $transactionContext, $failedTransaction){
		$adapter = $this->getAdapterInstanceByPaymentMethod($transactionContext->getOrderContext()->getPaymentMethod());
		$transaction = $adapter->createTransaction($transactionContext, $failedTransaction);
		$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
		$this->handleFailedTransaction($transaction, $failedTransaction);
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		return $transaction;
	}
	
	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext) {
		$adapter = $this->getAdapterInstanceByPaymentMethod($orderContext->getPaymentMethod());
		$paymentMethod = $this->getPaymentMethodFactory()->getPaymentMethod($orderContext->getPaymentMethod(), self::AUTHORIZATION_METHOD_NAME);
		return $paymentMethod->getFormFields($orderContext, $aliasTransaction, $failedTransaction, $adapter->getAuthorizationMethodName(), true, $customerPaymentContext);
	}
	
	public function getFormActionUrl(Customweb_Payment_Authorization_ITransaction $transaction) {
		$adapter = $this->getAdapterInstanceByPaymentMethod($transaction->getPaymentMethod());
		if ($adapter instanceof Customweb_Ogone_Authorization_Hidden_Adapter) {
			return $adapter->getFormActionUrl($transaction);
		}
		else {
			return $this->getEndpointAdapter()->getUrl('process', 'index', array('cw_transaction_id' => $transaction->getExternalTransactionId()));
		}
	}
	
	public function getParameters(Customweb_Payment_Authorization_ITransaction $transaction) {
		$adapter = $this->getAdapterInstanceByPaymentMethod($transaction->getPaymentMethod());
		if ($adapter instanceof Customweb_Ogone_Authorization_Hidden_Adapter) {
			return $adapter->getHiddenFormFields($transaction);
		}
		else {
			return $transaction->getTransactionContext()->getCustomParameters();
		}
	}

	public function processAuthorization(Customweb_Payment_Authorization_ITransaction $transaction, array $parameters) {
		global $ogone_form_url, $ogone_form_parameters;
		
		$adapter = $this->getAdapterInstanceByPaymentMethod($transaction->getPaymentMethod());
		if ($adapter instanceof Customweb_Ogone_Authorization_Hidden_Adapter) {
			return $adapter->processAuthorization($transaction, $parameters);
		}
		else {
			// Check if this is a callback
			if (isset($parameters['STATUS'])) {
				return $adapter->processAuthorization($transaction, $parameters);
			}
			else {
				$ogone_form_url = $adapter->getFormActionUrl($transaction, $parameters);
				$ogone_form_parameters = $adapter->getParameters($transaction, $parameters);
				return $this->finalizeAuthorizationRequest($transaction);
			}
		}
		return $this->finalizeAuthorizationRequest($transaction);
	}
	
	public function finalizeAuthorizationRequest(Customweb_Payment_Authorization_ITransaction $transaction) {
		$adapter = $this->getAdapterInstanceByPaymentMethod($transaction->getPaymentMethod());
		if ($adapter instanceof Customweb_Ogone_Authorization_Hidden_Adapter) {
			return $adapter->finalizeAuthorizationRequest($transaction);
		}
		else {
			global $ogone_form_url, $ogone_form_parameters;
			
			if (isset($ogone_form_url) && !empty($ogone_form_url)) {
				$output = '<html><body>';
					$output .= '<form name="redirectionform" action="' . $ogone_form_url . '" method="POST">';
						foreach ($ogone_form_parameters as $key => $value) {
							$output .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
						}
						$output .= '<noscript>';
							$output .= '<input type="submit" name="complete" value="' . Customweb_I18n_Translation::__('Continue') . '" />';
						$output .= '</noscript>';
					$output .= '</form>';
				
					$output .= '<script type="text/javascript"> ' . "\n";
						$output .= ' document.redirectionform.submit(); ' . "\n";
					$output .= '</script>';
				$output .= '</body></html>';
				return Customweb_Core_Http_Response::_($output);
			}
			else {
				return $adapter->finalizeAuthorizationRequest($transaction);
			}
		}
	}
	
	protected function getAdapterInstanceByPaymentMethod(Customweb_Payment_Authorization_IPaymentMethod $paymentMethod) {
		return new Customweb_Ogone_Authorization_Hidden_Adapter($this->getConfiguration()->getConfigurationAdapter(), $this->getContainer());
	}
	
	
}