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

use Gibbon\FileUploader;
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Stream\Domain\PostGateway;
use Gibbon\Module\Stream\Domain\PostTagGateway;
use Gibbon\Module\Stream\Domain\PostAttachmentGateway;
use Gibbon\Module\Stream\Domain\CategoryGateway;
use Gibbon\Data\Validator;

$_POST['address'] = '/modules/Stream/stream_postProcess.php';

require_once '../../gibbon.php';

$source = $_POST['source'] ?? '';
$category = $_POST['category'] ?? '';
$streamCategoryID = $_POST['streamCategoryID'] ?? '';
$URL = $source == 'stream'
    ? $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Stream/stream.php&category='.$category.'&streamCategoryID='.$streamCategoryID
    : $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Stream/posts_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Stream/posts_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $postGateway = $container->get(PostGateway::class);
    $postTagGateway = $container->get(PostTagGateway::class);
    $postAttachmentGateway = $container->get(PostAttachmentGateway::class);
    $categoryGateway = $container->get(CategoryGateway::class);

    $partialFail = false;

    // Sanitize the whole $_POST array
    $validator = new Validator();
    $_POST = $validator->sanitize($_POST);

    $data = [
        'gibbonSchoolYearID'    => $gibbon->session->get('gibbonSchoolYearID'),
        'gibbonPersonID'        => $gibbon->session->get('gibbonPersonID'),
        'post'                  => $_POST['post'] ?? '',
        'streamCategoryIDList'  => (!empty($_POST['streamCategoryIDList']) && (is_array($_POST['streamCategoryIDList'])) ? implode(",", $_POST['streamCategoryIDList']) : null),
        'timestamp'             => date('Y-m-d H:i:s'),
    ];

    // Validate the required values are present
    if (empty($data['post'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Create the post
    $streamPostID = $postGateway->insert($data);

    // Auto-detect tags used in this post
    $matches = [];
    if (preg_match_all('/[#]+([\w]+)/iu', $data['post'], $matches)) {
        foreach ($matches[1] as $tag) {
            $data = [
                'streamPostID' => $streamPostID,
                'tag' => $tag,
            ];

            $postTagGateway->insertAndUpdate($data, $data);
        }
    }

    // Handle file upload for multiple attachments, including resizing images & generating thumbnails
    if (!empty($_FILES['attachments']['tmp_name'][0])) {
        $fileUploader = new FileUploader($pdo, $gibbon->session);
        $absolutePath = $gibbon->session->get('absolutePath');
        $maxImageSize = $container->get(SettingGateway::class)->getSettingByScope('Stream', 'maxImageSize');

        foreach ($_FILES['attachments']['name'] as $index => $name) {
            $file = array_combine(array_keys($_FILES['attachments']), array_column($_FILES['attachments'], $index));
            $attachment = $fileUploader->uploadAndResizeImage($file, 'streamPhoto', $maxImageSize, 90);

            if (!empty($attachment)) {
                $thumbPath = $absolutePath.'/'.str_replace('streamPhoto', 'streamThumb', $attachment);
                $thumbnail = $fileUploader->resizeImage($absolutePath.'/'.$attachment, $thumbPath, 650);

                $data = [
                    'streamPostID' => $streamPostID,
                    'attachment'   => $attachment,
                    'thumbnail'    => str_replace($absolutePath.'/', '', $thumbnail),
                    'type'         => 'Image',
                ];

                $postAttachmentGateway->insert($data);
            } else {
                $partialFail = true;
            }
        }
    }

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0&editID=$streamPostID";

    header("Location: {$URL}");
}
