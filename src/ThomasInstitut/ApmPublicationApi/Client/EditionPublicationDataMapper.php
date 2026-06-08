<?php

namespace ThomasInstitut\ApmPublicationApi\Client;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\MapperBuilder;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use ThomasInstitut\ApmPublicationApi\EditionPublication\EditionPublicationData;
use ThomasInstitut\FmtText\FmtTextFactory;

class EditionPublicationDataMapper
{

    /**
     * @param array<string, mixed> $data
     * @return EditionPublicationData
     * @throws CustomMapperErrorException
     * @throws MappingError
     */
    public static function map(array $data): EditionPublicationData
    {
        $normalizedData = self::normalizeCompactFmtTextValues(EditionPublicationData::class, $data);

        /** @var EditionPublicationData $editionPublicationData */
        $editionPublicationData = (new MapperBuilder())
            ->allowSuperfluousKeys()
            ->mapper()
            ->map(EditionPublicationData::class, $normalizedData);

        return $editionPublicationData;
    }

    /**
     * @param class-string $targetClass
     * @param array<int|string, mixed> $source
     * @return array<int|string, mixed>
     * @throws CustomMapperErrorException|MappingError
     */
    private static function normalizeCompactFmtTextValues(string $targetClass, array $source): array
    {
        try {
            $reflection = new ReflectionClass($targetClass);
        } catch (ReflectionException $e) {
            throw new \RuntimeException("Failed to reflect class '$targetClass': " . $e->getMessage(), 0, $e);
        }
        $normalized = $source;

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyName = $property->getName();
            if (!array_key_exists($propertyName, $normalized)) {
                continue;
            }

            $sourceValue = $normalized[$propertyName];

            if (count($property->getAttributes(CompactFmtText::class)) > 0) {
                if (!is_string($sourceValue) && !is_array($sourceValue)) {
                    throw new CustomMapperErrorException("Property '$propertyName' should be string|array");
                }

                /** @var string|array<string|array<string, mixed>> $sourceValue */
                $normalized[$propertyName] = FmtTextFactory::fromCompactFmtText($sourceValue);
                continue;
            }

            if (!is_array($sourceValue)) {
                continue;
            }

            $propertyType = $property->getType();
            $objectPropertyClass = self::getNamedClassFromPropertyType($propertyType);
            if ($objectPropertyClass !== null) {
                $normalized[$propertyName] = self::normalizeCompactFmtTextValues($objectPropertyClass, $sourceValue);
                continue;
            }

            $arrayItemClass = self::getArrayItemClass($property, $reflection);
            if ($arrayItemClass === null) {
                continue;
            }

            foreach ($sourceValue as $index => $item) {
                if (!is_array($item)) {
                    continue;
                }

                $sourceValue[$index] = self::normalizeCompactFmtTextValues($arrayItemClass, $item);
            }

            $normalized[$propertyName] = $sourceValue;
        }

        return $normalized;
    }

    /**
     * @param ReflectionType|null $propertyType
     * @return class-string|null
     */
    private static function getNamedClassFromPropertyType(?ReflectionType $propertyType): ?string
    {
        if ($propertyType === null) {
            return null;
        }

        if ($propertyType instanceof ReflectionUnionType) {
            foreach ($propertyType->getTypes() as $type) {
                $className = self::getNamedClassFromPropertyType($type);
                if ($className !== null) {
                    return $className;
                }
            }

            return null;
        }

        if (!$propertyType instanceof ReflectionNamedType) {
            return null;
        }

        if ($propertyType->isBuiltin()) {
            return null;
        }

        $className = $propertyType->getName();
        if (!class_exists($className)) {
            return null;
        }

        return $className;
    }

    /**
     * @param ReflectionProperty $property
     * @param ReflectionClass<object> $reflection
     * @return class-string|null
     */
    private static function getArrayItemClass(ReflectionProperty $property, ReflectionClass $reflection): ?string
    {
        $docComment = $property->getDocComment();
        if (!is_string($docComment)) {
            return null;
        }

        if (!preg_match('/@var\\s+array<([A-Za-z_\\\\][A-Za-z0-9_\\\\]*)>/', $docComment, $matches)) {
            return null;
        }

        $arrayItemType = $matches[1];
        if (str_contains($arrayItemType, '\\')) {
            return class_exists($arrayItemType) ? $arrayItemType : null;
        }

        $fqcn = $reflection->getNamespaceName() . '\\' . $arrayItemType;
        if (class_exists($fqcn)) {
            return $fqcn;
        }

        return class_exists($arrayItemType) ? $arrayItemType : null;
    }
}