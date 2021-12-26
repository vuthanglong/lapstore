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

use Lof\ZaloPayment\Helper\Data;
use Lof\ZaloPayment\Helper\ZaloPayHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Checkout\Model\Cart;

class Callback extends Action implements CsrfAwareActionInterface
{
    protected $_order;
    protected $_helperData;
    protected $_zaloPayHelper;
    protected $_cart;

    /**
     * @param Context $context
     * @param Order $order
     * @param Data $helperData
     * @param ZaloPayHelper $zaloPayHelper
     */
    public function __construct(
        Context $context,
        Order $order,
        Data $helperData,
        ZaloPayHelper $zaloPayHelper,
        Cart $cart
    ) {
        parent::__construct($context);
        $this->_order = $order;
        $this->_helperData = $helperData;
        $this->_zaloPayHelper = $zaloPayHelper;
        $this->_cart = $cart;
    }
    
    /**
     * set order is paid
     */
    public function execute()
    {
//        \Lof\ZaloPayment\Helper\Log::logCallback();
        # VALIDATE METHOD POST
        $isPostMethod = $_SERVER['REQUEST_METHOD'] === 'POST';
        if (!$isPostMethod) {
            http_response_code(405);
            return;
        }
        
        try {
            $params = $this->_helperData->decode(file_get_contents('php://input'), true);
            $result = $this->_zaloPayHelper->verifyCallback($params);
            if ($result['returncode'] === 1) {
                # PAY MONENY IS SUCCESSFUL
                $data = $this->_helperData->decode($params["data"]);
                $item = $this->_helperData->decode($data['item']);
                
                $post_order_id = $item['entity_id'];
                $post_protect_code = $item['protect_code'];
                $order = $this->_order->loadByIncrementId((int)$post_order_id);

                if ($order && $order->getProtectCode() == $post_protect_code) {
                    # UPDATE ORDER MONEY
                    $order->setTotalPaid($item['order_total_paid']);
                    $order->setBaseTotalPaid($item['order_total_paid']);
                    
                    # UPDATE ORDER STATUS
                    $order->setState(Order::STATE_PAYMENT_REVIEW);
                    $order->setStatus(Order::STATE_PAYMENT_REVIEW);
                    $order->save();
                    
                    # EMPTY CART
                    $this->_cart->truncate();
                    $this->_cart->saveQuote();
                }
            } else {
                # ZALOPAY RETURN ERROR
            }
            
            # PAYMENT NOTIFICATION
            echo $this->_helperData->encode($result);
        } catch (Exception $ex) {
            # PAYMENT NOTIFICATION
            echo $this->_helperData->encode([
                'returncode' => 0, # ZaloPay Server callback is 3 times
                'returnmessage' => 'exception'
            ]);
        }
    }

    public function createCsrfValidationException(RequestInterface $request): ? InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ? bool
    {
        return true;
    }
}
