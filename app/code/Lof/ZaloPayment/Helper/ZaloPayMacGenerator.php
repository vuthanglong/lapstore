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

namespace Lof\ZaloPayment\Helper;

use Magento\Framework\App\Action\Context;
use Lof\ZaloPayment\Helper\Data as HelperData;

class ZaloPayMacGenerator
{
    protected $_context;
    protected $_helperData;

    public function __construct(
        Context $context,
        HelperData $helperData
    ) {
        $this->_context = $context;
        $this->_helperData = $helperData;
    }

    public function compute(string $params, string $key = null)
    {
        if (is_null($key)) {
            $key = $this->_helperData->getConfig('key1');
        }
        return hash_hmac("sha256", $params, $key);
    }

    public function createOrderMacData(Array $order)
    {
        return $order["appid"] . "|" . $order["apptransid"] . "|" . $order["appuser"] . "|" . $order["amount"] . "|" . $order["apptime"] . "|" . $order["embeddata"] . "|" . $order["item"];
    }

    public function createOrder(Array $order)
    {
        return $this->compute($this->createOrderMacData($order));
    }

    public function quickPay(Array $order, String $paymentcodeRaw)
    {
        return $this->compute($this->createOrderMacData($order) . "|" . $paymentcodeRaw);
    }

    public function refund(Array $params)
    {
        return $this->compute($params['appid'] . "|" . $params['zptransid'] . "|" . $params['amount'] . "|" . $params['description'] . "|" . $params['timestamp']);
    }

    public function getOrderStatus(Array $params)
    {
        return $this->compute($params['appid'] . "|" . $params['apptransid'] . "|" . $this->_helperData->getConfig('key1'));
    }

    public function getRefundStatus(Array $params)
    {
        return $this->compute($params['appid'] . "|" . $params['mrefundid'] . "|" . $params['timestamp']);
    }

    public function getBankList(Array $params)
    {
        return $this->compute($params['appid'] . "|" . $params['reqtime']);
    }

    public function redirect(Array $params)
    {
        $str_param = $params['appid'] . "|" . $params['apptransid'] . "|" . $params['pmcid'] . "|" . $params['bankcode'] . "|" . $params['amount'] . "|" . $params['discountamount'] . "|" . $params["status"];
        $key2 = $this->_helperData->getConfig('key2');
        return $this->compute($str_param, $key2);
    }
}