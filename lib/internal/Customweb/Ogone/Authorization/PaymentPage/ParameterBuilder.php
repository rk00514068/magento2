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

//require_once 'Customweb/Ogone/AbstractParameterBuilder.php';

class Customweb_Ogone_Authorization_PaymentPage_ParameterBuilder extends Customweb_Ogone_AbstractParameterBuilder {
	private $formData = array();

	public function __construct($transaction, Customweb_DependencyInjection_IContainer $container, $formData){
		parent::__construct($transaction, $container);
		$this->formData = $formData;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Customweb_Ogone_AbstractParameterBuilder::buildParameters()
	 */
	public function buildParameters(){
		$this->addShopIdToCustomParameters();
		
		$parameters = array_merge($this->getLanguageParameter(), 
				$this->getAmountParameter($this->getTransactionContext()->getOrderContext()->getOrderAmountInDecimals()), 
				$this->getCurrencyParameter(), $this->getPspParameter(), $this->getOrderIdParameter(), $this->getOrderDescriptionParameter(), 
				$this->getOrigParameter(), $this->getCustomerParameters(), $this->getDeliveryAndInvoicingParameters(), 
				$this->getAliasManagerParameters(), $this->getReactionUrlParameters(), $this->getParamPlusParameters(), $this->getTemplateParameter(), 
				$this->getCapturingModeParameter(), $this->getTitleParameter(), 
				$this->getPaymentMethod()->getAuthorizationParameters($this->getTransaction(), $this->formData, 
						$this->getTransaction()->getAuthorizationMethod()));
		
		$this->addShaSignToParameters($parameters);
		
		return $parameters;
	}

	private function getTitleParameter(){
		$titleParameter = array();
		$title = trim($this->getConfiguration()->getTitle($this->getTransaction()->getTransactionContext()->getOrderContext()->getLanguage()));
		if (!empty($title)) {
			$titleParameter["TITLE"] = $title;
		}
		return $titleParameter;
	}

	protected function getReactionUrlParameters(){
		$parameters = array(
			'cw_transaction_id' => $this->getTransaction()->getExternalTransactionId(),
			'cuctr' => 't' 
		);
		$backUrl = $this->getEndpointAdapter()->getUrl('process', 'index', $parameters);
		
		return array(
			'ACCEPTURL' => $this->getSuccessUrl(),
			'DECLINEURL' => $this->getFailedUrl(),
			'EXCEPTIONURL' => $this->getFailedUrl(),
			'CANCELURL' => $backUrl,
			'BACKURL' => $backUrl 
		);
	}
}