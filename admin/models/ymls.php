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

/**
 * Модель списка сообщений компонента Yandex.Market.
 * @since 0.1
 */
class YandexMarketModelYmls extends JModelList {
    private $folder = 'components/com_yandexmarket/files';

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
                'id', 'yml.id',
                'name', 'yml.name',
                'menuitem', 'yml.yml_menuitem',
                'menuitem_title', 'menu.title',
                'published', 'yml.published',
            );
        }

        parent::__construct($config);
    }

    /**
     * Возвращает путь к директории с файлами YML
     * @return string
     * @since 0.1
     */
    public function getFolder() {
        return JPATH_SITE . '/' . $this->folder;
    }

    /**
     * @return array|mixed
     * @since 0.1
     */
    public function getItems() {
        $items = parent::getItems();

        if(is_array($items) && count($items)) {
            foreach($items as $i => $item) {
                $ext = '.xml';
                $filePath = JPATH_ROOT . DS . $this->folder. DS . 'yml' . $item->id . $ext;
                $fileName = 'yml' . $item->id . $ext;
                $url = JUri::root() . $this->folder . '/' . $fileName;

                if(!file_exists($filePath)) {
                    $url = '';
                }

                $items[$i]->file = array(
                    'name' => $fileName,
                    'path' => $filePath,
                    'url'  => $url
                );
            }
        } else {
            $items = array();
        }

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

        // Выбираем всё из таблицы __yandexmarket_ymls
        $query->select('yml.*');
        $query->from('#__yandexmarket_ymls AS yml');

        // Присоединяем таблицу меню joomla
        $query->select('menu.title AS menuitem_title');
        $query->leftJoin('#__menu AS menu ON yml.yml_menuitem = menu.id');

        // Filter by publishing state
        $published = $this->getState('filter.published', '');
        if ($published != '*') {
            if ($published != '') {
                $query->where('yml.published = ' . $db->quote($published));
            } else {
                $query->where('yml.published >= 0');
            }
        } else {
            $query->where('(yml.published = 0 OR yml.published = 1)');
        }

        // Filter by default state
        $default = $this->getState('filter.default');
        if ($default != '') {
            $query->where('yml.is_default = ' . (int)$default);
        }

        // Фильтруем по поиску в тексте сообщения.
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = $db->quote('%' . $db->escape($search, true) . '%', false);
            $query->where('(
            yml.name LIKE ' . $search . ' OR
            menu.title LIKE ' . $search . ')');
        }

        // добавляем сортировку
        $orderCol  = $this->state->get('list.ordering', 'yml.id');
        $orderDir = $this->state->get('list.direction', 'DESC');
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
    protected function populateState($ordering = 'yml.id', $direction = 'DESC') {
        // Получаем и устанавливаем значение фильтра состояния.
        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published');
        $this->setState('filter.published', $published);

        // Получаем и устанавливаем значение фильтра поиска по тексту сообщения.
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        // Получаем и устанавливаем значение фильтра по умолчанию.
        $default = $this->getUserStateFromRequest($this->context . '.filter.default', 'filter_default');
        $this->setState('filter.default', $default);

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
        $id .= ':' . $this->getState('filter.default');

        return parent::getStoreId($id);
    }
} //YandexMarketModelYmls
