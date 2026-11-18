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
use MageTips\Appliances\Api\Data\EppInterfaceFactory;
use MageTips\Appliances\Api\EppRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Save EPP Controller
 *
 * @since 1.0.0
 */
class Save extends Action
{
    public const ADMIN_RESOURCE = 'MageTips_Appliances::appliances_epp';

    /** @var DataPersistorInterface */
    private DataPersistorInterface $dataPersistor;

    /** @var EppRepositoryInterface */
    private EppRepositoryInterface $eppRepository;

    /** @var EppInterfaceFactory */
    private EppInterfaceFactory $eppInterfaceFactory;

    /**
     * @param Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param EppRepositoryInterface $eppRepository
     * @param EppInterfaceFactory $eppInterfaceFactory
     */
    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        EppRepositoryInterface $eppRepository,
        EppInterfaceFactory $eppInterfaceFactory
    ) {
        $this->dataPersistor    = $dataPersistor;
        $this->eppRepository    = $eppRepository;
        $this->eppInterfaceFactory = $eppInterfaceFactory;

        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $this->redirectToIndex();
        }

        $eppId = (int)$this->getRequest()->getParam(EppInterface::ENTITY_ID);

        $epp = $this->getEppById($eppId);

        if ($epp === null) {
            /** @var EppInterface $epp */
            $epp = $this->eppInterfaceFactory->create();
        }

        unset($data['form_key'], $data[EppInterface::ENTITY_ID]);
        $epp->addData($data);

        try {
            $savedEpp = $this->eppRepository->save($epp);
            $this->messageManager->addSuccessMessage(
                (string)__('You saved the EPP successfully.')
            );
            $this->dataPersistor->clear(EppInterface::TABLE_NAME);

            if ($this->getRequest()->getParam('back')) {
                return $this->redirectToEdit((int)$savedEpp->getEntityId());
            }

            return $this->redirectToIndex();
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                (string)__('Something went wrong while saving the EPP.')
            );
        }

        $this->dataPersistor->set(EppInterface::TABLE_NAME, $data);

        return $this->redirectToEdit($eppId);
    }

    /**
     * Load EPP by ID, or return null if not found or ID is zero.
     *
     * @param int $eppId
     * @return EppInterface|null
     */
    private function getEppById(int $eppId): ?EppInterface
    {
        if (!$eppId) {
            return null;
        }

        try {
            return $this->eppRepository->getById($eppId);
        } catch (LocalizedException $e) {
            return null;
        }
    }

    /**
     * Build a redirect response to the EPP edit page.
     *
     * @param int|null $entityId
     * @return Redirect
     */
    private function redirectToEdit(?int $entityId): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/edit', [EppInterface::ENTITY_ID => $entityId]);

        return $resultRedirect;
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
