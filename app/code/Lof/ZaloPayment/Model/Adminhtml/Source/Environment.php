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

namespace Lof\ZaloPayment\Model\Adminhtml\Source;

use Lof\ZaloPayment\Helper\Data as HelperData;

class Environment implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Possible environment types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => HelperData::ENVIRONMENT_SANDBOX,
                'label' => 'Sandbox',
            ],
            [
                'value' => HelperData::ENVIRONMENT_PRODUCTION,
                'label' => 'Production'
            ]
        ];
    }
}
