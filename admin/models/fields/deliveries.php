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

class JFormFieldDeliveries extends JFormField {
    public $type = 'Deliveries';
    private $dataConfig;

    public function getInput() {
        $dataConfig = $this->form->getData();
        $this->dataConfig = new JRegistry($dataConfig->get('params'));

        $alert = false;
        $alertPeriodOld = '';
        $alertPeriodNew = '';

        for ($k=1; $k<=5; $k++) {
            $from = (int)$this->dataConfig->get("delivery-days-from-" . $k);
            $to = (int)$this->dataConfig->get("delivery-days-to-" . $k);
            if ($from < ($to - 2)) {
                $alert = true;
                $alertPeriodOld = $from . '-' . $to;
                $alertPeriodNew = $from . '-' . ($from + 2);
                break;
            }
        }

        $html = '<div id="yandexmarketDeliveryAlert" class="alert alert-no-items';
        if (!$alert) {
            $html .= ' hidden';
        }
        $html .= '"><span class="icon-warning" aria-hidden="true"></span>' . JText::sprintf('COM_YANDEXMARKET_DELIVERY_ALERT', $alertPeriodOld, $alertPeriodNew) . '</div>';

        $html .= '<div class="table-responsive">
            <table class="table table-condensed">
              <thead>
                <tr>
                  <th>' . JText::_("COM_YANDEXMARKET_DELIVERY_COST") . '</th>
                  <th>' . JText::_("COM_YANDEXMARKET_DELIVERY_PERIOD") . '</th>
                  <th>' . JText::_("COM_YANDEXMARKET_DELIVERY_TIME") . '</th>
                </tr>
              </thead>
            <tbody>';

        $js = "";

        for ($k=1; $k<=5; $k++) {
            $js .= <<<HTML
jQuery(document).ready(function() {
  jQuery('#yandexmarket-delivery-cost-$k > input').change(function() {
    var deliveryCostValue$k = jQuery(this).val();
    jQuery('#jform_params_delivery_cost_$k').val(deliveryCostValue$k);
  });

  jQuery('#jform_params_yandexmarket-delivery-days-from-$k').change(function() {
    var deliveryDaysFromValue$k = parseInt(jQuery(this).val());
    var deliveryDaysToValue$k = parseInt(jQuery('#jform_params_yandexmarket-delivery-days-to-$k').val());

    <!-- Если интервал доставки больше двух, то его надо уменьшить и вывести предупреждение -->
    if (deliveryDaysFromValue$k < (deliveryDaysToValue$k - 2)) {
      jQuery('#jform_params_yandexmarket-delivery-days-to-$k').val(deliveryDaysFromValue$k + 2);
      jQuery('#jform_params_delivery_days_to_$k').val(deliveryDaysFromValue$k + 2);
      <!-- Формируем и выводим алерт -->
      var alertPeriodOld = "'" + deliveryDaysFromValue$k + "-" + deliveryDaysToValue$k + "'";
      var alertPeriodNew = "'" + deliveryDaysFromValue$k + '-' + (deliveryDaysFromValue$k + 2) + "'";
      jQuery('.yandexmarket-alert-delivery-old').text(alertPeriodOld);
      jQuery('.yandexmarket-alert-delivery-new').text(alertPeriodNew);
      jQuery('#yandexmarketDeliveryAlert').removeClass("hidden");
    } else {
      jQuery('#yandexmarketDeliveryAlert').addClass("hidden");
    }

    jQuery('#jform_params_delivery_days_from_$k').val(deliveryDaysFromValue$k);
  });

  jQuery('#jform_params_yandexmarket-delivery-days-to-$k').change(function() {
    var deliveryDaysToValue$k = parseInt(jQuery(this).val());
    var deliveryDaysFromValue$k = parseInt(jQuery('#jform_params_yandexmarket-delivery-days-from-$k').val());

    <!-- Если интервал доставки больше двух, то его надо уменьшить и вывести предупреждение -->
    if (deliveryDaysFromValue$k < (deliveryDaysToValue$k - 2)) {
      jQuery(this).val(deliveryDaysFromValue$k + 2);
      jQuery('#jform_params_delivery_days_to_$k').val(deliveryDaysFromValue$k + 2);
      <!-- Формируем и выводим алерт -->
      var alertPeriodOld = "'" + deliveryDaysFromValue$k + '-' + deliveryDaysToValue$k + "'";
      var alertPeriodNew = "'" + deliveryDaysFromValue$k + '-' + (deliveryDaysFromValue$k + 2) + "'";
      jQuery('.yandexmarket-alert-delivery-old').text(alertPeriodOld);
      jQuery('.yandexmarket-alert-delivery-new').text(alertPeriodNew);
      jQuery('#yandexmarketDeliveryAlert').removeClass("hidden");
    } else {
      jQuery('#yandexmarketDeliveryAlert').addClass("hidden");
      jQuery('#jform_params_delivery_days_to_$k').val(deliveryDaysToValue$k);
    }
  });

  jQuery('#jform_params_yandexmarket-delivery-order-before-$k').change(function() {
    var deliveryOrderBeforeValue$k = jQuery(this).val();
    jQuery('#jform_params_delivery_order_before_$k').val(deliveryOrderBeforeValue$k);
  });
});
HTML;

            if (!empty($this->dataConfig->get("delivery-cost-" . $k))) {
                $deliveryCost = $this->dataConfig->get("delivery-cost-" . $k);
            } else {
                $deliveryCost = "0";
            }

            $alertDeliveryCost = JText::_("COM_YANDEXMARKET_DELIVERIES") . ' -> ' . JText::_("COM_YANDEXMARKET_DELIVERY_COST");
            $html .= <<<HTML
            <tr>
              <td>
                <div id="yandexmarket-delivery-cost-$k">
                  <label id="jform_params_yandexmarket-delivery-cost-$k-lbl" class="hidden">
                    $alertDeliveryCost
                  </label>
                  <input
                    class="form-control required"
                    name="jform[params][yandexmarket-delivery-cost-$k]"
                    id="jform_params_yandexmarket-delivery-cost-$k"
                    type="number"
                    aria-required="true"
                    step="0.01"
                    value="$deliveryCost"
                  />
                </div>
              </td>
HTML;

            if (!empty($this->dataConfig->get("delivery-days-from-" . $k))) {
                $deliveryDaysFrom = $this->dataConfig->get("delivery-days-from-" . $k);
            } else {
                $deliveryDaysFrom = "0";
            }

            if (!empty($this->dataConfig->get("delivery-days-to-" . $k))) {
                $deliveryDaysTo = $this->dataConfig->get("delivery-days-to-" . $k);
            } else {
                $deliveryDaysTo = "0";
            }

            $alertDeliveryPeriod = JText::_("COM_YANDEXMARKET_DELIVERIES") . ' -> ' . JText::_("COM_YANDEXMARKET_DELIVERY_PERIOD");

            $html .= <<<HTML
              <td>
                <div>
                  <label id="jform_params_yandexmarket-delivery-days-from-$k-lbl" class="hidden">
                    $alertDeliveryPeriod
                  </label>
                  <input
                    class="form-control required"
                    name="jform[params][yandexmarket-delivery-days-from-$k]"
                    id="jform_params_yandexmarket-delivery-days-from-$k"
                    style="width: 30px"
                    type="number"
                    aria-required="true"
                    step="1"
                    value="$deliveryDaysFrom"
                  />
                  -
                  <label id="jform_params_yandexmarket-delivery-days-to-$k-lbl" class="hidden">
                    $alertDeliveryPeriod
                  </label>
                  <input
                    class="form-control required"
                    name="jform[params][yandexmarket-delivery-days-to-$k]"
                    id="jform_params_yandexmarket-delivery-days-to-$k"
                    style="width: 30px"
                    type="number"
                    aria-required="true"
                    step="1"
                    value="$deliveryDaysTo"
                  />
                </div>
              </td>
HTML;

            if (!empty($this->dataConfig->get("delivery-order-before-" . $k))) {
                $deliveryOrderBefore = $this->dataConfig->get("delivery-order-before-" . $k);
            } else {
                $deliveryOrderBefore = "0";
            }

            $alertDeliveryOrderBefore = JText::_("COM_YANDEXMARKET_DELIVERIES") . ' -> ' . JText::_("COM_YANDEXMARKET_DELIVERY_TIME");

            $html .= <<<HTML
              <td>
                <div id="yandexmarket-delivery-order-before-$k">
                  до
                  <label id="jform_params_yandexmarket-delivery-order-before-$k-lbl" class="hidden">$alertDeliveryOrderBefore</label>
                  <input
                    class="form-control required"
                    name="jform[params][yandexmarket-delivery-order-before-$k]"
                    id="jform_params_yandexmarket-delivery-order-before-$k"
                    style="width: 30px"
                    type="number"
                    min="0" max="24" step="1"
                    aria-required="true"
                    value="$deliveryOrderBefore"
                  />
                  : 00
                </div>
              </td>
            </tr>
HTML;
        }

        $html .= '</tbody>
                </table>
            </div>';

        JFactory::getDocument()->addScriptDeclaration($js);

        return $html;
    }
} //JFormFieldDeliveries
