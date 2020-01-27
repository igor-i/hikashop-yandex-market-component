<?php
/**
 * @package   Yandex.Market for HikaShop
 * @subpackage   com_yandexmarket
 * @author   Igor Inkovskiy
 * @copyright   Copyright (C) 2017 Igor Inkovskiy. All rights reserved.
 * @contact   https://shop.igor-i.ru, igor-i-shop@ya.ru
 * @license   Beerware
 */
defined('_JEXEC') or die();

class MyLibBase extends \JViewLegacy
{
    protected $state = null;

    public function __construct($config = array())
    {
        parent::__construct($config);
    }

    /**
     * Admin display
     *
     * @param null $tpl
     * @throws Exception
     * @return void
     * @since 0.1
     */
    public function display($tpl = null)
    {
        $hide    = JFactory::getApplication()->input->getBool('hidemainmenu', false);
        $sidebar = count(\JHtmlSidebar::getEntries()) + count(\JHtmlSidebar::getFilters());
        if (!$hide && $sidebar > 0) {
            $start = array(
                '<div id="j-sidebar-container" class="span2">',
                \JHtmlSidebar::render(),
                '</div>',
                '<div id="j-main-container" class="span10">'
            );

        } else {
            $start = array('<div id="j-main-container">');
        }

        echo join("\n", $start) . "\n";
        parent::display($tpl);
        echo "\n</div>";
    }

    /**
     * Default admin screen title
     *
     * @param string $sub
     * @param string $icon
     * @return void
     * @since 0.1
     */
    public static function setTitle($sub = null, $icon = 'yandexmarket')
    {
        $img = JHtml::_('image', 'com_yandexmarket/' . $icon . '-25x25.png', null, null, true, true);

        if ($img) {
            $doc = JFactory::getDocument();
            $doc->addStyleDeclaration('.icon-' . $icon . ' {
                background-image: url(' . $img . ');
                width: 25px;
                height: 25px;
                margin-right: 5px!important;
                position: relative;
                top: 5px;
            }');
        }

        $title = JText::_('COM_YANDEXMARKET');
        if ($sub) {
            $title .= ': ' . JText::_($sub);
        }

        JToolbarHelper::title($title, $icon);
    }

    /**
     * Display a standard footer on all admin pages
     * @return string
     * @since 0.1
     */
    public static function displayFooter()
    {
        //Устанавливаем годы в копирайте
        if (date('Y') === '2017') {
            $year = date('Y');
        } else {
            $year = '2017-' . date('Y');
        }

        //Подгружаем стили
        $mediaPath = JPATH_SITE . '/media/com_yandexmarket';
        $mediaURI  = JURI::root() . 'media/com_yandexmarket';
        $logoURL   = $mediaURI . "/images/igor-i-logo-25x25.jpg";
        $logoYaMaURL   = $mediaURI . "/images/yandexmarket-16x16.png";
        $html = MyLibHelper::getStyle($mediaPath . '/css/field_customfooter.css');

        //Узнаём номер версии компонента
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('manifest_cache')
            ->from($db->qn('#__extensions'))
            ->where('name = ' . $db->quote('com_yandexmarket'));
        $db->setQuery($query);
        $manifest_cache = $db->loadResult();
        $manifest = json_decode($manifest_cache);

        //Блок с футером
        $html .='
        <hr class="hr"/>
        <div class="igori-footer row-fluid">';

        //JED Link
//        $html .='
//            <div class="span-12">
//                <div class="igori-jedlink">' .
//                    JText::_("IGORI_FOOTER_LIKE_THIS_EXTENSION") . '&nbsp;' .
//                    '<a href="" target="_blank">' .
//                        JText::_("IGORI_FOOTER_LEAVE_A_REVIEW_ON_JED") .  "</a>&nbsp;" .
//                        str_repeat("<i class=\"icon-star\"></i>", 5) .
//                '</div>';

        //Component name
        $html .='
                <div class="component-name">
                    <img src="' . $logoYaMaURL . '" />
                    Yandex.Market ' . $manifest->version . ' <small>for HikaShop, Joomla!™</small>
                </div>';

        $html .='
                <div class="parent-igori-poweredby">
                    <div class="block-igori-poweredby">';

        //Powered by
        $html .='
                        <div class="igori-poweredby">
                            <small>Powered by</small>
                            <a href="https://shop.igor-i.ru" target="_blank">
                                <img class="igori-logo" src="' . $logoURL . '" />
                                shop.igor-i.ru
                            </a>
                        </div>';
        //Copyright
        $html .='
                <div class="igori-copyright">&copy; ' . $year . ' Igor Inkovskiy. All rights reserved.</div>
            </div>';

        $html .='
                    </div>
                </div>
            </div>';

        return $html;
    }

    /**
     * Конфигурирует подменю.
     *
     * @param   string  $viewName  Активный пункт меню.
     * @return  void
     * @since 0.1
     */
    public static function addSubmenu($viewName)
    {
        $submenus = array(
            array(
                'text' => 'COM_YANDEXMARKET_SUBMENU_YMLS',
                'link' => 'index.php?option=com_yandexmarket&view=ymls',
                'view' => 'ymls'
            ),
            array(
                'text' => 'COM_YANDEXMARKET_SUBMENU_CATEGORIES',
                'link' => 'index.php?option=com_yandexmarket&view=categories',
                'view' => 'categories'
            )
        );

        if (!empty($submenus)) {
            foreach ($submenus as $submenu) {
                if (is_array($submenu)) {
                    \JHtmlSidebar::addEntry(
                        \JText::_($submenu['text']),
                        $submenu['link'],
                        $viewName == $submenu['view']
                    );
                }
            }
        }
    }
} //Base
