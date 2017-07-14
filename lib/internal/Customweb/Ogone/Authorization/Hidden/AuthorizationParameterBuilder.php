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

//require_once 'Customweb/Ogone/Authorization/AbstractAuthorizationParameterBuilder.php';
//require_once 'Customweb/Util/Url.php';


/**
 *
 * @author Thomas Hunziker
 * @Bean
 *
 */
class Customweb_Ogone_Authorization_Hidden_AuthorizationParameterBuilder extends Customweb_Ogone_Authorization_AbstractAuthorizationParameterBuilder {

	protected function getReactionUrlParameters() {
		$url = Customweb_Util_Url::appendParameters(
				$this->getTransactionContext()->getSuccessUrl(),
				$this->getTransactionContext()->getCustomParameters()
		);
		
		return array(
				'ACCEPTURL' => $url,
				'DECLINEURL' => $url,
				'EXCEPTIONURL' => $url
		);
	}
	
	protected function getCustomerParameters(){
		$parameters = parent::getCustomerParameters();
		
		//For alias gateway use, the cardholder entred in the form
		if(isset($this->formData['CN'])){
			$cn = $this->formData['CN'];
			if(!empty($cn)){
				$parameters['CN'] = $cn;
			}
		}
		
		return $parameters;
	}
}