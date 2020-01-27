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

class JFormFieldCurrencies extends JFormField {
    public $type = 'Currencies';
    private $dataConfig;

    public function getInput() {
        $currenciesList = array(
            'usd' => JText::_("COM_YANDEXMARKET_CURRENCY_USD"),
            'eur' => JText::_("COM_YANDEXMARKET_CURRENCY_EUR"),
            'rub' => JText::_("COM_YANDEXMARKET_CURRENCY_RUB"),
            'byn' => JText::_("COM_YANDEXMARKET_CURRENCY_BYN"),
            'uah' => JText::_("COM_YANDEXMARKET_CURRENCY_UAH"),
            'kzt' => JText::_("COM_YANDEXMARKET_CURRENCY_KZT"),
        );

        $dataConfig = $this->form->getData();
        $this->dataConfig = new JRegistry($dataConfig->get('params'));

        $html = '<div class="table-responsive">
            <table class="table table-condensed">';

        $js = "";

        foreach ($currenciesList as $k=>$v) {
            $js .= <<<HTML
jQuery(document).ready(function() {
  jQuery('#yandexmarket-select-rate-$k').change(function() {
    var variableValue$k = jQuery(this).val();
    jQuery('#jform_select_rate_$k').val(variableValue$k);
    jQuery('#jform_params_select_rate_$k').val(variableValue$k);
    if (variableValue$k=="manual") {
      jQuery('#yandexmarket-rate-manual-$k').removeClass("hidden");
      jQuery('#yandexmarket-rate-percent-$k').addClass("hidden");
    } else if (variableValue$k=="0") {
      jQuery('#yandexmarket-rate-manual-$k').addClass("hidden");
      jQuery('#yandexmarket-rate-percent-$k').addClass("hidden");
    } else {
      jQuery('#yandexmarket-rate-manual-$k').addClass("hidden");
      jQuery('#yandexmarket-rate-percent-$k').removeClass("hidden");
    }
  });

  jQuery('#yandexmarket-rate-manual-$k > input').change(function() {
    var rateManualValue$k = jQuery(this).val();
    jQuery('#jform_params_rate_manual_$k').val(rateManualValue$k);
  });

  jQuery('#yandexmarket-rate-percent-$k > input').change(function() {
    var ratePercentValue$k = jQuery(this).val();
    jQuery('#jform_params_rate_percent_$k').val(ratePercentValue$k);
  });
});
HTML;

            $html .= <<<HTML
<tr>
  <td>$v</td>
  <td>
    <select class="form-control" name="yandexmarket-select-rate-$k" id="yandexmarket-select-rate-$k">
      <option value="0">Не использовать</option>
HTML;

            switch ($this->dataConfig->get("select-rate-" . $k)) {
                case 'manual':
                    $html .= '<option selected value="manual">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_MANUAL") . '</option>
                        <option value="СВ">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_CB") . '</option>
                        <option value="CBRF">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_CBRF") . '</option>
                        <option value="NBU">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_NBU") . '</option>
                        <option value="NBK">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_NBK") . '</option>';
                    break;
                case "СВ":
                    $html .= '<option value="manual">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_MANUAL") . '</option>
                        <option selected value="СВ">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_CB") . '</option>
                        <option value="CBRF">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_CBRF") . '</option>
                        <option value="NBU">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_NBU") . '</option>
                        <option value="NBK">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_NBK") . '</option>';
                    break;
                case "CBRF":
                    $html .= '<option value="manual">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_MANUAL") . '</option>
                        <option value="СВ">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_CB") . '</option>
                        <option selected value="CBRF">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_CBRF") . '</option>
                        <option value="NBU">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_NBU") . '</option>
                        <option value="NBK">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_NBK") . '</option>';
                    break;
                case "NBU":
                    $html .= '<option value="manual">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_MANUAL") . '</option>
                        <option value="СВ">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_CB") . '</option>
                        <option value="CBRF">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_CBRF") . '</option>
                        <option selected value="NBU">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_NBU") . '</option>
                        <option value="NBK">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_NBK") . '</option>';
                    break;
                case "NBK":
                    $html .= '<option value="manual">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_MANUAL") . '</option>
                        <option value="СВ">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_CB") . '</option>
                        <option value="CBRF">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_CBRF") . '</option>
                        <option value="NBU">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_NBU") . '</option>
                        <option selected value="NBK">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_NBK") . '</option>';
                    break;
                default:
                    $html .= '<option value="manual">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_MANUAL") . '</option>
                        <option selected value="СВ">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_CB") . '</option>
                        <option value="CBRF">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_CBRF") . '</option>
                        <option value="NBU">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_NBU") . '</option>
                        <option value="NBK">' . JText::_("COM_YANDEXMARKET_CURRENCY_SELECT_RATE_NBK") . '</option>';
            }

            $html .= '</select>
            </td>
            <td>
              <div';

            if ($this->dataConfig->get("select-rate-" . $k) !== 'manual') {
                $html .= ' class="hidden"';
            }

            if (!empty($this->dataConfig->get("rate-manual-" . $k))) {
                $rateManual = $this->dataConfig->get("rate-manual-" . $k);
            } else {
                $rateManual = 0;
            }

            $html .= ' id="yandexmarket-rate-manual-' . $k . '">
                <label id="jform_params_rate-manual-' . $k . '-lbl" class="hidden">
                ' . JText::_("COM_YANDEXMARKET_ADDITIONAL_CURRENCY") . ' ->
                ' . $v . '
                </label>
               <input
                 class="form-control required"
                 name="jform[params][rate-manual-' . $k . ']"
                 id="jform_params_rate-manual-' . $k . '"
                 style="width: 50px"
                 type="number"
                 step="0.01"
                 aria-required="true"
                 value="' . $rateManual . '"
               />
             </div>
             <div';
            if (!(
                $this->dataConfig->get("select-rate-" . $k) === 'СВ' ||
                $this->dataConfig->get("select-rate-" . $k) === 'CBRF' ||
                $this->dataConfig->get("select-rate-" . $k) === 'NBU' ||
                $this->dataConfig->get("select-rate-" . $k) === 'NBK'
               ) && !empty($this->dataConfig->get("select-rate-" . $k))) {
               $html .= ' class="hidden"';
            }

            if (!empty($this->dataConfig->get("rate-percent-" . $k))) {
                $ratePercent = $this->dataConfig->get("rate-percent-" . $k);
            } else {
                $ratePercent = 0;
            }

            $html .= ' id="yandexmarket-rate-percent-' . $k . '">
                                <label id="jform_params_rate-percent-' . $k . '-lbl" class="hidden">
                                    ' . JText::_("COM_YANDEXMARKET_ADDITIONAL_CURRENCY") . ' ->
                                    ' . $v . '
                                </label>
                        +
            <input
              class="form-control required"
              name="jform[params][rate-percent-' . $k . ']"
              id="jform_params_rate-percent-' . $k . '"
              style="width: 40px"
              type="number"
              step="0.01"
              aria-required="true"
              value="' . $ratePercent . '"
            />
            %
          </div>
        </td>
      </tr>';
        }

        $html .= '</table>
				</div>';

        JFactory::getDocument()->addScriptDeclaration($js);

        return $html;
    }
} //JFormFieldCurrencies
