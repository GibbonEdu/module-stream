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

use Gibbon\Module\Stream\Domain\PostGateway;
use Gibbon\Services\Format;
use Gibbon\Forms\Form;
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Stream/stream.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $urlParams = [
        'tag' => $_GET['tag'] ?? ''
    ];


    $page->scripts->add('magnific', 'modules/Stream/js/magnific/jquery.magnific-popup.min.js');
    $page->stylesheets->add('magnific', 'modules/Stream/js/magnific/magnific-popup.css');

    $page->breadcrumbs
        ->add(__m('View Stream'), 'stream.php');

    if (!empty($urlParams['tag'])) {
        $page->breadcrumbs->add(__('Viewing {filter}', ['filter' => '#' . $urlParams['tag']]));
    }

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    // Query
    $postGateway = $container->get(PostGateway::class);
    $gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');

    $criteria = $postGateway->newQueryCriteria(true)
        ->sortBy(['timestamp'], 'DESC')
        ->filterBy('tag', $urlParams['tag'])
        ->fromPOST();

    $stream = $postGateway->queryPostsBySchoolYear($criteria, $gibbonSchoolYearID);

    $stream = array_map(function ($item) {
        // Decode attachments into an array
        $item['attachments'] = json_decode($item['attachments'], true);

        // Auto-link hashtags
        $item['post'] = preg_replace_callback('/([#]+)([\w]+)/iu', function ($matches) {
            return Format::link('./index.php?q=/modules/Stream/stream.php&tag=' . $matches[2], $matches[1] . $matches[2]);
        }, $item['post']);

        return $item;
    }, $stream->toArray());

    // Render Stream
    echo $page->fetchFromTemplate('stream.twig.html', [
        'stream' => $stream,
    ]);

    // FORM
    $form = Form::create('block', $gibbon->session->get('absoluteURL').'/modules/Stream/stream_postProcess.php');
    $form->addClass('mt-10');
    $form->addHiddenValue('address', $gibbon->session->get('address'));

    $postLength = $container->get(SettingGateway::class)->getSettingByScope('Stream', 'postLength');
    $col = $form->addRow()->addColumn();
        $col->addLabel('post', __('Post'));
        $col->addTextArea('post')->required()->setRows(6)->maxLength($postLength);

    $row = $form->addRow()->addDetails()->summary(__('Add Photos'));
        $row->addFileUpload('attachments')->accepts('.jpg,.jpeg,.gif,.png')->uploadMultiple(true);

    $row = $form->addRow()->addSubmit(__('Post'));

    $_SESSION[$guid]['sidebarExtra'] .= $form->getOutput();
}
?>

<script>
    $(document).ready(function() {
        $('.image-container').magnificPopup({
            type: 'image',
            delegate: 'a',
            gallery: {
                enabled: true
            },
        });
    });
</script>
