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

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

class YandexMarketControllerFile extends JControllerLegacy
{
    /**
     * Директория для хранения файлов YML
     * @var string
     * @since 0.1
     */
    protected $folder = JPATH_COMPONENT_SITE . DS . 'files';
    protected $pathToHikaConnector;
    protected $pathToMainConnector;

    /**
     * YandexMarketControllerFile constructor.
     *
     * @param array $config
     * @since 0.1
     */
    public function __construct($config = array())
    {
        parent::__construct($config);
        if (!isset($this->input)) {
            $this->input = JFactory::getApplication()->input;
        }

        $this->pathToMainConnector = implode(
            DS,
            [
                rtrim(JPATH_ADMINISTRATOR, DS),
                'components',
                'com_yandexmarket',
                'connectors',
                'mainconnector.php'
            ]
        );

        $this->pathToHikaConnector = implode(
            DS,
            [
                rtrim(JPATH_ADMINISTRATOR, DS),
                'components',
                'com_yandexmarket',
                'connectors',
                'hikashop.php'
            ]
        );
    }

    /**
     * @param $action
     *
     * @return bool
     * @since 0.1
     */
    protected function authoriseUser($action)
    {
        if (!JFactory::getUser()->authorise('core.' . strtolower($action), 'com_yandexmarket')) {
            // User is not authorised
            JError::raiseWarning(403, JText::_('JLIB_APPLICATION_ERROR_' . strtoupper($action) . '_NOT_PERMITTED'));
            return false;
        }

        return true;
    }

    /**
     * Удаление файла
     *
     * @return bool
     * @since 0.1
     */
    public function delete()
    {
        JSession::checkToken('request') or jexit(JText::_('JINVALID_TOKEN'));
        $this->setRedirect('index.php?option=com_yandexmarket');
        // Nothing to delete
        if (empty($this->input->get('ymlid'))) {
            $this->setMessage(JText::_('COM_YANDEXMARKET_ERROR_UNABLE_TO_DELETE_FILE'), 'error');
            return false;
        }

        // Authorize the user
        if (!$this->authoriseUser('delete')) {
            $this->setMessage(JText::_('COM_YANDEXMARKET_ERROR_UNABLE_TO_DELETE_FILE'), 'error');
            return false;
        }

        $filename = 'yml' . $this->input->get('ymlid') . '.xml';

        // Проверяем чтобы имя файла было безопасным
        if ($filename !== JFile::makeSafe($filename)) {
            $this->setMessage(JText::_('COM_YANDEXMARKET_ERROR_UNABLE_TO_DELETE_FILE'), 'error');
            return false;
        }

        // Обнуляем количество товарных предложений в файле
        $this->setCreatedAndOffersCount((int)$this->input->get('ymlid'), 0);

        // Если в каталоге файла нет
        if (!JFile::exists($this->folder . DS . $filename)) {
            $this->setMessage(JText::_('COM_YANDEXMARKET_ERROR_UNABLE_TO_DELETE_FILE'), 'error');
            return false;
        }

        // Удаление файла
        JFile::delete($this->folder . DS . $filename);
        $this->setMessage(JText::_('COM_YANDEXMARKET_DELETE_COMPLETE'));

        return true;
    }

    /**
     * Создание нового файла
     *
     * @return bool
     * @since 0.1
     */
    public function create()
    {
        JSession::checkToken('request') or jexit(JText::_('JINVALID_TOKEN'));

        $this->setRedirect('index.php?option=com_yandexmarket');

        $ymlId = (int)$this->input->get('ymlid');

        if (empty($ymlId)) {
            $this->setMessage(JText::_('COM_YANDEXMARKET_ERROR_UNABLE_TO_CREATE_FILE'), 'error');
            return false;
        }

        // Authorize the user
        if (!$this->authoriseUser('create')) {
            $this->setMessage(JText::_('COM_YANDEXMARKET_ERROR_UNABLE_TO_CREATE_FILE'), 'error');
            return false;
        }

        $filename = 'yml' . $ymlId . '.xml';

        // Проверяем чтобы имя файла было безопасным
        if ($filename !== JFile::makeSafe($filename)) {
            $this->setMessage(JText::_('COM_YANDEXMARKET_ERROR_UNABLE_TO_CREATE_FILE'), 'error');
            return false;
        }

        // Проверяем на всякий случай чтобы уже не было файла с таким именем
        if (JFile::exists($this->folder . DS . $filename)) {
            $this->setMessage(JText::_('COM_YANDEXMARKET_ERROR_UNABLE_TO_CREATE_FILE_EXISTS'), 'error');
            return false;
        }

        // Создаём каталог для наших файлов, если его ещё нет
        if (!JFolder::exists($this->folder)) {
            JFolder::create($this->folder);
        }

        // Достаём из базы параметры для формирования товарных предложений в Yml
        $data = null;
        $data['include_categories'] = $this->getOffersRule($ymlId, 'category', 'include');
        $data['exclude_categories'] = $this->getOffersRule($ymlId, 'category', 'exclude');
        $data['include_products'] = $this->getOffersRule($ymlId, 'product', 'include');
        $data['exclude_products'] = $this->getOffersRule($ymlId, 'product', 'exclude');

        require_once($this->pathToMainConnector);
        require_once($this->pathToHikaConnector);

        $hikashop = new igoriHikashopConnector($data);

        //если HikaShop установлен, то
        if (!empty($hikashop->enableShop)) {
            $offers = $hikashop->getOffers(0, 10000);
        } else {
            $this->setMessage(JText::_('COM_YANDEXMARKET_HIKASHOP_NOT_INSTALLED'), 'error');
            return false;
        }

        // Создаём YML-файл
        if (!$this->makeYML($this->folder . DS . $filename, $offers)) {
            $this->setMessage(JText::_('COM_YANDEXMARKET_ERROR_UNABLE_TO_CREATE_FILE'), 'error');
            return false;
        }

        // Записываем в базу количество товарных предложений и дату обновления файла
        $countProducts = $hikashop->countProducts;
        $this->setCreatedAndOffersCount($ymlId, $countProducts);

        $this->setMessage(JText::_('COM_YANDEXMARKET_CREATE_COMPLETE'));
        return true;
    }

    /**
     * Обновление файла
     *
     * @return bool
     * @since 0.1
     */
    public function remake()
    {
        if (!$this->delete()) {
            return false;
        }

        if (!$this->create()) {
            return false;
        }

        return true;
    }

    /**
     * Записывает в базу дату создания файла и количество товарных предложений
     *
     * @param $ymlId
     * @param $offersCount
     * @since 0.1
     */
    private function setCreatedAndOffersCount($ymlId, $offersCount)
    {
        $app = JFactory::getApplication();
        $date = JFactory::getDate('now', $app->get('offset'));
        $db   = JFactory::getDbo();
        if ((empty($offersCount)) || !(is_int($offersCount))) {
            $offersCount = 0;
        }
        if ($ymlId) {
            $created_on = $date->toSql(true);
            $query = $db->getQuery(true)
                ->update('#__yandexmarket_ymls')
                ->set('offers_count = ' . $db->quote($offersCount))
                ->set('created_on = ' . $db->quote($created_on))
                ->where('id = ' . $db->quote((int)$ymlId));
            $db->setQuery($query)->execute();
        }
    }

