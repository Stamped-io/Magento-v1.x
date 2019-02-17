<?php 
class Stamped_App_Block_Adminhtml_System_Config_Form_Syncproductbutton extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /*
     * template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('stamped_app/syncproductbutton.phtml');
    }
 
    /**
     * return html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }
}

?>