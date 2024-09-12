<?php

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Stream\Domain\PostGateway;
use Gibbon\Module\Stream\Domain\PostAttachmentGateway;
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
 use Gibbon\Services\Format;
 use Gibbon\Tables\DataTable;
 use Gibbon\Http\Url;


 global $session, $container, $page;

include_once $session->get('absolutePath').'/modules/Stream/src/Domain/PostGateway.php';
include_once $session->get('absolutePath').'/modules/Stream/src/Domain/PostAttachmentGateway.php';


//Implementing the mini-stream
$output = '';

$output .=  '<h2>';
$output .=  __('Stream');
$output .=  '</h2>';

$page->scripts->add('magnific', 'modules/Stream/js/magnific/jquery.magnific-popup.min.js');
$page->stylesheets->add('magnific', 'modules/Stream/js/magnific/magnific-popup.css');

$postGateway = $container->get(PostGateway::class);
$gibbonSchoolYearID = $session->get('gibbonSchoolYearID');

$criteria = $postGateway->newQueryCriteria(true)
        ->sortBy(['timestamp'], 'DESC')
        ->pageSize(5)
        ->fromPOST();

 // Get the stream, join a set of attachment data per post
 $showPreviousYear = $container->get(SettingGateway::class)->getSettingByScope('Stream', 'showPreviousYear');
 $stream = $postGateway->queryPostsBySchoolYear($criteria, $gibbonSchoolYearID, $showPreviousYear, null, $session->get('gibbonRoleIDCurrent'));
 $streamPosts = $stream->getColumn('streamPostID');
 $attachments = $container->get(PostAttachmentGateway::class)->selectAttachmentsByPost($streamPosts)->fetchGrouped();
 $stream->joinColumn('streamPostID', 'attachments', $attachments);

 $streamData = array_map(function ($item) {
    // Auto-link urls
    $item['post'] = preg_replace_callback('/(https?:\/\/[^\s\$.?#].[^\s]*)(\s|$)+/iu', function ($matches) {
        $linktext = strlen($matches[1]) > 45 ? substr($matches[1], 0, 45).'…' : $matches[1];
        return Format::link($matches[1], $linktext).' ';
    }, $item['post']);

    // Auto-link hashtags
    $item['post'] = preg_replace_callback('/(?:\s|^)+([#]+)([\w]+)($|\b)+/iu', function ($matches) {
        return ' '.Format::link('./index.php?q=/modules/Stream/stream.php&tag=' . $matches[2], $matches[1] . $matches[2]).$matches[3];
    }, $item['post']);

    return $item;
}, $stream->toArray());

   // RENDER STREAM
   $output .=  $page->fetchFromTemplate('sidebarStream.twig.html', [
        'stream' => $streamData,
]);

$output .=  "<p style='padding-top: 5px; text-align: right'>";
$output .= "<a href='".Url::fromModuleRoute('Stream', 'stream')."'>".__('View Stream').'</a>';
$output .=  '</p>';

return $output;