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

class JFormFieldParam extends JFormField {
    public $type = 'Param';
    private $dataConfig;

    public function getInput() {
        // Подгружаем js и стили для select2 (https://select2.github.io/)
//        JFactory::getDocument()->addScript('/media/com_yandexmarket/js/select2.min.js');
//        JFactory::getDocument()->addStyleSheet('/media/com_yandexmarket/css/select2.min.css');

        // Подгружаем класс hikashop
        require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_yandexmarket' . DS . 'connectors' . DS . 'mainconnector.php');
        require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_yandexmarket' . DS . 'connectors' . DS . 'hikashop.php');

        $hikashop = new igoriHikashopConnector($data = null);

        //если HikaShop установлен, то
        if (!empty($hikashop->enableShop)) {
            $dataConfig = $this->form->getData();
            $this->dataConfig = new JRegistry($dataConfig->get('params'));

            // Достаём хикашоповские Дополнительные поля и Характеристики (варианты)
            $fields = $hikashop->loadCustomFields();
            $characteristics = $hikashop->loadRootCharacteristics();

            // Парсим сохранённое значение выбранных элементов мультисписка
            $paramValueString = $this->dataConfig->get("param-select");
            $paramValueArray = explode('||',$paramValueString);
            // Устраняем особенность explode() пустой строки, из-за которой в массив записывается пустая строка
            if (empty($paramValueArray[0])) unset ($paramValueArray[0]);
            $paramValueArrayFlip = array_flip($paramValueArray);

            $param = array(
                'weight' => 'COM_YANDEXMARKET_OFFERS_PARAM_WEIGHT',
                'dimensions' => 'COM_YANDEXMARKET_OFFERS_PARAM_DIMENSIONS'
            );

            $html = '
<div class="form-inline">
    <span>
        <select multiple="multiple" class="form-control" name="yandexmarket-select-param" id="yandexmarket-select-param">
        <option value="weight"';
            if (isset($paramValueArrayFlip['weight'])) $html .= " selected='selected'";
            $html .= '>' . JText::_("COM_YANDEXMARKET_OFFERS_PARAM_WEIGHT") . '
        </option>
        <option value="dimensions"';
            if (isset($paramValueArrayFlip['dimensions'])) $html .= " selected='selected'";
            $html .= '>' . JText::_("COM_YANDEXMARKET_OFFERS_PARAM_DIMENSIONS") . '
        </option>
        <optgroup label="' . JText::_("COM_YANDEXMARKET_OFFERS_PARAM_FIELD") . '">';
            $param['fields'] = JText::_("COM_YANDEXMARKET_OFFERS_PARAM_FIELD");
            foreach ($fields as $fieldKey => $field) {
                $param['field:' . $fieldKey] = $field['field_realname'];
                $html .= "<option value='field:$fieldKey'";
                if (isset($paramValueArrayFlip['field:' . $fieldKey])) $html .= " selected='selected'";
                $html .= ">" . JText::_($field['field_realname']) . "</option>";
            }
            $html .= '
        </optgroup>
        <optgroup label="' . JText::_("COM_YANDEXMARKET_OFFERS_PARAM_CHARACTERISTIC") . '">';
            $param['chars'] = JText::_("COM_YANDEXMARKET_OFFERS_PARAM_CHARACTERISTIC");
            foreach ($characteristics as $charKey => $char) {
                $param['char:' . $charKey] = $char['value'];
                $html .= "<option value='char:$charKey'";
                if (isset($paramValueArrayFlip['char:' . $charKey])) $html .= " selected='selected'";
                $html .= ">" . JText::_($char['value']) . "</option>";
            }
            $html .= '
        </optgroup>
        </select>
    </span>
</div>
';

            // Готовим мультисписок в виде строки с элементами в кавычках и через запятую, чтобы сформировать массив в js
            $paramChoiceNameJsString = '';
            if (!empty($paramValueArray)) {
                foreach ($paramValueArray as $item) {
                    $paramChoiceNameJsString .= "'" . $item . "',";
                }
            }
            $paramChoiceValueJsString = '';
            if (!empty($paramValueArray)) {
                foreach ($paramValueArray as $item) {
                    $paramChoiceValueJsString .= "'" . JText::_($param[$item]) . "',";
                }
            }

            // Готовим массив всех элементов списка в виде строки для формирования массива в js
            $paramNamesJs = '';
            $paramValuesJs = '';
            foreach ($param as $key=>$value) {
                $paramNamesJs .= "'" . $key . "',";
                $paramValuesJs .= "'" . JText::_($value) . "',";
            }

            // JS для отображения второго выпадающего списка для выбора дополнительного поля или характеристики
            $js = <<<HTML
jQuery(document).ready(function() {
  <!--jQuery("#yandexmarket-select-param").select2();-->
  <!--Все элементы выпадающего списка-->
  var yandexmarketParamNames = [$paramNamesJs];
  <!--Значения всех элементов выпадающего списка-->
  var yandexmarketParamValues = [$paramValuesJs];
  <!--Выбранные элементы-->
  var yandexmarketParamNamesChoice = [$paramChoiceNameJsString];
  <!--Значения выбранных элементов-->
  var yandexmarketParamValuesChoice = [$paramChoiceValueJsString];
  <!--Удаляем элемент-->
  jQuery('#yandexmarket_select_param_chzn a.search-choice-close').click(function() {
    var arrayKey = jQuery(this).attr('data-option-array-index');
    yandexmarketParamRemoveValue(arrayKey);
  });
  function yandexmarketParamRemoveValue(arrayKey) {
    var delElement = yandexmarketParamNames[arrayKey];
    var delElementKey = yandexmarketParamNamesChoice.indexOf(delElement);
    yandexmarketParamNamesChoice.splice(delElementKey,1);
    yandexmarketParamValuesChoice.splice(delElementKey,1);
    var paramChoiceStr = yandexmarketParamNamesChoice.join('||');
    jQuery('#jform_params_param_select').val(paramChoiceStr);
  }
  <!--Добавляем элемент-->
  jQuery('#yandexmarket_select_param_chzn ul.chzn-results').click(function() {
    var addElements = jQuery('#yandexmarket_select_param_chzn ul.chzn-choices').children('li.search-choice');
    yandexmarketParamAddValue(addElements);
  });
  function yandexmarketParamAddValue(addElements) {
    yandexmarketParamNamesChoice = [];
    yandexmarketParamValuesChoice = [];
    jQuery(addElements).each(function(key) {
      var itemElement = jQuery(this).find('a.search-choice-close');
      var addElementKey = jQuery(itemElement).attr('data-option-array-index');
      var addElementName = yandexmarketParamNames[addElementKey];
      var addElementValue = yandexmarketParamValues[addElementKey];
      yandexmarketParamNamesChoice.splice(key,0,addElementName)
      yandexmarketParamValuesChoice.splice(key,0,addElementValue)
    });
    var paramChoiceStr = yandexmarketParamNamesChoice.join('||');
    jQuery('#jform_params_param_select').val(paramChoiceStr);
  }
});
HTML;

            JFactory::getDocument()->addScriptDeclaration($js);

            return $html;
        }

        return JText::_('COM_YANDEXMARKET_HIKASHOP_NOT_INSTALLED');
    }
} //JFormFieldParam
