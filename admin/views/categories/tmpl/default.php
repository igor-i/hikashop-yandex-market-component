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

//JHtml::addIncludePath(YANDEXMARKET_ADMIN_PATH . '/helpers/html');
// Загружаем тултипы.
JHtml::_('bootstrap.tooltip');
JHtml::_('formbehavior.chosen', 'select');
JHTML::_('behavior.modal', 'a.modal');

JHtml::stylesheet('media/com_yandexmarket/css/admin.min.css');

$token = JSession::getFormToken();
JFactory::getDocument()->addScriptDeclaration('var rootUrl = "' . JUri::root() . '";');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDir   = $this->escape($this->state->get('list.direction'));

$js = <<<HTML
function loadCatMyName(element) {
    var element = jQuery(element);
    var catId = element.attr('data-id');
    var url = rootUrl + 'administrator/index.php?option=com_yandexmarket&view=category_myname&category_id=' + catId;
    SqueezeBox.open(url, {
        handler: 'ajax',
        size: {x: 580, y: 150}
    });
}

function saveCatMyName(catId) {
    var mynameLink = '<a href="#" onclick="loadCatMyName(this); return false;" data-id="' + catId + '">' + jQuery('#jform_category_myname').val() + ' <span class="icon-edit"></span></a>'
    var form = jQuery('#form_category_myname_' + catId);
    var tr = jQuery('#yandexmarket_category_' + catId);
    var data = form.serialize();
    jQuery.ajax({
        url: form.attr('action'),
        data: data,
        method: 'POST',
        dataType: 'json'
    }).done(function(data) {
        if(data.error == 1) {
            alert(data.msg);
            SqueezeBox.close();
        } else {
            tr.find('.category_myname').html(mynameLink);
            SqueezeBox.close();
        }
    });
}

function loadCatMenuItem(element) {
    var element = jQuery(element);
    var catId = element.attr('data-id');
    var url = rootUrl + 'administrator/index.php?option=com_yandexmarket&view=category_menuitem&category_id=' + catId;
    SqueezeBox.open(url, {
        handler: 'ajax',
        size: {x: 580, y: 150}
    });
}

function saveCatMenuItem(catId) {
    var menuitemId = jQuery('#jform_category_menuitem').val();
    if (!empty(menuitemId)) {
        var menuitemTitle = jQuery('#jform_category_menuitem option[value = ' + menuitemId + ']').text();
        var menuitemLink = '<a href="#" onclick="loadCatMenuItem(this); return false;" data-id="' + catId + '"><small>' + menuitemId + ': ' + menuitemTitle + '</small> <span class="icon-edit"></span></a>'
    } else {
        var menuitemLink = '<a href="#" onclick="loadCatMenuItem(this); return false;" data-id="' + catId + '"><span class="icon-edit"></span></a>'
    }
    var form = jQuery('#form_category_menuitem_' + catId);
    var tr = jQuery('#yandexmarket_category_' + catId);
    var data = form.serialize();
    jQuery.ajax({
        url: form.attr('action'),
        data: data,
        method: 'POST',
        dataType: 'json'
    }).done(function(data) {
        if(data.error == 1) {
            alert(data.msg);
            SqueezeBox.close();
        } else {
            tr.find('.category_menuitem').html(menuitemLink);
            SqueezeBox.close();
        }
    });
}

function empty(mixed_var) {
    return (
        mixed_var === "" ||
        mixed_var === 0   ||
        mixed_var === "0" ||
        mixed_var === null  ||
        mixed_var === false  ||
        (is_array(mixed_var) && mixed_var.length === 0)
    );
}

function is_array(mixed_var) {
    return (mixed_var instanceof Array);
}


HTML;

JFactory::getDocument()->addScriptDeclaration($js);

