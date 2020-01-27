<?php
/**
 * @package   Yandex.Market for HikaShop
 * @subpackage   com_yandexmarket
 * @author   Igor Inkovskiy
 * @copyright   Copyright (C) 2017 Igor Inkovskiy. All rights reserved.
 * @contact   https://shop.igor-i.ru, igor-i-shop@ya.ru
 * @license   Beerware
 */

defined( '_JEXEC' ) or die;

jimport('joomla.filesystem.file');

if (version_compare(JVERSION, '3.5.0', 'ge')) {
    if (!class_exists('StringHelper1')) {
        class StringHelper1 extends \Joomla\String\StringHelper{}
    }
} else {
    if (!class_exists('StringHelper1')) {
        jimport('joomla.string.string');
        class StringHelper1 extends JString{}
    }
}

class IgoriHikashopConnector extends IgoriMainConnector
{
    public $enableShop;
    public $countProducts = 0;

    private $characteristics;
    private $rootCharacteristics;
    private $customFields;

    protected $allIncludeCategoriesIds = array();
    protected $allIncludeCategoriesInfo = array();
    protected $allIncludeProductsCategoryIds = array();
    protected $allIncludeProductsCategoryInfo = array();
    protected $allUsageCategoriesIds = array();
    protected $allUsageCategoriesInfo = array();

    protected $pathToHikaHelper;
    protected $pathToHikaProductClass;
    protected $pathToHikaCategoryType;
    protected $pathToHikaProductDisplayType;

    public function __construct($data = null)
    {
        parent::__construct($data);

        $this->pathToHikaHelper = implode(
            DS,
            [
                rtrim(JPATH_ADMINISTRATOR, DS),
                'components',
                'com_hikashop',
                'helpers',
                'helper.php'
            ]
        );

        $this->pathToHikaProductClass = implode(
            DS,
            [
                rtrim(JPATH_ADMINISTRATOR, DS),
                'components',
                'com_hikashop',
                'classes',
                'product.php'
            ]
        );

        $this->pathToHikaCategoryType = implode(
            DS,
            [
                rtrim(JPATH_ADMINISTRATOR, DS),
                'components',
                'com_hikashop',
                'types',
                'categorysub.php'
            ]
        );

        $this->pathToHikaProductDisplayType = implode(
            DS,
            [
                rtrim(JPATH_ADMINISTRATOR, DS),
                'components',
                'com_hikashop',
                'types',
                'productdisplay.php'
            ]
        );

        if (@include_once($this->pathToHikaHelper)) {
            include_once($this->pathToHikaProductClass);
            include_once($this->pathToHikaCategoryType);
            include_once($this->pathToHikaProductDisplayType);

            $this->enableShop = true;

            if (empty($this->includeCategories) && empty($this->includeProducts)) {
                // Достаём все категории (или все товары)
                $db = JFactory::getDbo();
                $query = $db->getQuery(true)
                    ->select('`category_id`')
                    ->from(hikashop_table('category'))
//                    ->where('category_published = 1')
                    ->where('category_type = ' . $db->quote('product'))
                    ->select('`category_id` > 1')
                    ->order('category_ordering ASC');
                $this->includeCategories = $db->setQuery($query)->loadColumn();
            }

        } else {
            $this->enableShop = false;
        }
    }

