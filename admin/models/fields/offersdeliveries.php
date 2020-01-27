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

class JFormFieldOffersdeliveries extends JFormField {
    public $type = 'Offersdeliveries';
    private $dataConfig;

    public function getInput() {
        $dataConfig = $this->form->getData();
        $this->dataConfig = new JRegistry($dataConfig->get('params'));

        $alert = false;
        $alertPeriodOld = '';
        $alertPeriodNew = '';

        for ($k=1; $k<=5; $k++) {
            $from = (int)$this->dataConfig->get("offers-delivery-days-from-" . $k);
            $to = (int)$this->dataConfig->get("offers-delivery-days-to-" . $k);
            if ($from < ($to - 2)) {
                $alert = true;
                $alertPeriodOld = $from . '-' . $to;
                $alertPeriodNew = $from . '-' . ($from + 2);
                break;
            }
        }

        $html = '<div id="yandexmarketOffersDeliveryAlert" class="alert alert-no-items';
        if (!$alert) {
            $html .= ' hidden';
        }
        $html .= '"><span class="icon-warning" aria-hidden="true"></span>' . JText::sprintf('COM_YANDEXMARKET_OFFERS_DELIVERY_ALERT', $alertPeriodOld, $alertPeriodNew) . '</div>';

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
  jQuery('#yandexmarket-offers-delivery-cost-$k > input').change(function() {
    var offersDeliveryCostValue$k = jQuery(this).val();
    jQuery('#jform_params_offers_delivery_cost_$k').val(offersDeliveryCostValue$k);
  });
  jQuery('#jform_params_yandexmarket-offers-delivery-days-from-$k').change(function() {
    var offersDeliveryDaysFromValue$k = parseInt(jQuery(this).val());
    var offersDeliveryDaysToValue$k = parseInt(jQuery('#jform_params_yandexmarket-offers-delivery-days-to-$k').val());
    <!-- Если интервал доставки больше двух, то его надо уменьшить и вывести предупреждение -->
    if (offersDeliveryDaysFromValue$k < (offersDeliveryDaysToValue$k - 2)) {
      jQuery('#jform_params_yandexmarket-offers-delivery-days-to-$k').val(offersDeliveryDaysFromValue$k + 2);
      jQuery('#jform_params_offers_delivery_days_to_$k').val(offersDeliveryDaysFromValue$k + 2);
      <!-- Формируем и выводим алерт -->
      var alertPeriodOld = "'" + offersDeliveryDaysFromValue$k + "-" + offersDeliveryDaysToValue$k + "'";
      var alertPeriodNew = "'" + offersDeliveryDaysFromValue$k + '-' + (offersDeliveryDaysFromValue$k + 2) + "'";
      jQuery('.yandexmarket-alert-offers-delivery-old').text(alertPeriodOld);
      jQuery('.yandexmarket-alert-offers-delivery-new').text(alertPeriodNew);
      jQuery('#yandexmarketOffersDeliveryAlert').removeClass("hidden");
    } else {
      jQuery('#yandexmarketOffersDeliveryAlert').addClass("hidden");
    }
    jQuery('#jform_params_offers_delivery_days_from_$k').val(offersDeliveryDaysFromValue$k);
  });
  jQuery('#jform_params_yandexmarket-offers-delivery-days-to-$k').change(function() {
    var offersDeliveryDaysToValue$k = parseInt(jQuery(this).val());
    var offersDeliveryDaysFromValue$k = parseInt(jQuery('#jform_params_yandexmarket-offers-delivery-days-from-$k').val());
    <!-- Если интервал доставки больше двух, то его надо уменьшить и вывести предупреждение -->
    if (offersDeliveryDaysFromValue$k < (offersDeliveryDaysToValue$k - 2)) {
      jQuery(this).val(offersDeliveryDaysFromValue$k + 2);
      jQuery('#jform_params_offers_delivery_days_to_$k').val(offersDeliveryDaysFromValue$k + 2);
      <!-- Формируем и выводим алерт -->
      var alertPeriodOld = "'" + offersDeliveryDaysFromValue$k + '-' + offersDeliveryDaysToValue$k + "'";
      var alertPeriodNew = "'" + offersDeliveryDaysFromValue$k + '-' + (offersDeliveryDaysFromValue$k + 2) + "'";
      jQuery('.yandexmarket-alert-offers-delivery-old').text(alertPeriodOld);
      jQuery('.yandexmarket-alert-offers-delivery-new').text(alertPeriodNew);
      jQuery('#yandexmarketOffersDeliveryAlert').removeClass("hidden");
    } else {
      jQuery('#yandexmarketOffersDeliveryAlert').addClass("hidden");
      jQuery('#jform_params_offers_delivery_days_to_$k').val(offersDeliveryDaysToValue$k);
    }
  });
  jQuery('#jform_params_yandexmarket-offers-delivery-order-before-$k').change(function() {
    var offersDeliveryOrderBeforeValue$k = jQuery(this).val();
    jQuery('#jform_params_offers_delivery_order_before_$k').val(offersDeliveryOrderBeforeValue$k);
  });
});
HTML;

            if ((!empty($this->dataConfig->get("offers-delivery-cost-" . $k))) || ($this->dataConfig->get("offers-delivery-cost-" . $k) == 0)) {
                $deliveryCost = $this->dataConfig->get("offers-delivery-cost-" . $k);
            } else {
                $deliveryCost = "";
            }

            $alertDeliveryCost = JText::_("COM_YANDEXMARKET_OFFERS_DELIVERIES") . ' -> ' . JText::_("COM_YANDEXMARKET_DELIVERY_COST");

            $html .= <<<HTML
              <tr>
                <td>
                  <div id="yandexmarket-offers-delivery-cost-$k">
                    <label id="jform_params_yandexmarket-offers-delivery-cost-$k-lbl" class="hidden">
                      $alertDeliveryCost
                    </label>
                    <input
                      class="form-control"
                      name="jform[params][yandexmarket-offers-delivery-cost-$k]"
                      id="jform_params_yandexmarket-offers-delivery-cost-$k"
                      type="number"
                      aria-required="true"
                      step="0.01"
                      value="$deliveryCost"
                    />
                  </div>
                </td>
