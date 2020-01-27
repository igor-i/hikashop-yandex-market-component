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

class JFormFieldTypeprefix extends JFormField {
    public $type = 'Typeprefix';
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
  jQuery('#yandexmarket-select-typePrefix').change(function() {
    var variableValue = jQuery(this).val();
    jQuery('#jform_params_typePrefix_select').val(variableValue);
    var fieldValue = jQuery('#yandexmarket-select-typePrefix-field').val();
    jQuery('#jform_params_typePrefix_field_select').val(fieldValue);
    var characteristicValue = jQuery('#yandexmarket-select-typePrefix-characteristic').val();
    jQuery('#jform_params_typePrefix_characteristic_select').val(characteristicValue);
    if (variableValue=="field") {
      jQuery('#yandexmarket-typePrefix-field').removeClass("hidden");
      jQuery('#yandexmarket-typePrefix-characteristic').addClass("hidden");
      jQuery('#yandexmarketTypePrefixAlert').addClass("hidden");
    } else if (variableValue=="characteristic") {
      jQuery('#yandexmarket-typePrefix-field').addClass("hidden");
      jQuery('#yandexmarket-typePrefix-characteristic').removeClass("hidden");
      jQuery('#yandexmarketTypePrefixAlert').addClass("hidden");
    } else if (variableValue=="non") {
      jQuery('#yandexmarket-typePrefix-field').addClass("hidden");
      jQuery('#yandexmarket-typePrefix-characteristic').addClass("hidden");
      jQuery('#yandexmarketTypePrefixAlert').removeClass("hidden");
    } else {
      jQuery('#yandexmarket-typePrefix-field').addClass("hidden");
      jQuery('#yandexmarket-typePrefix-characteristic').addClass("hidden");
      jQuery('#yandexmarketTypePrefixAlert').addClass("hidden");
    }
  });
  jQuery('#yandexmarket-select-typePrefix-field').change(function() {
    var fieldValue = jQuery(this).val();
    jQuery('#jform_params_typePrefix_field_select').val(fieldValue);
  });
  jQuery('#yandexmarket-select-typePrefix-characteristic').change(function() {
    var characteristicValue = jQuery(this).val();
    jQuery('#jform_params_typePrefix_characteristic_select').val(characteristicValue);
  });
});
HTML;

            // Если в конфиге не установлены параметры формирования Типа товара, то нужно задать умолчательное значение
            if (empty($this->dataConfig->get("typePrefix-select"))) {
                $js .= <<<HTML
                    <!--Устанавливаем умолчательные значения-->
                    jQuery(document).ready(function() {
                        jQuery('#jform_params_typePrefix_select').val('category');
                    });
HTML;
            }

            $fields = $hikashop->loadCustomFields();
            $characteristics = $hikashop->loadRootCharacteristics();

            $html = '<div id="yandexmarketTypePrefixAlert" class="alert alert-no-items';
            if ($this->dataConfig->get("typePrefix-select") !== "non") {
                $html .= ' hidden';
            }
            $html .= '"><span class="icon-warning" aria-hidden="true"></span>' . JText::_("COM_YANDEXMARKET_OFFERS_TYPE_PREFIX_ALERT") . '</div>';

            $html .= '
<div class="form-inline">
    <span>
        <select
            class="form-control" name="yandexmarket-select-typePrefix" id="yandexmarket-select-typePrefix">
            <option value="non"';
            if ($this->dataConfig->get("typePrefix-select")=="non") $html .= " selected='selected'";
            $html .= '>' . JText::_("COM_YANDEXMARKET_OFFERS_TYPE_PREFIX_NON") . '</option>
            <option value="category"';
            if (($this->dataConfig->get("typePrefix-select")=="category") || (empty($this->dataConfig->get("typePrefix-select")))) $html .= " selected='selected'";
            $html .= '>' . JText::_("COM_YANDEXMARKET_OFFERS_TYPE_PREFIX_CATEGORY") . '</option>
            <option value="field"';
            if ($this->dataConfig->get("typePrefix-select")=="field") $html .= " selected='selected'";
            $html .= '>' . JText::_("COM_YANDEXMARKET_OFFERS_TYPE_PREFIX_FIELD") . '</option>
            <option value="characteristic"';
            if ($this->dataConfig->get("typePrefix-select")=="characteristic") $html .= " selected='selected'";
            $html .= '>' . JText::_("COM_YANDEXMARKET_OFFERS_TYPE_PREFIX_CHARACTERISTIC") . '</option>
        </select>
    </span>
    <span id="yandexmarket-typePrefix-field"';
            if ($this->dataConfig->get("typePrefix-select")!="field") $html .= ' class="hidden"';
            $html .= '>
        <select class="form-control" name="yandexmarket-select-typePrefix" id="yandexmarket-select-typePrefix-field">';
            foreach ($fields as $fieldKey => $field) {
                $html .= "<option value='$fieldKey'";
                if ($this->dataConfig->get("typePrefix-field-select")=="$fieldKey") $html .= " selected='selected'";
                $html .= ">" . JText::_($field['field_realname']) . "</option>";
            }
            $html .= '
        </select>
    </span>
    <span id="yandexmarket-typePrefix-characteristic"';
            if ($this->dataConfig->get("typePrefix-select")!="characteristic") $html .= ' class="hidden"';
            $html .= '>
        <select class="form-control" name="yandexmarket-select-typePrefix" id="yandexmarket-select-typePrefix-characteristic">';
            foreach ($characteristics as $charKey => $char) {
                $html .= "<option value='$charKey'";
                if ($this->dataConfig->get("typePrefix-characteristic-select")=="$charKey") $html .= " selected='selected'";
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
} //JFormFieldTypeprefix
