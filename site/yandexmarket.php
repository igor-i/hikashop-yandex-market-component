<?php
/**
 * @package   Yandex.Market for HikaShop
 * @subpackage   com_yandexmarket
 * @author   Igor Inkovskiy
 * @copyright   Copyright (C) 2017 Igor Inkovskiy. All rights reserved.
 * @contact   https://shop.igor-i.ru, igor-i-shop@ya.ru
 * @license   Beerware
 */
// Запрет прямого доступа
defined('_JEXEC') or die('Restricted access');

// Подключаем библиотеку контроллера Joomla
jimport('joomla.application.component.controller');

// Получаем экземпляр контроллера с префиксом YandexMarket.
$controller = JControllerLegacy::getInstance('YandexMarket');

// Исполняем задачу task из Запроса.
$input = JFactory::getApplication()->input;
$controller->execute($input->getCmd('task', 'display'));

// Перенаправляем, если перенаправление установлено в контроллере.
$controller->redirect();
