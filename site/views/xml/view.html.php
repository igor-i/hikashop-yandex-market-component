<?php
/**
 * @package   Yandex.Market for HikaShop
 * @subpackage   com_yandexmarket
 * @author   Igor Inkovskiy
 * @copyright   Copyright (C) 2017 Igor Inkovskiy. All rights reserved.
 * @contact   https://shop.igor-i.ru, igor-i-shop@ya.ru
 * @license   Beerware
 */
// Запрет прямого доступа.
defined('_JEXEC') or die('Restricted access');

// Подключаем библиотеку представления Joomla.
jimport('joomla.application.component.view');

class YandexMarketViewXml extends JViewLegacy {
    protected $item;

    public function display($tpl = null) {
        // Получаем данные из модели.
        $model = $this->getModel();
        $this->item = $model->getItem();

        // Отображаем представление.
        parent::display($tpl);

        // Force to show a clean XML without other content
        jexit();
    }
} //YandexMarketViewXml
