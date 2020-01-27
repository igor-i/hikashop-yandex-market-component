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

// Подключаем библиотеку modelitem Joomla.
jimport('joomla.application.component.modelitem');

/**
 * Модель сообщения компонента Yandex.Market.
 * @since 0.1
 */
class YandexMarketModelXml extends JModelItem {
    /**
     * Получаем сообщение.
     *
     * @return  string  Сообщение, которое отображается пользователю.
     * @since 0.1
     */
    public function getItem() {
        if (!isset($this->_item)) {
            $data = JFactory::getApplication();
            //передаём на отображение контент из файла
            $folder = JPATH_COMPONENT_SITE . DS . 'files';
            $filename = 'yml' . $data->input->get('ymlid') . '.xml';
            $this->_item = file_get_contents($folder . DS . $filename);
        }

        return $this->_item;
    }
} //YandexMarketModelXml
