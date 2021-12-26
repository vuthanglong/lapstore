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
use Lof\ZaloPayment\Helper\ZaloPayMacGenerator;

class ZaloPayHelper
{

    private static $PUBLIC_KEY;
    private static $UID;

    protected $_context;
    protected $_helperData;
    protected $_zaloPayMacGenerator;
    
    public function __construct(
        Context $context,
        HelperData $helperData,
        ZaloPayMacGenerator $zaloPayMacGenerator
    ) {
        $this->_context = $context;
        $this->_helperData = $helperData;
        $this->_zaloPayMacGenerator = $zaloPayMacGenerator;
    }

    public function init()
    {
        self::$PUBLIC_KEY = file_get_contents('publickey.pem');
        self::$UID = getTimestamp();
    }

    /**
     * is valid Callback
     *
     * @param array $params ["data" => string, "mac" => string]
     * @return array ["returncode" => int, "returnmessage" => string]
     */
    public function verifyCallback(Array $params)
    {
        $data = $params["data"];
        $requestMac = $params["mac"];

        $result = [];
        $mac = $this->_zaloPayMacGenerator->compute($data, $this->_helperData->getConfig('key2'));

        if ($mac != $requestMac) {
            $result['returncode'] = -1;
            $result['returnmessage'] = 'mac not equal';
        } else {
            $result['returncode'] = 1;
            $result['returnmessage'] = 'success';
        }

        return $result;
    }

    /**
     * is valid Redirect
     *
     * @param array $data - is query string ($_GET)
     * @return bool
     *  - true: valid
     *  - false: invalid
     */
    public function verifyRedirect(Array $data)
    {
        $reqChecksum = $data["checksum"];
        $checksum = $this->_zaloPayMacGenerator->redirect($data);

        return $reqChecksum === $checksum;
    }

    /**
     * Generate apptransid || mrefundid
     *
     * @return string
     *  - apptransid format yyMMddxxxxx
     *  - mrefundid format yyMMdd_appid_xxxxx
     */
    public function genTransID($appID, $getTimestamp)
    {
        return date("ymd") . "_" . $appID . "_" . $getTimestamp;
    }

    /**
     * @param Array $params [
     *  "amount" => long,
     *  "description" => string (optional),
     *  "bankcode" => string (optional - default "zalopayapp"),
     *  "appuser" => string (optional - default "demo"),
     *  "item" => string (optional - default "")
     * ]
     * @return Array
     */
    public function newCreateOrderData(Array $params)
    {
        $embeddata = [];

        if (array_key_exists("embeddata", $params)) {
            $embeddata = $params["embeddata"];
        }
        
        $getTimeStamp = $this->getTimeStamp();

        $order = [
            'appid' => $this->_helperData->getConfig('appid'),
            "apptime" => $this->getTimeStamp(),
            "apptransid" => $this->genTransID($this->_helperData->getConfig('appid'), $getTimeStamp),
            "appuser" => array_key_exists("appuser", $params) ? $params["appuser"] : "demo",
            "item" => $this->_helperData->encode(array_key_exists("item", $params) ? $params["item"] : []),
            "embeddata" => $this->_helperData->encode($embeddata),
            "bankcode" => array_key_exists("bankcode", $params) ? $params["bankcode"] : "zalopayapp",
            "description" => array_key_exists("description", $params) ? $params['description'] : "",
            "amount" => $params['amount'],
        ];

        return $order;
    }

    /**
     * @param array $order
     * @return array
     */
    public function createOrder(Array $order)
    {
        $order['mac'] = $this->_zaloPayMacGenerator->createOrder($order);
        $result = $this->_helperData->postForm($this->_helperData->getUrlGateway('createorder'), $order);
        return $result;
    }

    /**
     * @param array $params
     * @return array
     */
    public function newQuickPayOrderData(Array $params)
    {
        $order = self::newCreateOrderData($params);
        $order['userip'] = array_key_exists('userip', $params) ? $params['userip'] : "127.0.0.1";
        openssl_public_encrypt($params['paymentcodeRaw'], $encrypted, self::$PUBLIC_KEY);
        $order['paymentcode'] = base64_encode($encrypted);
        $order['mac'] = ZaloPayMacGenerator::quickPay($order, $params['paymentcodeRaw']);
        return $order;
    }

    /**
     * @param Array $order
     * @return Array
     */
    public function quickPay(Array $order)
    {
        $result = Http::postForm(Config::get()['api']['quickpay'], $order);
        return $result;
    }

    /**
     * @param String $apptransid
     * @return array
     */
    public function getOrderStatus(string $apptransid)
    {
        $params = [
            "appid" => Config::get()['appid'],
            "apptransid" => $apptransid
        ];
        $params["mac"] = ZaloPayMacGenerator::getOrderStatus($params);
        return Http::postForm(Config::get()['api']['getorderstatus'], $params);
    }

    /**
     * @param array $params [
     *    "zptransid" => string,
     *    "amount" => long,
     *    "description" => string
     * ]
     * @return array
     */
    public function newRefundData(Array $params)
    {
        $refundData = [
            "appid" => Config::get()['appid'],
            "timestamp" => getTimestamp(),
            "mrefundid" => self::genTransID(),
            "zptransid" => $params['zptransid'],
            "amount" => $params['amount'],
            "description" => $params['description']
        ];

        $refundData['mac'] = ZaloPayMacGenerator::refund($refundData);
        return $refundData;
    }

    /**
     * @param array $refundData
     * @return array
     */
    public function refund(Array $refundData)
    {
        $result = Http::postForm(Config::get()['api']['refund'], $refundData);
        $result['mrefundid'] = $refundData['mrefundid'];

        return $result;
    }

    /**
     * @param String
     * @return array
     */
    public function getRefundStatus(String $mrefundid)
    {
        $params = [
            "appid" => Config::get()['appid'],
            "mrefundid" => $mrefundid,
            "timestamp" => getTimestamp()
        ];

        $params['mac'] = ZaloPayMacGenerator::getRefundStatus($params);
        return Http::postForm(Config::get()['api']['getrefundstatus'], $params);
    }

    /**
     * @return array
     */
    public function getBankList()
    {
        $params = [
            "appid" => Config::get()['appid'],
            "reqtime" => getTimestamp()
        ];

        $params['mac'] = ZaloPayMacGenerator::getBankList($params);
        return Http::postForm(Config::get()['api']['getbanklist'], $params);
    }
    
    public function getTimestamp()
    {
        return round(microtime(true) * 1000);
    }
}
