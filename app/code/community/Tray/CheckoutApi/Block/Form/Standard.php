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

class Tray_CheckoutApi_Block_Form_Standard extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        $this->setTemplate('tray/checkoutapi/form/standard.phtml');
        parent::_construct();
    }
    protected function _getConfig()
    {
        return Mage::getSingleton('payment/config');
    }
    public function getCcMonths()
    {
        $months = $this->getData('cc_months');
        if (is_null($months)) {
            $months[0] =  $this->__('Month');
            $months = array_merge($months, $this->_getConfig()->getMonths());
            $this->setData('cc_months', $months);
        }
        return $months;
    }
    
    public function getCcYears()
    {
        $years = $this->getData('cc_years');
        if (is_null($years)) {
            $years = $this->_getConfig()->getYears();
            $years = array(0=>$this->__('Year'))+$years;
            $this->setData('cc_years', $years);
        }
        return $years;
    }
    
    public function getSplitSimulate($totalValue = "0")
    {
        $tcStandard = Mage::getModel('checkoutapi/standard');
        $tcNoSplitTaxRate = $tcStandard->getConfigData('tcNoSplitTaxRate');
        
        $tcNoSplitTaxRate = explode(",",$tcNoSplitTaxRate);
        
        $params = array(
            "token_account" => $tcStandard->getConfigData('token'),
            "payment_method_id" => "3",
            "price" => $totalValue
        );
        
        
        if(sizeof($tcNoSplitTaxRate) > 1){
            for($itc = 0; $itc < sizeof($tcNoSplitTaxRate);$itc++){
                $params["splits[$itc][split_transaction]"] = $tcNoSplitTaxRate[$itc];
                $params["splits[$itc][percentage]"] = $tcStandard->getConfigData('splitTax');
            }
        }else{
            $params["splits[][split_transaction]"] = "1";
            $params["splits[][percentage]"] = $tcStandard->getConfigData('splitTax');
        }
        
        $params = preg_replace("/splits\%5B\d+\%5D/","splits%5B%5D", http_build_query($params));
                
        $tcResponse = simplexml_load_string($tcStandard->getTrayCheckoutRequest("/api/seller_splits/simulate_split",$params));
        $splitSimulate = array(""=>'Parcela(s)');
        
        for($iTc = 0; $iTc < (int)$tcStandard->getConfigData('tcQtdSplit'); $iTc++){
            $splittings = $tcResponse->data_response->splittings->splitting[$iTc];
            $splitSimulate[(int)$splittings->split] = (string)$splittings->split . " x de R$" . number_format((float)$splittings->value_split, 2, ',','');
        }
        
        $this->setData('splitSimulate', $splitSimulate);
        
        return $splitSimulate;
    }
}
