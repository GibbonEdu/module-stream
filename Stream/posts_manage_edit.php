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
use Gibbon\Module\Stream\Domain\PostGateway;
use Gibbon\Module\Stream\Domain\PostAttachmentGateway;
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

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    if (empty($streamPostID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $container->get(PostGateway::class)->getByID($streamPostID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('post', $gibbon->session->get('absoluteURL').'/modules/'.$gibbon->session->get('module').'/posts_manage_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $gibbon->session->get('address'));
    $form->addHiddenValue('streamPostID', $streamPostID);

    $postLength = $container->get(SettingGateway::class)->getSettingByScope('Stream', 'postLength');
    $col = $form->addRow()->addColumn();
        $col->addLabel('post', __('Post'));
        $col->addTextArea('post')->required()->setRows(6)->addClass('font-sans text-sm')->maxLength($postLength);

    // ATTACHMENTS
    $absoluteURL = $gibbon->session->get('absoluteURL');
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

        // echo $table->render(new DataSet($attachments));
    }


    $row = $form->addRow();
        $row->addLabel('attachments', __('Attachments'));
        $row->addFileUpload('attachments')->accepts('.jpg,.jpeg,.gif,.png')->uploadMultiple(true);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();

    


    // $form = Form::create('attachments', $gibbon->session->get('absoluteURL').'/modules/'.$gibbon->session->get('module').'/posts_manage_edit_addProcess.php');
    // $form->setFactory(DatabaseFormFactory::create($pdo));

    // $form->addHiddenValue('address', $gibbon->session->get('address'));
    // $form->addHiddenValue('streamPostID', $streamPostID);

    // $row = $form->addRow()->addHeading(__('Add'));

    

    // $row = $form->addRow();
    //     $row->addSubmit();

    // echo $form->getOutput();
}
