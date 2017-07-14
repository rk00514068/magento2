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


class Customweb_Ogone_Authorization_Server_AuthorizationParameterBuilder extends Customweb_Ogone_Authorization_AbstractAuthorizationParameterBuilder {
	
	/**
	 * We need different reactions url for server authorization
	 *         	 	 	  	 	  	 
	 */
	protected function getReactionUrlParameters() {
		$url = $this->getEndpointAdapter()->getUrl('process', 'index', array('cw_transaction_id' => $this->getTransaction()->getExternalTransactionId()));
		
		return array(
				'ACCEPTURL' => $url,
				'DECLINEURL' => $url,
				'EXCEPTIONURL' => $url
		);
	}
}