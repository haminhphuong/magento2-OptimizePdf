<?php

namespace Ymow\OptimizePdf\Controller\Adminhtml\Order\Invoice;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order\Pdf\Invoice;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Ymow\OptimizePdf\Helper\Data;

class PrintAction extends \Magento\Sales\Controller\Adminhtml\Order\Invoice\PrintAction
{
    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;
    /**
     * @var Invoice
     */
    protected $invoice;
    /**
     * @var DateTime
     */
    protected $dateTime;
    /**
     * @var ResponseInterface
     */
    protected $response;
    /**
     * @var DirectoryList
     */
    protected $directoryList;
    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param ForwardFactory $resultForwardFactory
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param Invoice $invoice
     * @param DateTime $dateTime
     * @param ResponseInterface $response
     * @param DirectoryList $directoryList
     * @param Data $helperData
     */
    public function __construct
    (
        Context $context,
        FileFactory $fileFactory,
        ForwardFactory $resultForwardFactory,
        InvoiceRepositoryInterface $invoiceRepository,
        Invoice $invoice,
        DateTime $dateTime,
        ResponseInterface $response,
        DirectoryList $directoryList,
        Data $helperData
    )
    {
        parent::__construct($context, $fileFactory, $resultForwardFactory);
        $this->invoiceRepository = $invoiceRepository;
        $this->invoice = $invoice;
        $this->dateTime = $dateTime;
        $this->response = $response;
        $this->directoryList = $directoryList;
        $this->helperData = $helperData;
    }

    /**
     * @return ResponseInterface|void
     * @throws \Exception
     */
    public function execute()
    {
        $invoiceId = $this->getRequest()->getParam('invoice_id');
        if ($invoiceId) {
            $invoice = $this->invoiceRepository->get($invoiceId);
            if ($invoice) {
                $pdf = $this->invoice->getPdf([$invoice]);
                $date = $this->dateTime->date('Y-m-d_H-i-s');
                $fileContent = ['type' => 'string', 'value' => $pdf->render(), 'rm' => true];
                return $this->helperData->optimizeFdfFileSize(
                    'invoice' . $date . '.pdf',
                    $fileContent,
                    DirectoryList::VAR_DIR
                );
            }
        } else {
            return $this->resultForwardFactory->create()->forward('noroute');
        }
    }

}
