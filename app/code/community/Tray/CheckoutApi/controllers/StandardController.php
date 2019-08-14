<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to suporte@tray.net.br so we can send you a copy immediately.
 *
 * @category   Tray
 * @package    Tray_CheckoutApi
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Tray_CheckoutApi_StandardController extends Mage_Core_Controller_Front_Action 
{

    /**
     * Order instance
     */
    protected $_order;

    
    public function paymentAction()
    {             
       $this->loadLayout();
       $this->renderLayout();       
    }
    
    public function returnAction()
    {
       $this->loadLayout();
       $this->renderLayout();
    }
    
    public function paymentbackendAction() 
    {
        $this->loadLayout();
        $this->renderLayout();

        $hash = explode("/order/", $this->getRequest()->getOriginalRequest()->getRequestUri());
        $hashdecode = explode(":", Mage::getModel('core/encryption')->decrypt($hash[1]));

        $order = Mage::getModel('sales/order')
                ->getCollection()
                ->addFieldToFilter('increment_id', $hashdecode[0])
                ->addFieldToFilter('quote_id', $hashdecode[1])
                ->getFirstItem();

        if ($order) {
            $session = Mage::getSingleton('checkout/session');
            $session->setLastQuoteId($order->getData('quote_id'));
            $session->setLastOrderId($order->getData('entity_id'));
            $session->setLastSuccessQuoteId($order->getData('quote_id'));
            $session->setLastRealOrderId($order->getData('increment_id'));
            $session->setCheckoutApiQuoteId($order->getData('quote_id'));
            $this->_redirect('checkoutapi/standard/payment/type/standard');
        } else {
            Mage::getSingleton('checkout/session')->addError('URL informada é inválida!');
            $this->_redirect('checkout/cart');
        }
    }

    public function errorAction()
    {
       $this->loadLayout();
       $this->renderLayout();
    }
    
    /**
     *  Get order
     *
     *  @return	  Mage_Sales_Model_Order
     */
    public function getOrder() {
        
        if ($this->_order == null) {
            
        }
        
        return $this->_order;
    }

    protected function _expireAjax() {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired');
            exit;
        }
    }

    /**
     * Get singleton with checkout standard order transaction information
     *
     * @return Tray_CheckoutApi_Model_Api
     */
    public function getApi() 
    {
        return Mage::getSingleton('checkoutapi/'.$this->getRequest()->getParam("type"));
    }

    /**
     * When a customer chooses Tray on Checkout/Payment page
     *
     */
    public function redirectAction() 
    {
        
        $type = $this->getRequest()->getParam('type', false);
        
        $session = Mage::getSingleton('checkout/session');

        $session->setCheckoutApiQuoteId($session->getQuoteId());
        
        $this->getResponse()->setHeader("Content-Type", "text/html; charset=ISO-8859-1", true);

        $this->getResponse()->setBody($this->getLayout()->createBlock('checkoutapi/redirect')->toHtml());

        $session->unsQuoteId();
    }

    /**
     * When a customer cancel payment from traycheckout .
     */
    public function cancelAction() 
    {
        
        $session = Mage::getSingleton('checkout/session');

        $session->setQuoteId($session->getCheckoutApiQuoteId(true));

        // cancel order
        if ($session->getLastRealOrderId()) {

            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());

            if ($order->getId()) {
                $order->cancel()->save();
            }
        }

        $this->_redirect('checkout/cart');
    }
    
    private function getUrlPostCheckoutApi($sandbox)
    {
         if ($sandbox == '1')
         {
        	return "https://api.sandbox.traycheckout.com.br/v2/transactions/get_by_token";
         } else {
		return "https://api.traycheckout.com.br/v2/transactions/get_by_token";
         }
    }
    
    /**
     * when checkout returns
     * The order information at this point is in POST
     * variables.  However, you don't want to "process" the order until you
     * get validation from the return post.
     */
    public function successAction() 
    {
        $_type = $this->getRequest()->getParam('type', false);
        $token = $this->getApi()->getConfigData('token');

	$urlPost = $this->getUrlPostCheckoutApi($this->getApi()->getConfigData('sandbox'));

        $dados_post = $this->getRequest()->getPost();
         
        $order_number_conf = utf8_encode(str_replace($this->getApi()->getConfigData('prefixo'),'',$dados_post['transaction']['order_number']));
        $transaction_token= $dados_post['token_transaction'];
        
        $dataRequest['token_transaction'] = $transaction_token;
        $dataRequest['token_account'] = trim($token);
        $dataRequest['type_response'] = 'J';
        
        //$transaction_token= $dados_post['transaction']['transaction_token']; 

        Mage::log('URL de Request: '.$urlPost, null, 'traycheckout.log');
        $ch = curl_init ( $urlPost );
        
        if(is_array($dataRequest)){
            Mage::log('Data: '. http_build_query($dataRequest), null, 'traycheckout.log');
        }else{
            Mage::log('Data: '.  $dataRequest, null, 'traycheckout.log');
        }
        
        curl_setopt ( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $dataRequest);
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );

        
        if (!($resposta = curl_exec($ch))) {
            Mage::log('Error: Erro na execucao! ', null, 'traycheckout.log');
            if(curl_errno($ch)){
                Mage::log('Error '.curl_errno($ch).': '. curl_error($ch), null, 'traycheckout.log');
            }else{
                Mage::log('Error : '. curl_error($ch), null, 'traycheckout.log');
            }
            
            Mage::app()->getResponse()->setRedirect('checkoutapi/standard/error', array('_secure' => true , 'descricao' => urlencode(utf8_encode("Erro de execução!")),'codigo' => urlencode("999")))->sendResponse();
            echo "Erro na execucao!";
            curl_close ( $ch );
            exit();    
        }
        
        
        $httpCode = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
        
        curl_close($ch); 
        
        $arrResponse = json_decode($resposta,TRUE);
        
        $message_response = $arrResponse['message_response'];
        $error_response = $arrResponse['error_response'];
        if($message_response['message'] == "error"){
            if(!empty($error_response['general_errors'])){
                foreach ($error_response['general_errors'] as $general_error){
                    $codigo_erro .= $general_error['code'] . " | ";
                    $descricao_erro .= $general_error['message'] . " | ";
                }

            }
            if(!empty($error_response['validation_errors'])){
                var_dump($error_response['validation_errors']);
                foreach ($error_response['validation_errors'] as $validation_error){
                    $codigo_erro .= $validation_error['field'] . " | ";
                    $descricao_erro .= $validation_error['message_complete'] . " | ";
                }
            }
            $codigo_erro = substr($codigo_erro, 0, - 3);
            $descricao_erro = substr($descricao_erro, 0, - 3);
            
            if ($codigo_erro == ''){
                $codigo_erro = '0000000';
            }
            if ($descricao_erro == ''){
                $descricao_erro = 'Erro Desconhecido';
            }
            $this->_redirect('checkoutapi/standard/error', array('_secure' => true , 'descricao' => urlencode(utf8_encode($descricao_erro)),'codigo' => urlencode($codigo_erro)));
        }else{
        	
            $transaction = $arrResponse['data_response']['transaction'];
            $order_number = str_replace($this->getApi()->getConfigData('prefixo'),'',$transaction['order_number']);
            $order = Mage::getModel('sales/order');

            $order->loadByIncrementId($order_number);
            
            echo "Pedido: $order_number - ID: ".$transaction['transaction_id'];
            
            if ($order->getId()) {

                if (floatval($transaction['payment']['price_original']) != floatval($order->getGrandTotal())) {
                    
                    $frase = 'Total pago à Tray é diferente do valor original.';

                    $order->addStatusToHistory(
                            $order->getStatus(), //continue setting current order status
                            Mage::helper('checkoutapi')->__($frase), true
                    );
                    echo $frase;
                    $order->sendOrderUpdateEmail(true, $frase);
                } else {
                    $cod_status = $transaction['status_id'];
                    
                    $comment = $cod_status . ' - ' . $transaction['status_name'];
                    switch ($cod_status){
                        case 4: 
                        case 5:
                        case 88:
                                $order->addStatusToHistory(
                                    Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage::helper('checkoutapi')->__('Tray enviou automaticamente o status: %s', $comment)
                                    
                                );
                            break;
                        case 6:
                                $items = $order->getAllItems();

                                $thereIsVirtual = false;

                                foreach ($items as $itemId => $item) {
                                    if ($item["is_virtual"] == "1" || $item["is_downloadable"] == "1") {
                                        $thereIsVirtual = true;
                                    }
                                }

                                // what to do - from admin
                                $toInvoice = $this->getApi()->getConfigData('acaopadraovirtual') == "1" ? true : false;

                                if ($thereIsVirtual && $toInvoice) {

                                    /*if ($order->canInvoice()) {
                                    	$isHolded = ( $order->getStatus() == Mage_Sales_Model_Order::STATE_HOLDED );

                                        $status = ($isHolded) ? Mage_Sales_Model_Order::STATE_PROCESSING :  $order->getStatus();
                                        $frase  = ($isHolded) ? 'Tray - Aprovado. Confirmado automaticamente o pagamento do pedido.' : 'Erro ao criar pagamento (fatura).';
										
                                        //when order cannot create invoice, need to have some logic to take care
                                        $order->addStatusToHistory(
                                            $status, //continue setting current order status
                                            Mage::helper('checkoutapi')->__( $frase )
                                        );

                                    } else {*/

                                    //need to save transaction id
                                    $order->getPayment()->setTransactionId($dados_post['transaction']['transaction_id']);

                                    //need to convert from order into invoice
                                    $invoice = $order->prepareInvoice();

                                    if ($this->getApi()->canCapture()) {
                                        $invoice->register()->capture();
                                    }

                                    Mage::getModel('core/resource_transaction')
                                            ->addObject($invoice)
                                            ->addObject($invoice->getOrder())
                                            ->save();

                                    $frase = 'Pagamento (fatura) ' . $invoice->getIncrementId() . ' foi criado. Tray - Aprovado. Confirmado automaticamente o pagamento do pedido.';

                                    if ($thereIsVirtual) {

                                        $order->addStatusToHistory(
                                            $order->getStatus(), Mage::helper('checkoutapi')->__($frase), true
                                        );

                                    } else {

                                        $order->addStatusToHistory(
                                            Mage_Sales_Model_Order::STATE_PROCESSING, //update order status to processing after creating an invoice
                                            Mage::helper('checkoutapi')->__($frase), true
                                        );
                                    }

                                    $invoice->sendEmail(true, $frase);
                                    
                                } else {
									
                                    $frase = 'Tray - Aprovado. Pagamento (fatura) confirmado automaticamente.';

                                    $order->addStatusToHistory(
                                            $order->getStatus(), //continue setting current order status
                                            Mage::helper('checkoutapi')->__($frase), true
                                    );

                                    $order->sendOrderUpdateEmail(true, $frase);
                                    
                                }
                            //}
                            break;
                        case 24:
                                $order->addStatusToHistory(
                                    Mage_Sales_Model_Order::STATE_HOLDED, Mage::helper('checkoutapi')->__('Tray enviou automaticamente o status: %s', $comment)
                                );
                            break;
                        case 7:
                        case 89:                        	
                                $frase = 'Tray - Cancelado. Pedido cancelado automaticamente (transação foi cancelada, pagamento foi negado, pagamento foi estornado ou ocorreu um chargeback).';

                                $order->addStatusToHistory(
                                    Mage_Sales_Model_Order::STATE_CANCELED, Mage::helper('checkoutapi')->__($frase), true
                                );

                                $order->sendOrderUpdateEmail(true, $frase);

                                $order->cancel();
                            break;
                        case 87:
                                $order->addStatusToHistory(
                                    Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, Mage::helper('checkoutapi')->__('Tray enviou automaticamente o status: %s', $comment)
                                );
                            break;
                    }
                }
                $order->save();
            }
        }
    }

}