    /**
     * Выбирает товары и формирует массив товарных предложений
     *
     * @param $limitStart
     * @param $limit
     * @return array
     * @since 0.1
     */
    public function getOffers($limitStart, $limit = 10000)
    {

        $offers = array();

        if (!$this->enableShop) {
            $this->setError('COM_YANDEXMARKET_HIKASHOP_NOT_INSTALLED');
            return array();
        }

        // Выбираем все категории из $this->includeCategories, включая вложенные
        $this->setIncludeCategories($this->includeCategories, $this->excludeCategories);

        // Выбираем все категории из $this->includeProducts, включая вложенные
        $this->setIncludeProductsCategories($this->includeProducts);

        // Собираем информацию обо всех задействованных категориях (из includeCategories и includeProducts)
        $this->setUsageCategories(
            $this->allIncludeCategoriesIds,
            $this->allIncludeCategoriesInfo,
            $this->allIncludeProductsCategoryIds,
            $this->allIncludeProductsCategoryInfo
        );

        //выбираем товары в отобранных категориях, а так же товары из includeProducts,
        //за исключением товаров из excludeProducts
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('p.`product_id`')
            ->from('`#__hikashop_product` AS p')
//            ->where('p.`product_parent_id` = 0')
            ->where('p.`product_published` = 1');

        if (count($this->allIncludeCategoriesIds)) {
            $query->leftJoin('#__hikashop_product_category as c ON c.product_id = p.product_id');
        }

        if (count($this->allIncludeCategoriesIds) && count($this->includeProducts)) {
            $query->where('(`c`.`category_id` IN (' . implode(',', $this->allIncludeCategoriesIds) . ')
                OR `p`.`product_id` IN (' . implode(',', $this->includeProducts) . '))');
        } elseif (count($this->allIncludeCategoriesIds)) {
            $query->where('`c`.`category_id` IN (' . implode(',', $this->allIncludeCategoriesIds) . ')');
        } elseif (count($this->includeProducts)) {
            $query->where('`p`.`product_id` IN (' . implode(',', $this->includeProducts) . ')');
        }

        if (count($this->excludeProducts)) {
            $query->where('`p`.`product_id` NOT IN (' . implode(',', $this->excludeProducts) . ')');
        }

        $productsIds = $db->setQuery($query, $limitStart, $limit)->loadColumn();

        // Выбираем варианты
        if (is_array($productsIds) && count($productsIds)) {
            $query = $db->getQuery(true)
                ->clear()
                ->select('p.`product_id`')
                ->from('`#__hikashop_product` AS p')
                ->where('p.`product_parent_id` IN (' . implode(',', $productsIds) . ')')
                ->where('p.`product_published` = 1');

            $variantsIds = $db->setQuery($query)->loadColumn();
        } else {
            $variantsIds = array();
        }

        $productsAndVariantsIds = array_merge($productsIds, $variantsIds);

        //если товары выбрались, то
        if (is_array($productsIds) && count($productsIds)) {
            //выбираем по ним всю информацию
            $products = $this->getProducts($productsIds);

            // Определяем для основной валюты идентификатор в HikaShop
            $config = hikashop_config();
            $currencyCode = $this->config->get('main_currency', 'RUB');
            $main_currency_id = $config->get('main_currency', 1);
            $currencyClass = hikashop_get('class.currency');
            $currencyId = $this->getCurrId($currencyCode);

            // Теперь актуализируем цены на товары с учётом актуальныых скидок, основной валюты и прочего
            // $zone_id = hikashop_getZone(null);
            // $discount_before_tax = (int)$config->get('discount_before_tax', 0);
            // FIXME: пришлось отключить формирование цены с помощью хикашоповского класса, так как не удалось оттуда вытащить наибольшую цену или все цены (возвращается меньшая цена)
            // $currencyClass->getPrices(
            //     $products,
            //     $productsAndVariantsIds,
            //     $currencyId,
            //     $main_currency_id,
            //     $zone_id,
            //     $discount_before_tax
            // );

            // Директория, где лежат фотографии товаров
            $uploadFolder = $config->get('uploadfolder');

            //идём по каждому из отобранных товаров
            foreach ($products as $product) {
                $offer = new offerObject();

                // Тип или категория товара
                foreach ($product->categories as $prodCatKey => $prodCatValue) {
                    foreach ($this->allUsageCategoriesInfo as $allCatInfo) {
                        if ($prodCatValue == $allCatInfo->category_id) {
                            $offer->categories[$prodCatKey] = $allCatInfo;
                            break;
                        }
                    }
                }

                // Наименование производителя или торговая марка
                $offer->vendor = $this->getManufacturerName($product->product_manufacturer_id);

                // Модель товара
                $offer->model = $product->product_name;

                // Наименование товара
                $offer->name = $product->product_name;

                // Код товара
                $offer->code = $product->product_code;

                // Дополнительные поля
                $product->customFields = $this->getCustomFields($product);
                $offer->customFields = $product->customFields;

                // Идентификатор валюты товара (RUR, USD, UAH, KZT, BYN)
                $offer->currencyId = $currencyCode;

                // Цена на товар со скидками
                // $offer->price = $this->getMaxPriceWithTax($product->prices);
                $offer->price = $this->getMaxPrice($product->prices);

                // Цена на товар без скидок
                // FIXME: пришлось пожертвовать, из-за того, что отключил формирование цены с помощью хикашоповского класса
                // $oldPrice = $this->getMaxPriceWithoutDiscountWithTax($product->prices);
                // $offer->oldPrice = empty($oldPrice) ? 0 : $oldPrice;
                $offer->oldPrice = $offer->price;

                // URL-ссылки на картинки товара
                $images = array();
                if (is_array($product->images) && count($product->images)) {
                    foreach ($product->images as $key => $image) {
                        if (empty($image)) {
                            break;
                        }
                        $images[] = JUri::root(false, '') . $uploadFolder . $product->images[$key]->file_path;
                    }
                }
                $offer->picture = $images;

                // Характеристики (варианты), цены для вариантов, описание и картинки
                if (!empty($product->variants)) {
                    foreach ($product->variants as $key => $variant) {
                        if ($variant->product_published=="0") {
                            unset($product->variants[$key]);
                        } else {
                            // Дополнительные поля для вариантов
                            $product->variants[$key]->customFields = $this->getCustomFields($variant);

                            // Характеристики для вариантов
                            $product->variants[$key]->characteristics =
                                $this->getCharacteristics($variant->variant_links);

                            // Цены для вариантов
                            // $product->variants[$key]->price = $this->getMaxPriceWithTax($product->variants[$key]->prices);
                            $product->variants[$key]->price = $this->getMaxPrice($product->variants[$key]->prices);

                            // FIXME: пришлось пожертвовать старой ценой (скидкой), из-за того что отключил формирование цены с помощью хикашоповского класса
                            // $oldPrice = $this->getMaxPriceWithoutDiscountWithTax($product->variants[$key]->prices);
                            // $product->variants[$key]->oldPrice = empty($oldPrice) ? 0 : $oldPrice;
                            $product->variants[$key]->oldPrice = $product->variants[$key]->price;

                            // Картинки
                            $images = array();
                            if (is_array($product->variants[$key]->images) && count($product->variants[$key]->images)) {
                                foreach ($product->variants[$key]->images as $keyImage => $image) {
                                    if (empty($image)) {
                                        break;
                                    }
                                    $images[] = implode(
                                        [
                                            JUri::root(false, ''),
                                            $uploadFolder,
                                            $product->variants[$key]->images[$keyImage]->file_path
                                        ]
                                    );
                                }
                            }
                            $product->variants[$key]->picture = $images;

                            // Описание варианта товара
                            $description = (!empty($variant->product_description)) ? $variant->product_description : $product->product_description;

                            // Описание товара надо обрезать до 2985 символов (разрешено 3000 символов, но 15 из них
                            // оставляем под троеточие в конце и обрамляющий блок CDATA)
                            $product->variants[$key]->product_description = rtrim(mb_strimwidth($description, 0, 2985)) . "...";

                        }
                    }
                }
                $offer->variants = $product->variants;

                // Идентификатор товарного предложения (product_id)
                $offer->product_id = $product->product_id;

                // URL страницы товара на сайте магазина
                // Динамическая ссылка
                if (empty($product->product_canonical)) {
//		            $link = 'index.php/product/' . (int)$product->product_id . '-' . $product->product_alias;
                    $link = implode(
                        [
                            'index.php?option=com_hikashop&ctrl=product&task=show&cid=',
                            $product->product_id,
                            '&name=',
                            $product->product_alias
                        ]
                    );
                    // Потом при формировании YML к динамическому урлу надо добавить ещё идентификатор пункта меню
                    // '&itemid=', достать его из конфига компонента
                    $offer->url_isCanonical = false;

                    // Каноническая ссылка
                } else {
                    // Каноническая ссылка может начинаться с http или со слеша или без слеша
                    if (preg_match('/^(http:\/\/)(.+)$/i', $product->product_canonical, $matches)) {
                        $link = $matches[2];
                    } elseif (preg_match('/^(\/)(.+)$/i', $product->product_canonical, $matches)) {
                        $link = $matches[2];
                    } else {
                        $link = $product->product_canonical;
                    }
                    $offer->url_isCanonical = true;
                }

                $offer->url = $link;

                // Идентификатор категории товара (так как у товара может быть несколько категорий,
                // то подходящую категорию будем опредеять при формировании YML, поэтому строка ниже закомментарена)
//                $offer->categoryId = isset($product->categories[0]) ? $product->categories[0] : 0;

                // Описание товара
                $description = $product->product_description;

                // Описание товара надо обрезать до 2985 символов
                // (разрешено 3000 символов, но 15 из них оставляем под троеточие в конце и обрамляющий блок CDATA)
                $offer->description = rtrim(mb_strimwidth($description, 0, 2985)) . "...";

                // Количество товара в точках продаж (outlets)
                // (чёта сложновато, решил пока не делать)
//                $offer->outletId = $product->product_warehouse_id;
//                $offer->product_quantity = $product->product_quantity;

                // Срок годности/срок службы либо дата истечения срока годности/срока службыe
                // (решил пока не делать)
//                $offer->expiry = '';

                // Вес товара в кг
                $weightHelper = hikashop_get('helper.weight');
                $offer->weight = $weightHelper->convert($product->product_weight, $product->product_weight_unit, 'kg');
                foreach ($product->variants as $key => $variant) {
                    $offer->variants[$key]->product_weight =
                        $weightHelper->convert($variant->product_weight, $variant->product_weight_unit, 'kg');
                }

                // Габариты (длина, ширина, высота) в сантиметрах
                $dimensions = $this->getDimensions($product);
                if (!empty($dimensions)) {
                    $offer->dimensions = $dimensions;
                }
                foreach ($product->variants as $key => $variant) {
                    $dimensions = $this->getDimensions($variant);
                    if (!empty($dimensions)) {
                        $offer->variants[$key]->dimensions = $dimensions;
                    }
                }

                // Признак товара, который можно скачать (downloadable)
                // (вроде бы можно этот признак достать откуда-то из настроек HikaShop, но я поленился)
//                $offer->downloadable = '';

                // Рекомендованные товары
                $offer->related = $this->getRelated($products, $product->product_id, 'related');
                $offer->options = $this->getRelated($products, $product->product_id, 'options');

                // Кладём результат в общий массив
                $offers[]= $offer;

                // Увеличиваем счётчик выбранных товарных предложений
                $this->countProducts ++;
            }
        }

        return $offers;
    }

