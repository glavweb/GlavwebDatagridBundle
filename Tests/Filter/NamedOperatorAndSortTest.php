<?php

declare(strict_types=1);

namespace Glavweb\DatagridBundle\Tests\Filter;

use Doctrine\DBAL\DriverManager;
use Glavweb\DatagridBundle\Doctrine\DBAL\Query\QueryBuilder;
use Glavweb\DatagridBundle\Filter\Doctrine\AbstractFilter;
use Glavweb\DatagridBundle\Filter\Doctrine\Native\NumberFilter;
use Glavweb\DatagridBundle\Filter\Doctrine\Native\StringFilter;
use PHPUnit\Framework\TestCase;

/**
 * Именованные операторы пишут SQL, сортировка задаёт порядок строк.
 *
 * @author nilov <nilov@glavweb.ru>
 */
final class NamedOperatorAndSortTest extends TestCase
{
    private \Doctrine\DBAL\Connection $connection;

    protected function setUp(): void
    {
        if (!\extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('pdo_sqlite is required.');
        }

        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);
        $this->connection->executeStatement(<<<'SQL'
            CREATE TABLE articles (
                name TEXT NOT NULL,
                body TEXT NOT NULL,
                count_events INTEGER NOT NULL,
                is_publish INTEGER NOT NULL,
                publish_at TEXT NOT NULL
            )
            SQL);
        $rows = [
            ['Article 1', 'Article about Katy', 2, 1, '2016-05-09 10:00'],
            ['Article 2', 'Article about Mary', 1, 0, '2016-05-10 11:00'],
            ['Article 3', 'Article about Niki', 0, 0, '2016-05-11 12:00'],
        ];
        foreach ($rows as $row) {
            $this->connection->insert('articles', [
                'name' => $row[0],
                'body' => $row[1],
                'count_events' => $row[2],
                'is_publish' => $row[3],
                'publish_at' => $row[4],
            ]);
        }
    }

    public function testNamedOperatorMapRecognizesOperatorsOnly(): void
    {
        $this->assertTrue(AbstractFilter::isNamedOperatorMap(['gte' => '1', 'lt' => '2']));
        $this->assertTrue(AbstractFilter::isNamedOperatorMap(['in' => ['a', 'b']]));
        $this->assertFalse(AbstractFilter::isNamedOperatorMap([]));
        $this->assertFalse(AbstractFilter::isNamedOperatorMap(['>=10']));
        $this->assertFalse(AbstractFilter::isNamedOperatorMap(['unknown' => '1']));
    }

    public function testStringEqContainsInAndNotContains(): void
    {
        $filter = $this->stringFilter();

        $this->assertSame(['Article 2'], $this->names($filter, 'body', ['eq' => 'Article about Mary']));
        $this->assertSame(['Article 1'], $this->names($filter, 'body', ['contains' => 'katy']));
        $this->assertSame(
            ['Article 2', 'Article 3'],
            $this->names($filter, 'body', ['in' => ['Article about Mary', 'Article about Niki']], 'name', 'ASC'),
        );
        $this->assertSame([], $this->names($filter, 'body', ['not_contains' => 'Article']));
        $this->assertSame(
            ['Article 2', 'Article 3'],
            $this->names($filter, 'body', ['neq' => 'Article about Katy'], 'name', 'ASC'),
        );
    }

    public function testNumberRangeIsAndAndSortsByName(): void
    {
        $filter = $this->numberFilter();

        $this->assertSame(['Article 2'], $this->names($filter, 'count_events', ['gte' => '1', 'lt' => '2']));
        $this->assertSame(['Article 1'], $this->names($filter, 'count_events', ['gt' => '1']));
        $this->assertSame(
            ['Article 1', 'Article 3'],
            $this->names($filter, 'count_events', ['nin' => ['1']], 'name', 'ASC'),
        );
        $this->assertSame(
            ['Article 3', 'Article 2', 'Article 1'],
            $this->names($filter, 'count_events', ['gte' => '0'], 'name', 'DESC'),
        );
    }

    public function testStringRejectsGreaterThan(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->names($this->stringFilter(), 'body', ['gt' => 'Article']);
    }

    public function testPrefixOperatorStillContains(): void
    {
        $this->assertSame(['Article 1'], $this->names($this->stringFilter(), 'body', 'Katy'));
    }

    private function names(AbstractFilter $filter, string $column, mixed $value, string $sort = 'name', string $direction = 'ASC'): array
    {
        $queryBuilder = new QueryBuilder($this->connection);
        $queryBuilder->select('name')->from('articles', 't');
        $filter->filter($queryBuilder, 't', $value);
        $queryBuilder->addOrderBy($sort, $direction);

        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();

        return array_column($rows, 'name');
    }

    private function stringFilter(): StringFilter
    {
        return new class extends StringFilter {
            public function __construct()
            {
                $this->name = 'body';
                $this->fieldName = 'body';
                $this->joinMap = null;
            }

            protected function getColumnName(string $fieldName): string
            {
                return $fieldName;
            }
        };
    }

    private function numberFilter(): NumberFilter
    {
        return new class extends NumberFilter {
            public function __construct()
            {
                $this->name = 'countEvents';
                $this->fieldName = 'count_events';
                $this->joinMap = null;
            }

            protected function getColumnName(string $fieldName): string
            {
                return $fieldName;
            }
        };
    }
}
