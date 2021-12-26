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

namespace Lof\ZaloPayment\Model\Payment;

use Lof\ZaloPayment\Helper\Data as HelperData;
use Lof\ZaloPayment\Helper\ZaloPayHelper;
use Lof\ZaloPayment\Helper\ZaloPayMacGenerator;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentData;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Magento\Directory\Helper\Data as DirectoryData;

class ZaloPayment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'lof_zalopayment';
    
    protected $_code = self::CODE;
    protected $_isOffline = true;
    
    protected $_urlBuilder;
    protected $_helperData;
    protected $_zaloPayHelper;
    protected $_zaloPayMacGenerator;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param PaymentData $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param UrlInterface $urlBuilder
     * @param HelperData $_helperData
     * @param ZaloPayHelper $_zaloPayHelper
     * @param ZaloPayMacGenerator $_zaloPayMacGenerator
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     * @param DirectoryData $directory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        PaymentData $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        UrlInterface $urlBuilder,
        HelperData $_helperData,
        ZaloPayHelper $_zaloPayHelper,
        ZaloPayMacGenerator $_zaloPayMacGenerator,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        DirectoryData $directory = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data,
            $directory
        );
        
        $this->_urlBuilder = $urlBuilder;
        $this->_helperData = $_helperData;
        $this->_zaloPayHelper = $_zaloPayHelper;
        $this->_zaloPayMacGenerator = $_zaloPayMacGenerator;
    }
    
    public function isAvailable(
        CartInterface $quote = null
    ) {
        return parent::isAvailable($quote);
    }
    
    public function getZaloPaymentRequest(Order $order)
    {
        $token = substr(md5(rand()), 0, 32);

        $payment = $order->getPayment();
        $payment->setAdditionalInformation('zalopayment_order_token', $token);
        $payment->save();

        $description = [];
        foreach ($order->getAllItems() as $item) {
            if (!$item->getParentItemId()) {
                $description[] = number_format($item->getQtyOrdered(), 0) . ' × ' . $item->getName();
            }
        }
        
        $params = array(
            'amount' => number_format($this->_helperData->getAmountPaid($order, $order->getGrandTotal()), 0, '.', ''),
            'description' => $this->_helperData->getStoreName2() . ' : ' . join($description, ', '),
            'embeddata' => [],
            'item'=>[
                'entity_id'     => $order->getEntityId(),
                'protect_code'  => $order->getProtectCode(),
                'order_total_paid' =>  $order->getGrandTotal(),
            ],
        );
        
        $orderData = $this->_zaloPayHelper->newCreateOrderData($params);
        $order = $this->_zaloPayHelper->createOrder($orderData);
        if ($order["returncode"] === 1) {
            return [
                'status' => true,
                'payment_url' => $order['orderurl']
            ];
        } else {
            # Create order fail
            return [
                'status' => false,
                'returncode' => $order["returncode"],   // returncode = -5 (if amount < 1000 )
                'error_msg' => $this->getMessageError($order["returncode"]),
            ];
        }
    }
    
    public function getMessageError($code)
    {
        $code = '' . $code;
        $msg = '';
        $msg_long = '';
        switch ($code) {
            case '0':
                $msg = 'EXCEPTION';
                break;
            case '-2':
                $msg = 'APPID_INVALID';
                $msg_long = 'Sai thông tin AppId.';
                break;
            case '-3':
                $msg = 'APP_NOT_AVAILABLE';
                break;
            case '-4':
                $msg = 'APP_TIME_INVALID';
                break;
            case '-5':
                $msg = 'AMOUNT_INVALID';
                break;
            case '-9':
                $msg = 'PMCID_INVALID';
                break;
            case '-10':
                $msg = 'PMC_INACTIVE';
                break;
            case '-16':
                $msg = 'UPDATE_RESULT_FAIL';
                break;
            case '-17':
                $msg = 'EXCEED_MAX_NOTIFY';
                break;
            case '-19':
                $msg = 'APPID_NOT_MATCH';
                break;
            case '-31':
                $msg = 'CARD_INVALID';
                break;
            case '-32':
                $msg = 'APP_INACTIVE';
                break;
            case '-33':
                $msg = 'APP_MAINTENANCE';
                break;
            case '-34':
                $msg = 'PMC_MAINTENANCE';
                break;
            case '-35':
                $msg = 'PMC_NOT_AVAILABLE';
                break;
            case '-49':
                $msg = 'TRANS_INFO_NOT_FOUND';
                break;
            case '-52':
                $msg = 'ITEMS_INVALID';
                break;
            case '-53':
                $msg = 'HMAC_INVALID';
                $msg_long = 'Sai thông tin Key1, Key2.';
                break;
            case '-54':
                $msg = 'TIME_INVALID';
                break;
            case '-57':
                $msg = 'APP_USER_INVALID';
                break;
            case '-59':
                $msg = 'ZPW_PURCHASE_FAIL';
                break;
            case '-60':
                $msg = 'ZPW_ACCOUNT_NAME_INVALID';
                break;
            case '-61':
                $msg = 'ZPW_ACCOUNT_SUSPENDED';
                break;
            case '-62':
                $msg = 'ZPW_ACCOUNT_NOT_EXIST';
                break;
            case '-63':
                $msg = 'ZPW_BALANCE_NOT_ENOUGH';
                break;
            case '-65':
                $msg = 'ZPW_WRONG_PASSWORD';
                break;
            case '-66':
                $msg = 'USER_INVALID';
                break;
            case '-70':
                $msg = 'APPTRANSID_EXIST';
                break;
            case '-79':
                $msg = 'REQUEST_FORMAT_INVALID';
                break;
            case '-81':
                $msg = 'USER_NOT_MATCH';
                break;
            case '-83':
                $msg = 'TRANSID_FORMAT_INVALID';
                break;
            case '-86':
                $msg = 'TRANSTYPE_INVALID';
                break;
            case '-87':
                $msg = 'TRANSTYPE_INACTIVE';
                break;
            case '-88':
                $msg = 'TRANSTYPE_MAINTENANCE';
                break;
            case '-92':
                $msg = 'APPTRANSID_INVALID';
                break;
            case '-94':
                $msg = 'TRANSTYPE_AMOUNT_INVALID';
                break;
            case '-100':
                $msg = 'SIG_INVALID';
                break;
            case '-111':
                $msg = 'PIN_INVALID';
                break;
            case '-116':
                $msg = 'USER_NOT_EXIST';
                break;
            case '-117':
                $msg = 'USER_NOT_EXIST';
                break;
            case '-124':
                $msg = 'USER_IS_LOCKED';
                break;
            case '-126':
                $msg = 'PIN_SIZE_INVALID';
                break;
            case '-146':
                $msg = 'CHARGE_INFO_INVALID';
                break;
            case '-149':
                $msg = 'ZALOPAYNAME_INVALID';
                break;
            case '-186':
                $msg = 'COM_INFOTYPE_INVALID';
                break;
            case '-187':
                $msg = 'APP_USER_TYPE_INVALID';
                break;
            case '-999':
                $msg = 'SYSTEM_MAINTAIN';
                break;
        }
        
        return "ERROR $code $msg. $msg_long";
    }
}
