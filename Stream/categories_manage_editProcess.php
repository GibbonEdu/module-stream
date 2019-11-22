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
use Gibbon\Module\Stream\Domain\CategoryGateway;

require_once '../../gibbon.php';

$streamCategoryID = $_POST['streamCategoryID'] ?? '';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Stream/categories_manage_edit.php&streamCategoryID='.$streamCategoryID;

if (isActionAccessible($guid, $connection2, '/modules/Stream/categories_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {

    // Proceed!
    $categoryGateway = $container->get(CategoryGateway::class);

    $data = [
        'name'          => $_POST['name'] ?? '',
        'active'        => $_POST['active'] ?? '',
        'staffAccess'   => $_POST['staffAccess'] ?? '',
        'studentAccess' => $_POST['studentAccess'] ?? '',
        'parentAccess'  => $_POST['parentAccess'] ?? '',
        'otherAccess'   => $_POST['otherAccess'] ?? '',
    ];

    // Validate the required values are present
    if (empty($streamCategoryID) || empty($data['name']) || empty($data['active']) || empty($data['staffAccess']) || empty($data['studentAccess']) || empty($data['parentAccess']) || empty($data['otherAccess'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$categoryGateway->exists($streamCategoryID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$categoryGateway->unique($data, ['name'], $streamCategoryID)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $updated = $categoryGateway->update($streamCategoryID, $data);

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
