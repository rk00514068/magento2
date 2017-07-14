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

//require_once 'Customweb/Core/Http/Request.php';
//require_once 'Customweb/Mvc/Template/RenderContext.php';
//require_once 'Customweb/Payment/ExternalCheckout/AbstractCheckoutEndpoint.php';
//require_once 'Customweb/Core/Http/Response.php';
//require_once 'Customweb/Ogone/Container.php';
//require_once 'Customweb/Payment/Authorization/OrderContext/Address/Default.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Ogone/IAdapter.php';
//require_once 'Customweb/Core/Http/Client/Factory.php';
//require_once 'Customweb/Ogone/Util.php';
//require_once 'Customweb/Mvc/Layout/RenderContext.php';
//require_once 'Customweb/Mvc/Template/SecurityPolicy.php';
//require_once 'Customweb/Ogone/ExternalCheckout/MasterPass/AddressRequestParameterBuilder.php';
//require_once 'Customweb/Ogone/ExternalCheckout/MasterPass/RedirectionRequestParameterBuilder.php';
//require_once 'Customweb/Ogone/ExternalCheckout/MasterPass/AuthorizeRequestParameterBuilder.php';



/**
 * @Controller("masterpass")
 */
class Customweb_Ogone_ExternalCheckout_MasterPass_Endpoint extends Customweb_Payment_ExternalCheckout_AbstractCheckoutEndpoint {
	
	/**
	 *
	 * @var Customweb_Ogone_Container
	 */
	private $container;

	public function __construct(Customweb_DependencyInjection_IContainer $container){
		parent::__construct($container);
		$this->container = new Customweb_Ogone_Container($container);
	}

	/**
	 * @Action("redirect")
	 */
	public function redirectAction(Customweb_Core_Http_IRequest $request){
		$context = $this->loadContextFromRequest($request);
		try{
			$this->checkContextTokenInRequest($request, $context);
		}catch(Customweb_Payment_Exception_ExternalCheckoutTokenExpiredException $e) {
			$this->getCheckoutService()->markContextAsFailed($context, $e->getMessage());
			return Customweb_Core_Http_Response::redirect($context->getCartUrl());
		}
		try {
			// We set already here the payment method to be able to access the
			// setting data in the redirection parameter builder.
			$checkoutService = $this->container->getCheckoutService();
			foreach ($checkoutService->getPossiblePaymentMethods($context) as $method) {
				if (strtolower($method->getPaymentMethodName()) == 'masterpass') {
					$checkoutService->updatePaymentMethod($context, $method);
					break;
				}
			}
			$builder = new Customweb_Ogone_ExternalCheckout_MasterPass_RedirectionRequestParameterBuilder($context, $this->container, $this->getSecurityTokenFromRequest($request));
			$responseParameters = $this->sendMessageToRemoteHost($builder->build());
			if (empty($responseParameters['REDIRECTIONURL'])) {
				throw new Exception("The response does not contain the parameter 'REDIRECTIONURL'.");
			}
			
			if (empty($responseParameters['PAYID'])) {
				throw new Exception("The response does not contain the parameter 'PAYID'.");
			}

			if (empty($responseParameters['orderID'])) {
				throw new Exception("The response does not contain the parameter 'orderID'.");
			}
			
			$checkoutService->updateProviderData($context, array_merge($context->getProviderData(), $responseParameters));
			
			return Customweb_Core_Http_Response::redirect($responseParameters['REDIRECTIONURL']);
		}
		catch(Exception $e) {
			$this->getCheckoutService()->markContextAsFailed($context, $e->getMessage());
			return Customweb_Core_Http_Response::redirect($context->getCartUrl());
		}
	}
	

