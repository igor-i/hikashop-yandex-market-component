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
    <script type="text/javascript">
        function deleteXmlFile<?=$this->item->id?>() {
            if (confirm ('<?php echo JText::_('COM_YANDEXMARKET_DELETE_QUERY_STRING'); ?>')) {
				<?php if(version_compare(JVERSION, '3', '>=')) : ?>
                jQuery('input[name=task]').val('file.delete');
                jQuery('input[name=ymlid]').val('<?=$this->item->id?>');
                jQuery('#adminForm').submit();
				<?php else : ?>
                jQuery('input[name=task]').setProperty('value', 'file.delete');
                jQuery('input[name=ymlid]').setProperty('value', '<?=$this->item->id?>');
                jQuery('adminForm').submit();
				<?php endif; ?>
            }
        }
        function remakeXmlFile<?=$this->item->id?>() {
	        <?php if(version_compare(JVERSION, '3', '>=')) : ?>
            jQuery('input[name=task]').val('file.remake');
            jQuery('input[name=ymlid]').val('<?=$this->item->id?>');
            jQuery('#adminForm').submit();
	        <?php else : ?>
            jQuery('input[name=task]').setProperty('value', 'file.remake');
            jQuery('input[name=ymlid]').setProperty('value', '<?=$this->item->id?>');
            jQuery('adminForm').submit();
	        <?php endif; ?>
        }
    </script>
<span>
    <a
            class="btn btn-default btn-mini"
            onclick="remakeXmlFile<?=$this->item->id?>()">
            <?php echo JText::_('COM_YANDEXMARKET_XML_ACTION_REMAKE') ?>
    </a>
    <a
            class="btn btn-default btn-mini"
            onclick="deleteXmlFile<?=$this->item->id?>()">
            <?php echo JText::_('JACTION_DELETE') ?>
    </a>
</span>

<?php else : ?>
<script type="text/javascript">
    function createXmlFile<?=$this->item->id?>() {
	<?php if(version_compare(JVERSION, '3', '>=')) : ?>
        jQuery('input[name=task]').val('file.create');
        jQuery('input[name=ymlid]').val('<?=$this->item->id?>');
        jQuery('#adminForm').submit();
	<?php else : ?>
        jQuery('input[name=task]').setProperty('value', 'file.create');
        jQuery('input[name=ymlid]').setProperty('value', '<?=$this->item->id?>');
        jQuery('adminForm').submit();
	<?php endif; ?>
    }
</script>
<span>
    <a
            class="btn btn-default btn-mini"
            onclick="createXmlFile<?=$this->item->id?>()">
		<?php echo JText::_('JACTION_CREATE') ?>
    </a>
</span>
<?php endif; ?>
