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

//require_once 'Customweb/Ogone/Method/DefaultMethod.php';


/**
 *
 * @author Thomas Hunziker
 * @Method(paymentMethods={'DirectEbanking'})
 */
class Customweb_Ogone_Method_DirectEbanking extends Customweb_Ogone_Method_DefaultMethod {
	
	public function getPaymentMethodBrandAndMethod(Customweb_Ogone_Authorization_Transaction $transaction) {
		$countryCode = strtoupper($this->getPaymentMethodConfigurationValue('brand_country'));
		
		if ($countryCode === 'NO_CODE') {
			return array(
				'pm' => 'DirectEbanking',
				'brand' => 'Sofort Uberweisung',
			);
		}
		else {
			return array(
				'pm' => 'DirectEbanking' . $countryCode,
				'brand' => 'DirectEbanking' . $countryCode,
			);
				
		}
	}
	
}