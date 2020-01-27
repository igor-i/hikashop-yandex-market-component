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

// Подключаем библиотеку для работы с файлами
jimport('joomla.filesystem.file');

/**
 * Контроллер сообщения компонента Yandex.Market.
 * @since 0.1
 */
class YandexMarketController extends JControllerLegacy {
    protected $default_view = 'xml';

    public $content;

    /**
     * @param bool  $cachable
     * @param array $urlparams
     * @return bool
     * @since 0.1
     */
    public function display($cachable = false, $urlparams = array()) {
        $params = JComponentHelper::getParams('com_yandexmarket');
        $password = $params->get('password');

        //проверяем чтобы был id yml в параметрах
        if (empty($this->input->get('ymlid'))) {
            JError::raiseWarning(404, JText::_('JERROR_PAGE_NOT_FOUND'));
            return false;
        }

        //проверяем пароль
        if (empty($this->input->get('pass')) || $this->input->get('pass')!=$password) {
            JError::raiseWarning(403, JText::_('JGLOBAL_AUTH_ACCESS_DENIED'));
            return false;
        }

        // Проверяем чтобы yml был опубликован
        //TODO достать published из базы

        $folder = JPATH_COMPONENT_SITE . DS . 'files';
        $filename = 'yml' . $this->input->get('ymlid') . '.xml';

        //проверяем наличие файла
        if (!JFile::exists($folder . DS . $filename)) {
            JError::raiseWarning(404, JText::_('JERROR_PAGE_NOT_FOUND'));
            return false;
        }

        parent::display($cachable);
    }
} // YandexMarketController
