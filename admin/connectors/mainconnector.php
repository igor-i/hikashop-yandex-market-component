<?php
/**
 * @package   Yandex.Market for HikaShop
 * @subpackage   com_yandexmarket
 * @author   Igor Inkovskiy
 * @copyright   Copyright (C) 2017 Igor Inkovskiy. All rights reserved.
 * @contact   shop.igor-i.ru, igor-i-shop@ya.ru
 * @license   Beerware
 */

defined( '_JEXEC' ) or die;

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

/**
 * Class MainConnector
 * @since 0.1
 */
class IgoriMainConnector {
    protected $db;
    protected $query;
    protected $config;
    protected $item;
    protected $offerType;
    protected $offerTypeParams;
    protected $includeCategories;
    protected $excludeCategories;
    protected $includeProducts;
    protected $excludeProducts;
    protected $offerTypesOrder;

    public function __construct($data)
    {
        $this->db = JFactory::getDbo();
        $this->query = $this->db->getQuery(true);
        $this->config = JComponentHelper::getParams('com_yandexmarket');

        $this->excludeCategories = (isset($data['exclude_categories'])) ?  $data['exclude_categories'] : array();
        $this->includeCategories = (isset($data['include_categories'])) ? $data['include_categories'] : array();
        $this->excludeProducts = (isset($data['exclude_products'])) ? $data['exclude_products'] : array();
        $this->includeProducts = (isset($data['include_products'])) ? $data['include_products'] : array();

        $this->offerTypesOrder = array(
            'vendor.model' => array(
                'offerAttributes' => array(
                    'id', 'bid', 'cbid', 'available', 'type', 'group_id'
                ),
                'required' => array(
                    'url', 'price', 'currencyId', 'categoryId', 'delivery', 'vendor', 'model'
                ),
                'notRequired' => array(
                    'market_category', 'store', 'pickup', 'delivery-options', 'typePrefix', 'picture',
                    'vendorCode', 'description', 'sales_notes', 'manufacturer_warranty', 'country_of_origin',
                    'downloadable', 'adult', 'age', 'barcode', 'cpa', 'rec', 'expiry', 'weight', 'dimensions', 'param',
                    'group_id', 'oldprice', 'rec', 'adult', 'age', 'cpa'
                )
            ),
            'book' => array(
                'offerAttributes' => array(
                    'id', 'bid', 'cbid', 'available', 'type', 'group_id'
                ),
                'required' => array(
                    'price', 'currencyId', 'categoryId', 'name'
                ),
                'notRequired' => array(
                    'url', 'market_category', 'picture', 'store', 'pickup', 'delivery', 'delivery-options',
                    'author', 'publisher', 'series', 'year', 'ISBN', 'volume', 'part', 'language', 'binding',
                    'page_extent', 'table_of_contents', 'description', 'downloadable', 'age', 'group_id', 'oldprice',
                    'rec', 'adult', 'age', 'cpa'
                )
            ),
            'audiobook' => array(
                'offerAttributes' => array(
                    'id', 'bid', 'cbid', 'available', 'type', 'group_id'
                ),
                'required' => array(
                    'price', 'currencyId', 'categoryId', 'name'
                ),
                'notRequired' => array(
                    'url', 'market_category', 'picture', 'author', 'publisher', 'series', 'year', 'ISBN', 'volume',
                    'part', 'language', 'table_of_contents', 'performed_by', 'performance_type', 'storage', 'format',
                    'recording_length', 'description', 'downloadable', 'age', 'group_id', 'oldprice', 'rec',
                    'adult', 'age', 'cpa'
                )
            ),
            'artist.title' => array(
                'offerAttributes' => array(
                    'id', 'bid', 'cbid', 'available', 'type', 'group_id'
                ),
                'required' => array(
                    'price', 'currencyId', 'categoryId', 'title', 'delivery'
                ),
                'notRequired' => array(
                    'url', 'market_category', 'picture', 'store', 'pickup', 'artist', 'year', 'media',
                    'description', 'age', 'barcode', 'group_id', 'oldprice', 'rec', 'adult', 'age', 'cpa'
                )
            ),
            'tour' => array(
                'offerAttributes' => array(
                    'id', 'bid', 'cbid', 'available', 'type', 'group_id'
                ),
                'required' => array(
                    'price', 'currencyId', 'categoryId', 'days', 'name', 'included', 'transport', 'delivery'
                ),
                'notRequired' => array(
                    'url', 'market_category', 'picture', 'store', 'pickup', 'worldRegion', 'country',
                    'region', 'dataTour', 'hotel_stars', 'room', 'meal', 'description', 'age', 'group_id', 'oldprice',
                    'rec', 'adult', 'age', 'cpa'
                )
            ),
            'event-ticket' => array(
                'offerAttributes' => array(
                    'id', 'bid', 'cbid', 'available', 'type', 'group_id'
                ),
                'required' => array(
                    'price', 'currencyId', 'categoryId', 'name', 'place', 'date', 'delivery'
                ),
                'notRequired' => array(
                    'url', 'market_category', 'picture', 'store', 'pickup', 'hall_plan', 'is_premiere',
                    'is_kids', 'description', 'age', 'group_id', 'oldprice', 'rec', 'adult', 'age', 'cpa'
                )
            )
        );
    }

    public function setError($msg)
    {
        $db = JFactory::getDbo();
        $object = new stdClass();
        $object->errors = $msg;
    }

}

final class offerObject
{
    var
        $price,
        $oldPrice,
        $categories,
        $vendor,
        $model,
        $name,
        $code,
        $variants,
        $customFields,
        $product_id,
        $url,
        $currencyId,
        $picture,
        $product_quantity,
        $description,
        $weight,
        $dimensions,
        $related,
        $options,
        $downloadable,
        $url_isCanonical;
}
