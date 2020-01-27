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

// Подключаем библиотеку modellist Joomla.
jimport('joomla.application.component.modellist');

class YandexMarketModelCategories extends JModelList {
    /**
     * Конструктор.
     *
     * @param   array  $config  Массив с конфигурационными параметрами.
     * @since 0.1
     */
    public function __construct($config = array()) {
        // Добавляем валидные поля для фильтров и сортировки.
        if (empty($config['filter_fields']))  {
            $config['filter_fields'] = array(
                'id', 'hika.category_id',
                'parent_id', 'hika.category_parent_id',
                'name', 'hika.category_name',
                'myname', 'cat.category_myname',
                'menuitem', 'cat.category_menuitem',
                'menuitem_title', 'menu.title',
                'published', 'cat.published',
            );
        }

        parent::__construct($config);
    }

    /**
     * @return array|mixed
     * @since 0.1
     */
    public function getItems() {
        $items = parent::getItems();
        return $items;
    }

    /**
     * Метод для построения SQL запроса для загрузки списка данных.
     *
     * @return  string  SQL запрос.
     * @since 0.1
     */
    protected function getListQuery() {
        // Создаем новый query объект.
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        // На всякий случай очищаем буфер конструктора запросов (я параноик)
        $query->clear();

        // Выбираем из таблицы категорий HikaShop (__hikashop_categories)
        $query->select('hika.category_id AS id, hika.category_name AS name, hika.category_parent_id AS parent_id');
        $query->from('#__hikashop_category AS hika');
        $query->where('hika.category_type = ' . $db->quote('product'));
//        $query->where('hika.category_published = 1');

        // Присоединяем наименование родительской категории
        $query->select('par.category_name AS parent_name');
        $query->leftJoin('#__hikashop_category AS par ON par.category_id = hika.category_parent_id');

        // Присоединяем таблицу категорий компонента
        $query->select('cat.category_myname AS myname, cat.category_menuitem AS menuitem, cat.published AS published, cat.params AS params');
        $query->leftJoin('#__yandexmarket_categories AS cat ON cat.category_id = hika.category_id');

        // Присоединяем таблицу меню joomla
        $query->select('menu.title AS menuitem_title');
        $query->leftJoin('#__menu AS menu ON cat.category_menuitem = menu.id');

        //Сортировка (закомментарена, потому что мешает сортировке ниже)
//        $query->order(array('hika.category_parent_id', 'hika.category_id'));

        // Фильтруем по состоянию.
        $published = $this->getState('filter.published', '');
        switch ($published) {
            case '0':
                $query->where('cat.published = 0');
                break;
            case '1':
                $query->where('(cat.published = 1 OR cat.published IS NULL)');
                break;
            default:
                $query->where('(cat.published = 0 OR cat.published = 1 OR cat.published IS NULL)');
        }

        // Фильтруем по поиску в тексте сообщения.
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = $db->quote('%' . $db->escape($search, true) . '%', false);
            $query->where('(
                hika.category_name LIKE ' . $search . ' OR
                cat.category_myname LIKE ' . $search . ' OR
                menu.title LIKE ' . $search . ')');
        }

        // добавляем сортировку
        $orderCol = $this->state->get('list.ordering', 'hika.category_parent_id');
        $orderDir = $this->state->get('list.direction', 'ASC');
        $query->order($db->escape($orderCol . ' ' . $orderDir));

        return $query;
    }

    /**
     * Метод для авто-заполнения состояния модели.
     *
     * @param   string  $ordering   Поле сортировки.
     * @param   string  $direction  Направление сортировки (asc|desc).
     * @return  void
     * @since 0.1
     */
    protected function populateState($ordering = 'hika.category_parent_id', $direction = 'ASC') {
        // Получаем и устанавливаем значение фильтра состояния.
        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published');
        $this->setState('filter.published', $published);

        // Получаем и устанавливаем значение фильтра поиска по тексту сообщения.
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        parent::populateState($ordering, $direction);
    }

    /**
     * Метод для получения store id, которое основывается на состоянии модели (для хранения фильтров в кэше).
     *
     * @param   string  $id  Идентификационная строка для генерации store id.
     * @return  string  Store id.
     * @since 0.1
     */
    protected function getStoreId($id = '') {
        // Компилируем store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');

        return parent::getStoreId($id);
    }
} //YandexMarketModelCategories
