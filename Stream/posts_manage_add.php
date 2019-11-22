<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Stream\Domain\CategoryGateway;

if (isActionAccessible($guid, $connection2, '/modules/Stream/posts_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Manage Posts'), 'posts_manage.php')
        ->add(__m('Add Post'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Stream/posts_manage_edit.php&streamPostID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $form = Form::create('post', $gibbon->session->get('absoluteURL').'/modules/'.$gibbon->session->get('module').'/posts_manage_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $gibbon->session->get('address'));

    $postLength = $container->get(SettingGateway::class)->getSettingByScope('Stream', 'postLength');
    $col = $form->addRow()->addColumn();
        $col->addLabel('post', __('Post'));
        $col->addTextArea('post')->required()->setRows(6)->addClass('font-sans text-sm')->maxLength($postLength);

    $row = $form->addRow();
        $row->addLabel('attachments', __('Attachments'));
        $row->addFileUpload('attachments')->accepts('.jpg,.jpeg,.gif,.png')->uploadMultiple(true);

    //Categories
    $categoryGateway = $container->get(CategoryGateway::class);
    $categories = $categoryGateway->selectPostableCategoriesByRole($gibbon->session->get('gibbonRoleIDCurrent'))->fetchKeyPair();

    if (!empty($categories)) {
        $row = $form->addRow();
            $row->addLabel('streamCategoryIDList', __('Categories'));
            $row->addCheckbox('streamCategoryIDList')->fromArray($categories);
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
