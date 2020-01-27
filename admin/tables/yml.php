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
 * Класс таблицы __yandexmarket_ymls.
 * @since 0.1
 */
class YandexMarketTableYml extends JTable {
    public $id = null;
    public $name = null;
    public $yml_menuitem = null;
    public $params = null;
    public $created_on = null;
    public $is_default = 0;
    public $published = 1; //JPUBLISHED's value is 1
    public $offers_count = 0;
    public $offers_settings;

    /**
     * Конструктор.
     *
     * @param   JDatabase  &$db  Коннектор объекта базы данных.
     * @since 0.1
     */
    public function __construct(&$db) {
        parent::__construct('#__yandexmarket_ymls', 'id', $db);
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
        $date = JFactory::getDate();

        if (!$this->id) {
            $this->created_on = $date->toSql();
        }

        // Make sure we have only one default yml
        if ((bool)$this->is_default) {
            // Set as not default any other yml
            $query = $db->getQuery(true)
                ->update('#__yandexmarket_ymls')
                ->set('is_default = 0');
            $db->setQuery($query)->execute();
        } else {
            // Check if we have another default yml. If not, force this as default
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from('#__yandexmarket_ymls')
                ->where('is_default = 1')
                ->where('id <> ' . $db->quote($this->id));
            $count = (int)$db->setQuery($query)->loadResult();

            if ($count === 0) {
                // Force as default
                $this->is_default = 1;

                JFactory::getApplication()->enqueueMessage(
                    JText::_('COM_YANDEXMARKET_MSG_YML_FORCED_AS_DEFAULT'),
                    'info'
                );
            }
        }

        // Get the offers
        $offersSettings = $this->offers_settings;
        unset($this->offers_settings);

        if (!empty($offersSettings['include_categories'])) {
            foreach ($offersSettings['include_categories'] as $v) {
                $offers[] = array(
                    'category_or_product_id' => $v,
                    'category_or_product_type' => 'category',
                    'mode' => 'include'
                );
            }
        }
        if (!empty($offersSettings['include_products'])) {
            foreach ($offersSettings['include_products'] as $v) {
                $offers[] = array(
                    'category_or_product_id' => $v,
                    'category_or_product_type' => 'product',
                    'mode' => 'include'
                );
            }
        }
        if (!empty($offersSettings['exclude_categories'])) {
            foreach ($offersSettings['exclude_categories'] as $v) {
                $offers[] = array(
                    'category_or_product_id' => $v,
                    'category_or_product_type' => 'category',
                    'mode' => 'exclude'
                );
            }
        }
        if (!empty($offersSettings['exclude_products'])) {
            foreach ($offersSettings['exclude_products'] as $v) {
                $offers[] = array(
                    'category_or_product_id' => $v,
                    'category_or_product_type' => 'product',
                    'mode' => 'exclude'
                );
            }
        }

        // Store the yml data
        $result = parent::store($updateNulls);

        if ($result) {
            // Remove the current offers
            $this->removeOffers();

            if (!empty($offers)) {
                // Store the offers for this yml
                foreach ($offers as $v) {
                    $query = $db->getQuery(true)
                        ->insert('#__yandexmarket_yml_offers')
                        ->set('yml_id = ' . $db->quote($this->id))
                        ->set('category_or_product_id = ' . $db->quote($v['category_or_product_id']))
                        ->set('category_or_product_type = ' . $db->quote($v['category_or_product_type']))
                        ->set('mode = ' . $db->quote($v['mode']));
                    $db->setQuery($query)->execute();
                }
            }
        }

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
        $userId = (int) $userId;
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
     * Remove all the offers params for the given yml
     * @since 0.1
     */
    public function removeOffers() {
        if (!empty($this->id)) {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true)
                ->delete('#__yandexmarket_yml_offers')
                ->where('yml_id = ' . $db->quote($this->id));
            $db->setQuery($query)->execute();
        }
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
//            $params = new JRegistry;
//            $params->loadString($this->params);
//            $this->params = $params;

            // Подгружаем настройки выборки товарных предложений
            $db = jFactory::getDbo();

            $query = $db->getQuery(true)
                ->select('*')
                ->from('#__yandexmarket_yml_offers')
                ->where('yml_id = ' . $db->quote($this->id));

            $offersRows = $db->setQuery($query)->loadObjectList();

            //{"include_categories":["56"],"attributes":"customfields","related":"0","canonical":"1"}

            if (!empty($offersRows)) {
                foreach ($offersRows as $key=>$row) {
                    if ($row->category_or_product_type==='category' && $row->mode==='include') {
                        $this->offers_settings['include_categories'][] = $row->category_or_product_id;
                    } elseif ($row->category_or_product_type==='product' && $row->mode==='include') {
                        $this->offers_settings['include_products'][] = $row->category_or_product_id;
                    } elseif ($row->category_or_product_type==='category' && $row->mode==='exclude') {
                        $this->offers_settings['exclude_categories'][] = $row->category_or_product_id;
                    } elseif ($row->category_or_product_type==='product' && $row->mode==='exclude') {
                        $this->offers_settings['exclude_products'][] = $row->category_or_product_id;
                    }
                }
            }

            return true;
        } else {

            return false;
        }
    }
} //YandexMarketTableYml
