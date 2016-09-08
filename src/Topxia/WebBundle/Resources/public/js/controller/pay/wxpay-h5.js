define(function (require, exports, module) {
    exports.run = function () {
        $('.section-wxpay').on('click', '.js-wxpay', function () {
            if (typeof WeixinJSBridge == "undefined") {
                if (document.addEventListener) {
                    document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
                } else if (document.attachEvent) {
                    document.attachEvent('WeixinJSBridgeReady', jsApiCall);
                    document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
                }
            } else {
                jsApiCall();
            }
        });

        //调用微信JS api 支付
        function jsApiCall() {
            WeixinJSBridge.invoke(
                'getBrandWCPayRequest',
                $("#jsApiParameters").data('value'),
                function (res) {
                    alert($("#jsApiParameters").data('goto'));
                    alert(res.err_msg);
                    if (res.err_msg == 'get_brand_wcpay_request:ok') {
                        window.location.href = $("#jsApiParameters").data('goto');
                    } else {
                        alert(res.err_msg);
                        window.location.href = $("#jsApiParameters").data('goto');
                    }

                }
            );
        }

    };
});