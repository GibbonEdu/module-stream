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

namespace Gibbon\Module\Stream\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class PostGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'streamPost';
    private static $primaryKey = 'streamPostID';
    private static $searchableColumns = [''];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryPostsBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID, $showPreviousYear = "N", $gibbonPersonID = null, $gibbonRoleID = null)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['streamPost.streamPostID', 'streamPost.post', 'streamPost.timestamp', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.username', 'gibbonPerson.image_240'])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=streamPost.gibbonPersonID')
            ->innerJoin('gibbonSchoolYear', 'streamPost.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID')
            ->leftJoin('streamPostTag', 'streamPostTag.streamPostID=streamPost.streamPostID')
            ->leftJoin('streamCategory', 'FIND_IN_SET(streamCategory.streamCategoryID, streamPost.streamCategoryIDList)')
            ->groupBy(['streamPost.streamPostID']);

        if ($showPreviousYear == "Y") {
            $query->where('(streamPost.gibbonSchoolYearID=:gibbonSchoolYearID OR streamPost.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE sequenceNumber<(SELECT sequenceNumber FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID) ORDER BY sequenceNumber DESC LIMIT 0,1))')
                ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);
        }
        else {
            $query->where('streamPost.gibbonSchoolYearID=:gibbonSchoolYearID')
                ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);
        }

        if (!empty($gibbonPersonID)) {
            $query->where('streamPost.gibbonPersonID=:gibbonPersonID')
                  ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        if (!empty($gibbonRoleID)) {
            $query->innerJoin('gibbonRole', "( (streamCategory.streamCategoryID IS NULL OR streamPost.streamCategoryIDList = '')
                    OR (gibbonRole.category = 'Staff' AND (streamCategory.staffAccess='View' OR streamCategory.staffAccess='Post'))
                    OR (gibbonRole.category = 'Student' AND (streamCategory.studentAccess='View' OR streamCategory.studentAccess='Post'))
                    OR (gibbonRole.category = 'Parent' AND (streamCategory.parentAccess='View' OR streamCategory.parentAccess='Post'))
                    OR (gibbonRole.category = 'Other' AND (streamCategory.otherAccess='View' OR streamCategory.otherAccess='Post'))
                )")
                ->where('gibbonRole.gibbonRoleID=:gibbonRoleID')
                  ->bindValue('gibbonRoleID', $gibbonRoleID);
        }

        $criteria->addFilterRules([
            'category' => function ($query, $category) {
                return $query
                    ->where('FIND_IN_SET(:category, streamPost.streamCategoryIDList)')
                    ->bindValue('category', $category);
            },
            'tag' => function ($query, $tag) {
                return $query
                    ->where('streamPostTag.tag=:tag')
                    ->bindValue('tag', $tag);
            },
            'user' => function ($query, $user) {
                return $query
                    ->where('gibbonPerson.username=:user')
                    ->bindValue('user', $user);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }
}
