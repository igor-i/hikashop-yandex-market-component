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

// Подключаем библиотеку контроллера Joomla.
jimport('joomla.application.component.controller');

/**
 * Общий контроллер компонента Yandex.Market.
 * @package     ${NAMESPACE}
 *
 * @since 0.1
 */
class YandexMarketController extends JControllerLegacy
{
    /**
     * Задача по отображению.
     *
     * @param   boolean  $cachable   Если true, то представление будет закешировано.
     * @param   array    $urlparams  Массив безопасных url-параметров и их валидных типов переменных.
     *
     * @return  void
     * @since 0.1
     */
    public function display($cachable = false, $urlparams = array())
    {
        // Устанавливаем представление по умолчанию, если оно не было установлено.
        $input = JFactory::getApplication()->input;
        $input->set('view', $input->getCmd('view', 'Ymls'));
        parent::display($cachable);
    }
} //YandexMarketController