	/**
	 * @Action("update-context")
	 */
	public function updateContextAction(Customweb_Core_Http_IRequest $request){
		
		$parameters = $request->getParameters();
		$checkoutService = $this->container->getCheckoutService();
		$this->getTransactionHandler()->beginTransaction();
		$context = $this->loadContextFromRequest($request);
		try{
			$this->checkContextTokenInRequest($request, $context);
		}catch(Customweb_Payment_Exception_ExternalCheckoutTokenExpiredException $e) {
			$this->getCheckoutService()->markContextAsFailed($context, $e->getMessage());
			$this->getTransactionHandler()->commitTransaction();
			return Customweb_Core_Http_Response::redirect($context->getCartUrl());
		}
		try {
			if ((isset($parameters['mpstatus']) && $parameters['mpstatus'] == 'failure') || !isset($parameters['oauth_verifier'])) {
				throw new Exception(Customweb_I18n_Translation::__("The checkout process was not successful. Please try again."));
			}
			
			

			if (!isset($parameters['oauth_token'])) {
				return 'no oauth_token parameter provided.';
			}

			if (!isset($parameters['oauth_verifier'])) {
				return 'no oauth_verifier parameter provided.';
			}

			if (!isset($parameters['checkout_resource_url'])) {
				return 'no checkout_resource_url parameter provided.';
			}

			$builder = new Customweb_Ogone_ExternalCheckout_MasterPass_AddressRequestParameterBuilder($context, $this->container, $parameters);
			$responseParameters = $this->sendMessageToRemoteHost($builder->build());
			
			$billingAddress = $this->getBillingAddressFromParameters($responseParameters);
			$checkoutService->updateBillingAddress($context, $billingAddress);
			
			$shippingAddress = $this->getShippingAddressFromParameters($responseParameters);
			$checkoutService->updateShippingAddress($context, $shippingAddress);
			
			$this->getTransactionHandler()->commitTransaction();
			
			if (empty($responseParameters['EMAIL'])) {
				return "No 'EMAIL' parameter provided.";
			}
			
			return $checkoutService->authenticate($context, $responseParameters['EMAIL'], $this->getConfirmationPageUrl($context, $this->getSecurityTokenFromRequest($request)));
		}
		catch(Exception $e) {
			$this->getCheckoutService()->markContextAsFailed($context, $e->getMessage());
			$this->getTransactionHandler()->commitTransaction();
			return Customweb_Core_Http_Response::redirect($context->getCartUrl());
		}
	}

	/**
	 * @Action("confirmation")
	 */
	public function confirmationAction(Customweb_Core_Http_IRequest $request){
		$context = $this->loadContextFromRequest($request);
		try{
			$this->checkContextTokenInRequest($request, $context);
		}catch(Customweb_Payment_Exception_ExternalCheckoutTokenExpiredException $e) {
			$this->getCheckoutService()->markContextAsFailed($context, $e->getMessage());
			return Customweb_Core_Http_Response::redirect($context->getCartUrl());
		}
		try {
			
			$checkoutService = $this->container->getCheckoutService();
			$parameters = $request->getParameters();
			
			$templateContext = new Customweb_Mvc_Template_RenderContext();
			$confirmationErrorMessage = null;
			$shippingMethodErrorMessage = null;
			$additionalFormErrorMessage = null;
			if (isset($parameters['masterpass_update_shipping'])) {
				try {
					$checkoutService->updateShippingMethod($context, $request);
				}
				catch (Exception $e) {
					$shippingMethodErrorMessage = $e->getMessage();
				}
			}
			else if (isset($parameters['masterpass_confirmation'])) {
				try {
					$checkoutService->processAdditionalFormElements($context, $request);
				} catch (Exception $e) {
					$additionalFormErrorMessage = $e->getMessage();
				}
				if ($additionalFormErrorMessage === null) {
					try {
						$checkoutService->validateReviewForm($context, $request);
				
						$transaction = $checkoutService->createOrder($context);
						if (!$transaction->isAuthorized() && !$transaction->isAuthorizationFailed()) {
							$this->authorizeTransaction($context, $transaction);
						}
						if ($transaction->isAuthorizationFailed()) {
							$confirmationErrorMessage = current($transaction->getErrorMessages());
						}
						else {
							return Customweb_Core_Http_Response::redirect($transaction->getSuccessUrl());
						}
					}
					catch (Exception $e) {
						$confirmationErrorMessage = $e->getMessage();
					}
				}
			}
			
			$templateContext->setSecurityPolicy(new Customweb_Mvc_Template_SecurityPolicy());
			$templateContext->setTemplate('checkout/masterpass/confirmation');
			
			$templateContext->addVariable('additionalFormElements', $checkoutService->renderAdditionalFormElements($context, $additionalFormErrorMessage));
			$templateContext->addVariable('shippingPane', $checkoutService->renderShippingMethodSelectionPane($context, $shippingMethodErrorMessage));
			$templateContext->addVariable('reviewPane', $checkoutService->renderReviewPane($context, true, $confirmationErrorMessage));
			$templateContext->addVariable('confirmationPageUrl', $this->getConfirmationPageUrl($context, $this->getSecurityTokenFromRequest($request)));
			$templateContext->addVariable('javascript', $this->getAjaxJavascript('.ogone-masterpass-shipping-pane', '.ogone-masterpass-confirmation-pane'));
			
			$content = $this->getTemplateRenderer()->render($templateContext);
			
			$layoutContext = new Customweb_Mvc_Layout_RenderContext();
			$layoutContext->setTitle(Customweb_I18n_Translation::__('MasterPass: Order Confirmation'));
			$layoutContext->setMainContent($content);
			return $this->getLayoutRenderer()->render($layoutContext);
			
		}
		catch(Exception $e) {
			$this->getCheckoutService()->markContextAsFailed($context, $e->getMessage());
			return Customweb_Core_Http_Response::redirect($context->getCartUrl());
		}
	}

