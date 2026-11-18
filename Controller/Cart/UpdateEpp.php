<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 */

declare(strict_types=1);

namespace MageTips\Appliances\Controller\Cart;

use MageTips\Appliances\Api\ApplianceDetectorInterface;
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
 * Applies one EPP plan code to all checked appliance items in a single request.
 * Also persists structured EPP data to quote_item columns for integrations.
 *
 * POST /appliances/cart/updateEpp
 *   item_ids[] – int[], quote item IDs the customer chose to cover
 *   epp_code   – string, EPP plan code; empty string = "no plan"
 *   form_key   – string, CSRF token
 */
class UpdateEpp implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     * @param ApplianceDetectorInterface $detector
     * @param FormKeyValidator $formKeyValidator
     * @param EppRepositoryInterface $eppRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected readonly RequestInterface $request,
        protected readonly JsonFactory $resultJsonFactory,
        protected readonly CheckoutSession $checkoutSession,
        protected readonly CartRepositoryInterface $cartRepository,
        protected readonly ApplianceDetectorInterface $detector,
        protected readonly FormKeyValidator $formKeyValidator,
        protected readonly EppRepositoryInterface $eppRepository,
        protected readonly LoggerInterface $logger
    ) {
    }

    /**
     * Apply one EPP plan to all selected appliance items in the cart.
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->formKeyValidator->validate($this->request)) {
            return $result->setData([
                'error' => (string)__('Invalid security token. Please refresh the page.'),
            ]);
        }

        $rawIds  = $this->request->getParam('item_ids', []);
        $itemIds = array_map('intval', is_array($rawIds) ? $rawIds : [$rawIds]);
        $eppCode = trim((string)$this->request->getParam('epp_code', ''));

        if ($eppCode === '' || empty($itemIds)) {
            return $result->setData(['success' => true]);
        }

        try {
            $quote = $this->checkoutSession->getQuote();

            try {
                $plan = $this->eppRepository->getByCode($eppCode);
            } catch (\Exception $e) {
                return $result->setData(['error' => (string)__('Invalid protection plan.')]);
            }

            if (!$plan->isEnabled()) {
                return $result->setData(['error' => (string)__('This protection plan is no longer available.')]);
            }

            $planMin = (float)$plan->getMinPrice();
            $planMax = (float)$plan->getMaxPrice();

            foreach ($quote->getAllVisibleItems() as $item) {
                if (!$this->detector->isApplianceItem($item)) {
                    continue;
                }

                $itemId = (int)$item->getId();
                if (!in_array($itemId, $itemIds, true)) {
                    continue;
                }

                // Reject if the plan's price range does not cover this item's price
                $itemPrice = (float)$item->getPrice();
                if ($itemPrice < $planMin || $itemPrice > $planMax) {
                    continue;
                }

                $existing = $item->getOptionByCode(EppCharge::EPP_ITEM_OPTION_CODE);
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

                if ($plan) {
                    $item->setData('epp_plan_code', $eppCode);
                    $item->setData('epp_plan_name', (string)$plan->getName());
                    $item->setData('epp_plan_price', (float)$plan->getRetailPrice());
                    $item->setData('epp_plan_years', (int)$plan->getYears());
                }
            }

            $quote->collectTotals();
            $this->cartRepository->save($quote);

            return $result->setData(['success' => true]);
        } catch (\Exception $e) {
            return $result->setData([
                'error' => (string)__('Could not save your plan selection. Please try again.'),
            ]);
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
