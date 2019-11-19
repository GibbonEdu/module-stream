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
}
