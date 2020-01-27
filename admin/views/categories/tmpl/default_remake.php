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

if (!empty($this->item->params)) :
?>
    <script type="text/javascript">
        function deleteOffersParams<?=$this->item->id?>() {
            if (confirm ('<?php echo JText::_('COM_YANDEXMARKET_DELETE_OFFERS_QUERY_STRING'); ?>')) {
				<?php if(version_compare(JVERSION, '3', '>=')) : ?>
                jQuery('input[name=task]').val('offers.delete');
                jQuery('input[name=category_id]').val('<?=$this->item->id?>');
                jQuery('#adminForm').submit();
				<?php else : ?>
                jQuery('input[name=task]').setProperty('value', 'offers.delete');
                jQuery('input[name=category_id]').setProperty('value', '<?=$this->item->id?>');
                jQuery('adminForm').submit();
				<?php endif; ?>
            }
        }
        function editOffersParams<?=$this->item->id?>() {
	        <?php if(version_compare(JVERSION, '3', '>=')) : ?>
            jQuery('input[name=task]').val('offers.create');
            jQuery('input[name=category_id]').val('<?=$this->item->id?>');
            jQuery('#adminForm').submit();
	        <?php else : ?>
            jQuery('input[name=task]').setProperty('value', 'offers.create');
            jQuery('input[name=category_id]').setProperty('value', '<?=$this->item->id?>');
            jQuery('adminForm').submit();
	        <?php endif; ?>
        }
    </script>
<span>
    <a
            class="btn btn-default btn-mini"
            onclick="editOffersParams<?=$this->item->id?>()">
            <?php echo JText::_('JACTION_EDIT') ?>
    </a>
    <a
            class="btn btn-default btn-mini"
            onclick="deleteOffersParams<?=$this->item->id?>()">
            <?php echo JText::_('JACTION_DELETE') ?>
    </a>
</span>

<?php else : ?>
<script type="text/javascript">
    function createOffersParams<?=$this->item->id?>() {
	<?php if(version_compare(JVERSION, '3', '>=')) : ?>
        jQuery('input[name=task]').val('offers.create');
        jQuery('input[name=category_id]').val('<?=$this->item->id?>');
        jQuery('#adminForm').submit();
	<?php else : ?>
        jQuery('input[name=task]').setProperty('value', 'offers.create');
        jQuery('input[name=category_id]').setProperty('value', '<?=$this->item->id?>');
        jQuery('adminForm').submit();
	<?php endif; ?>
    }
</script>
<span>
    <a
            class="btn btn-default btn-mini"
            onclick="createOffersParams<?=$this->item->id?>()">
		<?php echo JText::_('JACTION_CREATE') ?>
    </a>
</span>
<?php endif; ?>
