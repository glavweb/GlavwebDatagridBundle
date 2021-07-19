<?php

namespace App\DataSchema;

use App\Entity\Article;
use Glavweb\DataSchemaBundle\DataTransformer\DataTransformerInterface;
use Glavweb\DataSchemaBundle\DataTransformer\SimpleDataTransformer;
use Glavweb\DataSchemaBundle\DataTransformer\TransformEvent;
use Glavweb\DataSchemaBundle\Extension\ExtensionInterface;

/**
 * Class AppDataSchemaExtension
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class AppDataSchemaExtension implements ExtensionInterface
{
    /**
     * @return DataTransformerInterface[]
     */
    public function getDataTransformers()
    {
        return [
            'upper' => new SimpleDataTransformer([$this, 'transformUpper']),
            'concat_slug_with_year' => new SimpleDataTransformer([$this, 'transformConcatSlugWithYear']),
            'has_events' => new SimpleDataTransformer([$this, 'transformHasEvents']),
        ];
    }

    /**
     * @param $value
     * @param TransformEvent $event
     * @return string
     */
    public function transformUpper($value, TransformEvent $event): string
    {
        return strtoupper($value);
    }

    /**
     * @param $value
     * @param TransformEvent $event
     * @return string
     */
    public function transformConcatSlugWithYear($value, TransformEvent $event): string
    {
        if ($event->getClassName() !== Article::class) {
            throw new \RuntimeException('This transformer may be used with only instance of Article.');
        }

        $data = $event->getData();
        $publishAt = !$data['publishAt'] instanceof \DateTime ? (new \DateTime($data['publishAt'])) : $data['publishAt'];

        return $data['slug'] . '_' . $publishAt->format('Y');
    }

    /**
     * @param $articleEvents
     * @param TransformEvent $event
     * @return bool
     */
    public function transformHasEvents($articleEvents, TransformEvent $event): bool
    {
        if ($event->getClassName() !== Article::class) {
            throw new \RuntimeException('This transformer may be used with only instance of Article.');
        }

        return $articleEvents > 0;
    }
 }
