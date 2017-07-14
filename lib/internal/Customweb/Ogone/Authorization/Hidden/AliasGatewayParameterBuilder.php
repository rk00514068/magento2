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

//require_once 'Customweb/Ogone/Util.php';
//require_once 'Customweb/Ogone/AbstractParameterBuilder.php';


/**
 *
 * @author Thomas Hunziker
 * @Bean
 *
 */
class Customweb_Ogone_Authorization_Hidden_AliasGatewayParameterBuilder extends Customweb_Ogone_AbstractParameterBuilder {
	
	/**
	 * (non-PHPdoc)
	 * @see Customweb_Ogone_AbstractParameterBuilder::buildParameters()
	 */
	public function buildParameters() {
		$parameters = array_merge(
			$this->getLanguageParameter(),
			$this->getPspParameter(),
			$this->getOrderIdParameter(),
			$this->getReactionUrlParameters(),
			$this->getPaymentMethod()->getAliasGatewayAuthorizationParameters(
				$this->getTransaction(), 
				array(), 
				$this->getTransaction()->getAuthorizationMethod()
			)
		);
		
		// The parameter 'pm' cannot be set!         	 	 	  	 	  	 
		unset($parameters['pm']);
		unset($parameters['ALIASUSAGE']);
		
		
		// Handle the alias manager
		$aliasTransaction = $this->getTransaction()->getTransactionContext()->getAlias();
		$cardno = null;
		if ($aliasTransaction !== null && $aliasTransaction != 'new') {
			/** @var $aliasTransaction Customweb_Ogone_Authorization_Transaction */
			$params = $aliasTransaction->getDirectLinkCreationParameters();
			$aliasCreationResponse = $aliasTransaction->getAliasCreationResponse();
			if (is_array($aliasCreationResponse)) {
				$params = $aliasCreationResponse;
			}
				
			// In anycase a card number must be set if we have create an alias
			if (isset($params['CARDNO'])) {
				$cardno = $params['CARDNO'];
				$parameters['ALIAS'] = $aliasTransaction->getAliasIdentifier();
			}
		}
		
		
		// We have to set the alias transaction id to ensure that we use the same id over multiple tries to prevent the
		// customer from entering the data on each try.
		if ($this->getTransaction()->getAliasTransactionId() !== null) {
			$parameters['ORDERID'] = Customweb_Ogone_Util::applyOrderSchema(
					$this->getConfiguration(),
					$this->getTransaction()->getAliasTransactionId()
			);
		}
		
		$this->addShaSignToParameters($parameters);
		
		// There is a bug: When sending new ACCEPTURL, PARAMPLUS etc. the remote side returns the parameter of the previous transaction.
		// Hence we need to add them here separately:         	 	 	  	 	  	 
		$additionalParms = $this->getTransaction()->getTransactionContext()->getCustomParameters();
		if (is_array($additionalParms)) {
			$parameters = array_merge($parameters, $additionalParms);
		}
		
		if ($cardno !== null) {
			$parameters['CARDNO'] = $cardno;
		}
		
		return $parameters;
	}
	
	protected function getReactionUrlParameters() {
		$url = $this->getEndpointAdapter()->getUrl('process', 'index', array('cwTransId' => $this->getTransaction()->getExternalTransactionId()));
		return array(
			'ACCEPTURL' => $url,
			'EXCEPTIONURL' => $url
		);
	}
}