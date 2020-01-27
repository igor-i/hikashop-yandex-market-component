<?php
/**
 * @package   Yandex.Market for HikaShop
 * @subpackage     com_yandexmarket
 * @author   Igor Inkovskiy
 * @copyright   Copyright (C) 2017 Igor Inkovskiy. All rights reserved.
 * @contact   https://shop.igor-i.ru, igor-i-shop@ya.ru
 * @license   Beerware
 */
defined('_JEXEC') or die('Restricted access');

class JFormFieldVendor extends JFormField {
    public $type = 'Vendor';
    private $dataConfig;

    public function getInput() {
//        $params = JComponentHelper::getParams('com_yandexmarket');
//        $connector = $params->get('connector');
        require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_yandexmarket' . DS . 'connectors' . DS . 'mainconnector.php');
        require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_yandexmarket' . DS . 'connectors' . DS . 'hikashop.php');

        $hikashop = new igoriHikashopConnector($data = null);

        //если HikaShop установлен, то
        if (!empty($hikashop->enableShop)) {

            $dataConfig = $this->form->getData();
            $this->dataConfig = new JRegistry($dataConfig->get('params'));

            // JS для отображения второго выпадающего списка для выбора дополнительного поля или характеристики
            $js = <<<HTML
jQuery(document).ready(function() {
  jQuery('#yandexmarket-select-vendor').change(function() {
    var variableValue = jQuery(this).val();
    jQuery('#jform_params_vendor_select').val(variableValue);
    var fieldValue = jQuery('#yandexmarket-select-vendor-field').val();
    jQuery('#jform_params_vendor_field_select').val(fieldValue);
    var characteristicValue = jQuery('#yandexmarket-select-vendor-characteristic').val();
    jQuery('#jform_params_vendor_characteristic_select').val(characteristicValue);
    if (variableValue=="field") {
      jQuery('#yandexmarket-vendor-field').removeClass("hidden");
      jQuery('#yandexmarket-vendor-characteristic').addClass("hidden");
      jQuery('#yandexmarketVendorAlert').addClass("hidden");
    } else if (variableValue=="characteristic") {
      jQuery('#yandexmarket-vendor-field').addClass("hidden");
      jQuery('#yandexmarket-vendor-characteristic').removeClass("hidden");
      jQuery('#yandexmarketVendorAlert').addClass("hidden");
    } else if (variableValue=="non") {
      jQuery('#yandexmarket-vendor-field').addClass("hidden");
      jQuery('#yandexmarket-vendor-characteristic').addClass("hidden");
      jQuery('#yandexmarketVendorAlert').removeClass("hidden");
    } else {
      jQuery('#yandexmarket-vendor-field').addClass("hidden");
      jQuery('#yandexmarket-vendor-characteristic').addClass("hidden");
      jQuery('#yandexmarketVendorAlert').addClass("hidden");
    }
  });
  jQuery('#yandexmarket-select-vendor-field').change(function() {
    var fieldValue = jQuery(this).val();
    jQuery('#jform_params_vendor_field_select').val(fieldValue);
  });
  jQuery('#yandexmarket-select-vendor-characteristic').change(function() {
    var characteristicValue = jQuery(this).val();
    jQuery('#jform_params_vendor_characteristic_select').val(characteristicValue);
  });
});
HTML;
            // Если в конфиге не установлены параметры формирования Производителя, то нужно задать умолчательное значение
            if (empty($this->dataConfig->get("vendor-select"))) {
                $js .= <<<HTML
                    <!--Устанавливаем умолчательные значения-->
                    jQuery(document).ready(function() {
                        jQuery('#jform_params_vendor_select').val('manufacturer');
                    });
HTML;
            }

            $fields = $hikashop->loadCustomFields();
            $characteristics = $hikashop->loadRootCharacteristics();

            $html = '<div id="yandexmarketVendorAlert" class="alert alert-no-items';
            if ($this->dataConfig->get("vendor-select") !== "non") {
                $html .= ' hidden';
            }
            $html .= '"><span class="icon-warning" aria-hidden="true"></span>' . JText::_("COM_YANDEXMARKET_OFFERS_VENDOR_ALERT") . '</div>';

            $html .= '
<div class="form-inline">
    <span>
        <select class="form-control" name="yandexmarket-select-vendor" id="yandexmarket-select-vendor">
            <option value="non"';
            if ($this->dataConfig->get("vendor-select")=="non") $html .= " selected='selected'";
            $html .= '>' . JText::_("COM_YANDEXMARKET_OFFERS_VENDOR_NON") . '</option>
            <option value="manufacturer"';
            if (($this->dataConfig->get("vendor-select")=="manufacturer") || (empty($this->dataConfig->get("vendor-select")))) $html .= " selected='selected'";
            $html .= '>' . JText::_("COM_YANDEXMARKET_OFFERS_VENDOR_MANUFACTURER") . '</option>
            <!--<option value="vendor"';
            if ($this->dataConfig->get("vendor-select")=="vendor") $html .= " selected='selected'";
            $html .= '>' . JText::_("COM_YANDEXMARKET_OFFERS_VENDOR_VENDOR") . '</option>-->
            <option value="field"';
            if ($this->dataConfig->get("vendor-select")=="field") $html .= " selected='selected'";
            $html .= '>' . JText::_("COM_YANDEXMARKET_OFFERS_VENDOR_FIELD") . '</option>
            <option value="characteristic"';
            if ($this->dataConfig->get("vendor-select")=="characteristic") $html .= " selected='selected'";
            $html .= '>' . JText::_("COM_YANDEXMARKET_OFFERS_VENDOR_CHARACTERISTIC") . '</option>
        </select>
    </span>
    <span id="yandexmarket-vendor-field"';
            if ($this->dataConfig->get("vendor-select")!="field") $html .= ' class="hidden"';
            $html .= '>
        <select class="form-control" name="yandexmarket-select-vendor" id="yandexmarket-select-vendor-field">';
            foreach ($fields as $fieldKey => $field) {
                $html .= "<option value='$fieldKey'";
                if ($this->dataConfig->get("vendor-field-select")=="$fieldKey") $html .= " selected='selected'";
                $html .= ">" . JText::_($field['field_realname']) . "</option>";
            }
            $html .= '
        </select>
    </span>
    <span id="yandexmarket-vendor-characteristic"';
            if ($this->dataConfig->get("vendor-select")!="characteristic") $html .= ' class="hidden"';
            $html .= '>
        <select class="form-control" name="yandexmarket-select-vendor" id="yandexmarket-select-vendor-characteristic">';
            foreach ($characteristics as $charKey => $char) {
                $html .= "<option value='$charKey'";
                if ($this->dataConfig->get("vendor-characteristic-select")=="$charKey") $html .= " selected='selected'";
                $html .= ">" . JText::_($char['value']) . "</option>";
            }
            $html .= '
        </select>
    </span>
</div>
';

            JFactory::getDocument()->addScriptDeclaration($js);

            return $html;
        }

        return JText::_('COM_YANDEXMARKET_HIKASHOP_NOT_INSTALLED');
    }
} //JFormFieldVendor
