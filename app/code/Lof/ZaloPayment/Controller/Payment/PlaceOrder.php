<?php
/**
 * Landofcoder
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Landofcoder.com license that is
 * available through the world-wide-web at this URL:
 * https://landofcoder.com/license
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category   Landofcoder
 * @package    Lof_PromotionBar
 * @copyright  Copyright (c) 2019 Landofcoder (http://www.landofcoder.com/)
 * @license    http://www.landofcoder.com/LICENSE-1.0.html
 */

namespace Lof\ZaloPayment\Controller\Payment;

use Lof\ZaloPayment\Model\Payment\ZaloPayment;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\OrderFactory;

class PlaceOrder extends Action
{
    protected $_orderFactory;
    protected $_zaloPayment;
    protected $_checkoutSession;
    protected $_quoteRepository;

    /**
     * @param Context $context
     * @param CartRepositoryInterface $quoteRepository
     * @param OrderFactory $orderFactory
     * @param Session $checkoutSession
     * @param ZaloPayment $zaloPayment
     */
    public function __construct(
        Context $context,
        CartRepositoryInterface $quoteRepository,
        OrderFactory $orderFactory,
        Session $checkoutSession,
        ZaloPayment $zaloPayment
    ) {
        parent::__construct($context);
        $this->_quoteRepository = $quoteRepository;
        $this->_orderFactory = $orderFactory;
        $this->_zaloPayment = $zaloPayment;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckout()
    {
        return $this->_objectManager->get('Magento\Checkout\Model\Session');
    }

    public function execute()
    {
        $id = $this->_checkoutSession->getLastOrderId();
        $order = $this->_orderFactory->create()->load($id);
        if (!$order->getIncrementId()) {
            $this->getResponse()->setBody(json_encode([
                'status' => false,
                'reason' => 'Order Not Found',
            ]));
            return;
        }

        # RESTORES CART
        $quote = $this->_quoteRepository->get($order->getQuoteId());
        $quote->setIsActive(1);
        $this->_quoteRepository->save($quote);

        $this->getResponse()->setBody(json_encode($this->_zaloPayment->getZaloPaymentRequest($order)));
    }

}
