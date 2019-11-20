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
    public function queryPostsBySchoolYear(QueryCriteria $criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['streamPost.streamPostID', 'streamPost.post', 'streamPost.tags', 'streamPost.attachments', 'streamPost.timestamp', 'gibbonPerson.title', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonPerson.username', 'gibbonPerson.image_240'])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=streamPost.gibbonPersonID')
            ->where('streamPost.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $criteria->addFilterRules([
            'tag' => function ($query, $tag) {
                return $query
                    ->where('FIND_IN_SET(:tag, streamPost.tags)')
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
