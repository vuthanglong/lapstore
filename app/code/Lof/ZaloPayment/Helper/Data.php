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

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    const ENVIRONMENT_PRODUCTION               = 'production';
    const ENVIRONMENT_SANDBOX                  = 'sandbox';

    const ORDER_PENDING                        = 'pending';
    const ORDER_INCOMPLETE                     = 'incomplete';
    const ORDER_SUCCESSFUL                     = 'successful';
    const ORDER_FAILED                         = 'failed';
    const ORDER_ERROR                          = 'error';
    
    /**
     * @var \Magento\Framework\App\Helper\Context
     */
    protected $_context;
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    
    /**
     * @param \Magento\Framework\App\Helper\Context              $context
     * @param \Magento\Store\Model\StoreManagerInterface         $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_context  = $context;
        $this->_storeManager  = $storeManager;
        parent::__construct($context);
    }
    
    /**
     * Get Store name
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }
    
    public function getStoreName2()
    {
        $store = $this->_storeManager->getStore();
        $result = $this->scopeConfig->getValue(
            'general/store_information/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        
        if (!$result) {
            $result = 'Store Name ';
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return true;
    }
    
    public function getConfig($key, $store = null)
    {
        if (!$store) {
            $store = $this->_storeManager->getStore($store);
        }
        $result = $this->scopeConfig->getValue(
            'payment/lof_zalopayment/' . $key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        return $result;
    }
    
    /**
     * @param string $mode (production, sanbox)
     * @return array
     */
    public function urlGateway($mode = 'production')
    {
        if ($mode == self::ENVIRONMENT_SANDBOX) {
            $configs = [
                'createorder'   => 'https://sandbox.zalopay.com.vn/v001/tpe/createorder',
                'gateway'       => 'https://sbgateway.zalopay.vn/pay?order=',
                'quickpay'      => 'https://sandbox.zalopay.com.vn/v001/tpe/submitqrcodepay',
                'refund'        => 'https://sandbox.zalopay.com.vn/v001/tpe/partialrefund',
                'getrefundstatus'   => 'https://sandbox.zalopay.com.vn/v001/tpe/getpartialrefundstatus',
                'getorderstatus'    => 'https://sandbox.zalopay.com.vn/v001/tpe/getstatusbyapptransid',
                'getbanklist'       => 'https://sbgateway.zalopay.vn/api/getlistmerchantbanks'
            ];
        } else {
            $configs = [
                'createorder'   => 'https://zalopay.com.vn/v001/tpe/createorder',
                'gateway'       => 'https://gateway.zalopay.vn/pay?order=',
                'quickpay'      => 'https://zalopay.com.vn/v001/tpe/submitqrcodepay',
                'refund'        => 'https://merchant.zalopay.vn/v001/partialrefund',
                'getrefundstatus'   => 'https://zalopay.com.vn/v001/tpe/getpartialrefundstatus',
                'getorderstatus'    => 'https://zalopay.com.vn/v001/tpe/getstatusbyapptransid',
                'getbanklist'       => 'https://gateway.zalopay.vn/api/getlistmerchantbanks'
            ];
        }
        return $configs;
    }
    
    public function getUrlGateway($key = 'createorder')
    {
        $environment = $this->getConfig('environment');
        $links = $this->urlGateway($environment);
        return $links[$key];
    }


    public function encode(Array $data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function decode(string $data)
    {
        return json_decode($data, true);
    }

    public function parseFile(string $path)
    {
        $jsonStr = file_get_contents($path);
        return $this->decode($jsonStr);
    }
    
    public function postForm($url, $params)
    {
        $context = stream_context_create([
            "http" => [
                "header" => "Content-type: application/x-www-form-urlencoded\r\n",
                "method" => "POST",
                "content" => http_build_query($params)
            ]
        ]);
    
        return $this->decode(file_get_contents($url, false, $context));
    }
    
    /**
     * Retrieve the amount paid by current store
     *
     * @param  \Magento\Sales\Model\Order $order
     * @param  string                     $amount
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAmountPaid($order, $amount)
    {
        $baseCurrencyCode = $order->getBaseCurrencyCode();
        switch ($baseCurrencyCode) {
            case 'VND':
                $orderCurrencyCode = $order->getOrderCurrencyCode();
                if ($orderCurrencyCode == 'VND') {
                    return $amount;
                }

                $currencyRate = $this->_storeManager->getStore()
                ->getBaseCurrency()
                ->getRate($orderCurrencyCode);

                if ($currencyRate) {
                    return round($amount * $currencyRate, 0);
                }
                return $amount;
            default:
                $orderCurrencyCode = $order->getOrderCurrencyCode();
                if ($orderCurrencyCode == 'VND') {
                    return $amount;
                }

                $currencyRate = $this->_storeManager->getStore()
                ->getBaseCurrency()
                ->getRate('VND');

                if ($currencyRate) {
                    return round($amount * $currencyRate, 0);
                }
                return $amount;
        }
    }
}