    /**
     * Выборка идентификаторов категорий или товаров для включения или исключения в или из товарной выборки
     *
     * @param $ymlId
     * @param $type
     * @param $mode
     * @return mixed
     * @since 0.1
     */
    private function getOffersRule($ymlId, $type, $mode)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->clear()
            ->select('o.`category_or_product_id`')
            ->from('#__yandexmarket_yml_offers AS o')
            ->where('o.`yml_id` = ' . $db->quote($ymlId))
            ->where('o.`category_or_product_type` = ' . $db->quote($type))
            ->where('o.`mode` = ' . $db->quote($mode));
        return $db->setQuery($query)->loadColumn();
    }

    /**
     * Создание XML в формате YML
     *
     * @param $pathToFile
     * @param $allOffers
     * @return string
     * @since 0.1
     */
    private function makeYML($pathToFile, $allOffers)
    {
        $hikashop = new igoriHikashopConnector();
        $menuClass = hikashop_get('class.menus');

        // Подгружаем из базы параметры YML
        $db = jFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__yandexmarket_ymls')
            ->where('id = ' . $db->quote((int)$this->input->get('ymlid')));
        $yml = $db->setQuery($query)->loadObject();

        // Конвертируем поле параметров в регистр.
        $params = new JRegistry;
        $ymlParams = $params->loadString($yml->params);

        // Создаём XML-документ версии 1.0 с кодировкой utf-8
        $dom = new domDocument("1.0", "utf-8");
        // Создаём корневой элемент yml_catalog
        $yml_catalog = $dom->createElement("yml_catalog");
        $dom->appendChild($yml_catalog);
        // Добавляем атрибуты date для yml_catalog
        $yml_catalog->setAttribute("date", date("Y-m-d H:i"));
        // Создаём элемент shop и добавляем его к yml_catalog
        $shop = $dom->createElement("shop");
        $yml_catalog->appendChild($shop);
        // Создаём элемент <name>ABC</name>
        $name = $dom->createElement("name", MyLibHelper::clearString($ymlParams->get("shopname")));
        $shop->appendChild($name);
        // Создаём элемент <company>ABC inc.</company>
        $company = $dom->createElement("company", MyLibHelper::clearString($ymlParams->get("company")));
        $shop->appendChild($company);
        // Создаём элемент <url>http://www.abc.ru/</url>
        $url = $dom->createElement("url", JURI::root());
        $shop->appendChild($url);
        // Создаём элемент <platform>Joomla!</platform>
        $platform = $dom->createElement("platform", 'Joomla!');
        $shop->appendChild($platform);
        // Создаём элемент <version>3.6.5</version>
        $version = $dom->createElement("version", JVERSION);
        $shop->appendChild($version);
        // Создаём элемент <agency>Igor-I-Studio</agency>
        if (!empty($ymlParams->get("agency"))) {
            $agency = $dom->createElement("agency", MyLibHelper::clearString($ymlParams->get("agency")));
            $shop->appendChild($agency);
        }
        // Создаём элемент <email>igor-i-shop@ya.ru</email>
        if (!empty($ymlParams->get("email"))) {
            $email = $dom->createElement("email", MyLibHelper::clearString($ymlParams->get("email")));
            $shop->appendChild($email);
        }
        // Создаём элемент <currencies>
        $currencies = $dom->createElement("currencies");
        $shop->appendChild($currencies);
        // Создаём элемент <currency id="RUB" rate="1"/> - Основная валюта
        $currency1 = $dom->createElement("currency");
        $currencies->appendChild($currency1);
        // Добавляем атрибуты id и rate для currency
        $currency1->setAttribute("id", MyLibHelper::clearString($ymlParams->get("main_currency")));
        $currency1->setAttribute("rate", "1");

        // Создаём элемент <currency id="USD" rate="80"/> - Дополнительные валюты
        $currenciesList = array(
            'usd' => 'USD',
            'eur' => 'EUR',
            'rub' => 'RUB',
            'byn' => 'BYN',
            'uah' => 'UAH',
            'kzt' => 'KZT',
        );
        unset($currenciesList[strtolower($ymlParams->get("main_currency"))]);

        foreach ($currenciesList as $k => $v) {
            if (empty($ymlParams->get("select-rate-$k"))) {
                break;
            }
            $currency[$k] = $dom->createElement("currency");
            $currencies->appendChild($currency[$k]);
            // Добавляем атрибуты id, rate и plus для currency
            $currency[$k]->setAttribute("id", $v);
            switch ($ymlParams->get("select-rate-$k")) {
                case 'manual':
                    $currency[$k]->setAttribute("rate", MyLibHelper::clearString($ymlParams->get("rate-manual-$k")));
                    break;
                default:
                    $currency[$k]->setAttribute("rate", MyLibHelper::clearString($ymlParams->get("select-rate-$k")));
                    if (!empty($ymlParams->get("rate-percent-$k"))) {
                        $currency[$k]->setAttribute(
                            "plus",
                            MyLibHelper::clearString($ymlParams->get("rate-percent-$k"))
                        );
                    }
            }
        }

        // Создаём элемент <categories> (вложенные элементы будут создаваться ниже)
        $categories = $dom->createElement("categories");
        $shop->appendChild($categories);

        // Создаём элемент <delivery-options>
        $delivery_options = $dom->createElement("delivery-options");
        $shop->appendChild($delivery_options);

        // По этому флагу будем проверять ниже, можно ли установить признак "Готов к отправке" (available)
        $deliveryAvailable = false;

        // Создаём элемент <option cost="500" days="0" order-before="15"/>
        for ($i=1; $i<=5; $i++) {
            if (empty($ymlParams->get("delivery-cost-$i"))) {
                continue;
            }

            $option[$i] = $dom->createElement("option");
            $delivery_options->appendChild($option[$i]);

            // Добавляем атрибуты cost, days и order-before для option
            $option[$i]->setAttribute("cost", MyLibHelper::clearString($ymlParams->get("delivery-cost-$i")));
            $deliveryDaysFrom = MyLibHelper::clearString($ymlParams->get("delivery-days-from-$i"));
            $deliveryDaysTo = MyLibHelper::clearString($ymlParams->get("delivery-days-to-$i"));

            if (empty($deliveryDaysFrom)) {
                $deliveryDaysFrom = 0;
            }

            if (empty($deliveryDaysTo)) {
                $deliveryDaysTo = 0;
            }

            if (!$deliveryDaysTo) {
                $option[$i]->setAttribute("days", $deliveryDaysFrom);
            } else {
                $option[$i]->setAttribute("days", $deliveryDaysFrom . "-" . $deliveryDaysTo);
            }

            if (!empty($ymlParams->get("delivery-order-before-$i"))) {
                $option[$i]->setAttribute(
                    "order-before",
                    MyLibHelper::clearString($ymlParams->get("delivery-order-before-$i"))
                );
            }

            // Если доставка менее 3 дней, то признак "Готов к отправке" устанавливаем в true
            if ((int)$deliveryDaysTo < 3) {
                $deliveryAvailable = true;
            }
        }

        // Создаём элемент <cpa>1</cpa>
        $cpa = $dom->createElement("cpa", $ymlParams->get("cpa"));
        $shop->appendChild($cpa);
        // Создаём элемент <offers>
        $offers = $dom->createElement("offers");
        $shop->appendChild($offers);

        $catArray = array();

        // Создаём элемент <offers>
        foreach ($allOffers as $k => $itemOffer) {
            // Проверяем категории товарного предложения, выбираем только одну,
            // при этом она не должна быть выключена в __yandexmarket_categories
            $activeCat = $this->getActiveCategory($itemOffer->categories);

            // Если не нашлось активных категорий, то нужно исключить товар из выборки
            if ($activeCat===false) {
                unset($allOffers[$k]);
                continue;
            }

            // Отобранная категория
            $allOffers[$k]->category = $activeCat;

            if (!empty($itemOffer->category['category_id'])) {
                // Формируем массив категорий для всех товаров,
                // потом ниже из этого массива будем формировать элементы XML
                $cat = $hikashop->getCategory($itemOffer->category['category_id']);
                $catId = (int)$cat->category_id;
                $catArray[$catId] = $cat;

                // Проверяем myname, если есть, то используем его
                if (!empty($itemOffer->category['data']['category_myname'])) {
                    // Сохраняем своё наименование категории для товара
                    $allOffers[$k]->category['category_name'] = $itemOffer->category['data']['category_myname'];
                    // Сохраняем своё наименование для категории
                    $catArray[$catId]->category_name = $itemOffer->category['data']['category_myname'];
                } else {
                    // Сохраняем наименование категории HikaShop для товара
                    $allOffers[$k]->category['category_name'] = $cat->category_name;
                }

                //Проверяем params, если есть, то для блока offers надо использовать их вместо $ymlParams
                $catParams = '';
                if (!empty($itemOffer->category['params'])) {
                    $offersParams = new JRegistry;
                    $catParams = $offersParams->loadString($itemOffer->category['params']);
                }

                // Формируем параметры из настроек категории и настроек YML
                $itemParams = $this->getParams($catParams, $ymlParams);

                // Если не заполнены поля "Тип товара" или "Производитель или бренд",
                // то можно формировать только Упрощённый тип описания, поэтому меняем тип описания
                if (($itemParams['typePrefix-select'] === 'non') || (($itemParams['vendor-select'] === 'non'))) {
                    $itemParams['offer_type'] = 'basic';
                }

                // Проверяем нет ли вариантов
                $hasVariants = is_array($itemOffer->variants) && (count($itemOffer->variants) > 0);

                /////////////////////
                // Теперь переходим к формированию описания товарного предложения (https://yandex.ru/support/partnermarket/offers.html)

                /////////////////////
                // Создаём корневой элемент <offer> (для товаров без вариантов)
                if ($hasVariants === false) {
                    $offer[$k] = $dom->createElement("offer");
                    $offers->appendChild($offer[$k]);

                    // Добавляем атрибуты id="158" available="true" bid="23" cbid="43" для offer
                    $offer[$k]->setAttribute("id", MyLibHelper::clearString($itemOffer->product_id));
                    $offer[$k]->setAttribute("bid", MyLibHelper::clearString($itemParams['bid']));
                    $offer[$k]->setAttribute("cbid", MyLibHelper::clearString($itemParams['cbid']));
                    $offer[$k]->setAttribute("fee", MyLibHelper::clearString($itemParams['fee']));

                    // Добавляем атрибут типа описания товарного предложения
                    switch ($itemParams['offer_type']) {
                        case 'vendor.model':
                            $offer[$k]->setAttribute("type", "vendor.model");
                            break;
                        case 'book':
                            $offer[$k]->setAttribute("type", "book");
                            break;
                        case 'audiobook':
                            $offer[$k]->setAttribute("type", "audiobook");
                            break;
                        case 'artist.title':
                            $offer[$k]->setAttribute("type", "artist.title");
                            break;
                        case 'medicine':
                            $offer[$k]->setAttribute("type", "medicine");
                            break;
                        case 'event-ticket':
                            $offer[$k]->setAttribute("type", "event-ticket");
                            break;
                        case 'tour':
                            $offer[$k]->setAttribute("type", "tour");
                            break;
                    }

                    // Готовим массив элементов для добавления внутрь offer
                    // Обязательные параметры
                    $offerElements = array(
                        'categoryId' => MyLibHelper::clearString($itemOffer->category['category_id']),
                        'currencyId' => MyLibHelper::clearString($itemOffer->currencyId),
                        'price' => MyLibHelper::clearString($itemOffer->price),
                    );

                    // Необязательные параметры
                    if (!empty($itemParams['sales_notes'])) {
                        $offerElements['sales_notes'] = MyLibHelper::clearString($itemParams['sales_notes']);
                    }
                    if (!empty($itemOffer->code)) {
                        $offerElements['vendorCode'] = MyLibHelper::clearString($itemOffer->code);
                    }
                    if (!empty($itemParams['country_of_origin'])) {
                        $offerElements['country_of_origin'] = MyLibHelper::clearString($itemParams['country_of_origin']);
                    }
                    if (!empty($itemParams['cpa'])) {
                        $offerElements['cpa'] = MyLibHelper::clearString($itemParams['cpa']);
                    }
                    if (!empty($itemParams['min-quantity'])) {
                        $offerElements['min-quantity'] = MyLibHelper::clearString($itemParams['min-quantity']);
                    }
                    if (!empty($itemParams['step-quantity'])) {
                        $offerElements['step-quantity'] = MyLibHelper::clearString($itemParams['step-quantity']);
                    }

                    // URL страницы товара на сайте магазина
                    $link = null;
                    $menuItemId = null;
                    $menuData = null;
                    if (!$itemOffer->url_isCanonical) {
                        // Если пункт меню задан для категории или YML, то подставляем в url его
                        $menuItemId = $this->getMenuItemId($yml, $itemOffer);

                        // Если пункт меню не задан, то определяем его автоматически
                        if (empty($menuItemId)) {
                            // Узнаём пункт меню joomla по идентификатору категории
                            $menuData = $menuClass->get($itemOffer->category['category_id']);
                            $menuItemId = $menuClass->getItemidFromCategory($menuData);
                        }

                        // Если пункт меню в конце концов определился, то формируем его в видепараметра get
                        if (!empty($menuItemId)) {
                            $menuItemId = '&Itemid=' . $menuItemId;
                        } else {
                            $menuItemId = '';
                        }

                        // Генерим ЧПУ ссылку
                        $link = $this->buildSefLink($itemOffer->url . $menuItemId);

                    } else {
                        $link = MyLibHelper::clearString($itemOffer->url);
                    }

                    $offerElements['url'] = JURI::root(false, '') . $link;

                    // Старая цена (oldprice)
                    $oldPrice = null;
                    if (!empty($itemOffer->oldPrice)) {
                        $oldPrice = MyLibHelper::clearString($itemOffer->oldPrice);
                        if ((int)$oldPrice < (int)$offerElements['price']) {
                            $offerElements['oldprice'] = $oldPrice;
                        }
                    }

                    // Можно купить в магазине (store)
                    if ($itemParams['store']) {
                        $offerElements['store'] = "true";
                    } else {
                        $offerElements['store'] = "false";
                    }

                    // Возможен самовывоз (pickup)
                    if ($itemParams['pickup']) {
                        $offerElements['pickup'] = "true";
                    } else {
                        $offerElements['pickup'] = "false";
                    }

                    // Возможна доставка (delivery)
                    if ($itemParams['delivery']) {
                        $offerElements['delivery'] = "true";
                    } else {
                        $offerElements['delivery'] = "false";
                    }

                    // Гарантия производителя (manufacturer_warranty)
                    if ($itemParams['manufacturer_warranty']) {
                        $offerElements['manufacturer_warranty'] = "true";
                    } else {
                        $offerElements['manufacturer_warranty'] = "false";
                    }

                    // Сексуальные товары (adult)
                    if ($itemParams['adult']) {
                        $offerElements['adult'] = "true";
                    } else {
                        $offerElements['adult'] = "false";
                    }

                    // Тип товара typePrefix (кроме упрощённого типа описания)
                    switch ($itemParams['typePrefix-select']) {
                        case 'field':
                            $field = $itemParams['typePrefix-field-select'];
                            $offerElements['typePrefix'] =
                                MyLibHelper::clearString($itemOffer->customFields[$field]['value']);
                            break;
                        case 'category':
                            $offerElements['typePrefix'] =
                                MyLibHelper::clearString($itemOffer->category['category_name']);
                            break;
                    }

                    // Производитель или бренд
                    switch ($itemParams['vendor-select']) {
                        case 'field':
                            $field = $itemParams['vendor-field-select'];
                            $offerElements['vendor'] =
                                MyLibHelper::clearString($itemOffer->customFields[$field]['value']);
                            break;
                        case 'manufacturer':
                            $offerElements['vendor'] =
                                MyLibHelper::clearString($itemOffer->vendor);
                            break;
                    }

                    // Модель товара
                    $offerElements['model'] = '';
                    $modelArray = explode("||", $itemParams['model-select']);
                    foreach ($modelArray as $itemModel) {
                        if (!empty($offerElements['model'])) {
                            $offerElements['model'] .= ' ';
                        }
                        if (preg_match('/^(field:)(.+)$/i', $itemModel, $matches)) {
                            if (!empty($itemOffer->customFields[$matches[2]]['name'])) {
                                $offerElements['model'] .= implode(
                                    [
                                        MyLibHelper::clearString($itemOffer->customFields[$matches[2]]['name']),
                                        ": ",
                                        MyLibHelper::clearString($itemOffer->customFields[$matches[2]]['value'])
                                    ]
                                );
                            }
                        } elseif ($itemModel === 'hika_prod_name') {
                            $offerElements['model'] .= MyLibHelper::clearString($itemOffer->name);
                        }
                    }

                    // Наименование товара (для произвольного описания не надо)
                    if ($itemParams['offer_type']!=='vendor.model') {
                        $offerElements['name'] = '';
                        $basicNameArray = explode("||", $itemParams['basicName-select']);
                        foreach ($basicNameArray as $itemBasicName) {
                            if (!empty($offerElements['name'])) {
                                $offerElements['name'] .= ' ';
                            }
                            if (preg_match('/^(field:)(.+)$/i', $itemBasicName, $matches)) {
                                if (!empty($itemOffer->customFields[$matches[2]]['name'])) {
                                    $offerElements['name'] .= implode(
                                        [
                                            MyLibHelper::clearString($itemOffer->customFields[$matches[2]]['name']),
                                            ": ",
                                            MyLibHelper::clearString($itemOffer->customFields[$matches[2]]['value'])
                                        ]
                                    );
                                }
                            } else {
                                switch ($itemBasicName) {
                                    case 'typePrefix':
                                        $offerElements['name'] .=
                                            MyLibHelper::clearString($offerElements['typePrefix']);
                                        break;
                                    case 'vendor':
                                        $offerElements['name'] .= MyLibHelper::clearString($offerElements['vendor']);
                                        break;
                                    case 'model':
                                        $offerElements['name'] .= MyLibHelper::clearString($offerElements['model']);
                                        break;
                                }
                            }
                        }
                    }

                    // Тип товара typePrefix не нужен для упрощённого типа описания, поэтому его надо удалить
                    if ($itemParams['offer_type'] === 'basic') {
                        unset($offerElements['typePrefix']);
                    }

                    // Описание товара
                    if ($itemParams['description']==='description') {
                        if (!empty($itemOffer->description)) {
                            $offerElements['description'] = MyLibHelper::clearString($itemOffer->description, true);
                        }
                    }

                    // Штрихкод
                    switch ($itemParams['barcode-select']) {
                        case 'code':
                            $offerElements['barcode'] = MyLibHelper::clearString($itemOffer->code);
                            break;
                        case 'field':
                            $offerElements['barcode'] =
                                MyLibHelper::clearString($itemOffer->customFields[$itemParams['barcode-field-select']]['value']);
                            break;
                    }

                    // Вес товара
                    if ($itemParams['weight']==='weight') {
                        if (!empty((float)$itemOffer->weight)) {
                            $offerElements['weight'] = (float)($itemOffer->weight);
                        }
                    }

                    // Габариты товара
                    if ($itemParams['dimensions']==='dimensions') {
                        if (!empty($itemOffer->dimensions)) {
                            $offerElements['dimensions'] = MyLibHelper::clearString($itemOffer->dimensions);
                        }
                    }

                    // Рекомендованные товары
                    switch ($itemParams['rec']) {
                        case 'related':
                            $offerElements['rec'] = MyLibHelper::clearString($itemOffer->related);
                            break;
                        case 'options':
                            $offerElements['rec'] = MyLibHelper::clearString($itemOffer->options);
                            break;
                        case 'related-and-options':
                            $offerElements['rec'] = MyLibHelper::clearString($itemOffer->related);
                            $offerElements['rec'] .= ',' . MyLibHelper::clearString($itemOffer->options);
                            break;
                    }

                    // Добавляем элементы из массива в xml
                    $obj = [];
                    foreach ($offerElements as $itemElement => $text) {
                        $obj[$itemElement] = $dom->createElement($itemElement, $text);
                        $offer[$k]->appendChild($obj[$itemElement]);
                    }

                    // Готовим массив элементов Характеристики товара (param)
                    $offerParams[$k] = [];
                    $paramArray = explode("||", $itemParams['param-select']);
                    foreach ($paramArray as $itemParam) {
                        if (preg_match('/^(field:)(.+)$/i', $itemParam, $matches)) {
                            if (!empty($itemOffer->customFields[$matches[2]]['name'])) {
                                $offerParams[$k][] = array(
                                    "value" => MyLibHelper::clearString($itemOffer->customFields[$matches[2]]['value']),
                                    "param" => array(
                                        "name" => MyLibHelper::clearString($itemOffer->customFields[$matches[2]]['name']),
                                    ),
                                );
                            }
                        } else {
                            switch ($itemParam) {
                                case 'dimensions':
                                    if (!empty($itemOffer->dimensions)) {
                                        $dimensions = explode('/', $itemOffer->dimensions);
                                        if (is_array($dimensions)) {
                                            if (!empty($dimensions[0])) {
                                                $offerParams[$k][] = array(
                                                    "value" => MyLibHelper::clearString($dimensions[0]),
                                                    "param" => array(
                                                        "name" => JText::_("COM_YANDEXMARKET_OFFERS_PARAM_LENGTH"),
                                                        "unit" => JText::_("COM_YANDEXMARKET_OFFERS_PARAM_SM"),
                                                    ),
                                                );
                                            }
                                            if (!empty($dimensions[1])) {
                                                $offerParams[$k][] = array(
                                                    "value" => MyLibHelper::clearString($dimensions[1]),
                                                    "param" => array(
                                                        "name" => JText::_("COM_YANDEXMARKET_OFFERS_PARAM_WIDTH"),
                                                        "unit" => JText::_("COM_YANDEXMARKET_OFFERS_PARAM_SM"),
                                                    ),
                                                );
                                            }
                                            if (!empty($dimensions[2])) {
                                                $offerParams[$k][] = array(
                                                    "value" => MyLibHelper::clearString($dimensions[2]),
                                                    "param" => array(
                                                        "name" => JText::_("COM_YANDEXMARKET_OFFERS_PARAM_HEIGHT"),
                                                        "unit" => JText::_("COM_YANDEXMARKET_OFFERS_PARAM_SM"),
                                                    ),
                                                );
                                            }
                                        }
                                    }
                                    break;
                                case 'weight':
                                    if (!empty($itemOffer->weight)) {
                                        $offerParams[$k][] = array(
                                            "value" => MyLibHelper::clearString($itemOffer->weight),
                                            "param" => array(
                                                "name" => JText::_("COM_YANDEXMARKET_OFFERS_PARAM_WEIGHT"),
                                                "unit" => JText::_("COM_YANDEXMARKET_OFFERS_PARAM_KG"),
                                            ),
                                        );
                                    }
                                    break;
                            }
                        }
                    }

                    // Добавляем характеристики товара в xml
                    $obj = array();
                    foreach ($offerParams[$k] as $itemParam => $valueArray) {
                        $obj[$itemParam] = $dom->createElement("param", $valueArray["value"]);
                        $offer[$k]->appendChild($obj[$itemParam]);
                        $obj[$itemParam]->setAttribute("name", $valueArray["param"]["name"]);
                        $obj[$itemParam]->setAttribute("unit", $valueArray["param"]["unit"]);
                    }

                    // Готовим массив элементов picture для offer
                    $obj = array();
                    //добавляем элементы из массива
                    foreach ($itemOffer->picture as $keyPicture => $valuePicture) {
                        $obj[$keyPicture] = $dom->createElement("picture", MyLibHelper::clearString($valuePicture));
                        $offer[$k]->appendChild($obj[$keyPicture]);
                    }

                    // Создаём элемент 'delivery-options' (если заданы индивидуальные условия доставки)
                    $isDeliveryOpt = false;

                    for ($i=1; $i<=5; $i++) {
                        if (empty($itemParams["offers-delivery-cost-$i"])) {
                            continue;
                        }
                        $isDeliveryOpt = true;
                    }

                    // Признак "Готов к отправке"
                    $deliveryAvailableItem = $deliveryAvailable;

                    if ($isDeliveryOpt) {
                        $offer_delivery_options[$k] = $dom->createElement("delivery-options");
                        $offer[$k]->appendChild($offer_delivery_options[$k]);

                        // Создаём элементы <option cost="1000" days="1" order-before="15"/>
                        for ($i=1; $i<=5; $i++) {
                            if (empty($itemParams["offers-delivery-cost-$i"])) {
                                continue;
                            }
                            $offer_option[$k][$i] = $dom->createElement("option");
                            $offer_delivery_options[$k]->appendChild($offer_option[$k][$i]);

                            // Добавляем атрибуты cost, days и order-before для option
                            $offer_option[$k][$i]->setAttribute(
                                "cost",
                                MyLibHelper::clearString($itemParams["offers-delivery-cost-$i"])
                            );

                            $deliveryDaysFrom = MyLibHelper::clearString($itemParams["offers-delivery-days-from-$i"]);
                            $deliveryDaysTo = MyLibHelper::clearString($itemParams["offers-delivery-days-to-$i"]);

                            if (empty($deliveryDaysFrom)) {
                                $deliveryDaysFrom = 0;
                            }

                            if (empty($deliveryDaysTo)) {
                                $deliveryDaysTo = 0;
                            }

                            if (!$deliveryDaysTo) {
                                $offer_option[$k][$i]->setAttribute("days", $deliveryDaysFrom);
                            } else {
                                $offer_option[$k][$i]->setAttribute("days", $deliveryDaysFrom . "-" . $deliveryDaysTo);
                            }

                            if (!empty($itemParams["offers-delivery-order-before-$i"])) {
                                $offer_option[$k][$i]->setAttribute(
                                    "order-before",
                                    MyLibHelper::clearString($itemParams["offers-delivery-order-before-$i"])
                                );
                            }

                            // Если доставка менее 3 дней, то признак "Готов к отправке" устанавливаем в true
                            if ((int)$deliveryDaysTo < 3) {
                                $deliveryAvailableItem = true;
                            }
                        }
                    }

                    // Добавляем атрибут available="true" для offer
                    if ($itemParams['available'] && $deliveryAvailableItem) {
                        $offer[$k]->setAttribute("available", "true");
                    } else {
                        $offer[$k]->setAttribute("available", "false");
                    }

                    // Возрастные ограничения
                    $age = $dom->createElement('age', MyLibHelper::clearString($itemParams['age']));
                    $offer[$k]->appendChild($age);
                    $age->setAttribute("unit", "year");

                    /////////////////////
                    // Если у товара есть варианты, то создаём сразу несколько offer (для каждого варианта)
                } else {
                    foreach ($itemOffer->variants as $varKey => $variant) {
                        $offer[$k][$varKey] = $dom->createElement("offer");
                        $offers->appendChild($offer[$k][$varKey]);

                        // Добавляем атрибуты id="158" bid="23" cbid="43" для offer
                        $offer[$k][$varKey]->setAttribute("id", MyLibHelper::clearString($variant->product_id));
                        $offer[$k][$varKey]->setAttribute("bid", MyLibHelper::clearString($itemParams['bid']));
                        $offer[$k][$varKey]->setAttribute("cbid", MyLibHelper::clearString($itemParams['cbid']));
                        $offer[$k][$varKey]->setAttribute("fee", MyLibHelper::clearString($itemParams['fee']));

                        // Добавляем атрибут group_id = id_родительского_товара
                        // (если включена соответствующая опция в конфиге)
                        if ($itemParams['group']==="1") {
                            $offer[$k][$varKey]->setAttribute(
                                "group_id",
                                MyLibHelper::clearString($itemOffer->product_id)
                            );
                        }

                        // Добавляем атрибут типа описания товарного предложения
                        switch ($itemParams['offer_type']) {
                            case 'vendor.model':
                                $offer[$k][$varKey]->setAttribute("type", "vendor.model");
                                break;
                            case 'book':
                                $offer[$k][$varKey]->setAttribute("type", "book");
                                break;
                            case 'audiobook':
                                $offer[$k][$varKey]->setAttribute("type", "audiobook");
                                break;
                            case 'artist.title':
                                $offer[$k][$varKey]->setAttribute("type", "artist.title");
                                break;
                            case 'medicine':
                                $offer[$k][$varKey]->setAttribute("type", "medicine");
                                break;
                            case 'event-ticket':
                                $offer[$k][$varKey]->setAttribute("type", "event-ticket");
                                break;
                            case 'tour':
                                $offer[$k][$varKey]->setAttribute("type", "tour");
                                break;
                        }

                        // Готовим массив элементов для добавления внутрь offer
                        // Обязательные параметры
                        $offerElements = array(
                            'price' => MyLibHelper::clearString($variant->price),
                            'currencyId' => MyLibHelper::clearString($itemOffer->currencyId),
                            'categoryId' => MyLibHelper::clearString($itemOffer->category['category_id']),
                        );
                        // Необязательные параметры
                        if (!empty($variant->product_code)) {
                            $offerElements['vendorCode'] = MyLibHelper::clearString($variant->product_code);
                        }
                        if (!empty($itemParams['sales_notes'])) {
                            $offerElements['sales_notes'] = MyLibHelper::clearString($itemParams['sales_notes']);
                        }
                        if (!empty($itemParams['country_of_origin'])) {
                            $offerElements['country_of_origin'] = MyLibHelper::clearString($itemParams['country_of_origin']);
                        }
                        if (!empty($itemParams['cpa'])) {
                            $offerElements['cpa'] = MyLibHelper::clearString($itemParams['cpa']);
                        }
                        if (!empty($itemParams['min-quantity'])) {
                            $offerElements['min-quantity'] = MyLibHelper::clearString($itemParams['min-quantity']);
                        }
                        if (!empty($itemParams['step-quantity'])) {
                            $offerElements['step-quantity'] = MyLibHelper::clearString($itemParams['step-quantity']);
                        }

                        // URL страницы товара на сайте магазина
                        $link = null;
                        $menuItemId = null;
                        $menuData = null;
                        if (!$itemOffer->url_isCanonical) {
                            // Если пункт меню задан для категории или YML, то подставляем в url его
                            $menuItemId = $this->getMenuItemId($yml, $itemOffer);

                            // Если пункт меню не задан, то определяем его автоматически
                            if (empty($menuItemId)) {
                                // Узнаём пункт меню joomla по идентификатору категории
                                $menuData = $menuClass->get($itemOffer->category['category_id']);
                                $menuItemId = $menuClass->getItemidFromCategory($menuData);
                            }

                            // Если пункт меню в конце концов определился, то формируем его в видепараметра get
                            if (!empty($menuItemId)) {
                                $menuItemId = '&Itemid=' . $menuItemId;
                            } else {
                                $menuItemId = '';
                            }

                            // Генерим ЧПУ ссылку
                            $link = $this->buildSefLink($itemOffer->url . $menuItemId);
                        } else {
                            $link = MyLibHelper::clearString($itemOffer->url);
                        }

                        $offerElements['url'] = JURI::root(false, '') . $link;

                        // Старая цена (oldprice)
                        $oldPrice = null;
                        if (!empty($variant->oldPrice)) {
                            $oldPrice = MyLibHelper::clearString($variant->oldPrice);
                            if ((int)$oldPrice < (int)$offerElements['price']) {
                                $offerElements['oldprice'] = $oldPrice;
                            }
                        }

                        // Можно купить в магазине (store)
                        if ($itemParams['store']) {
                            $offerElements['store'] = "true";
                        } else {
                            $offerElements['store'] = "false";
                        }

                        // Возможен самовывоз (pickup)
                        if ($itemParams['pickup']) {
                            $offerElements['pickup'] = "true";
                        } else {
                            $offerElements['pickup'] = "false";
                        }

                        // Возможна доставка (delivery)
                        if ($itemParams['delivery']) {
                            $offerElements['delivery'] = "true";
                        } else {
                            $offerElements['delivery'] = "false";
                        }

                        // Гарантия производителя (manufacturer_warranty)
                        if ($itemParams['manufacturer_warranty']) {
                            $offerElements['manufacturer_warranty'] = "true";
                        } else {
                            $offerElements['manufacturer_warranty'] = "false";
                        }

                        // Сексуальные товары (adult)
                        if ($itemParams['adult']) {
                            $offerElements['adult'] = "true";
                        } else {
                            $offerElements['adult'] = "false";
                        }

                        // Тип товара typePrefix
                        $field = null;
                        $characteristic = null;
                        switch ($itemParams['typePrefix-select']) {
                            case 'field':
                                $field = $itemParams['typePrefix-field-select'];
                                if (!empty($variant->customFields[$field]['value'])) {
                                    $offerElements['typePrefix'] =
                                        MyLibHelper::clearString($variant->customFields[$field]['value']);
                                } else {
                                    $offerElements['typePrefix'] =
                                        MyLibHelper::clearString($itemOffer->customFields[$field]['value']);
                                }
                                break;
                            case 'characteristic':
                                $characteristic = $itemParams['typePrefix-characteristic-select'];
                                $offerElements['typePrefix'] =
                                    MyLibHelper::clearString($variant->characteristics[$characteristic]['value']);
                                break;
                            default:
                                $offerElements['typePrefix'] =
                                    MyLibHelper::clearString($itemOffer->category['category_name']);
                        }

                        // Производитель или бренд
                        $field = null;
                        $characteristic = null;
                        switch ($itemParams['vendor-select']) {
                            case 'field':
                                $field = $itemParams['vendor-field-select'];
                                if (!empty($variant->customFields[$field]['value'])) {
                                    $offerElements['vendor'] =
                                        MyLibHelper::clearString($variant->customFields[$field]['value']);
                                } else {
                                    $offerElements['vendor'] =
                                        MyLibHelper::clearString($itemOffer->customFields[$field]['value']);
                                }
                                break;
                            case 'characteristic':
                                $characteristic = $itemParams['vendor-characteristic-select'];
                                $offerElements['vendor'] =
                                    MyLibHelper::clearString($variant->characteristics[$characteristic]['value']);
                                break;
                            default:
                                $offerElements['vendor'] =
                                    MyLibHelper::clearString($itemOffer->vendor);
                        }

                        // Модель товара
                        $offerElements['model'] = '';
                        $modelArray = explode("||", $itemParams['model-select']);
                        foreach ($modelArray as $itemModel) {
                            if (!empty($offerElements['model'])) {
                                $offerElements['model'] .= ' ';
                            }
                            if (preg_match('/^(field:)(.+)$/i', $itemModel, $matches)) {
                                if (!empty($variant->customFields[$matches[2]]['value'])) {
                                    $offerElements['model'] .= implode(
                                        [
                                            MyLibHelper::clearString($variant->customFields[$matches[2]]['name']),
                                            ": ",
                                            MyLibHelper::clearString($variant->customFields[$matches[2]]['value'])
                                        ]
                                    );
                                } elseif (!empty($itemOffer->customFields[$matches[2]]['value'])) {
                                    $offerElements['model'] .= implode(
                                        [
                                            MyLibHelper::clearString($itemOffer->customFields[$matches[2]]['name']),
                                            ": ",
                                            MyLibHelper::clearString($itemOffer->customFields[$matches[2]]['value'])
                                        ]
                                    );
                                }
                            } elseif (preg_match('/^(char:)(.+)$/i', $itemModel, $matches)) {
                                $offerElements['model'] .= implode(
                                    [
                                        MyLibHelper::clearString($variant->characteristics[$matches[2]]['parent_name']),
                                        ": ",
                                        MyLibHelper::clearString($variant->characteristics[$matches[2]]['value'])
                                    ]
                                );
                            } else {
                                $offerElements['model'] .= MyLibHelper::clearString($itemOffer->name);
                            }
                        }

                        // Наименование товара (для произвольного описания не надо)
                        if ($itemParams['offer_type']!=='vendor.model') {
                            $offerElements['name'] = '';
                            $basicNameArray = explode("||", $itemParams['basicName-select']);
                            foreach ($basicNameArray as $itemBasicName) {
                                if (!empty($offerElements['name'])) {
                                    $offerElements['name'] .= ' ';
                                }
                                if (preg_match('/^(field:)(.+)$/i', $itemBasicName, $matches)) {
                                    if (!empty($variant->customFields[$matches[2]]['value'])) {
                                        $offerElements['name'] .= implode(
                                            [
                                                MyLibHelper::clearString($variant->customFields[$matches[2]]['name']),
                                                ": ",
                                                MyLibHelper::clearString($variant->customFields[$matches[2]]['value'])
                                            ]
                                        );
                                    } elseif (!empty($itemOffer->customFields[$matches[2]]['value'])) {
                                        $offerElements['name'] .= implode(
                                            [
                                                MyLibHelper::clearString($itemOffer->customFields[$matches[2]]['name']),
                                                ": ",
                                                MyLibHelper::clearString($itemOffer->customFields[$matches[2]]['value'])
                                            ]
                                        );
                                    }
                                } elseif (preg_match('/^(char:)(.+)$/i', $itemBasicName, $matches)) {
                                    $offerElements['name'] .= implode(
                                        [
                                            MyLibHelper::clearString($variant->characteristics[$matches[2]]['parent_name']),
                                            ": ",
                                            MyLibHelper::clearString($variant->characteristics[$matches[2]]['value'])
                                        ]
                                    );
                                } else {
                                    switch ($itemBasicName) {
                                        case 'typePrefix':
                                            $offerElements['name'] .=
                                                MyLibHelper::clearString($offerElements['typePrefix']);
                                            break;
                                        case 'vendor':
                                            $offerElements['name'] .=
                                                MyLibHelper::clearString($offerElements['vendor']);
                                            break;
                                        case 'model':
                                            $offerElements['name'] .=
                                                MyLibHelper::clearString($offerElements['model']);
                                            break;
                                    }
                                }
                            }
                        }

                        // Тип товара typePrefix не нужен для упрощённого типа описания, поэтому его надо удалить
                        if ($itemParams['offer_type'] === 'basic') {
                            unset($offerElements['typePrefix']);
                        }

                        // Описание товара
                        if ($itemParams['description']==='description') {
                            $offerElements['description'] =
                                MyLibHelper::clearString($variant->product_description, true);
                        }

                        // Штрихкод
                        switch ($itemParams['barcode-select']) {
                            case 'code':
                                $offerElements['barcode'] = MyLibHelper::clearString($variant->product_code);
                                break;
                            case 'field':
                                if (!empty($variant->customFields[$itemParams['barcode-field-select']]['value'])) {
                                    $offerElements['barcode'] =
                                        MyLibHelper::clearString($variant->customFields[$itemParams['barcode-field-select']]['value']);
                                } else {
                                    $offerElements['barcode'] =
                                        MyLibHelper::clearString($itemOffer->customFields[$itemParams['barcode-field-select']]['value']);
                                }
                                break;
                        }

                        // Вес товара
                        if ($itemParams['weight']==='weight') {
                            if (!empty((float)$variant->product_weight)) {
                                $offerElements['weight'] = (float)($variant->product_weight);
                            } elseif (!empty((float)$itemOffer->weight)) {
                                $offerElements['weight'] = (float)($itemOffer->weight);
                            }
                        }

                        // Габариты товара
                        if ($itemParams['dimensions']==='dimensions') {
                            if (!empty($variant->dimensions)) {
                                $offerElements['dimensions'] = MyLibHelper::clearString($variant->dimensions);
                            } elseif (!empty($itemOffer->dimensions)) {
                                $offerElements['dimensions'] = MyLibHelper::clearString($itemOffer->dimensions);
                            }
                        }

                        // Рекомендованные товары
                        switch ($itemParams['rec']) {
                            case 'related':
                                $offerElements['rec'] = MyLibHelper::clearString($itemOffer->related);
                                break;
                            case 'options':
                                $offerElements['rec'] = MyLibHelper::clearString($itemOffer->options);
                                break;
                            case 'related-and-options':
                                $offerElements['rec'] = MyLibHelper::clearString($itemOffer->related);
                                $offerElements['rec'] .= ',' . MyLibHelper::clearString($itemOffer->options);
                                break;
                        }

                        // Добавляем элементы из массива в xml
                        $obj = [];
                        foreach ($offerElements as $itemElement => $text) {
                            $obj[$itemElement] = $dom->createElement($itemElement, $text);
                            $offer[$k][$varKey]->appendChild($obj[$itemElement]);
                        }

                        // Готовим массив элементов Характеристики товара (param)
                        $offerParams[$k][$varKey] = [];
                        $paramArray = explode("||", $itemParams['param-select']);
                        foreach ($paramArray as $itemParam) {
                            if (preg_match('/^(field:)(.+)$/i', $itemParam, $matches)) {
                                if (!empty($variant->customFields[$matches[2]]['name'])) {
                                    $offerParams[$k][$varKey][] = [
                                        "value" => MyLibHelper::clearString($variant->customFields[$matches[2]]['value']),
                                        "param" => [
                                            "name" => MyLibHelper::clearString($variant->customFields[$matches[2]]['name']),
                                        ]
                                    ];
                                } elseif (!empty($itemOffer->customFields[$matches[2]]['name'])) {
                                    $offerParams[$k][$varKey][] = [
                                        "value" => MyLibHelper::clearString($itemOffer->customFields[$matches[2]]['value']),
                                        "param" => [
                                            "name" => MyLibHelper::clearString($itemOffer->customFields[$matches[2]]['name']),
                                        ]
                                    ];
                                }
                            } elseif (preg_match('/^(char:)(.+)$/i', $itemParam, $matches)) {
                                if (!empty($variant->characteristics[$matches[2]]['parent_name'])) {
                                    $offerParams[$k][$varKey][] = array(
                                        "value" => MyLibHelper::clearString($variant->characteristics[$matches[2]]['value']),
                                        "param" => array(
                                            "name" => MyLibHelper::clearString($variant->characteristics[$matches[2]]['parent_name']),
                                        ),
                                    );
                                }
                            } else {
                                switch ($itemParam) {
                                    case 'dimensions':
                                        if (!empty($variant->dimensions)) {
                                            $dimensions = explode('/', $variant->dimensions);
                                        } elseif (!empty($itemOffer->dimensions)) {
                                            $dimensions = explode('/', $itemOffer->dimensions);
                                        }
                                        if (!empty($dimensions) && is_array($dimensions)) {
                                            if (!empty($dimensions[0])) {
                                                $offerParams[$k][$varKey][] = array(
                                                    "value" => MyLibHelper::clearString($dimensions[0]),
                                                    "param" => array(
                                                        "name" => JText::_("COM_YANDEXMARKET_OFFERS_PARAM_LENGTH"),
                                                        "unit" => JText::_("COM_YANDEXMARKET_OFFERS_PARAM_SM"),
                                                    ),
                                                );
                                            }
                                            if (!empty($dimensions[1])) {
                                                $offerParams[$k][$varKey][] = array(
                                                    "value" => MyLibHelper::clearString($dimensions[1]),
                                                    "param" => array(
                                                        "name" => JText::_("COM_YANDEXMARKET_OFFERS_PARAM_WIDTH"),
                                                        "unit" => JText::_("COM_YANDEXMARKET_OFFERS_PARAM_SM"),
                                                    ),
                                                );
                                            }
                                            if (!empty($dimensions[2])) {
                                                $offerParams[$k][$varKey][] = array(
                                                    "value" => MyLibHelper::clearString($dimensions[2]),
                                                    "param" => array(
                                                        "name" => JText::_("COM_YANDEXMARKET_OFFERS_PARAM_HEIGHT"),
                                                        "unit" => JText::_("COM_YANDEXMARKET_OFFERS_PARAM_SM"),
                                                    ),
                                                );
                                            }
                                        }
                                        break;
                                    case 'weight':
                                        $weight = '';
                                        if (!empty($variant->product_width)) {
                                            $weight = $variant->product_width;
                                        } elseif (!empty($itemOffer->weight)) {
                                            $weight = $itemOffer->weight;
                                        }
                                        if (!empty($weight)) {
                                            $offerParams[$k][$varKey][] = array(
                                                "value" => MyLibHelper::clearString($weight),
                                                "param" => array(
                                                    "name" => JText::_("COM_YANDEXMARKET_OFFERS_PARAM_WEIGHT"),
                                                    "unit" => JText::_("COM_YANDEXMARKET_OFFERS_PARAM_KG"),
                                                ),
                                            );
                                        }
                                        break;
                                }
                            }
                        }

                        // Добавляем характеристики товара в xml
                        $obj = array();
                        foreach ($offerParams[$k][$varKey] as $itemParam => $valueArray) {
                            $obj[$itemParam] = $dom->createElement("param", $valueArray["value"]);
                            $offer[$k][$varKey]->appendChild($obj[$itemParam]);
                            $obj[$itemParam]->setAttribute("name", $valueArray["param"]["name"]);
                            $obj[$itemParam]->setAttribute("unit", $valueArray["param"]["unit"]);
                        }

                        // Готовим массив элементов picture для offer
                        $obj = array();
                        //добавляем элементы из массива
                        if (!empty($variant->picture && count($variant->picture))) {
                            $picture = $variant->picture;
                        } else {
                            $picture = $itemOffer->picture;
                        }
                        if (!empty($picture && count($picture))) {
                            foreach ($picture as $keyPicture => $valuePicture) {
                                $obj[$keyPicture] =
                                    $dom->createElement("picture", MyLibHelper::clearString($valuePicture));
                                $offer[$k][$varKey]->appendChild($obj[$keyPicture]);
                            }
                        }

                        // Создаём элемент 'delivery-options' (если заданы индивидуальные условия доставки)
                        $isDeliveryOpt = false;

                        for ($i=1; $i<=5; $i++) {
                            if (empty($itemParams["offers-delivery-cost-$i"])) {
                                continue;
                            }
                            $isDeliveryOpt = true;
                        }

                        // Признак "Готов к отправке"
                        $deliveryAvailableItem = $deliveryAvailable;

                        if ($isDeliveryOpt) {
                            $offer_delivery_options[$k][$varKey] = $dom->createElement("delivery-options");
                            $offer[$k][$varKey]->appendChild($offer_delivery_options[$k][$varKey]);

                            // Создаём элементы <option cost="1000" days="1" order-before="15"/>
                            for ($i=1; $i<=5; $i++) {
                                if (empty($itemParams["offers-delivery-cost-$i"])) {
                                    continue;
                                }
                                $offer_option[$k][$varKey][$i] = $dom->createElement("option");
                                $offer_delivery_options[$k][$varKey]->appendChild($offer_option[$k][$varKey][$i]);

                                // Добавляем атрибуты cost, days и order-before для option
                                $offer_option[$k][$varKey][$i]->setAttribute(
                                    "cost",
                                    MyLibHelper::clearString($itemParams["offers-delivery-cost-$i"])
                                );

                                $deliveryDaysFrom =
                                    MyLibHelper::clearString($itemParams["offers-delivery-days-from-$i"]);
                                $deliveryDaysTo = MyLibHelper::clearString($itemParams["offers-delivery-days-to-$i"]);

                                if (empty($deliveryDaysFrom)) {
                                    $deliveryDaysFrom = 0;
                                }

                                if (empty($deliveryDaysTo)) {
                                    $deliveryDaysTo = 0;
                                }

                                if (!$deliveryDaysTo) {
                                    $offer_option[$k][$varKey][$i]->setAttribute("days", $deliveryDaysFrom);
                                } else {
                                    $offer_option[$k][$varKey][$i]->setAttribute(
                                        "days",
                                        $deliveryDaysFrom . "-" . $deliveryDaysTo
                                    );
                                }

                                if (!empty($itemParams["offers-delivery-order-before-$i"])) {
                                    $offer_option[$k][$varKey][$i]->setAttribute(
                                        "order-before",
                                        MyLibHelper::clearString($itemParams["offers-delivery-order-before-$i"])
                                    );
                                }

                                // Если доставка менее 3 дней, то признак "Готов к отправке" устанавливаем в true
                                if ((int)$deliveryDaysTo < 3) {
                                    $deliveryAvailableItem = true;
                                }
                            }
                        }

                        // Добавляем атрибут available="true" для offer
                        if ($itemParams['available'] && $deliveryAvailableItem) {
                            $offer[$k][$varKey]->setAttribute("available", "true");
                        } else {
                            $offer[$k][$varKey]->setAttribute("available", "false");
                        }

                        // Возрастные ограничения
                        $age = $dom->createElement('age', $itemParams['age']);
                        $offer[$k][$varKey]->appendChild($age);
                        $age->setAttribute("unit", "year");

                    } //foreach ($itemOffer->variants as $varKey=>$variant)
                }

            } //if (!empty($itemOffer->category['category_id']))
        } //foreach ($allOffers as $k=>$itemOffer)

        // Теперь закончим с категориями
        // Дополняем массив категорий родителями
        $catArray = $this->getCategoryElements($catArray);
        unset($catArray[1]);

        // Создаём элементы <category id="3761" parentId="1278">Телевизоры</category>
        foreach ($catArray as $k => $v) {
            if ((int)$k <= 2) {
                continue;
            }
            $category[$k] = $dom->createElement("category", MyLibHelper::clearString($v->category_name));
            $categories->appendChild($category[$k]);
            // Добавляем атрибут id для category
            $category[$k]->setAttribute("id", $k);
            if (!empty($v->category_parent_id) && (int)$v->category_parent_id > 2) {
                // Добавляем атрибут parentId для category
                $category[$k]->setAttribute("parentId", $v->category_parent_id);
                // Формируем отдельно массив топовых родительских категорий
            }
        }

        //сохраняем XML в файле
        $html = $dom->save($pathToFile);
        return $html;
    }

    /**
     *
     * Возвращает информацию по категории для формирования элемента category в XML
     *
     * @param $catId
     * @param $catArray
     * @return int|stdClass
     * @since 0.1.2
     */
    private function getCategoryElementInfo($catId, $catArray)
    {
        // Если в массиве категорий есть искомая, то возвращаем информацию из массива
        if (!empty($catArray[(int)$catId])) {
            $catInfo = new stdClass();
            $catInfo->category_id = (int)$catId;
            $catInfo->category_parent_id = (int)$catArray[(int)$catId]['category_parent_id'];
            $catInfo->category_name = $catArray[(int)$catId]['category_name'];

            // Если в массиве такой категории нет, то выбираем по ней информацию
        } else {
            // Определяем ближайшую активную категорию
            $activeCat = $this->publishedCategory($catId, 0);

            // Если информацию по ближайшей активной категории достали из __yandexmarket_categories, то
            if (!empty($activeCat['data'])) {
                // Достаём идентификатор родительской категории
                $db   = JFactory::getDbo();
                $query = $db->getQuery(true)
                    ->select('category_parent_id')
                    ->from('#__hikashop_category')
                    ->where('category_id = ' . (int)$activeCat['data']['category_id']);
                $parentCategoryId = (int)$db->setQuery($query)->loadResult();

                // Если своего наименования категории в __yandexmarket_categories нет, то достаём из __hikashop_category
                if (!empty($activeCat['data']['category_myname'])) {
                    $categoryName = $activeCat['data']['category_myname'];
                } else {
                    $query = $db->getQuery(true)
                        ->select('category_name')
                        ->from('#__hikashop_category')
                        ->where('category_id = ' . (int)$activeCat['data']['category_id']);
                    $categoryName = $db->setQuery($query)->loadResult();
                }

                // Собираем объект
                $catInfo = new stdClass();
                $catInfo->category_id = (int)$activeCat['data']['category_id'];
                $catInfo->category_name = $categoryName;
                if ($parentCategoryId > 1) {
                    $catInfo->category_parent_id = $parentCategoryId;
                }

                // Если информацию по ближайшей активной категории достали из __hikashop_category, то
            } else {
                // Выбираем в объект всю необходимую инфо
                $db   = JFactory::getDbo();
                $query = $db->getQuery(true)
                    ->select('category_id, category_parent_id, category_name')
                    ->from('#__hikashop_category')
                    ->where('category_id = ' . $activeCat['category_id']);
                $res = $db->setQuery($query)->loadObject();

                // Собираем объект
                $catInfo = new stdClass();
                $catInfo->category_id = (int)$res->category_id;
                $catInfo->category_name = $res->category_name;
                if ((int)$res->category_parent_id > 1) {
                    $catInfo->category_parent_id = (int)$res->category_parent_id;
                }
            }
        }

        return $catInfo;
    }

    /**
     * Рекурсивно дополняет массив с категориями информацией о родительских категориях,
     * при этом ещё заменяет родительскую категорию на ближайшую активную
     *
     * @param $catId
     * @param $catArray
     * @return mixed
     * @since 0.1.2
     */
    private function addCategoryElement($catId, $catArray)
    {
        if (empty($catArray[(int)$catId])) {
            $res = $this->getCategoryElementInfo($catId, $catArray);

            // Если вернулся другой идентификатор категории, чем тот с которым вызывали функцию,
            // то это значит, что данная родительская категория не активна и нужно заменить её на ближайшую активную
            // категорию, для этого в массиве меняем всех родителей на вернувшийся идентификатор
            if ((int)$catId!==(int)$res->category_id) {
                foreach ($catArray as $k => $v) {
                    if ((int)$catId===(int)$v->category_parent_id) {
                        $catArray[$k]->category_parent_id = $res->category_id;
                    }
                }
            }

            // Добавляем в массив нового родителя
            $catArray[$res->category_id] = $res;

            // И проверяем родителя родителя
            if (!empty($catArray[(int)$catId]->category_parent_id)) {
                $catArray = $this->addCategoryElement($catArray[(int)$catId]->category_parent_id, $catArray);
            }
        }

        return $catArray;
    }

    /**
     * Дополняет массив категорий родительскими категориями
     *
     * @param $catArray
     * @return mixed
     * @since 0.1.2
     */
    private function getCategoryElements($catArray)
    {
        foreach ($catArray as $k => $itemCat) {
            $catArray = $this->addCategoryElement($itemCat->category_parent_id, $catArray);
        }

        return $catArray;
    }

    /**
     * Готовит массив с параметрами Яндекс.Маркет (выбирает из конфига плагина и параметров категории)
     *
     * @param $catParams
     * @param $ymlParams
     * @return mixed
     * @since 0.1
     */
    private function getParams($catParams, $ymlParams)
    {
        //выбираем параметры из конфига плагина
        $resultParams = $ymlParams;

        //выбираем параметры товарных предложений из настроек категории
        if (!empty($catParams)) {
            foreach ($catParams->offersParams as $k => $v) {
                $resultParams[$k] = $v;
            }
        }

        return $resultParams;
    }

    /**
     * Проверяет опубликована ли категория и если нет, то рекурсивно проверяет и её родителей
     *
     * @param $categoryId
     * @param $level
     * @return array
     * @since 0.1
     */
    private function publishedCategory($categoryId, $level)
    {
        if ((int)$categoryId===0) {
            return array (
                'category_id' => (int)$categoryId,
                'level' => (int)$level
            );
        }

        $db   = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__yandexmarket_categories')
            ->where('category_id = ' . (int)$categoryId);
        $row = $db->setQuery($query)->loadAssoc();

        // Если ничего не выбралось, значит используем информацию по этой категории из __hikashop_category
        if (empty($row)) {
            return array (
                'category_id' => (int)$categoryId,
                'level' => (int)$level
            );

            // Если что-то выбралось и категория опубликованна,
            //то возвращаем информацию по этой категории из __yandexmarket_categories
        } elseif (!empty($row['published']) && $row['published']==1) {
            return array (
                'category_id' => (int)$categoryId,
                'level' => (int)$level,
                'data' => $row
            );

            // Если что-то выбралось и категория не опубликованна, то проверяем родительскую категорию
        } else {
            $query = $db->getQuery(true)
                ->select('category_parent_id')
                ->from('#__hikashop_category')
                ->where('category_id = ' . (int)$categoryId);
            $parentCategoryId = (int)$db->setQuery($query)->loadResult();
            $level++;
            return $this->publishedCategory($parentCategoryId, $level);
        }
    }

    /**
     * Выбирает только одну активную категорию из множества категорий товарного предложения
     *
     * @param $categories
     * @return bool
     * @since 0.1
     */
    private function getActiveCategory($categories)
    {
        $excludeProd = false;
        $categoriesSort = array();
        foreach ($categories as $category) {
            // Проверяем нет ли этой категории в __yandexmarket_categories
            $db   = JFactory::getDbo();
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from('#__yandexmarket_categories')
                ->where('category_id = ' . (int)$category->category_id);
            $count = (int)$db->setQuery($query)->loadResult();

            //Если такая категория есть в __yandexmarket_categories, то достаём её
            if ($count!==0) {
                $row = $this->publishedCategory((int)$category->category_id, 0);

                // Если это корневая категория, то она нам не подходит, переходим к следующей
                if ($row['category_id']===1 || $row['category_id']===0) {
                    $excludeProd = true;
                    continue;
                }

                // Формируем массив подходящих категорий
                $categoriesSort[$row['level']] = $row;

                // Если такой категории в __yandexmarket_categories нет, то берём категорию hikashop
            } else {
                // Формируем массив подходящих категорий
                $categoriesSort[0] = array(
                    'category_id' => (int)$category->category_id,
                );
            }
            $excludeProd = false;
        }

        // Если подходящих категорий не нашлось, то возвращаем неудачу
        if ($excludeProd===true) {
            return false;
        }

        // Если категории есть, то нужно отобрать только одну наиболее подходящую
        foreach ($categoriesSort as $itemCat) {
            if (!empty($itemCat['category_id'])) {
                // Возвращаем одну категорию, которую будем использовать для этого товара
                return $itemCat;
            }
        }

        // Если что-то пошло не так
        return false;
    }

    /**
     * Возвращает идентификатор пункта меню Joomla из конфига YML или из конфига категории
     *
     * @param $yml
     * @param $itemOffer
     * @return int
     * @since 0.5.0
     */
    private function getMenuItemId($yml, $itemOffer)
    {
        if (!empty($itemOffer->category['data']['category_menuitem'])) {
            return (int)$itemOffer->category['data']['category_menuitem'];
        } else {
            return (int)$yml->yml_menuitem;
        }
    }

    /**
     * Преобразует ссылку вида
     * index.php?option=com_hikashop&ctrl=product&task=show&name=byaz-ayvengo&cid=5613&Itemid=509
     * в ЧПУ ссылку
     *
     * @param $link
     * @return mixed|string
     * @since 0.6.0
     */
    public function buildSefLink($link)
    {
        JRoute::_('');
        $routerOptions = [];
        if (JFactory::getConfig()->get('sef')) {
            $routerOptions['mode'] = JROUTER_MODE_SEF;
        }
        $siteRouter = JRouter::getInstance('site', $routerOptions);
        $uri = $siteRouter->build($link)->toString();
        $uri = preg_replace('#^/administrator/#', '', $uri);

        return $uri;
    }
} //YandexMarketControllerFile
