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



/**
 * This interface provides some constants required for handling the interaction
 * with Ogone service.
 *         	 	 	  	 	  	 
 * @author Simon Schurter
 *
 */
interface Customweb_Ogone_IAdapter  {
	
	const STATUS_INVALID								= 0;
	const STATUS_CANCELED_BY_CUSTOMER					= 1;
	const STATUS_AUTHORISATION_REFUSED					= 2;
	
	const STATUS_ORDER_STORED							= 4;
	const STATUS_STORED_WAITING_EXTERNAL_RESULT			= 40;
	const STATUS_WAITING_FOR_CLIENT_PAYMENT				= 41;
	const STATUS_WAITING_FOR_IDENTIFICATION				= 46;
	
	const STATUS_AUTHORISED								= 5;
	const STATUS_AUTHORISED_WAITING_EXTERNAL_RESULT		= 50;
	const STATUS_AUTHORISED_WAITING						= 51;
	const STATUS_AUTHORISED_NOT_KNOWN					= 52;
	const STATUS_STANDBY								= 55;
	const STATUS_SCHEDULED_PAYMENTS_OK					= 56;
	const STATUS_SCHEDULED_PAYMENTS_NOT_OK				= 57;
	const STATUS_AUTHORISATION_REQUEST_MANUALLY			= 59;
	
	const STATUS_CANCELED								= 6;
	const STATUS_CANCELED_WAITING						= 61;
	const STATUS_CANCELED_UNCERTAIN						= 62;
	const STATUS_CANCELED_REFUSED						= 63;
	const STATUS_CANCELED_OK							= 64;
	
	const STATUS_PAYMENT_DELETED						= 7;
	const STATUS_PAYMENT_DELETED_PENDING				= 71;
	const STATUS_PAYMENT_DELETED_UNCERTAIN				= 72;
	const STATUS_PAYMENT_DELETED_REFUSED				= 73;
	const STATUS_PAYMENT_DELETED_OK						= 74;
	const STATUS_PAYMENT_DELETED_PROCESSED_MERCHANT		= 75;
	
	const STATUS_REFUND									= 8;
	const STATUS_REFUND_PENDING							= 81;
	const STATUS_REFUND_UNCERTAIN						= 82;
	const STATUS_REFUND_REFUSED							= 83;
	const STATUS_REFUND_DECLINED_ACQUIRER				= 84;
	const STATUS_REFUND_PROCESSED_MERCHANT				= 85;
	
	const STATUS_PAYMENT_REQUESTED						= 9;
	const STATUS_PAYMENT_PROCESSING						= 91;
	const STATUS_PAYMENT_UNCERTAIN						= 92;
	const STATUS_PAYMENT_REFUSED						= 93;
	const STATUS_PAYMENT_DECLINED_ACQUIRER				= 94;
	const STATUS_PAYMENT_PROCESSED_MERCHANT				= 95;
	const STATUS_PAYMENT_IN_PROGRESS					= 99;
	
	const URL_PAYMENT_PAGE								= 'orderstandard_utf8.asp';
	const URL_ALIAS_GATEWAY								= 'alias_gateway_utf8.asp';
	const URL_DIRECT_ORDER								= 'orderdirect_utf8.asp';
	const URL_MAINTENANCE								= 'maintenancedirect.asp';
	const URL_QUERY_ORDER								= 'querydirect.asp';
	
	const OPERATION_AUTHORISATION						= 'RES';
	const OPERATION_DIRECT_SALE							= 'SAL';
	const OPERATION_CAPTURE_FULL						= 'SAS';
	const OPERATION_CAPTURE_PARTIAL						= 'SAL';
	const OPERATION_DELETE_AUTHORISATION				= 'DEL';
	const OPERATION_DELETE_AUTHORISATION_AND_CLOSE		= 'DES';
	const OPERATION_REFUND_FULL							= 'RFS';
	const OPERATION_REFUND_PARTIAL						= 'RFD';
	
	const ECI_MOTO										= 1;
	const ECI_MOTO_RECURRING							= 2;
	const ECI_INSTALLMENT								= 3;
	const ECI_3D										= 5;
	const ECI_CARDHOLD_NOT_PARTICIPATE_IN_3D			= 6;
	const ECI_SSL										= 7;
	const ECI_REGULAR_RECURRING							= 9;
	
	
	
}