?>
<form
        action="<?php echo JRoute::_('index.php?option=com_yandexmarket&view=categories'); ?>"
        method="post"
        name="adminForm"
        id="adminForm">

    <?php
    echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>

        <p>
            <div class="tab-description alert alert-info">
                <span class="icon-info"></span>
                <?php echo JText::_('COM_YANDEXMARKET_CATEGORY_SUBMENU_DESC'); ?>
            </div>
        </p>

        <div id="j-main-container">
            <table class="adminlist table table-striped" id="categoryList">
                <thead>
                <tr>
                    <th width="1%">
                        <?php echo JHtml::_('grid.checkall'); ?>
                    </th>

                    <th width="1%" style="min-width:55px" class="nowrap center">
                        <?php echo JHtml::_(
                            'searchtools.sort',
                            'COM_YANDEXMARKET_HEADING_STATUS',
                            'cat.published',
                            $listDir,
                            $listOrder
                        ); ?>
                    </th>

                    <th width="1%" class="nowrap">
                        <?php echo JHtml::_(
                            'searchtools.sort',
                            'COM_YANDEXMARKET_HEADING_PARENT_CATEGORY_ID',
                            'hika.category_parent_id',
                            $listDir,
                            $listOrder
                        ); ?>
                    </th>

                    <th class="title">
                        <?php
                        echo JHtml::_(
                            'searchtools.sort',
                            'COM_YANDEXMARKET_HEADING_HIKASHOP_CATEGORY_NAME',
                            'hika.category_name',
                            $listDir,
                            $listOrder
                        );
                        ?>
                    </th>

                    <th class="title">
                        <?php
                        echo JHtml::_(
                            'searchtools.sort',
                            'COM_YANDEXMARKET_HEADING_MY_CATEGORY_NAME',
                            'cat.category_myname',
                            $listDir,
                            $listOrder
                        );
                        ?>
                    </th>

                    <th class="title">
                        <?php
                        echo JHtml::_(
                            'searchtools.sort',
                            'COM_YANDEXMARKET_HEADING_MENU_ITEM',
                            'cat.category_menuitem',
                            $listDir,
                            $listOrder
                        );
                        ?>
                    </th>

                    <th width="260" class="center">
		                <?php echo JText::_('COM_YANDEXMARKET_HEADING_OFFERS_SETTINGS'); ?>
                    </th>

                    <th width="1%" class="nowrap">
                        <?php
                        echo JHtml::_(
                            'searchtools.sort',
                            'COM_YANDEXMARKET_HEADING_ID',
                            'hika.category_id',
                            $listDir,
                            $listOrder
                        );
                        ?>
                    </th>
                </tr>
                </thead>

                <tbody>
                <?php
                foreach ($this->items as $i => $this->item) :
                    if ($this->item->published===null) $this->item->published = "1"; ?>
                    <tr class="<?php echo 'row' . ($i % 2); ?>" id="yandexmarket_category_<?=(int)$this->item->id;?>">
                        <td class="center">
                            <?php echo JHtml::_('grid.id', $i, $this->item->id); ?>
                        </td>

                        <td class="center category_published">
                            <div class="btn-group">
                                <?php echo JHtml::_('jgrid.published', $this->item->published, $i,'categories.'); ?>
                            </div>
                        </td>

                        <td class="category_parent_id">
                            <small>
                            <?php echo (int)$this->item->parent_id; ?>:
                            <?php echo $this->item->parent_name; ?>
                            </small>
                        </td>

                        <td class="category_name">
                            <?php echo $this->item->name; ?>
                        </td>

                        <td class="nowrap category_myname">
                            <?php echo $this->loadTemplate('edit'); ?>
                        </td>

                        <td class="nowrap category_menuitem">
                            <?php echo $this->loadTemplate('edit_menuitem'); ?>
                        </td>

                        <td class="center">
                            <?php echo $this->loadTemplate('remake'); ?>
                        </td>

                        <td class="center category_id">
                            <?php echo (int)$this->item->id; ?>
                        </td>
                    </tr>
                    <?php
                endforeach;
                ?>
                </tbody>

                <tfoot>
                    <tr>
                        <td colspan="7">
                            <?php echo $this->pagination->getListFooter(); ?>
                        </td>
                    </tr>
                </tfoot>

            </table>
        </div>
    <div>
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" name="category_id" value=""/>
        <?php echo JHtml::_('form.token'); ?>

    </div>
</form>
<?=$this->footer;?>
