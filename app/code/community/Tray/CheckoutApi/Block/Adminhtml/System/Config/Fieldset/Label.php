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

class Tray_CheckoutApi_Block_Adminhtml_System_Config_Fieldset_Label extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{   
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $paymentMethod = str_replace(array("payment_","_configButtom"),"",$element->getHtmlId());
        
        $paymentMethod = ($paymentMethod == "traycheckoutapi_bankslip") ? "bankslip" : (($paymentMethod == "traycheckoutapi_onlinetransfer") ? "onlinetransfer" : "standard");
        
        $configTc = Mage::getSingleton('checkoutapi/'.$paymentMethod);
        
        $urlLoja = (Mage::app()->getStore()->isCurrentlySecure()) ? Mage::getUrl('',array("_secure" => true)) : Mage::getBaseUrl();

        $html = sprintf('
            <tr id="row_payment_traycheckoutapi_%s">
                <td class="label">
                    <label for="payment_traycheckoutapi_%s">%s</label>
                </td>
                <td class="value">
                    <button type="button" id="%s" name="%s" class="button" onclick="openModalTc(\'http://developers.tray.com.br/authLogin.php?environment=%s&path=%s&type=%s\', \'Configuração TrayCheckout\');openMessagePopup();return false;"> Configurar </button>
                </td>
                <td class="scope-label"></td>
                <td class=""></td>
            </tr>
            ',
            $element->getHtmlId(), $element->getHtmlId(), $element->getLabel(), $element->getHtmlId(), $element->getName(), $configTc->getConfigData("sandbox") ,$urlLoja, $paymentMethod
        );
        
        $html .= <<<HTML
        
HTML;
        
        return $html;
    }
}
