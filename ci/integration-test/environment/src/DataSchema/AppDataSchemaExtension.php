<?php

namespace App\DataSchema;

use App\Entity\Article;
use Glavweb\DataSchemaBundle\DataTransformer\DataTransformerInterface;
use Glavweb\DataSchemaBundle\DataTransformer\SimpleDataTransformer;
use Glavweb\DataSchemaBundle\DataTransformer\TransformEvent;
use Glavweb\DataSchemaBundle\Extension\AbstractExtension;

/**
 * Class AppDataSchemaExtension.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class AppDataSchemaExtension extends AbstractExtension
{
    /**
     * @return DataTransformerInterface[]
     */
    #[\Override]
    public function getDataTransformers(): array
    {
        return [
            'upper' => new SimpleDataTransformer($this->transformUpper(...)),
            'concat_slug_with_year' => new SimpleDataTransformer($this->transformConcatSlugWithYear(...)),
            'has_events' => new SimpleDataTransformer($this->transformHasEvents(...)),
        ];
    }

    public function transformUpper($value, TransformEvent $event): string
    {
        return strtoupper((string) $value);
    }

    public function transformConcatSlugWithYear($value, TransformEvent $event): string
    {
        if ($event->getClassName() !== Article::class) {
            throw new \RuntimeException('This transformer may be used with only instance of Article.');
        }

        $data = $event->getData();
        $publishAt = $data['publishAt'] instanceof \DateTime ? ($data['publishAt']) : new \DateTime($data['publishAt']);

        return $data['slug'].'_'.$publishAt->format('Y');
    }

    public function transformHasEvents($articleEvents, TransformEvent $event): bool
    {
        if ($event->getClassName() !== Article::class) {
            throw new \RuntimeException('This transformer may be used with only instance of Article.');
        }

        return $articleEvents > 0;
    }
}
