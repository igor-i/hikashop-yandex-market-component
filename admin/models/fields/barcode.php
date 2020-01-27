<?php
/**
 * @package   Yandex.Market for HikaShop
 * @subpackage   com_yandexmarket
 * @author   Igor Inkovskiy
 * @copyright   Copyright (C) 2017 Igor Inkovskiy. All rights reserved.
 * @contact   https://shop.igor-i.ru, igor-i-shop@ya.ru
 * @license   Beerware
 */
defined('_JEXEC') or die('Restricted access');

class JFormFieldBarcode extends JFormField {
    public $type = 'Barcode';
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

            // JS для отображения второго выпадающего списка для выбора дополнительного поля
            $js = <<<HTML
jQuery(document).ready(function() {
  jQuery('#yandexmarket-select-barcode').change(function() {
    var variableValue = jQuery(this).val();
    jQuery('#jform_params_barcode_select').val(variableValue);
    var fieldValue = jQuery('#yandexmarket-select-barcode-field').val();
    jQuery('#jform_params_barcode_field_select').val(fieldValue);
    if (variableValue=="field") {
      jQuery('#yandexmarket-barcode-field').removeClass("hidden");
    } else {
      jQuery('#yandexmarket-barcode-field').addClass("hidden");
    }
  });

  jQuery('#yandexmarket-select-barcode-field').change(function() {
    var fieldValue = jQuery(this).val();
    jQuery('#jform_params_barcode_field_select').val(fieldValue);
  });
});
HTML;

            // Если в конфиге не установлены параметры формирования Штрихкода товара, то нужно задать умолчательное значение
            if (empty($this->dataConfig->get("barcode-select"))) {
                $js .= <<<HTML
                    <!--Устанавливаем умолчательные значения-->
                    jQuery(document).ready(function() {
                        jQuery('#jform_params_barcode-select').val('non');
                    });
HTML;
            }

            $fields = $hikashop->loadCustomFields();

            $html = '
<div class="form-inline">
    <span>
        <select class="form-control" name="yandexmarket-select-barcode" id="yandexmarket-select-barcode">
            <option value="non"';
            if (($this->dataConfig->get("barcode-select")=="non") || (empty($this->dataConfig->get("barcode-select")))) {
                $html .= " selected='selected'";
            }
            $html .= '>' . JText::_("COM_YANDEXMARKET_OFFERS_BARCODE_NON") . '</option>
            <option value="code"';
            if ($this->dataConfig->get("barcode-select")=="code") $html .= " selected='selected'";
            $html .= '>' . JText::_("COM_YANDEXMARKET_OFFERS_BARCODE_CODE") . '</option>
            <option value="field"';
            if ($this->dataConfig->get("barcode-select")=="field") $html .= " selected='selected'";
            $html .= '>' . JText::_("COM_YANDEXMARKET_OFFERS_BARCODE_FIELD") . '</option>
        </select>
    </span>
    <span id="yandexmarket-barcode-field"';
            if ($this->dataConfig->get("barcode-select")!="field") $html .= ' class="hidden"';
            $html .= '>
        <select class="form-control" name="yandexmarket-select-barcode" id="yandexmarket-select-barcode-field">';
            foreach ($fields as $fieldKey => $field) {
                $html .= "<option value='$fieldKey'";
                if ($this->dataConfig->get("barcode-field-select")=="$fieldKey") $html .= " selected='selected'";
                $html .= ">" . JText::_($field['field_realname']) . "</option>";
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
} //JFormFieldBarcode
