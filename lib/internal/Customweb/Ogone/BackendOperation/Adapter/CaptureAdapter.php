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

//require_once 'Customweb/Ogone/BackendOperation/Adapter/CapturingParameterBuilder.php';
//require_once 'Customweb/Payment/BackendOperation/Adapter/Service/ICapture.php';
//require_once 'Customweb/Util/Invoice.php';
//require_once 'Customweb/Ogone/AbstractAdapter.php';
//require_once 'Customweb/I18n/Translation.php';


/**
 *
 * @author Thomas Hunziker
 * @Bean
 *
 */
class Customweb_Ogone_BackendOperation_Adapter_CaptureAdapter extends Customweb_Ogone_AbstractAdapter 
	implements Customweb_Payment_BackendOperation_Adapter_Service_ICapture {

	public function capture(Customweb_Payment_Authorization_ITransaction $transaction){
		/* @var $transaction Customweb_Ogone_Authorization_Transaction */
		$items = $transaction->getUncapturedLineItems();
		return $this->partialCapture($transaction, $items, true);
	}
	
	public function partialCapture(Customweb_Payment_Authorization_ITransaction $transaction, $items, $close){

		/* @var $transaction Customweb_Ogone_Authorization_Transaction */
		// Check transaction state
		$transaction->partialCaptureByLineItemsDry($items, $close);
		$amount = Customweb_Util_Invoice::getTotalAmountIncludingTax($items);
		
		$builder = new Customweb_Ogone_BackendOperation_Adapter_CapturingParameterBuilder($transaction, $this->getContainer(), $amount, $close);
		$parameters = $builder->buildParameters();
		
		$response = $this->sendMaintenanceRequest($parameters);
		
		$additionalMessage = '';
		switch($response['STATUS']) {
			case self::STATUS_PAYMENT_REQUESTED:
			case self::STATUS_PAYMENT_PROCESSED_MERCHANT:
				$additionalMessage = '';
				break;
		
			case self::STATUS_PAYMENT_UNCERTAIN:
				$additionalMessage = Customweb_I18n_Translation::__('The capturing is in uncertain.');
				break;
		
			case self::STATUS_PAYMENT_PROCESSING:
			case self::STATUS_PAYMENT_IN_PROGRESS:
				$additionalMessage = Customweb_I18n_Translation::__('The capturing is in progress.');
				break;
		
			case self::STATUS_PAYMENT_REFUSED:
			case self::STATUS_PAYMENT_DECLINED_ACQUIRER:
			case self::STATUS_AUTHORISATION_REFUSED:
			case self::STATUS_INVALID:
			default:
		
				$detailedMessage = $response['NCERRORPLUS'];
				throw new Exception(Customweb_I18n_Translation::__(
					'The transaction could not be captured. Details: @details',
					array('@details' => $detailedMessage)
				));
		}
		
		$captureItem = $transaction->partialCaptureByLineItems($items, $close, $additionalMessage);
		return true;
	}
		
}