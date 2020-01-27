<?php
/**
 * @package   Yandex.Market for HikaShop
 * @subpackage   com_yandexmarket
 * @author   Igor Inkovskiy
 * @copyright   Copyright (C) 2017 Igor Inkovskiy. All rights reserved.
 * @contact   https://shop.igor-i.ru, igor-i-shop@ya.ru
 * @license   Beerware
 */

defined('_JEXEC') or die;

class YandexMarketControllerOffers extends JControllerLegacy {
    /**
     * YandexMarketControllerOffers constructor.
     *
     * @param array $config
     * @since 0.1
     */
    public function __construct($config = array()) {
        parent::__construct($config);
        if(!isset($this->input)) {
            $this->input = JFactory::getApplication()->input;
        }
    }

    /**
     * @param $action
     *
     * @return bool
     * @since 0.1
     */
    protected function authoriseUser($action) {
        if (!JFactory::getUser()->authorise('core.' . strtolower($action), 'com_yandexmarket')) {
            // User is not authorised
            JError::raiseWarning(403, JText::_('JLIB_APPLICATION_ERROR_' . strtoupper($action) . '_NOT_PERMITTED'));
            return false;
        }

        return true;
    }

    /**
     * Удаление настроек товарных предложений для категории
     *
     * @return bool
     * @since 0.1
     */
    public function delete() {
        JSession::checkToken('request') or jexit(JText::_('JINVALID_TOKEN'));

        $categoryId = (int)$this->input->get('category_id');
        $this->setRedirect('index.php?option=com_yandexmarket&view=categories');

        // Nothing to delete
        if (empty($categoryId)) {
            $this->setMessage(JText::_('COM_YANDEXMARKET_ERROR_UNABLE_TO_DELETE_OFFERS'), 'error');
            return false;
        }

        // Authorize the user
        if (!$this->authoriseUser('delete')) {
            $this->setMessage(JText::_('COM_YANDEXMARKET_ERROR_UNABLE_TO_DELETE_OFFERS'), 'error');
            return false;
        }

        // Если в таблице __yandexmarket_categories нет записи с таким идентификатором, то ругаемся
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from('#__yandexmarket_categories')
            ->where('category_id = ' . $db->quote($categoryId));
        $count = (int)$db->setQuery($query)->loadResult();
        if ($count === 0) {
            $this->setMessage(JText::_('COM_YANDEXMARKET_ERROR_UNABLE_TO_DELETE_OFFERS'), 'error');
            return false;
        }

        // Удаление настроек
        $query = $db->getQuery(true)
            ->select('category_myname')
            ->from('#__yandexmarket_categories')
            ->where('category_id = ' . $db->quote($categoryId));
        $myname = (int)$db->setQuery($query)->loadResult();

        //если есть своё наименование категории, то удаляем только params
        //если своего наименования категории нет, то можно удалить всю запись
        if (!empty($myname)) {
            $query = $db->getQuery(true)
                ->update('#__yandexmarket_categories')
                ->set('params = ' . $db->quote(''))
                ->where('category_id = ' . $db->quote($categoryId));
        } else {
            $query = $db->getQuery(true)
                ->delete('#__yandexmarket_categories')
                ->where('category_id = ' . $db->quote($categoryId));
        }
        $db->setQuery($query);

        if(!$db->execute()) {
            $this->setMessage(JText::_('COM_YANDEXMARKET_ERROR_UNABLE_TO_DELETE_OFFERS'), 'error');
            return false;
        }

        $this->setMessage(JText::_('COM_YANDEXMARKET_DELETE_OFFERS_COMPLETE'));
            return true;
        }

        /**
         * Создание и редактирование настроек товарных предложений для категории
         *
         * @return bool
         * @since 0.1
         */
        public function create() {
            JSession::checkToken('request') or jexit(JText::_('JINVALID_TOKEN'));

            $categoryId = (int)$this->input->get('category_id');
            $this->setRedirect('index.php?option=com_yandexmarket&view=category&layout=edit&category_id=' . $categoryId);

            if (empty($categoryId)) {
                $this->setMessage(JText::_('COM_YANDEXMARKET_ERROR_UNABLE_TO_CREATE_OFFERS'), 'error');
                return false;
            }

            // Authorize the user
            if (!$this->authoriseUser('create')) {
                $this->setMessage(JText::_('COM_YANDEXMARKET_ERROR_UNABLE_TO_CREATE_OFFERS'), 'error');
                return false;
            }

            return true;
        }
} //YandexMarketControllerOffers
