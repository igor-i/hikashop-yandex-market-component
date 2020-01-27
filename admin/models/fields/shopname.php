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

class JFormFieldShopname extends JFormField {
    public $type = 'Shopname';
    private $dataConfig;

    public function getInput() {
        $dataConfig = $this->form->getData();
        $this->dataConfig = new JRegistry($dataConfig->get('params'));

        // Достаём название магазина из конфига компонента или из глобального конфига Joomla
        if (!empty($this->dataConfig->get('shopname'))) {
            $siteName = $this->dataConfig->get('shopname');
        } else {
            $app = JFactory::getApplication();
            $siteName = $app->get('sitename');
        }

        $html = "<input
                    id='jform_params_shopname'
                    name='jform[params][shopname]'
                    class='required'
                    aria-required='true'
                    type='text'
                    maxlength='20'
                    value='$siteName'>";

        return $html;
    }
} //JFormFieldShopname
