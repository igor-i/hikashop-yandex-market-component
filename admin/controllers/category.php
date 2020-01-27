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

// Подключаем библиотеку controllerform Joomla.
jimport('joomla.application.component.controllerform');

/**
 * Category контроллер.
 * @since 0.1
 */
class YandexMarketControllerCategory extends JControllerForm {

    /**
     * Method override to check if the user can edit an existing record.
     *
     * @param    array    An array of input data.
     * @param    string   The name of the key for the primary key.
     * @return   boolean
     * @since 0.1
     */
    protected function _allowEdit($data = array(), $key = 'id') {
        // Initialise variables.
        $recordId = (int) isset($data[$key]) ? $data[$key] : 0;

        // Assets are being tracked, so no need to look into the category.
        return \JFactory::getUser()->authorise('core.edit', 'com_yandexmarket.category.' . $recordId);
    }
} //YandexMarketControllerCategory
