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
$params = JComponentHelper::getParams('com_yandexmarket');
$password = $params->get('password');
if (!empty($this->item->file['url'])) :
?>

<span class="yandexmarket-link">
    <a
            href="<?php echo $baseUrl . DS . 'index.php?option=com_yandexmarket&view=xml&ymlid=' . $this->item->id . '&pass=' . $password; ?>"
            target="_blank"
            title="<?php echo JText::_('COM_YANDEXMARKET_XML_LINK_TOOLTIP', true); ?>">
            <?php echo JText::_('COM_YANDEXMARKET_XML_LINK'); ?>
    </a>
    <span class="icon-new-tab"></span>
</span>

<br/>

<?php else : ?>
    <span>
        <small>
            <?php echo JText::_('COM_YANDEXMARKET_XML_FILE_MISSING'); ?>
        </small>
    </span>
<?php endif; ?>
