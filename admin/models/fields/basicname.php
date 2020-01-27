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

class JFormFieldBasicname extends JFormField {
    public $type = 'Basicname';
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
            $basicNameValueString = $this->dataConfig->get("basicName-select");
            $basicNameValueArray = explode('||',$basicNameValueString);
            // Устраняем особенность explode() пустой строки, из-за которой в массив записывается пустая строка
            if (empty($basicNameValueArray[0])) unset ($basicNameValueArray[0]);
            $basicNameValueArrayFlip = array_flip($basicNameValueArray);

            $basicName = array(
                'typePrefix' => 'COM_YANDEXMARKET_OFFERS_TYPE_PREFIX',
                'vendor' => 'COM_YANDEXMARKET_OFFERS_VENDOR',
                'model' => 'COM_YANDEXMARKET_OFFERS_MODEL'
            );

            $html = '
<div class="form-inline">
    <span>
        <select multiple="multiple" class="form-control" name="yandexmarket-select-basicName" id="yandexmarket-select-basicName">
        <option value="typePrefix"';
            if (isset($basicNameValueArrayFlip['typePrefix']) || empty($basicNameValueArrayFlip)) {
                $html .= " selected='selected'";
            }
            $html .= '>' . JText::_("COM_YANDEXMARKET_OFFERS_TYPE_PREFIX") . '
        </option>
        <option value="vendor"';
            if (isset($basicNameValueArrayFlip['vendor']) || empty($basicNameValueArrayFlip)) {
                $html .= " selected='selected'";
            }
            $html .= '>' . JText::_("COM_YANDEXMARKET_OFFERS_VENDOR") . '
        </option>
        <option value="model"';
            if (isset($basicNameValueArrayFlip['model']) || empty($basicNameValueArrayFlip)) {
                $html .= " selected='selected'";
            }
            $html .= '>' . JText::_("COM_YANDEXMARKET_OFFERS_MODEL") . '
        </option>
        <optgroup label="' . JText::_("COM_YANDEXMARKET_OFFERS_VENDOR_FIELD") . '">';
            $basicName['fields'] = JText::_("COM_YANDEXMARKET_OFFERS_VENDOR_FIELD");
            foreach ($fields as $fieldKey => $field) {
                $basicName['field:' . $fieldKey] = $field['field_realname'];
                $html .= "<option value='field:$fieldKey'";
                if (isset($basicNameValueArrayFlip['field:' . $fieldKey])) $html .= " selected='selected'";
                $html .= ">" . JText::_($field['field_realname']) . "</option>";
            }
            $html .= '
        </optgroup>
        <optgroup label="' . JText::_("COM_YANDEXMARKET_OFFERS_VENDOR_CHARACTERISTIC") . '">';
            $basicName['chars'] = JText::_("COM_YANDEXMARKET_OFFERS_VENDOR_CHARACTERISTIC");
            foreach ($characteristics as $charKey => $char) {
                $basicName['char:' . $charKey] = $char['value'];
                $html .= "<option value='char:$charKey'";
                if (isset($basicNameValueArrayFlip['char:' . $charKey])) $html .= " selected='selected'";
                $html .= ">" . JText::_($char['value']) . "</option>";
            }
            $html .= '
        </optgroup>
        </select>
    </span>
    <div>';
            $count = count($basicNameValueArray);
            if ($count!==0) {
                $html .= '
        <br />
        <span>' . JText::_("COM_YANDEXMARKET_OFFERS_GROUPING_ORDER") . '</span>
        <span id="yandexmarket-select-basicName-grouping-order">';
            }
            foreach ($basicNameValueArray as $item) {
                $html .= JText::_($basicName[$item]);
                if ($count!==1) $html .= ' -> ';
                $count--;
            }
            $html .=
        '</span>
    </div>
</div>
';

            // Готовим мультисписок в виде строки с элементами в кавычках и через запятую, чтобы сформировать массив в js
            $basicNameChoiceNameJsString = '';
            if (!empty($basicNameValueArray)) {
                foreach ($basicNameValueArray as $item) {
                    $basicNameChoiceNameJsString .= "'" . $item . "',";
                }
            }
            $basicNameChoiceValueJsString = '';
            if (!empty($basicNameValueArray)) {
                foreach ($basicNameValueArray as $item) {
                    $basicNameChoiceValueJsString .= "'" . JText::_($basicName[$item]) . "',";
                }
            }