HTML;

            if ((!empty($this->dataConfig->get("offers-delivery-days-from-" . $k))) || ($this->dataConfig->get("offers-delivery-days-from-" . $k) == 0)) {
                $deliveryDaysFrom = $this->dataConfig->get("offers-delivery-days-from-" . $k);
            } else {
                $deliveryDaysFrom = "";
            }

            if ((!empty($this->dataConfig->get("offers-delivery-days-to-" . $k))) || ($this->dataConfig->get("offers-delivery-days-to-" . $k) == 0)) {
                $deliveryDaysTo = $this->dataConfig->get("offers-delivery-days-to-" . $k);
            } else {
                $deliveryDaysTo = "";
            }

            $alertDeliveryPeriod = JText::_("COM_YANDEXMARKET_OFFERS_DELIVERIES") . ' -> ' . JText::_("COM_YANDEXMARKET_DELIVERY_PERIOD");

            $html .= <<<HTML
               <td>
                 <div>
                   <label id="jform_params_yandexmarket-offers-delivery-days-from-$k-lbl" class="hidden">$alertDeliveryPeriod</label>
                   <input
                     class="form-control"
                     name="jform[params][yandexmarket-offers-delivery-days-from-$k]"
                     id="jform_params_yandexmarket-offers-delivery-days-from-$k"
                     style="width: 30px"
                     type="number"
                     aria-required="true"
                     step="1"
                     value="$deliveryDaysFrom"
                   />
                   -
                   <label id="jform_params_yandexmarket-offers-delivery-days-to-$k-lbl" class="hidden">$alertDeliveryPeriod</label>
                   <input
                     class="form-control"
                     name="jform[params][yandexmarket-offers-delivery-days-to-$k]"
                     id="jform_params_yandexmarket-offers-delivery-days-to-$k"
                     style="width: 30px"
                     type="number"
                     aria-required="true"
                     step="1"
                     value="$deliveryDaysTo"
                   />
                 </div>
               </td>
HTML;

            if ((!empty($this->dataConfig->get("offers-delivery-order-before-" . $k))) || ($this->dataConfig->get("offers-delivery-order-before-" . $k) == 0)) {
                $deliveryOrderBefore = $this->dataConfig->get("offers-delivery-order-before-" . $k);
            } else {
                $deliveryOrderBefore = "";
            }

            $alertDeliveryOrderBefore = JText::_("COM_YANDEXMARKET_OFFERS_DELIVERIES") . ' -> ' . JText::_("COM_YANDEXMARKET_DELIVERY_TIME");

            $html .= <<<HTML
              <td>
                <div id="yandexmarket-offers-delivery-order-before-$k">
                  до
                  <label id="jform_params_yandexmarket-offers-delivery-order-before-$k-lbl" class="hidden">$alertDeliveryOrderBefore</label>
                  <input
                    class="form-control"
                    name="jform[params][yandexmarket-offers-delivery-order-before-$k]"
                    id="jform_params_yandexmarket-offers-delivery-order-before-$k"
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
} //JFormFieldOffersdeliveries
