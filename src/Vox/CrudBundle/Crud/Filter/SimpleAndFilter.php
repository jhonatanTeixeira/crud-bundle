<?php

namespace Vox\CrudBundle\Crud\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpFoundation\Request;
use Vox\CrudBundle\Crud\AddFilterEvent;
use Vox\CrudBundle\Crud\FilterInterface;

class SimpleAndFilter
{
    private $fields;

    private $className;

    public function __construct(array $fields, string $className)
    {
        $this->fields    = $fields;
        $this->className = $className;
    }

    public function applyFilter(AddFilterEvent $event): void
    {
        $queryBuilder = $event->getQueryBuilder();
        $request      = $event->getRequest();

        if (empty($this->fields)) {
            $this->fields = $this->getFilterFields($event->getEntityManager());
        }

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

    protected function getFilterFields(EntityManager $entityManager): iterable
    {
        $metadata = $entityManager->getClassMetadata($this->className);

        $fields = [];

        foreach ($metadata->getFieldNames() as $fieldName) {
            $fields[$fieldName] = 'exact';
        }

        return $fields;
    }
}
