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

use Lof\ZaloPayment\Helper\ZaloPayHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Cart;

class Redirect extends Action
{
    protected $_zaloPayHelper;
    protected $_cart;

    /**
     * @param Context $context
     * @param ZaloPayHelper $zaloPayHelper
     * @param Cart $cart
     */
    public function __construct(
        Context $context,
        ZaloPayHelper $zaloPayHelper,
        Cart $cart
    ) {
        parent::__construct($context);
        $this->_zaloPayHelper = $zaloPayHelper;
        $this->_cart = $cart;
    }
    
    /**
     * set order is paid
     */
    public function execute()
    {
//        \Lof\ZaloPayment\Helper\Log::logRedirect();
        # EMPTY CART
        $this->_cart->truncate();
        $this->_cart->saveQuote();
        $data = $this->getRequest()->getParams();
        if (isset($data['checksum'])
            && isset($data['appid'])
            && isset($data['apptransid'])
            && isset($data['pmcid'])
            && isset($data['bankcode'])
            && isset($data['amount'])
            && isset($data['discountamount'])
            && isset($data['status'])
        ) {
            $isValidRedirect = $this->_zaloPayHelper->verifyRedirect($data);
            if ($isValidRedirect) {
                if ($data['status'] == 1) {
                    # CUSTOMER NOTIFICATION
                    $this->messageManager->addSuccess(
                        __('You paid via ZaloPay successfully.')
                    );
                    $this->_redirect('checkout/cart');
                    return;
                } elseif ($data['status'] == -49) {
                    # CUSTOMER NOTIFICATION
                    $this->messageManager->addWarning(
                        __("You have canceled your order")
                    );
                    $this->_redirect('checkout/cart');
                    return;
                }
            }
        }
        
        $this->messageManager->addError(
            __('You pay via ZaloPay failed.')
        );
        $this->_redirect('checkout/cart');
    }
}
