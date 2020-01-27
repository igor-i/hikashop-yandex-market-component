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

class JFormFieldModel extends JFormField {
    public $type = 'Model';
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
            $modelValueString = $this->dataConfig->get("model-select");
            $modelValueArray = explode('||',$modelValueString);
            // Устраняем особенность explode() пустой строки, из-за которой в массив записывается пустая строка
            if (empty($modelValueArray[0])) unset ($modelValueArray[0]);
            $modelValueArrayFlip = array_flip($modelValueArray);

            $model = array(
                'hika_prod_name' => 'COM_YANDEXMARKET_OFFERS_MODEL_HIKA_PROD_NAME'
            );

            $html = '
<div class="form-inline">
    <span>
        <select multiple="multiple" class="form-control" name="yandexmarket-select-model" id="yandexmarket-select-model">
        <option value="hika_prod_name"';
            if (isset($modelValueArrayFlip['hika_prod_name']) || empty($modelValueArrayFlip)) {
                $html .= " selected='selected'";
            }
            $html .= '>' . JText::_("COM_YANDEXMARKET_OFFERS_MODEL_HIKA_PROD_NAME") . '
        </option>
        <optgroup label="' . JText::_("COM_YANDEXMARKET_OFFERS_VENDOR_FIELD") . '">';
            $model['fields'] = JText::_("COM_YANDEXMARKET_OFFERS_VENDOR_FIELD");
            foreach ($fields as $fieldKey => $field) {
                $model['field:' . $fieldKey] = $field['field_realname'];
                $html .= "<option value='field:$fieldKey'";
                if (isset($modelValueArrayFlip['field:' . $fieldKey])) $html .= " selected='selected'";
                $html .= ">" . JText::_($field['field_realname']) . "</option>";
            }
            $html .= '
        </optgroup>
        <optgroup label="' . JText::_("COM_YANDEXMARKET_OFFERS_VENDOR_CHARACTERISTIC") . '">';
            $model['chars'] = JText::_("COM_YANDEXMARKET_OFFERS_VENDOR_CHARACTERISTIC");
            foreach ($characteristics as $charKey => $char) {
                $model['char:' . $charKey] = $char['value'];
                $html .= "<option value='char:$charKey'";
                if (isset($modelValueArrayFlip['char:' . $charKey])) $html .= " selected='selected'";
                $html .= ">" . JText::_($char['value']) . "</option>";
            }
            $html .= '
        </optgroup>
        </select>
    </span>
    <div>';
            $count = count($modelValueArray);
            if ($count!==0) {
                $html .= '
        <br />
        <span>' . JText::_("COM_YANDEXMARKET_OFFERS_GROUPING_ORDER") . '</span>
        <span id="yandexmarket-select-model-grouping-order">';
            }
            foreach ($modelValueArray as $item) {
                $html .= JText::_($model[$item]);
                if ($count!==1) $html .= ' -> ';
                $count--;
            }
            $html .=
                '</span>
    </div>
</div>
';

            // Готовим мультисписок в виде строки с элементами в кавычках и через запятую, чтобы сформировать массив в js
            $modelChoiceNameJsString = '';
            if (!empty($modelValueArray)) {
                foreach ($modelValueArray as $item) {
                    $modelChoiceNameJsString .= "'" . $item . "',";
                }
            }
            $modelChoiceValueJsString = '';
            if (!empty($modelValueArray)) {
                foreach ($modelValueArray as $item) {
                    $modelChoiceValueJsString .= "'" . JText::_($model[$item]) . "',";
                }
            }

            // Готовим массив всех элементов списка в виде строки для формирования массива в js
            $modelNamesJs = '';
            $modelValuesJs = '';
            foreach ($model as $key=>$value) {
                $modelNamesJs .= "'" . $key . "',";
                $modelValuesJs .= "'" . JText::_($value) . "',";
            }

            // JS для отображения второго выпадающего списка для выбора дополнительного поля или характеристики
            $js = <<<HTML
jQuery(document).ready(function() {
  <!--jQuery("#yandexmarket-select-model").select2();-->
  <!--Все элементы выпадающего списка-->
  var yandexmarketModelNames = [$modelNamesJs];
  <!--Значения всех элементов выпадающего списка-->
  var yandexmarketModelValues = [$modelValuesJs];
  <!--Выбранные элементы-->
  var yandexmarketModelNamesChoice = [$modelChoiceNameJsString];
  <!--Значения выбранных элементов-->
  var yandexmarketModelValuesChoice = [$modelChoiceValueJsString];
  <!--Удаляем элемент-->
  jQuery('#yandexmarket_select_model_chzn a.search-choice-close').click(function() {
    var arrayKey = jQuery(this).attr('data-option-array-index');
    yandexmarketModelRemoveValue(arrayKey);
    yandexmarketModelRedrawGroupingOrder();
  });
  function yandexmarketModelRemoveValue(arrayKey) {
    var delElement = yandexmarketModelNames[arrayKey];
    var delElementKey = yandexmarketModelNamesChoice.indexOf(delElement);
    yandexmarketModelNamesChoice.splice(delElementKey,1);
    yandexmarketModelValuesChoice.splice(delElementKey,1);
    var modelChoiceStr = yandexmarketModelNamesChoice.join('||');
    jQuery('#jform_params_model_select').val(modelChoiceStr);
  }
  <!--Добавляем элемент-->
  jQuery('#yandexmarket_select_model_chzn ul.chzn-results').click(function() {
    var addElements = jQuery('#yandexmarket_select_model_chzn ul.chzn-choices').children('li.search-choice');
    yandexmarketModelAddValue(addElements);
    yandexmarketModelRedrawGroupingOrder();
  });
  function yandexmarketModelAddValue(addElements) {
    yandexmarketModelNamesChoice = [];
    yandexmarketModelValuesChoice = [];
    jQuery(addElements).each(function(key) {
      var itemElement = jQuery(this).find('a.search-choice-close');
      var addElementKey = jQuery(itemElement).attr('data-option-array-index');
      var addElementName = yandexmarketModelNames[addElementKey];
      var addElementValue = yandexmarketModelValues[addElementKey];
      yandexmarketModelNamesChoice.splice(key,0,addElementName)
      yandexmarketModelValuesChoice.splice(key,0,addElementValue)
    });
    var modelChoiceStr = yandexmarketModelNamesChoice.join('||');
    jQuery('#jform_params_model_select').val(modelChoiceStr);
  }
  <!--Перерисовываем "Порядок группировки"-->
  function yandexmarketModelRedrawGroupingOrder() {
    var index, len;
    var strGrOrder = '';
    for (index = 0, len = yandexmarketModelValuesChoice.length; index < len; ++index) {
      strGrOrder += yandexmarketModelValuesChoice[index];
      if ((len-1)!==index) strGrOrder += ' -> ';
    }
    jQuery('#yandexmarket-select-model-grouping-order').text(strGrOrder);
  }
});
HTML;

            // Если в конфиге не установлены параметры формирования Модели, то нужно задать умолчательное значение
            if (empty($modelValueArrayFlip)) {
                $js .= <<<HTML
                    <!--Устанавливаем умолчательные значения-->
                    jQuery(document).ready(function() {
                        jQuery('#jform_params_model_select').val('hika_prod_name');
                    });
HTML;
            }

            JFactory::getDocument()->addScriptDeclaration($js);
            return $html;
        }

        return JText::_('COM_YANDEXMARKET_HIKASHOP_NOT_INSTALLED');
    }
} //JFormFieldModel
