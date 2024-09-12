<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Module\Stream\Domain\PostGateway;
use Gibbon\Module\Stream\Domain\PostAttachmentGateway;
use Gibbon\Module\Stream\Domain\CategoryGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Stream/posts_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $streamPostID = $_GET['streamPostID'] ?? '';

    $page->breadcrumbs
        ->add(__m('Manage Posts'), 'posts_manage.php')
        ->add(__m('Edit Post'));

    if (empty($streamPostID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $container->get(PostGateway::class)->getByID($streamPostID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('post', $session->get('absoluteURL').'/modules/'.$session->get('module').'/posts_manage_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('streamPostID', $streamPostID);

    $postLength = $container->get(SettingGateway::class)->getSettingByScope('Stream', 'postLength');
    $col = $form->addRow()->addColumn();
        $col->addLabel('post', __('Post'));
        $col->addTextArea('post')->required()->setRows(6)->addClass('font-sans text-sm')->maxLength($postLength);

    // ATTACHMENTS
    $absoluteURL = $session->get('absoluteURL');
    $attachments = $container->get(PostAttachmentGateway::class)->selectAttachmentsByPost($streamPostID)->fetchAll();

    if (!empty($attachments)) {
        $table = $form->addRow()->addDataTable('attachments')->withData(new DataSet($attachments));
        $table->addColumn('attachment', __('Attachment'))
            ->width('12%')
            ->format(function ($attachment) use ($absoluteURL) {
                return sprintf('<div class="rounded overflow-hidden w-16 h-16 flex justify-center"><img class="h-full" src="%1$s"></div>', $absoluteURL.'/'.$attachment['thumbnail']);
            });

        $table->addColumn('file', __('File'))
            ->format(function ($attachment) use ($absoluteURL) {
                return Format::link($absoluteURL.'/'.$attachment['attachment'], $attachment['attachment'], ['target' => '_blank']);
            });

        $table->addActionColumn()
            ->addParam('streamPostID', $streamPostID)
            ->addParam('streamPostAttachmentID')
            ->format(function ($attachment, $actions) {
                $actions->addAction('deleteInstant', __('Delete'))
                    ->setURL('/modules/Stream/posts_manage_edit_deleteProcess.php')
                    ->addConfirmation(__('Are you sure you wish to delete this record?'))
                    ->setIcon('garbage')
                    ->directLink();
            });
    }


    $row = $form->addRow();
        $row->addLabel('attachments', __('Attachments'));
        $row->addFileUpload('attachments')->accepts('.jpg,.jpeg,.gif,.png')->uploadMultiple(true);

    // CATEGORIES
    $categoryGateway = $container->get(CategoryGateway::class);
    $categories = $categoryGateway->selectPostableCategoriesByRole($session->get('gibbonRoleIDCurrent'))->fetchKeyPair();

    if (!empty($categories)) {
        $values['streamCategoryIDList'] = explode(',', $values['streamCategoryIDList']);
        $row = $form->addRow();
            $row->addLabel('streamCategoryIDList', __('Categories'));
            $row->addCheckbox('streamCategoryIDList')->fromArray($categories);
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();
        
    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
