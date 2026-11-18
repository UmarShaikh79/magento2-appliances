<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 */

declare(strict_types=1);

namespace MageTips\Appliances\Controller\Cart;

use MageTips\Appliances\Api\EppRepositoryInterface;
use MageTips\Appliances\Model\Quote\Total\EppCharge;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Saves the selected EPP plan on a quote item and writes the plan details
 * onto the quote_item row so they carry through to the order.
 *
 * POST /appliances/cart/saveEpp
 *   item_id  – int, quote item ID
 *   epp_code – string, EPP plan code (empty string to remove selection)
 *   form_key – string, CSRF form key
 */
class SaveEpp implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     * @param FormKeyValidator $formKeyValidator
     * @param EppRepositoryInterface $eppRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected readonly RequestInterface $request,
        protected readonly JsonFactory $resultJsonFactory,
        protected readonly CheckoutSession $checkoutSession,
        protected readonly CartRepositoryInterface $cartRepository,
        protected readonly FormKeyValidator $formKeyValidator,
        protected readonly EppRepositoryInterface $eppRepository,
        protected readonly LoggerInterface $logger
    ) {
    }

    /**
     * Save selected EPP plan code on the quote item.
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->formKeyValidator->validate($this->request)) {
            return $result->setData(['error' => (string)__('Invalid security token. Please refresh the page.')]);
        }

        $itemId  = (int)$this->request->getParam('item_id');
        $eppCode = trim((string)$this->request->getParam('epp_code', ''));

        if ($itemId <= 0) {
            return $result->setData(['error' => (string)__('Invalid cart item.')]);
        }

        try {
            $quote = $this->checkoutSession->getQuote();
            $item  = $quote->getItemById($itemId);

            if (!$item) {
                return $result->setData(['error' => (string)__('Cart item not found.')]);
            }

            $existing = $item->getOptionByCode(EppCharge::EPP_ITEM_OPTION_CODE);

            if ($eppCode === '') {
                if ($existing) {
                    $existing->setValue('');
                }
                $item->setData('epp_plan_code', null);
                $item->setData('epp_plan_name', null);
                $item->setData('epp_plan_price', null);
                $item->setData('epp_plan_years', null);
            } else {
                if ($existing) {
                    $existing->setValue($eppCode);
                    // Ensure product_id is set — configurable items fail validation if product_id=0
                    if (!(int)$existing->getProductId()) {
                        $existing->setData('product_id', (int)$item->getProductId());
                    }
                } else {
                    $item->addOption([
                        'code'       => EppCharge::EPP_ITEM_OPTION_CODE,
                        'value'      => $eppCode,
                        'product_id' => (int)$item->getProductId(),
                    ]);
                }
                try {
                    $plan = $this->eppRepository->getByCode($eppCode);

                    if (!$plan->isEnabled()) {
                        return $result->setData(
                            ['error' => (string)__('This protection plan is no longer available.')]
                        );
                    }

                    $itemPrice = (float)$item->getPrice();
                    if ($itemPrice < (float)$plan->getMinPrice() || $itemPrice > (float)$plan->getMaxPrice()) {
                        return $result->setData(
                            ['error' => (string)__('This protection plan is not available for this product\'s price.')]
                        );
                    }

                    $item->setData('epp_plan_code', $eppCode);
                    $item->setData('epp_plan_name', (string)$plan->getName());
                    $item->setData('epp_plan_price', (float)$plan->getRetailPrice());
                    $item->setData('epp_plan_years', (int)$plan->getYears());
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage(), ['exception' => $e]);
                }
            }

            $quote->collectTotals();
            $this->cartRepository->save($quote);

            return $result->setData(['success' => true]);
        } catch (\Exception $e) {
            return $result->setData(['error' => (string)__('Could not save plan selection. Please try again.')]);
        }
    }

    /**
     * Always returns null — CSRF validation is handled by FormKeyValidator.
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Always returns true — form key check in execute() handles CSRF.
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
