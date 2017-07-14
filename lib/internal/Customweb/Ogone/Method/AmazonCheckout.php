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

//require_once 'Customweb/Core/String.php';
//require_once 'Customweb/Ogone/Authorization/PaymentPage/ParameterBuilder.php';
//require_once 'Customweb/Ogone/Method/DefaultMethod.php';
//require_once 'Customweb/Ogone/Method/LineItemBuilder/AmazonCheckout.php';
//require_once 'Customweb/Mvc/Layout/RenderContext.php';
//require_once 'Customweb/Util/Html.php';
//require_once 'Customweb/Ogone/AbstractAdapter.php';



/**
 *
 * @author Thomas Hunziker
 * @Method(paymentMethods={'amazoncheckout'})
 */
class Customweb_Ogone_Method_AmazonCheckout extends Customweb_Ogone_Method_DefaultMethod {
	
	private static $SECOND_STEP = "SECOND_STEP";
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Customweb_Ogone_Method_DefaultMethod::processAuthorization()
	 */
	public function processAuthorization(Customweb_Ogone_AbstractAdapter $adapter, Customweb_Ogone_Authorization_Transaction $transaction, $parameters){
		
		if($this->isSecondStep($transaction)){
			return parent::processAuthorization($adapter, $transaction, $parameters);
		}
		
		$parameters[self::$SECOND_STEP] = true;
		$transaction->appendAuthorizationParameters($parameters);
		
		//handle step two
		$builder = new Customweb_Ogone_Authorization_PaymentPage_ParameterBuilder($transaction, $this->getContainer(), array());
		$params = $builder->buildParameters();
		
		$url = $this->getGlobalConfiguration()->getBaseEndPointUrl() . Customweb_Ogone_AbstractAdapter::URL_PAYMENT_PAGE;
		
		$content = '<form id="scnd" method="POST" action="' . $url . '">';
		$content .= Customweb_Util_Html::buildHiddenInputFields($params);
		$content .= '</form>';
		$content .= '<script type="text/javascript">document.getElementById(\'scnd\').submit()</script>';
				
		$layoutContext = new Customweb_Mvc_Layout_RenderContext();
		$layoutContext->setTitle('Payment');
		$layoutContext->setMainContent($content);
		$template = $this->getContainer()->getBean('Customweb_Mvc_Layout_IRenderer')->render($layoutContext);
		
		return Customweb_Core_String::_($template)->replaceNonAsciiCharsWithEntities()->toString();
	}
	

	/**
	 * (non-PHPdoc)
	 * 
	 * @see Customweb_Ogone_Method_DefaultMethod::getAuthorizationParameters()
	 */
	public function getAuthorizationParameters(Customweb_Ogone_Authorization_Transaction $transaction, array $formData, $authorizationMethod){
		$parameters = parent::getAuthorizationParameters($transaction, $formData, $authorizationMethod);
		
		//notification url 	
		$urlParams = array(
			'cw_transaction_id' => $transaction->getExternalTransactionId(),
		);
		$endpoint = $this->getContainer()->getBean('Customweb_Payment_Endpoint_IAdapter');
		$backUrl = $endpoint->getUrl('process', 'index', $urlParams);
		$parameters['ACCEPTURL'] = $backUrl;
		
		if ($this->isSecondStep($transaction)) {
			$parameters = array_merge($parameters, $this->addSecondStepParameters($transaction));
		}

		return $parameters;
	}

	private function addSecondStepParameters(Customweb_Ogone_Authorization_Transaction $transaction){
		//Line items
		$builder = new Customweb_Ogone_Method_LineItemBuilder_AmazonCheckout($transaction->getTransactionContext()->getOrderContext());
		$parameters = $builder->build();
		
		//amazon parameters
		$transParams = $transaction->getAuthorizationParameters();
		$parameters['PAYID'] = $transParams['PAYID'];
		$parameters['CN'] = $transParams['PAYERNAME'];
		$parameters['OWNERADDRESS'] = $transParams['PAYERADRSTREET1'];
		if(isset($transParams['PAYERADRSTREET2']))
			$parameters['OWNERADDRESS2'] = $transParams['PAYERADRSTREET2'];
		$parameters['OWNERTOWN'] = $transParams['PAYERADRCITYNAME'];
		$parameters['OWNERZIP'] = $transParams['PAYERADRPOSTALCODE'];
		$parameters['OWNERCTY'] = $transParams['PAYERADRCOUNTRY'];
		$parameters['TXTOKEN'] = $transParams['TXTOKEN'];
		
		return $parameters;
	}
	
	private function isSecondStep(Customweb_Ogone_Authorization_Transaction $transaction){
		$step = $transaction->getAuthorizationParameter(self::$SECOND_STEP);
		return ( $step ? true : false );
	}

}
