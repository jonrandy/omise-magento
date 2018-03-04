define(
    [
        'ko',
        'Magento_Payment/js/view/payment/cc-form',
        'mage/storage',
        'mage/translate',
        'jquery',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder'
    ],
    function (
        ko,
        Component,
        storage,
        $t,
        $,
        validator,
        errorProcessor,
        fullScreenLoader,
        redirectOnSuccessAction,
        quote,
        urlBuilder
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Omise_Payment/payment/omise-cc-form'
            },

            redirectAfterPlaceOrder: true,

            isPlaceOrderActionAllowed: ko.observable(quote.billingAddress() != null),

            /**
             * Get payment method code
             *
             * @return {string}
             */
            getCode: function() {
                return 'omise_cc';
            },

            /**
             * Get a checkout form data
             *
             * @return {Object}
             */
            getData: function() {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'omise_card_token': this.omiseCardToken(),
                        'omise_card_id'   : this.getOmiseCardId(),
                        'omise_save_card' : this.getOmiseSaveCard()
                    }
                };
            },

            /**
             * Get Omise public key
             *
             * @return {string}
             */
            getPublicKey: function() {
                return window.checkoutConfig.payment.omise_cc.publicKey;
            },

            /**
             * Get Omise cards
             *
             * @return {array}
             */
            getCards: function(){
                return window.checkoutConfig.payment.omise_cc.cards;
            },

            /**
             * @return {bool}
             */
            hasCard: function(){
                var cards = this.getCards();
                return cards.length > 0;
            },

            /**
             * @return {bool}
             */
            setOmiseCardId: function(elm){
                var element = $(elm);
                $('.omise_card_wrap fieldset').css({display: 'none'});
                element.closest('.omise_card_wrap').find('fieldset').css({display: 'block'});
                return true;
            },

            /**
             * @return {string}
             */
            getOmiseCardId: function(){
                var form = $('#' + this.getCode() + 'Form');
                var card_id = $('input[name="payment[omise_card_id]"]:checked', form).val();
                return card_id;
            },

            /**
             * @return {string}
             */
            getOmiseSaveCard: function () {
                var form = $('#' + this.getCode() + 'Form');
                var save_card = $('input[name="payment[omise_save_card]"]', form).is(":checked");
                return save_card ? '1' : '0';
            },

            /**
             * @return {bool}
             */
            isCustomerLogin: function(){
                return window.checkoutConfig.payment.omise_cc.isCustomerLogin == 1;
            },

            /**
             * @return {bool}
             */
            checkSaveCard: function(){
                var form = $('#' + this.getCode() + 'Form');
                if(this.getOmiseSaveCard() == 1){
                    $('input[name="payment[omise_save_card]"]', form).prop("checked", false);
                } else {
                    $('input[name="payment[omise_save_card]"]', form).prop("checked", true);
                }
            },

            /**
             * @return {void}
             */
            checkedByLabel: function(elm){
                var element = $(elm);
                var parent = element.parent();
                $('input[type="checkbox"], input[type="radio"]', parent).trigger('click');
            },

            /**
             * Initiate observable fields
             *
             * @return this
             */
            initObservable: function() {
                this._super()
                    .observe([
                        'omiseCardNumber',
                        'omiseCardHolderName',
                        'omiseCardExpirationMonth',
                        'omiseCardExpirationYear',
                        'omiseCardSecurityCode',
                        'omiseCardToken',
                        'omiseCardId'
                    ]);
                return this;
            },

            /**
             * Is method available to display
             *
             * @return {boolean}
             */
            isActive: function() {
                return true;
            },

            /**
             * Is 3-D Secure config enabled
             *
             * @return {boolean}
             */
            isThreeDSecureEnabled: function() {
                if (window.checkoutConfig.payment.omise_cc.offsitePayment) {
                    return true;
                }

                return false;
            },

            /**
             * Start performing place order action,
             * by disable a place order button and show full screen loader component.
             */
            startPerformingPlaceOrderAction: function() {
                this.isPlaceOrderActionAllowed(false);
                fullScreenLoader.startLoader();
            },

            /**
             * Stop performing place order action,
             * by disable a place order button and show full screen loader component.
             */
            stopPerformingPlaceOrderAction: function() {
                fullScreenLoader.stopLoader();
                this.isPlaceOrderActionAllowed(true);
            },

            /**
             * Generate Omise token before proceed the placeOrder process.
             *
             * @return {void}
             */
            generateTokenAndPerformPlaceOrderAction: function(data) {
                var self = this;
                if(self.getOmiseCardId()){
                    self.processOrder();
                } else {
                    this.startPerformingPlaceOrderAction();

                    var card = {
                        number           : this.omiseCardNumber(),
                        name             : this.omiseCardHolderName(),
                        expiration_month : this.omiseCardExpirationMonth(),
                        expiration_year  : this.omiseCardExpirationYear(),
                        security_code    : this.omiseCardSecurityCode()
                    };

                    Omise.setPublicKey(this.getPublicKey());
                    Omise.createToken('card', card, function(statusCode, response) {
                        if (statusCode === 200) {
                            self.omiseCardToken(response.id);
                            self.processOrder();
                        } else {
                            alert(response.message);
                            self.stopPerformingPlaceOrderAction();
                        }
                    });
                }
            },

            /**
             * @return {void}
             */
            processOrder: function(){
                var self = this;
                self.getPlaceOrderDeferredObject()
                    .fail(
                    function(response) {
                        errorProcessor.process(response, self.messageContainer);
                        fullScreenLoader.stopLoader();
                        self.isPlaceOrderActionAllowed(true);
                    }
                ).done(
                    function(response) {
                        if (self.isThreeDSecureEnabled()) {
                            var serviceUrl = urlBuilder.createUrl(
                                '/orders/:order_id/omise-offsite',
                                {
                                    order_id: response
                                }
                            );

                            storage.get(serviceUrl, false)
                                .fail(
                                function (response) {
                                    errorProcessor.process(response, self.messageContainer);
                                    fullScreenLoader.stopLoader();
                                    self.isPlaceOrderActionAllowed(true);
                                }
                            )
                                .done(
                                function (response) {
                                    if (response) {
                                        $.mage.redirect(response.authorize_uri);
                                    } else {
                                        errorProcessor.process(response, self.messageContainer);
                                        fullScreenLoader.stopLoader();
                                        self.isPlaceOrderActionAllowed(true);
                                    }
                                }
                            );
                        } else if (self.redirectAfterPlaceOrder) {
                            redirectOnSuccessAction.execute();
                        }
                    }
                );
            },

            /**
             * Hook the placeOrder function.
             * Original source: placeOrder(data, event); @ module-checkout/view/frontend/web/js/view/payment/default.js
             *
             * @return {boolean}
             */
            placeOrder: function(data, event) {
                if (event) {
                    event.preventDefault();
                }

                if (typeof Omise === 'undefined') {
                    alert($t('Unable to process the payment, loading the external card processing library is failed. Please contact the merchant.'));
                    return false;
                }

                if (! this.validate()) {
                    return false;
                }

                this.generateTokenAndPerformPlaceOrderAction(data);

                return true;
            },

            /**
             * Hook the validate function.
             * Original source: validate(); @ module-checkout/view/frontend/web/js/view/payment/default.js
             *
             * @return {boolean}
             */
            validate: function () {
                $('#' + this.getCode() + 'Form').validation();
                var isCardId = this.getOmiseCardId();
                if(isCardId){
                    return true;
                }
                var isCardNumberValid          = $('#' + this.getCode() + 'CardNumber').valid();
                var isCardHolderNameValid      = $('#' + this.getCode() + 'CardHolderName').valid();
                var isCardExpirationMonthValid = $('#' + this.getCode() + 'CardExpirationMonth').valid();
                var isCardExpirationYearValid  = $('#' + this.getCode() + 'CardExpirationYear').valid();
                var isCardSecurityCodeValid    = $('#' + this.getCode() + 'CardSecurityCode').valid();

                if (isCardNumberValid
                    && isCardHolderNameValid
                    && isCardExpirationMonthValid
                    && isCardExpirationYearValid
                    && isCardSecurityCodeValid) {
                    return true;
                }

                return false;
            }
        });
    }
);
