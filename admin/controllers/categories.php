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

// Подключаем библиотеку controlleradmin Joomla.
jimport('joomla.application.component.controlleradmin');

/**
 * Categories контроллер.
 */
class YandexMarketControllerCategories extends JControllerAdmin {
    /**
     * Прокси метод для getModel.
     *
     * @param   string  $name    Имя класса модели.
     * @param   string  $prefix  Префикс класса модели.
     * @return  object  Объект модели.
     * @since 0.1
     */
    public function getModel($name = 'Category', $prefix = 'YandexMarketModel')
    {
        return parent::getModel($name, $prefix, array('ignore_request' => true));
    }

    /**
     * Сохраняет запись в таблице __yandexmarket_categories,
     * вызывается аджаксом при редактировании Наименования в YML (myname)
     *
     * @since 0.1
     */
    function saveCategoryMyName()
    {
        if (version_compare(JVERSION, '3.0.0', 'ge')) {
            JSession::checkToken() or jexit(json_encode(array('error' => 1, 'msg' => JText::_('JINVALID_TOKEN'))));
            $data   = $this->input->get('jform', array(), 'array');
        } else {
            $data   = JRequest::getVar('jform',	array(), 'request', 'array');
        }

        $app = JFactory::getApplication();
        $db = JFactory::getDbo();

        // Если в базе уже есть запись для этой категории, то её надо обновить, иначе - вставить новую запись
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from('#__yandexmarket_categories')
            ->where('category_id = ' . $db->quote($data["category_id"]));
        $count = (int)$db->setQuery($query)->loadResult();

        if ($count === 0) {
            // Вставляем запись
            $query = $db->getQuery(true)
                ->insert('#__yandexmarket_categories')
                ->set('category_id = ' . $db->quote($data["category_id"]))
//                ->set('category_parent_id = ' . $db->quote($data["category_parent_id"]))
                ->set('category_myname = ' . $db->quote($data["category_myname"]));
        } else {
            // Обновляем запись
            $query = $db->getQuery(true)
                ->update('#__yandexmarket_categories')
//                ->set('category_parent_id = ' . $db->quote($data["category_parent_id"]))
                ->set('category_myname = ' . $db->quote($data["category_myname"]))
                ->where('category_id = ' . $db->quote($data["category_id"]));
        }

        $db->setQuery($query);

        if($db->execute()) {
            echo json_encode(array('error' => 0, 'msg' => 'Ok'));
        } else {
            echo json_encode(array('error' => 1, 'msg' => 'Error store data'));
        }
        $app->close();
    }

    /**
     * Сохраняет запись в таблице __yandexmarket_categories,
     * вызывается аджаксом при редактировании Наименования в YML (myname)
     *
     * @since 0.1
     */
    function saveCategoryMenuItem()
    {
        if (version_compare(JVERSION, '3.0.0', 'ge')) {
            JSession::checkToken() or jexit(json_encode(array('error' => 1, 'msg' => JText::_('JINVALID_TOKEN'))));
            $data   = $this->input->get('jform', array(), 'array');
        } else {
            $data   = JRequest::getVar('jform',	array(), 'request', 'array');
        }

        $app = JFactory::getApplication();
        $db = JFactory::getDbo();

        // Если в базе уже есть запись для этой категории, то её надо обновить, иначе - вставить новую запись
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from('#__yandexmarket_categories')
            ->where('category_id = ' . $db->quote($data["category_id"]));
        $count = (int)$db->setQuery($query)->loadResult();

        if ($count === 0) {
            // Вставляем запись
            $query = $db->getQuery(true)
                ->insert('#__yandexmarket_categories')
                ->set('category_id = ' . $db->quote($data["category_id"]))
//                ->set('category_parent_id = ' . $db->quote($data["category_parent_id"]))
                ->set('category_menuitem = ' . $db->quote($data["category_menuitem"]));
        } else {
            // Обновляем запись
            $query = $db->getQuery(true)
                ->update('#__yandexmarket_categories')
//                ->set('category_parent_id = ' . $db->quote($data["category_parent_id"]))
                ->set('category_menuitem = ' . $db->quote($data["category_menuitem"]))
                ->where('category_id = ' . $db->quote($data["category_id"]));
        }

        $db->setQuery($query);

        if($db->execute()) {
            echo json_encode(array('error' => 0, 'msg' => 'Ok'));
        } else {
            echo json_encode(array('error' => 1, 'msg' => 'Error store data'));
        }
        $app->close();
    }
} //YandexMarketControllerCategories