    /**
     * Устанавливает в объекте информацию о категориях из $this->includeCategories, включая вложенные,
     * но без $this->excludeCategories
     *
     * @param $includeCategories
     * @param $excludeCategories
     * @return array|mixed
     * @since 0.1
     */
    private function setIncludeCategories($includeCategories = array(), $excludeCategories = array())
    {
        //проверяем чтобы  HikaShop был установлен
        if (!$this->enableShop) {
            return false;
        }

        if (!is_array($includeCategories) || !count($includeCategories)) {
            return false;
        }

        //формируем массив идентификаторов всех категорий для выборки товаров, включая дочерние категории
        $this->allIncludeCategoriesIds = $this->getCategoriesIds($includeCategories, $excludeCategories);

        if (!(is_array($this->allIncludeCategoriesIds)) || !(count($this->allIncludeCategoriesIds))) {
            $this->allIncludeCategoriesIds = array();
            return false;
        }

        //выбираем информацию по всем отобранным категориям
        $this->allIncludeCategoriesInfo = $this->getCategoriesInfo($this->allIncludeCategoriesIds);

        if (!(is_array($this->allIncludeCategoriesInfo)) || !(count($this->allIncludeCategoriesInfo))) {
            $this->allIncludeCategoriesInfo = array();
            return false;
        }

        return true;
    }

