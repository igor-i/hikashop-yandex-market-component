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

use Joomla\Utilities\ArrayHelper;

// Подключаем библиотеку таблиц Joomla.
jimport('joomla.database.table');

/**
 * Класс таблицы __yandexmarket_categories.
 * @since 0.1
 */
class YandexMarketTableCategory extends JTable {
    public $category_id = null;
//    public $category_parent_id = null;
    public $category_myname = null;
    public $menuitem = null;
    public $published = 1; //JPUBLISHED's value is 1
    public $params = null;

    /**
     * Конструктор.
     *
     * @param   JDatabase  &$db  Коннектор объекта базы данных.
     * @since 0.1
     */
    public function __construct(&$db) {
        parent::__construct('#__yandexmarket_categories', 'category_id', $db);
        //так как у нас первичный ключ не автоинкрементный, то нужно убрать соответствующий флаг
        $this->_autoincrement = false;
    }

    /**
     * Переопределяем bind метод JTable.
     *
     * @param   array  $array   Массив значений.
     * @param   array  $ignore  Массив значений, которые должны быть игнорированы.
     * @return  boolean  True если все прошло успешно, в противном случае false.
     * @since 0.1
     */
    public function bind($array, $ignore = array()) {
        if (isset($array['params']) && is_array($array['params'])) {
            // Конвертируем поле параметров в JSON строку.
            $parameter = new JRegistry;
            $parameter->loadArray($array['params']);
            $array['params'] = (string) $parameter;
        }

        return parent::bind($array, $ignore);
    }

    /**
     * Overriden JTable::store to set modified data and user id.
     *
     * @param       boolean True to update fields even if they are null.
     * @return      boolean True on success.
     * @since       0.1
     */
    public function store($updateNulls = false) {
        $db   = JFactory::getDbo();
        // Store the category data
        $result = parent::store($updateNulls);

        return $result;
    }

    /**
     * Method to set the publishing state for a row or list of rows in the database
     * table.
     *
     * @param       mixed   An optional array of primary key values to update.  If not
     *                      set the instance property value is used.
     * @param       integer The publishing state. eg. [0 = unpublished, 1 = published]
     * @param       integer The user id of the user performing the operation.
     * @return      boolean True on success.
     * @since       0.1
     */
    public function publish($pks = null, $state = 1, $userId = 0) {
        // Initialize variables.
        $k = $this->_tbl_key;

        // Очищаем входные параметры.
        ArrayHelper::toInteger($pks);
        $state  = (int) $state;

        // Если первичные ключи не установлены, то проверяем ключ в текущем объекте.
        if (empty($pks)) {
            if ($this->$k) {
                $pks = array($this->$k);
            } else {
                // Nothing to set publishing state on, return false.
                throw new RuntimeException(JText::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'), 500);
            }
        }

        // Устанавливаем состояние для всех первичных ключей.
        foreach ($pks as $pk) {
            // Загружаем сообщение.
            if (!$this->load($pk)) {

                // Если отключаем категорию и такой записи нет в таблице, то надо её создать
                if ($state===0) {
                    $db   = JFactory::getDbo();

                    // Достаём информацию по категории
                    $query = $db->getQuery(true)
                        ->select('category_id, category_parent_id')
                        ->from('#__hikashop_category')
                        ->where('category_id = ' . $db->quote($pk));
                    $data = $db->setQuery($query)->loadAssoc();

                    // Записываем в таблицу
                    $query = $db->getQuery(true)
                        ->clear()
                        ->insert('#__yandexmarket_categories')
                        ->set('category_id = ' . (int)$data["category_id"])
//                        ->set('category_parent_id = ' . (int)$data["category_parent_id"])
                        ->set('published = 0');
                    $db->setQuery($query)->execute();

                } else
                    throw new RuntimeException(JText::_('COM_YANDEXMARKET_TABLE_ERROR_RECORD_LOAD'), 500);

            }
            $this->published = $state;
            // Сохраняем сообщение.
            if (!$this->store()) {
                throw new RuntimeException(JText::_('COM_YANDEXMARKET_TABLE_ERROR_RECORD_STORE'), 500);
            }
        }

        return true;
    }

    /**
     * Переопределяем load метод JTable.
     *
     * @param   int      $pk     Первичный ключ.
     * @param   boolean  $reset  Сбрасывать данные перед загрузкой или нет.
     * @return  boolean  True если все прошло успешно, в противном случае false.
     * @since 0.1
     */
    public function load($pk = null, $reset = true) {
        if (parent::load($pk, $reset)) {

            // Конвертируем поле параметров в регистр.
            $params = new JRegistry;
            $params->loadString($this->params);
            $this->params = $params;

            return true;
        } else {

            return false;
        }
    }
} //YandexMarketTableCategory
