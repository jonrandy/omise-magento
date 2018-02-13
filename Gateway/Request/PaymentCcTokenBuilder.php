<?php
namespace Omise\Payment\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Omise\Payment\Observer\OmiseDataAssignObserver;

class PaymentCcTokenBuilder implements BuilderInterface
{
    /**
     * @var string
     */
    const CARD = 'card';
    const CARD_ID = 'card_id';
    const SAVE_CARD = 'save_card';

    /**
     * @param  array $buildSubject
     *
     * @return array
     */
    public function build(array $buildSubject)
    {
        $payment = SubjectReader::readPayment($buildSubject);
        $method  = $payment->getPayment();
        return [
            self::CARD => $method->getAdditionalInformation(OmiseDataAssignObserver::OMISE_CARD_TOKEN),
            self::CARD_ID => $method->getAdditionalInformation(OmiseDataAssignObserver::OMISE_CARD_ID),
            self::SAVE_CARD => $method->getAdditionalInformation(OmiseDataAssignObserver::OMISE_SAVE_CARD),
        ];
    }
}
