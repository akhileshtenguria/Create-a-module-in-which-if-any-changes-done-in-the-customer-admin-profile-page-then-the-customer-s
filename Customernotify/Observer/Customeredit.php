<?php
namespace Akhilesh\Customernotify\Observer;
class Customeredit implements \Magento\Framework\Event\ObserverInterface {


    const XML_PATH_EMAIL_RECIPIENT = 'trans_email/ident_general/email';
    const SENDER_EMAIL  = 'trans_email/ident_general/email';
    const SENDER_NAME   = 'trans_email/ident_general/name'; 
    const IS_ENABLED   = 'customeremailadminchange/customeremail/enable';   
    protected $transportBuilder;
    protected $inlineTranslation;
    protected $scopeConfig;
    protected $storeManager;
    protected $request;
    public function __construct(
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->customerRepository = $customerRepository;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {
         $customerData = $this->request->getPost();

        // print_r($customerData); die; 
         $event = $observer->getEvent()->getCustomer();

         $senderEmail = $this->scopeConfig->getValue(self::SENDER_EMAIL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
         $senderName = $this->scopeConfig->getValue(self::SENDER_NAME, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
         // die; 
         // $senderEmail = "akhilesh.zestard@gmail.com";
         // $senderName = "akhilesh tenguria";
         $customer_email="";
         $fullname="";


         $customerData = $this->request->getPost();
         if(isset($customerData['parent_id'])){
                    $id = $customerData['parent_id'];
                    $customer = $this->customerRepository->getById($id);
                    $fullname =  $customer->getFirstname()." ".$customer->getLastname();
                    $customer_email = $customer->getEmail();
         }
         if(!empty($this->request->getPost())) {
            $customerData = $this->request->getPost();
            if(isset($customerData['customer'])){
               $customer = $this->request->getPost('customer');
               $customer_email = $this->request->getPost('customer')['email'];
               $fullname = $customer['firstname']." ".$customer['lastname'];
            }
         }
      


         $adminEmail = $senderEmail;
         $isEnabled = $this->scopeConfig->getValue(self::IS_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if($isEnabled){
            try 
            {
                $sender = [    
                    'email' => $senderEmail,
                    'name' => $senderName
                ];
                $to_email = $customer_email; 
                $transport= $this->transportBuilder->setTemplateIdentifier('admin_customer_account_change_email')
                        ->setTemplateOptions(
                            [
                                'area' => 'frontend',
                                'store' => $this->storeManager->getStore()->getId()
                            ])
                        ->setTemplateVars([
                                'name'  => $fullname,
                                'email' => $customer_email 
                            ])
                        ->setFrom($sender)
                        ->addTo($to_email,$senderName)
                        ->getTransport();
                $transport->sendMessage();
                $this->inlineTranslation->resume();
            } 
            catch (\Exception $e) 
            {
                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug($e->getMessage());
            }
         }
       return true;
    }
}