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

use Gibbon\Services\Format;
use Gibbon\Module\Stream\Domain\PostGateway;
use Gibbon\FileUploader;

$_POST['address'] = '/modules/Stream/stream_postProcess.php';

require_once '../../gibbon.php';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Stream/stream.php';

if (isActionAccessible($guid, $connection2, '/modules/Stream/stream.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $postGateway = $container->get(PostGateway::class);

    $data = [
        'gibbonSchoolYearID' => $gibbon->session->get('gibbonSchoolYearID'),
        'gibbonPersonID'     => $gibbon->session->get('gibbonPersonID'),
        'post'               => $_POST['post'] ?? '',
        'timestamp'          => date('Y-m-d H:i:s'),
    ];

    // Validate the required values are present
    if (empty($data['post'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Auto-detect tags used in this post
    $matches = [];
    if (preg_match_all('/[#]+([\w]+)/iu', $data['post'], $matches)) {
        $data['tags'] = implode(',', $matches[1] ?? []);
    }

    // Handle file upload for multiple attachments, including resizing images & generating thumbnails
    if (!empty($_FILES['attachments']['tmp_name'][0])) {
        $fileUploader = new FileUploader($pdo, $gibbon->session);
        $absolutePath = $gibbon->session->get('absolutePath');

        foreach ($_FILES['attachments']['name'] as $index => $name) {
            $file = array_combine(array_keys($_FILES['attachments']), array_column($_FILES['attachments'], $index));
            $attachment = $fileUploader->uploadAndResizeImage($file, 'streamPhoto', 1400, 1400);
            
            $thumbPath = $absolutePath.'/'.str_replace('streamPhoto', 'streamThumb', $attachment);
            $thumb = $fileUploader->resizeImage($absolutePath.'/'.$attachment, $thumbPath, 650);

            $attachments[] = [
                'type'  => 'image',
                'src'   => $attachment,
                'thumb' => str_replace($absolutePath.'/', '', $thumb),
            ];
        }
        if (!empty($attachments)) {
            $data['attachments'] = json_encode($attachments);
        }
    }

    // Create the post
    $streamPostID = $postGateway->insert($data);

    $URL .= !$streamPostID
        ? "&return=error2"
        : "&return=success0&editID=$streamPostID";

    header("Location: {$URL}");
}