            // Готовим массив всех элементов списка в виде строки для формирования массива в js
            $basicNameJs = '';
            $basicValuesJs = '';
            foreach ($basicName as $key=>$value) {
                $basicNameJs .= "'" . $key . "',";
                $basicValuesJs .= "'" . JText::_($value) . "',";
            }

            // JS для отображения второго выпадающего списка для выбора дополнительного поля или характеристики
            $js = <<<HTML
jQuery(document).ready(function() {
  <!--jQuery("#yandexmarket-select-basicName").select2();-->
  <!--Все элементы выпадающего списка-->
  var yandexmarketBasicNameNames = [$basicNameJs];
  <!--Значения всех элементов выпадающего списка-->
  var yandexmarketBasicNameValues = [$basicValuesJs];
  <!--Выбранные элементы-->
  var yandexmarketBasicNameChoiceNames = [$basicNameChoiceNameJsString];
  <!--Значения выбранных элементов-->
  var yandexmarketBasicNameChoiceValues = [$basicNameChoiceValueJsString];
  <!--Удаляем элемент-->
  jQuery('#yandexmarket_select_basicName_chzn a.search-choice-close').click(function() {
    var arrayKey = jQuery(this).attr('data-option-array-index');
    yandexmarketBasicNameRemoveValue(arrayKey);
    yandexmarketBasicNameRedrawGroupingOrder();
  });

  function yandexmarketBasicNameRemoveValue(arrayKey) {
    var delElement = yandexmarketBasicNameNames[arrayKey];
    var delElementKey = yandexmarketBasicNameChoiceNames.indexOf(delElement);
    yandexmarketBasicNameChoiceNames.splice(delElementKey,1);
    yandexmarketBasicNameChoiceValues.splice(delElementKey,1);
    var basicNameChoiceStr = yandexmarketBasicNameChoiceNames.join('||');
    jQuery('#jform_params_basicName_select').val(basicNameChoiceStr);
  }

  <!--Добавляем элемент-->
  jQuery('#yandexmarket_select_basicName_chzn ul.chzn-results').click(function() {
    var addElements = jQuery('#yandexmarket_select_basicName_chzn ul.chzn-choices').children('li.search-choice');
    yandexmarketBasicNameAddValue(addElements);
    yandexmarketBasicNameRedrawGroupingOrder();
  });

  function yandexmarketBasicNameAddValue(addElements) {
    yandexmarketBasicNameChoiceNames = [];
    yandexmarketBasicNameChoiceValues = [];
    jQuery(addElements).each(function(key) {
      var itemElement = jQuery(this).find('a.search-choice-close');
      var addElementKey = jQuery(itemElement).attr('data-option-array-index');
      var addElementName = yandexmarketBasicNameNames[addElementKey];
      var addElementValue = yandexmarketBasicNameValues[addElementKey];
      yandexmarketBasicNameChoiceNames.splice(key,0,addElementName)
      yandexmarketBasicNameChoiceValues.splice(key,0,addElementValue)
    });
    var basicNameChoiceStr = yandexmarketBasicNameChoiceNames.join('||');
    jQuery('#jform_params_basicName_select').val(basicNameChoiceStr);
  }

  <!--Перерисовываем "Порядок группировки"-->
  function yandexmarketBasicNameRedrawGroupingOrder() {
    var index, len;
    var strGrOrder = '';
    for (index = 0, len = yandexmarketBasicNameChoiceValues.length; index < len; ++index) {
      strGrOrder += yandexmarketBasicNameChoiceValues[index];
      if ((len-1)!==index) strGrOrder += ' -> ';
    }
    jQuery('#yandexmarket-select-basicName-grouping-order').text(strGrOrder);
  }
});
HTML;

            // Если в конфиге не установлены параметры формирования Наименования товара, то нужно задать умолчательное значение
            if (empty($basicNameValueArrayFlip)) {
                $js .= <<<HTML
                    <!--Устанавливаем умолчательные значения-->
                    jQuery(document).ready(function() {
                        jQuery('#jform_params_basicName_select').val('typePrefix||vendor||model');
                    });
HTML;
            }

            JFactory::getDocument()->addScriptDeclaration($js);
            return $html;
        }

        return JText::_('COM_YANDEXMARKET_HIKASHOP_NOT_INSTALLED');
    }
} //JFormFieldBasicname
