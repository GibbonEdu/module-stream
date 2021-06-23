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

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Module\Stream\Domain\PostGateway;

if (isActionAccessible($guid, $connection2, '/modules/Stream/posts_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Manage Posts'));

    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    if ($highestAction == 'Manage Posts_all') {
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? null;
    } elseif ($highestAction == 'Manage Posts_my') {
        $gibbonPersonID = $session->get('gibbonPersonID');
    } else {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    // Query posts
    $postGateway = $container->get(PostGateway::class);
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');

    $criteria = $postGateway->newQueryCriteria(true)
        ->sortBy('timestamp', 'DESC')
        ->fromPOST();

    $posts = $postGateway->queryPostsBySchoolYear($criteria, $gibbonSchoolYearID, $gibbonPersonID);

    // Render table
    $table = DataTable::createPaginated('posts', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Stream/posts_manage_add.php')
        ->displayLabel();

    if ($highestAction == 'Manage Posts_all') {
        $table->addColumn('name', __('Person'))
            ->width('20%')
            ->sortable(['surname', 'preferredName'])
            ->format(function ($post) {
                return Format::name($post['title'], $post['preferredName'], $post['surname'], 'Staff', false, true);
            });
    }

    $table->addColumn('timestamp', __('Date'))
        ->width('18%')
        ->format(function ($post) {
            return Format::dateTimeReadable($post['timestamp']);
        });

    $table->addColumn('post', __('Post'))
        ->format(Format::using('truncate', ['post', 120]));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('streamPostID')
        ->format(function ($post, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Stream/posts_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Stream/posts_manage_delete.php');
        });

    echo $table->render($posts);
}
