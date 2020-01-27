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

// Устанавливаем обработку ошибок в режим использования Exception.
JError::$legacy = false;

// Подключаем библиотеку контроллера Joomla.
jimport('joomla.application.component.controller');

// Регистрируем все файлы в папке /helpers, а также в подпапках как классы с именем Mylib<имяфайла>
JLoader::discover('MyLib', JPATH_COMPONENT_ADMINISTRATOR . '/libraries',true,true);
JLoader::register('Yml', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/html/yml.php');

// Получаем экземпляр контроллера с префиксом Yandex.Market.
$controller = JControllerLegacy::getInstance('YandexMarket');

// Исполняем задачу task из Запроса.
$input = JFactory::getApplication()->input;
$controller->execute($input->getCmd('task', 'display'));

// Перенаправляем, если перенаправление установлено в контроллере.
$controller->redirect();