    /**
     * Устанавливает в объекте информацию о категориях товаров из $this->includeProducts, включая вложенные
     *
     * @param $includeProducts
     * @return array|mixed
     * @since 0.1
     */
    private function setIncludeProductsCategories($includeProducts = array())
    {
        //проверяем чтобы  HikaShop был установлен
        if (!$this->enableShop) {
            return false;
        }

        // Формируем массив идентификаторов категорий товаров
        $categoryIds = $this->getCategoryIdByProducts($includeProducts);

        if (!is_array($categoryIds) || !count($categoryIds)) {
            return false;
        }

        //формируем массив идентификаторов всех категорий для выборки товаров, включая дочерние категории
        $this->allIncludeProductsCategoryIds = $this->getCategoriesIds($categoryIds);

        if (!is_array($this->allIncludeProductsCategoryIds) || !count($this->allIncludeProductsCategoryIds)) {
            $this->allIncludeProductsCategoryIds = array();
            return false;
        }

        //выбираем информацию по всем отобранным категориям
        $this->allIncludeProductsCategoryInfo = $this->getCategoriesInfo($this->allIncludeProductsCategoryIds);

        if (!is_array($this->allIncludeProductsCategoryInfo) || !count($this->allIncludeProductsCategoryInfo)) {
            $this->allIncludeProductsCategoryInfo = array();
            return false;
        }

        return true;
    }


    /**
     * Устанавливает в объекте информацию об используемых категориях товаров из
     * $this->includeCategories и $this->includeProducts
     *
     * @param array $allIncludeCategoriesIds
     * @param array $allIncludeCategoriesInfo
     * @param array $allIncludeProductsCategoryIds
     * @param array $allIncludeProductsCategoryInfo
     * @return bool
     * @since 0.4.1
     */
    private function setUsageCategories(
        $allIncludeCategoriesIds = array(),
        $allIncludeCategoriesInfo = array(),
        $allIncludeProductsCategoryIds = array(),
        $allIncludeProductsCategoryInfo = array()
    ) {
        //формируем массив идентификаторов всех используемых категорий
        $this->allUsageCategoriesIds = array_merge($allIncludeCategoriesIds, $allIncludeProductsCategoryIds);

        if (!is_array($this->allUsageCategoriesIds) || !count($this->allUsageCategoriesIds)) {
            $this->allUsageCategoriesIds = array();
            return false;
        }

        //выбираем информацию по всем используемым категориям
        $this->allUsageCategoriesInfo = array_merge($allIncludeCategoriesInfo, $allIncludeProductsCategoryInfo);

        if (!is_array($this->allUsageCategoriesInfo) || !count($this->allUsageCategoriesInfo)) {
            $this->allUsageCategoriesInfo = array();
            return false;
        }

        return true;
    }

    /**
     * Рекурсивно собирает массив идентификаторов категорий, с их дочками
     *
     * @param array $includeCategories
     * @param array $excludeCategories
     * @return array|bool
     * @since 0.4.1
     */
    public function getCategoriesIds($includeCategories = array(), $excludeCategories = array())
    {
        if (!is_array($includeCategories) || !count($includeCategories)) {
            return array();
        }

        //сначала удаляем из массива те категории, которые есть в исключениях
        foreach ($includeCategories as $catKey => $category) {
            foreach ($excludeCategories as $excCat) {
                if ($category===$excCat) {
                    unset($includeCategories[$catKey]);
                    break;
                }
            }
        }

        if (!count($includeCategories)) {
            return array();
        }

        $allIncludeCategoriesIds = array();

        //идём по каждой из оставшихся категорий
        foreach ($includeCategories as $key => $value) {
            //сохраняем категорию в результирующем массиве
            $allIncludeCategoriesIds[] = $includeCategories[$key];
            //рекурсивно проверяем дочерние категории
            $children = $this->getCategoriesByParent($value);
            $allIncludeCategoriesIds = array_merge($allIncludeCategoriesIds, $this->getCategoriesIds($children));
        }

        return $allIncludeCategoriesIds;
    }

    /**
     * Выбирает все прямые дочерние категории по идентификатору родительской категории
     * @param $parentId
     * @return array|mixed
     * @since 0.1
     */
    public function getCategoriesByParent($parentId)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('c.`category_id`')
            ->from('#__hikashop_category AS c')
//            ->where('c.category_published = 1')
            ->where('c.category_type = ' . $db->quote('product'))
            ->where('c.`category_parent_id` = ' . (int)$parentId)
            ->order('c.`category_ordering` ASC');
        $result = $db->setQuery($query)->loadColumn();

