<?php

namespace Vox\CrudBundle\Crud\Filter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpFoundation\Request;
use Vox\CrudBundle\Crud\FilterInterface;

class SimpleAndFilter implements FilterInterface
{
    private $fields;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public function applyFilter(QueryBuilder $queryBuilder, Request $request): void
    {
        foreach ($this->fields as $field => $type) {
            if (!$request->query->has($field)) {
                continue;
            }

            switch (strtolower($type)) {
                case 'like':
                    $queryBuilder->andWhere($queryBuilder->expr()->like("e.$field", ":$field"))
                        ->setParameter($field, "%{$request->query->get($field)}%");
                    break;
                case 'exact':
                    $queryBuilder->andWhere("e.$field = :$field")
                        ->setParameter($field, $request->query->get($field));
                    break;
                default:
                    throw new InvalidConfigurationException('invalid field filter type: like, exact allowed');
            }
        }
    }
}