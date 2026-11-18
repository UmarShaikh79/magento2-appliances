<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 *
 * @category  MageTips
 * @package   MageTips_Appliances
 * @author    Muhammad Umar <umarshiekh619@gmail.com>
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License 3.0
 * @link      https://github.com/UmarShaikh79/magento2-appliances
 */

declare(strict_types=1);

namespace MageTips\Appliances\Controller\Adminhtml\Epp;

use MageTips\Appliances\Api\Data\EppInterface;
use MageTips\Appliances\Api\EppRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Delete EPP Controller
 *
 * @since 1.0.0
 */
class Delete extends Action
{
    public const ADMIN_RESOURCE = 'MageTips_Appliances::appliances_epp';

    /** @var EppRepositoryInterface */
    private EppRepositoryInterface $eppRepository;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /**
     * @param Context $context
     * @param EppRepositoryInterface $eppRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        EppRepositoryInterface $eppRepository,
        LoggerInterface $logger
    ) {
        $this->eppRepository = $eppRepository;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * Delete the EPP by ID and redirect back to the grid.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $eppId = (int)$this->getRequest()->getParam(EppInterface::ENTITY_ID);

        try {
            $epp = $this->eppRepository->getById($eppId);
        } catch (LocalizedException $e) {
            $this->logger->error(__CLASS__ . ': ' . $e->getMessage());
            $this->messageManager->addErrorMessage(
                (string)__('We can\'t find the EPP to delete.')
            );

            return $this->redirectToIndex();
        }

        try {
            $this->eppRepository->delete($epp);
        } catch (LocalizedException $e) {
            $this->logger->error(__CLASS__ . ': ' . $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());

            return $this->redirectToIndex();
        }

        $this->messageManager->addSuccessMessage(
            (string)__('The EPP with id %1 has been deleted.', $eppId)
        );

        return $this->redirectToIndex();
    }

    /**
     * Build a redirect response to the EPP grid index page.
     *
     * @return Redirect
     */
    private function redirectToIndex(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/');

        return $resultRedirect;
    }
}