        if (!is_array($result)) {
            return array();
        }

        return $result;
    }

    /**
     * Формирует контролы с данными для настройки выборки товарных предложений
     *
     * @param $field
     * @return array
     * @since 0.1
     */
    public function loadCustomParams($field)
    {
//        $values = json_decode($field->value, true);
        $values = $this->objectToArray($field->value);

        $include_categories = (empty($values["include_categories"])) ? array() : $values["include_categories"];
        $exclude_categories = (empty($values["exclude_categories"])) ? array() : $values["exclude_categories"];

        $cat = new hikashopCategorysubType;
        $cat->type = 'product';
        $categoriesIncSelect = $cat->displayMultiple($field->name . '[include_categories][]', $include_categories);
        $categoriesExcSelect = $cat->displayMultiple($field->name . '[exclude_categories][]', $exclude_categories);

        $exclude_products = (empty($values["exclude_products"])) ? array() : $values["exclude_products"];
        $include_products = (empty($values["include_products"])) ? array() : $values["include_products"];

        $prod = new hikashopProductdisplayType;
        $productsIncSelect = $prod->displayMultiple($field->name . '[include_products][]', $include_products);
        $productsExcSelect = $prod->displayMultiple($field->name . '[exclude_products][]', $exclude_products);

        $customParams = array(
            array(
                'label' => JText::_('COM_YANDEXMARKET_SHOP_INCLUDE_CATS'),
                'desc' => JText::_('COM_YANDEXMARKET_SHOP_INCLUDE_CATS_DESC'),
                'input' => $categoriesIncSelect
            ),
            array(
                'label' => JText::_('COM_YANDEXMARKET_SHOP_INCLUDE_PRODUCTS'),
                'desc' => JText::_('COM_YANDEXMARKET_SHOP_INCLUDE_PRODUCTS_DESC'),
                'input' => $productsIncSelect
            ),
            array(
                'label' => JText::_('COM_YANDEXMARKET_SHOP_EXCLUDE_CATS'),
                'desc' => JText::_('COM_YANDEXMARKET_SHOP_EXCLUDE_CATS_DESC'),
                'input' => $categoriesExcSelect
            ),
            array(
                'label' => JText::_('COM_YANDEXMARKET_SHOP_EXCLUDE_PRODUCTS'),
                'desc' => JText::_('COM_YANDEXMARKET_SHOP_EXCLUDE_PRODUCTS_DESC'),
                'input' => $productsExcSelect
            )
        );

        return $customParams;
    }

    public function getFilterCategory()
    {
        $cat = new hikashopCategorysubType;
        $cat->type = 'product';
        $categoriesSelect = $cat->displaySingle('filter_categories', '');
        return $categoriesSelect;
    }

    public function getFilteredProducts(
        $category = 0,
        $dateFrom = '',
        $dateTo = '',
        $text = '',
        array $excludeProducts = array(),
        array $includeProducts = array(),
        array $params = array()
    ) {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('DISTINCT p.product_id AS id, p.product_code AS sku, p.product_name AS name')
            ->from('#__hikashop_product AS p')
            ->leftJoin('#__hikashop_product_category as c ON c.product_id = p.product_id')
            ->where('product_published = 1')
            ->group('p.product_id');

        if ($category > 0) {
            $query->where('c.category_id = '.(int)$category);
        }

        if ($dateFrom != '') {
            if (StringHelper1::strlen($dateFrom) < 11) {
                $dateFrom .= ' 00:00:00';
            }
            $d = new JDate($dateFrom);
            $dateFrom = $d->toUnix();
            $query->where('p.product_modified >= ' . $db->quote($dateFrom));
        }

        if ($dateTo != '') {
            if (StringHelper1::strlen($dateTo) < 11) {
                $dateTo .= ' 23:59:59';
            }
            $d = new JDate($dateTo);
            $dateTo = $d->toUnix();
            $query->where('p.product_modified <= '.$db->quote($dateTo));
        }

        if (!empty($text)) {
            $search = $db->quote('%' . str_replace(
                    ' ',
                    '%',
                    $db->escape(trim(StringHelper1::strtolower($text)), true) . '%'
                ));
            $query->where('p.product_name LIKE LOWER(' . $search . ')');
        }

        if (count($excludeProducts)) {
            $query->where('p.product_id NOT IN ('.implode(', ', $excludeProducts).')');
        }

        if (count($includeProducts)) {
            $query->where('p.product_id NOT IN ('.implode(', ', $includeProducts).')');
        }

        $result = $db->setQuery($query)->loadObjectList();

        return $result;
    }

    /**
     * @param $prices
     * @return mixed
     * @since 1.2.1
     */
    private function getMaxPrice($prices)
    {
        return array_reduce($prices, function ($carry, $item) {
            return max($carry, $item->price_value);
        });
    }

    /**
     * @param $prices
     * @return mixed
     * @since 1.2.0
     */
    private function getMaxPriceWithTax($prices)
    {
        return array_reduce($prices, function ($carry, $item) {
            return max($carry, $item->price_value_with_tax);
        });
    }

    /**
     * @param $prices
     * @return mixed
     * @since 1.2.0
     */
    private function getMaxPriceWithoutDiscountWithTax($prices)
    {
        $maxPriceArray = array_reduce($prices, function ($carry, $item) {
            return $item->price_value_with_tax > $carry['price_value_with_tax'] ? [
                'price_value_with_tax' => $item->price_value_with_tax,
                'price_value_without_discount_with_tax' => $item->price_value_without_discount_with_tax
                ] : $carry;
        });

        return $maxPriceArray['price_value_without_discount_with_tax'];
    }

    /**
     * Выбирает из множества цен за товар ту, которая указана для минимального количества товара
     * (то есть по логике это должна быть самая дорогая цена, так как скидки обычно при увеличении количества)
     *
     * @param $aPrices
     * @return mixed
     * @since 0.1
     */
    private function getMinCountPrice($aPrices)
    {
        $count = count($aPrices);
        $minVal = 0;
        for ($i=0; $i<$count; $i++) {
            if ($i == 0) {
                $minElem = $i;
                $minVal = $aPrices[$i]->price_min_quantity;
                continue;
            }

            if ($minVal > $aPrices[$i]->price_min_quantity) {
                $minElem = $i;
                $minVal = $aPrices[$i]->price_min_quantity;
            }
        }

        return $aPrices[$minElem];
    }

    /**
     * Определяет идентификатор валюты в HikaShop
     *
     * @param $currencyCode
     * @return int
     * @since 0.1
     */
    private function getCurrId($currencyCode)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('`currency_id`')
            ->from('`#__hikashop_currency`')
            ->where('`currency_code` = ' . $db->quote($currencyCode));
        return (int)$db->setQuery($query)->loadResult();
    }

    /**
     * Выбирает наименование производителя
     *
     * @param $manufacturer_id
     * @return string
     * @since 0.1
     */
    private function getManufacturerName($manufacturer_id)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('`category_name`')
            ->from('`#__hikashop_category`')
            ->where('`category_type` = ' . $db->quote('manufacturer'))
            ->where('`category_id` = ' . $db->quote($manufacturer_id));
        return (string)$db->setQuery($query)->loadResult();
    }

    /**
     * Выбирает информацию о товарах
     *
     * @param array $products
     * @return array|mixed
     * @since 0.1
     */
    private function getProducts(array $products)
    {
        if (!count($products)) {
            return array();
        }
        if (!@include_once($this->pathToHikaHelper)) {
            return array();
        }
        $productClass = hikashop_get('class.product');
        $productClass->getProducts($products);

        // $productClass возвращает:
        // - товары (с вложенными вариантами): $productClass->products
        // - варианты: $productClass->variants
        // - вместе товары и варианты одним массивом: all_products;
        $result = $productClass->products;

//        $productClass = hikashop_get('class.product');
//        $productClass->getProducts($productsIds, 'import');

//        $db = JFactory::getDbo();
//        $query = $db->getQuery(true);
//        $query->select('`product_id`, `product_code` AS sku, `product_name` AS name')
//            ->from('`#__hikashop_product`')
//            ->where('`product_id` IN ('.implode(', ', $products).')');
//        $result = $db->setQuery($query)->loadObjectList('product_id');
        return $result;
    }

    /**
     * Выбирает сопутствующие товары и опции
     *
     * @param $products
     * @param $product_id
     * @param string $mode
     * @return string
     * @since 0.1
     */
    private function getRelated($products, $product_id, $mode = 'related')
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('product_related_id')
            ->from(hikashop_table('product_related'))
            ->where('product_id = ' . (int)$product_id)
            ->where('product_related_type = ' . $db->quote($mode))
            ->order('product_related_ordering ASC');
        $result = $db->setQuery($query)->loadColumn();

        if (!is_array($result) || !count($result)) {
            return '';
        }

        // Сопутствующие товары должны быть в этом же прайс-листе, поэтому лишние убираем
        if (!empty($products) && is_array($products)) {
            foreach ($result as $key => $item) {
                if (empty($products[$item])) {
                    unset($result[$key]);
                }
            }
        }

        // Сокращаем массив до 30 элементов (требования яндекса)
        if (count($result) > 30) {
            array_splice($result, 30);
        }

        return implode(',', $result);
    }

    /**
     * Выбирает характеристики товара по product_id
     *  (не используется)
     *
     * @param $product_id
     * @return array
     * @since 0.1
     */
    private function getCharacteristicsByProductId($product_id)
    {
        $return = array();
        $aCharacteristics = $this->loadAllCharacteristics();
        if (!count($aCharacteristics)) {
            return $return;
        }
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('variant_characteristic_id')
//            ->from('#__hikashop_variant')
            ->from(hikashop_table('variant'))
            ->where('variant_product_id = ' . (int)$product_id);
        $result = $db->setQuery($query)->loadColumn();
        if (!is_array($result) || !count($result)) {
            return $return;
        }

        foreach ($result as $key) {
            $key = (int)$key;
            if (!isset($aCharacteristics[$key]) || $aCharacteristics[$key]['parent'] == 0
                || !isset($aCharacteristics[$aCharacteristics[$key]['parent']])) {
                continue;
            }

            $return[] = array(
                'name' => $aCharacteristics[$aCharacteristics[$key]['parent']]['value'],
                'value' => $aCharacteristics[$key]['value']
            );
        }

        return $return;
    }

    /**
     * Выбирает характеристики по их дентификаторам
     *
     * @param $charIds - массив идентификаторов характеристик
     * (можно взять как есть variant_links из объекта класса products - $products[]->variants[]->variant_links)
     * @return array
     * @since 0.1
     */
    private function getCharacteristics($charIds)
    {
        $return = array();

        if (!is_array($charIds) || !count($charIds)) {
            return $return;
        }

        $aCharacteristics = $this->loadAllCharacteristics();
        if (!count($aCharacteristics)) {
            return $return;
        }

        foreach ($charIds as $charId) {
            $charId = (int)$charId;

            if (!isset($aCharacteristics[$charId]) || $aCharacteristics[$charId]['parent'] == 0 ||
                !isset($aCharacteristics[$aCharacteristics[$charId]['parent']])) {
                continue;
            }

            $return[$aCharacteristics[$charId]['parent']] = array(
                'parent_id' => $aCharacteristics[$charId]['parent'],
                'parent_name' => $aCharacteristics[$aCharacteristics[$charId]['parent']]['value'],
                'id' => $charId,
                'value' => $aCharacteristics[$charId]['value']
            );
        }

        return $return;
    }

    /**
     * Выбирает все Характеристики (варианты)
     *
     * @return array
     * @since 0.1
     */
    public function loadAllCharacteristics()
    {
        if (is_array($this->characteristics)) {
            return $this->characteristics;
        }
        $this->characteristics = array();

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('characteristic_id, characteristic_parent_id, characteristic_value')
            ->from(hikashop_table('characteristic'));
        $result = $db->setQuery($query)->loadObjectList();

        if (is_array($result) && count($result)) {
            foreach ($result as $v) {
                $this->characteristics[(int)$v->characteristic_id] = array(
                    'parent' => (int)$v->characteristic_parent_id,
                    'value' => $v->characteristic_value);
            }
        }

        return $this->characteristics;
    }

    /**
     * Выбирает только корневые Характеристики (разновидности вариантов) для Product
     *
     * @return array
     * @since 0.1
     */
    public function loadRootCharacteristics()
    {
        if (is_array($this->rootCharacteristics)) {
            return $this->rootCharacteristics;
        }
        $this->rootCharacteristics = array();

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('characteristic_id, characteristic_parent_id, characteristic_value')
            ->from(hikashop_table('characteristic'))
            ->where('characteristic_parent_id = ' . $db->quote(0));
        $result = $db->setQuery($query)->loadObjectList();

        if (is_array($result) && count($result)) {
            foreach ($result as $v) {
                $this->rootCharacteristics[(int)$v->characteristic_id] = array(
                    'parent' => (int)$v->characteristic_parent_id,
                    'value' => $v->characteristic_value);
            }
        }

        return $this->rootCharacteristics;
    }

    public function getCharacteristic($charId)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('c.characteristic_id, c.characteristic_value, p.characteristic_id AS parent_id, p.characteristic_value AS parent_value')
            ->from(hikashop_table('characteristic AS c'))
            ->where('c.characteristic_id = ' . (int)$charId)
            ->innerJoin(hikashop_table('characteristic AS p ON p.characteristic_id = c.characteristic_parent_id'));
        $result = $db->setQuery($query)->loadObjectList();

        if (is_array($result) && count($result)) {
            foreach ($result as $v) {
                $this->rootCharacteristics[(int)$v->characteristic_id] = array(
                    'parent' => (int)$v->characteristic_parent_id,
                    'value' => $v->characteristic_value);
            }
        }

        return $this->rootCharacteristics;
    }

    private function getCustomFields($product)
    {
        $return = array();
        $aCustomfields = $this->loadCustomFields();
        if (!count($aCustomfields)) {
            return $return;
        }

        foreach ($aCustomfields as $key => $customField) {
            if (empty($product->$key)) {
                continue;
            }
            $productValue = $product->$key;
            $customName = $customField['field_realname'];

            switch ($customField['field_type']) {
                case '':
                case 'text':
                case 'link':
                case 'textarea':
                case 'wysiwyg':
                case 'date':
                case 'zone':
                case 'coupon':
                case 'customtext':
                    $customValue = $productValue;
                    if (!empty($customValue)) {
                        $return[$key] = array(
                            'name' => $customName,
                            'value' => $customValue
                        );
                    }
                    break;
                case 'radio':
                case 'singledropdown':
                    $customValue = isset($customField['field_value'][$productValue])
                        ? $customField['field_value'][$productValue] : '';
                    if (!empty($customValue)) {
                        $return[$key] = array(
                            'name' => $customName,
                            'value' => $customValue
                        );
                    }
                    break;
                case 'checkbox':
                case 'multipledropdown':
                    $productValues = explode(',', $productValue);
                    if (count($productValues)) {
                        foreach ($productValues as $productVal) {
                            $customValue = isset($customField['field_value'][$productVal])
                                ? $customField['field_value'][$productVal] : '';
                            if (!empty($customValue)) {
                                $return[$key] = array(
                                    'name' => $customName,
                                    'value' => $customValue
                                );
                            }
                        }
                    }
                    break;
                case 'file':
                case 'image':
                case 'ajaxfile':
                case 'ajaximage':
                default:
                    break;
            }
        }

        return $return;
    }

    /**
     * Выбирает Дополнительные поля
     *
     * @return array
     * @since 0.1
     */
    public function loadCustomFields()
    {
        if (is_array($this->customFields)) {
            return $this->customFields;
        }
        $this->customFields = array();

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('field_id, field_type, field_realname, field_value, field_namekey')
            ->from(hikashop_table('field'))
            ->where('field_published = 1')
            ->where('field_table = ' . $db->quote('product'));
//            ->where('`field_display` LIKE ' . $db->quote('%;field\_product\_show=1;%'));
        $result = $db->setQuery($query)->loadObjectList();

        if (is_array($result) && count($result)) {
            foreach ($result as $v) {
//                $this->customProductFields[] = $v->field_namekey;
                $values = array();
                if (!empty($v->field_value)) {
                    $field_data = explode("\n", $v->field_value);
                    if (count($field_data)) {
                        foreach ($field_data as $fd) {
                            list($fk, $fv, $fsort) = explode('::', $fd);
                            if (!empty($fk) && !empty($fv)) {
                                $values[$fk] = $fv;
                            }
                        }
                    }
                }
                $this->customFields[$v->field_namekey] = array(
                    'field_realname' => $v->field_realname,
                    'field_type' => $v->field_type,
                    'field_value' => $values);
            }
        }

        return $this->customFields;
    }

    /**
     * Возвращает информацию о категории
     *
     * @param $id
     * @return mixed
     * @since 0.1
     */
    public function getCategory($id)
    {
        $categoryClass = hikashop_get('class.category');

        return $categoryClass->get((int)$id);
    }

    /**
     * Возвращает информацию по категориям
     *
     * @param $ids
     * @return array|mixed
     * @since 0.4.1
     */
    private function getCategoriesInfo($ids)
    {
        if (is_array($ids) && count($ids)) {
            $db = JFactory::getDbo();
            $query = $db->getQuery(true);
            $query->select('`category_id`, `category_parent_id`, `category_name`')
                ->from(hikashop_table('category'))
//                ->where('`category_published` = 1')
                ->where('`category_type` = ' . $db->quote('product'))
                ->where('`category_id` IN (' . implode(',', $ids) . ')')
                ->order('`category_parent_id` ASC, `category_ordering` ASC');
            return $db->setQuery($query)->loadObjectList();
        }

        return array();
    }

    /**
     * Возвращает идентификаторы категорий по идентификаторам товара
     *
     * @param $ids
     * @return mixed|string
     * @since 0.4.0
     */
    public function getCategoryIdByProducts($ids)
    {
        if (is_array($ids) && count($ids)) {
            $db = JFactory::getDbo();
            $query = $db->getQuery(true);
            $query->select('category_id')
                ->from(hikashop_table('product_category'))
                ->where('product_id IN (' . implode(',', $ids) . ')');
            $result = $db->setQuery($query)->loadColumn();

            return $result;
        }

        return array();
    }

    /**
     * Конвертирует габариты товара в сантиметры, возвращает строку 'длина/ширина/высота'
     *
     * @param $product
     * @return string
     * @since 0.1
     */
    public function getDimensions($product)
    {
        $volumeHelper = hikashop_get('helper.volume');
        $dimensions = "";
        if (empty((float)$product->product_length) ||
            empty((float)$product->product_width) ||
            empty((float)$product->product_height)) {
            return $dimensions;
        }

        $dimensions .=
            $volumeHelper->convert($product->product_length, $product->product_dimension_unit, 'cm', 'dimension') . '/';
        $dimensions .=
            $volumeHelper->convert($product->product_width, $product->product_dimension_unit, 'cm', 'dimension') . '/';
        $dimensions .=
            $volumeHelper->convert($product->product_height, $product->product_dimension_unit, 'cm', 'dimension');

        return $dimensions;
    }

    /**
     * Для моего магазина достаёт содержимое статьи, чтобы сформировать полное описание товара
     *
     * @param $desc
     * @return string
     * @since 0.7.0
     */
    public function getDescription($desc)
    {
        $pattern = '/^\{article\s?(.+)(?=\}\{)\}\{introtext\}\s?\{\/article\}(.*)$/iu';
        preg_match($pattern, $desc, $matches);

        if (empty($matches[1])) {
            return $desc;
        }

        // Если определилось название статьи, то ищем её в базе
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName('introtext'))
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('title') . ' = ' . $db->quote($matches[1]));
        $db->setQuery($query);
        $introtext = $db->loadResult();

        if (!$introtext) {
            return $desc;
        }

        return $introtext . ' ' . $matches[2];
    }

    /**
     * Вспомогательная функция - Преобразует объект в массив
     * @param $data
     * @return array
     * @since 0.1
     */
    public function objectToArray($data)
    {
        if (is_array($data) || is_object($data)) {
            $result = array();
            foreach ($data as $key => $value) {
                $result[$key] = $this->objectToArray($value);
            }
            return $result;
        }
        return $data;
    }
} //IgoriHikashopConnector
