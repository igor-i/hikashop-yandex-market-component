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

class JFormFieldOffers extends JFormField {
    public $type = 'Offers';

    public function getInput() {
//        $params = JComponentHelper::getParams('com_yandexmarket');
//        $connector = $params->get('connector');
        require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_yandexmarket' . DS . 'connectors' . DS . 'mainconnector.php');
        require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_yandexmarket' . DS . 'connectors' . DS . 'hikashop.php');

        $hikashop = new igoriHikashopConnector($data = null);

        //если HikaShop установлен, то
        if (!empty($hikashop->enableShop)) {
            $data = $hikashop->loadCustomParams($this);
            if (is_array($data) && count($data)) {
                $html = '';
                foreach ($data as $k=>$v) {
                    $html .= '
                        <div class="control-group">
                            <div class="control-label">
                                <label
                                    id="jform_hendler_offers_' . $k . '-lbl"
                                    class="hasPopover"
                                    for="jform_hendler_offers_' . $k . '"
                                    title=""
                                    data-content="' . $v['desc'] . '"
                                    data-original-title="' . $v['label'] . '">
                                    ' . $v['label'] . '
                                </label>
                            </div>
                            <div class="controls">
                                <div id="jform_hendler_offers_' . $k . '">
                                    ' . $v['input'] . '
                                </div>
                            </div>
                        </div>';
                }

                return $html;
            }
        }

        return JText::_('COM_YANDEXMARKET_HIKASHOP_NOT_INSTALLED');
    }
} //JFormFieldOffers
