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
 *
 * @category	Customweb
 * @package		Customweb_OgoneCw
 * 
 */

namespace Customweb\OgoneCw\Controller\Endpoint;

class Index extends \Customweb\OgoneCw\Controller\Endpoint
{
	/**
	 * @var \Customweb\OgoneCw\Model\DependencyContainer
	 */
	protected $_container;
	
	/**
	 * @var \Customweb\OgoneCw\Model\Adapter\Endpoint
	 */
	protected $_endpointAdapter;
	
	/**
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Customweb\OgoneCw\Model\DependencyContainer $container
	 * @param \Customweb\OgoneCw\Model\Adapter\Endpoint $endpointAdapter
	 */
	public function __construct(
			\Magento\Framework\App\Action\Context $context,
			\Customweb\OgoneCw\Model\DependencyContainer $container,
			\Customweb\OgoneCw\Model\Adapter\Endpoint $endpointAdapter
	) {
		parent::__construct($context);
		$this->_container = $container;
		$this->_endpointAdapter = $endpointAdapter;
	}
	
	public function execute()
	{
		$packages = array(
			0 => 'Customweb_Ogone',
 			1 => 'Customweb_Payment_Authorization',
 		);
		
		$om = \Magento\Framework\App\ObjectManager::getInstance();
		if (!$this->getRequest()->getParam('currency')) {
			$registychecksession = $om->get('Magento\Checkout\Model\Session');
			$registychecksession->setBackurl('1');
		}
		$dispatcher = new \Customweb_Payment_Endpoint_Dispatcher($this->_endpointAdapter, $this->_container, $packages);
		$response = $dispatcher->dispatch(\Customweb_Core_Http_ContextRequest::getInstance());
		
		$wrapper = new \Customweb_Core_Http_Response($response);
		$wrapper->send();
		die();
	}
}