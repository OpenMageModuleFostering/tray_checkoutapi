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

class Tray_CheckoutApi_Block_Adminhtml_System_Config_Fieldset_Hidden extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{   
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $id = $element->getHtmlId();
        
        $html = sprintf('
            <tr id="row_payment_traycheckoutapi_%s" >
                <td class="value" colspan="4">
                    <input id="%s" name="%s" value="%s" class="input-text" type="hidden" >
                </td>
            </tr>',
            $element->getHtmlId(), $element->getHtmlId(), $element->getName(), $element->getValue()
        );
                
        $html .= <<<HTML
        
HTML;
        
        return $html;
    }
}
