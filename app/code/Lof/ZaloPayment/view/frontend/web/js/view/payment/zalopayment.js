define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'lof_zalopayment',
                component: 'Lof_ZaloPayment/js/view/payment/method-renderer/zalopayment-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);