	private function authorizeTransaction(Customweb_Payment_ExternalCheckout_IContext $context, Customweb_Ogone_Authorization_Transaction $transaction){
		$this->getTransactionHandler()->beginTransaction();
		try {
			$builder = new Customweb_Ogone_ExternalCheckout_MasterPass_AuthorizeRequestParameterBuilder($context, $this->container);
			$response = $this->sendMessageToRemoteHost($builder->build());
			
			$transaction->authorize();
			$transaction->setAuthorizationParameters($response);
			$transaction->setPaymentId($response['PAYID']);
		}
		catch (Exception $e) {
			$transaction->setAuthorizationFailed($e->getMessage());
		}
		$this->getTransactionHandler()->persistTransactionObject($transaction);
		$this->getTransactionHandler()->commitTransaction();
	}

	private function getConfirmationPageUrl(Customweb_Payment_ExternalCheckout_IContext $context, $token){
		return $this->getUrl('masterpass', 'confirmation', 
				array(
					'context-id' => $context->getContextId(),
					'token' => $token,
				));
	}

	private function getRemoteUrl(){
		return $this->container->getConfiguration()->getBaseEndPointUrl() . Customweb_Ogone_IAdapter::URL_DIRECT_ORDER;
	}

	private function getShippingAddressFromParameters(array $parameters){
		$requiredParamters = array(
			'ECOM_SHIPTO_POSTAL_CITY',
			'ECOM_SHIPTO_POSTAL_COUNTRYCODE',
			'ECOM_SHIPTO_POSTAL_STREET_LINE1',
			'ECOM_SHIPTO_POSTAL_POSTALCODE',
			'ECOM_SHIPTO_POSTAL_NAME_LAST',
			'EMAIL' 
		);
		foreach ($requiredParamters as $parameterName) {
			if (!isset($parameters[$parameterName])) {
				throw new Exception("Parameter $parameterName is missing.");
			}
		}
		
		$shippingAddress = new Customweb_Payment_Authorization_OrderContext_Address_Default();
		list($firstname, $lastname) = explode(' ', $parameters['ECOM_SHIPTO_POSTAL_NAME_LAST'], 2);
		
		// @formatter:off
		$shippingAddress
			->setFirstName($firstname)
			->setLastName($lastname)
			->setStreet($parameters['ECOM_SHIPTO_POSTAL_STREET_LINE1'])
			->setCity($parameters['ECOM_SHIPTO_POSTAL_CITY'])
			->setCountryIsoCode($parameters['ECOM_SHIPTO_POSTAL_COUNTRYCODE'])
			->setPostCode($parameters['ECOM_SHIPTO_POSTAL_POSTALCODE'])
			->setEMailAddress($parameters['EMAIL']);
		// @formatter:on

		if (isset($parameters['ECOM_SHIPTO_TELECOM_PHONE_NUMBER'])) {
			$shippingAddress->setPhoneNumber($parameters['ECOM_SHIPTO_TELECOM_PHONE_NUMBER']);
		}
		if (isset($parameters['ECOM_SHIPTO_POSTAL_STREET_LINE2'])) {
			$shippingAddress->setStreet($shippingAddress->getStreet() . " " . $parameters['ECOM_SHIPTO_POSTAL_STREET_LINE2']);
		}
		return $shippingAddress;
	}

