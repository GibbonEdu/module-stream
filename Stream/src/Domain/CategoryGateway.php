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

namespace Gibbon\Module\Stream\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class CategoryGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'streamCategory';
    private static $primaryKey = 'streamCategoryID';
    private static $searchableColumns = [''];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryCategories(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['streamCategory.streamCategoryID', 'name', 'active', 'staffAccess', 'studentAccess', 'parentAccess', 'otherAccess']);

        $criteria->addFilterRules([
            'active' => function ($query, $active) {
                return $query
                    ->where('streamCategory.active = :active')
                    ->bindValue('active', $active);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectViewableCategoriesByPerson($gibbonPersonID)
    {
        $query = $this
            ->newSelect()
            ->from('gibbonPerson')
            ->cols(['streamCategory.streamCategoryID as groupBy', 'streamCategory.streamCategoryID', 'streamCategory.name', 'streamCategory.active', 'staffAccess', 'studentAccess', 'parentAccess', 'otherAccess', 'streamCategoryViewed.timestamp', "COUNT(DISTINCT CASE WHEN streamPost.timestamp>streamCategoryViewed.timestamp THEN streamPost.streamPostID END) as recentPosts"])
            ->innerJoin('gibbonRole', 'FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)')
            ->innerJoin('streamCategory', "streamCategory.active='Y'")
            ->leftJoin('streamCategoryViewed', "streamCategoryViewed.streamCategoryID=streamCategory.streamCategoryID AND streamCategoryViewed.gibbonPersonID=gibbonPerson.gibbonPersonID")
            ->leftJoin('streamPost', 'FIND_IN_SET(streamCategory.streamCategoryID, streamPost.streamCategoryIDList)')
            ->where('gibbonPerson.gibbonPersonID=:gibbonPersonID')
            ->bindValue('gibbonPersonID', $gibbonPersonID)
            ->where("((gibbonRole.category = 'Staff' AND (streamCategory.staffAccess='View' OR streamCategory.staffAccess='Post')) 
                OR (gibbonRole.category = 'Student' AND (streamCategory.studentAccess='View' OR streamCategory.studentAccess='Post'))
                OR (gibbonRole.category = 'Parent' AND (streamCategory.parentAccess='View' OR streamCategory.parentAccess='Post'))
                OR (gibbonRole.category = 'Other' AND (streamCategory.otherAccess='View' OR streamCategory.otherAccess='Post'))
            )")
            ->groupBy(['streamCategory.streamCategoryID'])
            ->orderBy(['streamCategory.sequenceNumber', 'streamCategory.name']);

        return $this->runSelect($query);
    }

    public function selectPostableCategoriesByRole($gibbonRoleID)
    {
        $query = $this
            ->newSelect()
            ->from('gibbonRole')
            ->cols(['streamCategory.streamCategoryID', 'streamCategory.name'])
            ->innerJoin('streamCategory', "streamCategory.active='Y'")
            ->where('gibbonRole.gibbonRoleID=:gibbonRoleID')
            ->bindValue('gibbonRoleID', $gibbonRoleID)
            ->where("((gibbonRole.category = 'Staff' AND streamCategory.staffAccess='Post') 
                OR (gibbonRole.category = 'Student' AND streamCategory.studentAccess='Post')
                OR (gibbonRole.category = 'Parent' AND streamCategory.parentAccess='Post')
                OR (gibbonRole.category = 'Other' AND streamCategory.otherAccess='Post')
            )")
            ->groupBy(['streamCategory.streamCategoryID']);

        return $this->runSelect($query);
    }
}
