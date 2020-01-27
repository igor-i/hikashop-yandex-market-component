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
defined('_JEXEC') or die;

// Подключаем библиотеку представления Joomla.
jimport('joomla.application.component.view');

class YandexMarketViewCategory_MenuItem extends MyLibBase {
    protected $item, $form, $user;

    /**
     * @param   string  $tpl  Имя файла шаблона.
     * @return  void
     * @throws  Exception
     * @since 0.4.4
     */
    public function display($tpl = null) {

        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $this->user = JFactory::getUser();

        parent::display($tpl);
        JFactory::getApplication()->close();
    }
} //YandexMarketViewCategory_MenuItem