	private function getBillingAddressFromParameters(array $parameters){
		$requiredParamters = array(
			'ECOM_BILLTO_POSTAL_CITY',
			'ECOM_BILLTO_POSTAL_COUNTRYCODE',
			'ECOM_BILLTO_POSTAL_STREET_LINE1',
			'ECOM_BILLTO_POSTAL_POSTALCODE',
			'EMAIL',
			'ECOM_BILLTO_POSTAL_NAME_LAST',
			'ECOM_BILLTO_POSTAL_NAME_FIRST',
		);
		foreach ($requiredParamters as $parameterName) {
			if (!isset($parameters[$parameterName])) {
				throw new Exception("Parameter $parameterName is missing.");
			}
		}
		
		$billingAddress = new Customweb_Payment_Authorization_OrderContext_Address_Default();
		// @formatter:off
		$billingAddress
			->setFirstName($parameters['ECOM_BILLTO_POSTAL_NAME_FIRST'])
			->setLastName($parameters['ECOM_BILLTO_POSTAL_NAME_LAST'])
			->setStreet($parameters['ECOM_BILLTO_POSTAL_STREET_LINE1'])
			->setCity($parameters['ECOM_BILLTO_POSTAL_CITY'])
			->setCountryIsoCode($parameters['ECOM_BILLTO_POSTAL_COUNTRYCODE'])
			->setPostCode($parameters['ECOM_BILLTO_POSTAL_POSTALCODE'])
			->setEMailAddress($parameters['EMAIL']);
		// @formatter:on

		if (isset($parameters['ECOM_BILLTO_POSTAL_STREET_LINE2'])) {
			$billingAddress->setStreet($billingAddress->getStreet() . " " . $parameters['ECOM_BILLTO_POSTAL_STREET_LINE2']);
		}
		
		if (isset($parameters['ECOM_BILLTO_TELEC OM_PHONE_NUMBER'])) {
			$billingAddress->setPhoneNumber($parameters['ECOM_BILLTO_TELEC OM_PHONE_NUMBER']);
		}
		
		return $billingAddress;
	}

	/**
	 * @return Customweb_Ogone_Method_Factory
	 */
	protected function getMethodFactory() {
		return $this->getContainer()->getBean('Customweb_Ogone_Method_Factory');
	}
	
	protected function getPaymentMethodByTransaction(Customweb_Ogone_Authorization_Transaction $transaction){
		return $this->getMethodFactory()->getPaymentMethod($transaction->getTransactionContext()->getOrderContext()->getPaymentMethod(), $transaction->getAuthorizationMethod());
	}
	
	private function sendMessageToRemoteHost(array $parameters) {
		$redirectRequest = new Customweb_Core_Http_Request();
		$redirectRequest->setUrl($this->getRemoteUrl());
		$redirectRequest->setBody($parameters);
		$redirectRequest->setMethod('POST');
		$redirectRequest->appendHeader('Content-Type: application/x-www-form-urlencoded');
		
		$client = Customweb_Core_Http_Client_Factory::createClient()->setConnectionTimeout(15);
		
		$response = $client->send($redirectRequest);
		if (substr($response->getStatusCode(), 0, 1) != '2') {
			throw new Exception("Non 2xx HTTP status code received.");
		}
		$responseParameters = Customweb_Ogone_Util::getXmlAttributes($response->getBody());
		$this->checkForErrors($responseParameters);
		return $responseParameters;
	}

	private function checkForErrors(array $responseParameters) {
	
		if (!empty($responseParameters['NCERROR'])) {
				
			if ($responseParameters['NCERROR'] == '50001111') {
				$message = Customweb_I18n_Translation::__("Some credentials for the API access are invalid. Error Code: @code", array('@code' => $responseParameters['NCERROR']));
				throw new Exception($message);
			}
				
			else if (!empty($responseParameters['NCERRORPLUS'])) {
				throw new Exception("Remote Host response with: " . strip_tags($responseParameters['NCERRORPLUS']));
			}
			
			else {
				throw new Exception(Customweb_I18n_Translation::__("Operation failed because of error code '@code'.", array('@code' => $responseParameters['NCERROR'])));
			}
		}
	}
	
	
}