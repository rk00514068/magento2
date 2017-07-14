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

//require_once 'Customweb/Payment/BackendOperation/Adapter/Service/IRefund.php';
//require_once 'Customweb/Ogone/BackendOperation/Adapter/RefundParameterBuilder.php';
//require_once 'Customweb/Util/Invoice.php';
//require_once 'Customweb/Ogone/AbstractAdapter.php';
//require_once 'Customweb/I18n/Translation.php';


/**
 *
 * @author Thomas Hunziker
 * @Bean
 *
 */
class Customweb_Ogone_BackendOperation_Adapter_RefundAdapter extends Customweb_Ogone_AbstractAdapter 
	implements Customweb_Payment_BackendOperation_Adapter_Service_IRefund {

	public function refund(Customweb_Payment_Authorization_ITransaction $transaction){
		/* @var $transaction Customweb_Ogone_Authorization_Transaction */
		$items = $transaction->getNonRefundedLineItems();
		return $this->partialRefund($transaction, $items, true);
	}

	public function partialRefund(Customweb_Payment_Authorization_ITransaction $transaction, $items, $close){

		/* @var $transaction Customweb_Ogone_Authorization_Transaction */
		$amount = Customweb_Util_Invoice::getTotalAmountIncludingTax($items);

		// Check transaction state
		$transaction->refundByLineItemsDry($items, $close);

		$builder = new Customweb_Ogone_BackendOperation_Adapter_RefundParameterBuilder($transaction, $this->getContainer(), $amount, $close);
		$parameters = $builder->buildParameters();

		$response = $this->sendMaintenanceRequest($parameters);

		$additionalMessage = '';
		switch($response['STATUS']) {
			case self::STATUS_REFUND:
				$additionalMessage = '';
				break;

			case self::STATUS_REFUND_UNCERTAIN:
				$additionalMessage = Customweb_I18n_Translation::__('The refund is in uncertain.');
				break;

			case self::STATUS_REFUND_PENDING:
			case self::STATUS_REFUND_PROCESSED_MERCHANT:
				$additionalMessage = Customweb_I18n_Translation::__('The refund is in progress.');
				break;

			case self::STATUS_REFUND_REFUSED:
			case self::STATUS_REFUND_DECLINED_ACQUIRER:
			case self::STATUS_INVALID:
			default:
				$detailedMessage = $response['NCERRORPLUS'];
				throw new Exception(Customweb_I18n_Translation::__(
					'The transaction could not be refunded. Details: @details',
					array('@details' => $detailedMessage)
				));
		}

		$transaction->refundByLineItems($items, $close, $additionalMessage);

		return true;
	}

}