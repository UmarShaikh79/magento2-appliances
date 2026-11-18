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
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Edit/New EPP Controller — handles both create and edit in one action
 *
 * @since 1.0.0
 */
class Edit extends Action
{
    public const ADMIN_RESOURCE = 'MageTips_Appliances::appliances_epp';

    /** @var EppRepositoryInterface */
    private EppRepositoryInterface $eppRepository;

    /** @var DataPersistorInterface */
    private DataPersistorInterface $dataPersist;

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
        parent::__construct($context);
        $this->eppRepository = $eppRepository;
        $this->dataPersist = $dataPersistor;
        $this->eppInterfaceFactory = $eppInterfaceFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        /** @var EppInterface $epp */
        $epp = $this->eppInterfaceFactory->create();

        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $eppId = (int)$this->getRequest()->getParam('entity_id');

        if ($eppId) {
            try {
                $epp = $this->eppRepository->getById($eppId);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('This EPP no longer exists.'));
                return $this->redirectToIndex();
            }
        }

        $this->dataPersist->set(EppInterface::TABLE_NAME, $epp);
        $eppId = (int) $epp->getEntityId();

        $title = $eppId
            ? (string) __('Edit Extended Protection Plan with id %1', $eppId)
            : (string) __('New Extended Protection Plan');

        $pageTitle = $resultPage->getConfig()->getTitle();

        $this->initPage($resultPage)->addBreadcrumb($title, $title);
        $pageTitle->prepend(__('Extended Protection Plans'));
        $pageTitle->prepend($title);

        return $resultPage;
    }

    /**
     * Set the active menu and breadcrumbs on the result page.
     *
     * @param Page $resultPage
     * @return Page
     */
    private function initPage(Page $resultPage): Page
    {
        $resultPage->setActiveMenu('MageTips_Appliances::appliances_epp')
            ->addBreadcrumb(__('Appliances'), __('Appliances'))
            ->addBreadcrumb(__('Extended Protection Plans'), __('Extended Protection Plans'));

        return $resultPage;
    }

    /**
     * Build a redirect response to the EPP grid index page.
     *
     * @return Redirect
     */
    private function redirectToIndex(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/');

        return $resultRedirect;
    }
}
