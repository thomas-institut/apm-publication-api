<?php

namespace ThomasInstitut\ApmPublicationApi\Client;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\MapperBuilder;
use ReflectionClass;
use ReflectionProperty;
use ThomasInstitut\ApmPublicationApi\EditionPublication\EditionPublicationData;
use ThomasInstitut\FmtText\FmtTextFactory;
use ThomasInstitut\FmtText\FmtTextToken;

class EditionPublicationDataMapper
{

    /**
     * @param array<string, mixed> $data
     * @return EditionPublicationData
     * @throws CustomMapperErrorException
     */
    public static function map(array $data): EditionPublicationData
    {
        try {
            /** @var EditionPublicationData $editionPublicationData */
            $editionPublicationData = (new MapperBuilder())
                ->allowSuperfluousKeys()
                ->mapper()
                ->map(EditionPublicationData::class, $data);
        } catch (MappingError $e) {
            throw new CustomMapperErrorException($e->getMessage(), 0, $e);
        }

        self::hydrateFmtTextProperties($editionPublicationData, $data);

        return $editionPublicationData;
    }

    /**
     * @param object $target
     * @param array<int|string, mixed> $source
     * @throws CustomMapperErrorException
     */
    private static function hydrateFmtTextProperties(object $target, array $source): void
    {
        $reflection = new ReflectionClass($target);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyName = $property->getName();
            if (!array_key_exists($propertyName, $source)) {
                continue;
            }

            $sourceValue = $source[$propertyName];
            $docComment = $property->getDocComment();

            if (is_string($docComment) && str_contains($docComment, '@var string|array<string|FmtTextToken>')) {
                if (!is_string($sourceValue) && !is_array($sourceValue)) {
                    throw new CustomMapperErrorException("Property '$propertyName' should be string|array");
                }

                try {
                    /** @var string|array<string|FmtTextToken|array<string, mixed>> $sourceValue */
                    $property->setValue($target, FmtTextFactory::fromCompactFmtText($sourceValue));
                } catch (MappingError $e) {
                    throw new CustomMapperErrorException($e->getMessage(), 0, $e);
                }
                continue;
            }

            $currentValue = $property->getValue($target);

            if (is_object($currentValue) && is_array($sourceValue)) {
                self::hydrateFmtTextProperties($currentValue, $sourceValue);
                continue;
            }

            if (!is_array($currentValue) || !is_array($sourceValue)) {
                continue;
            }

            foreach ($currentValue as $index => $item) {
                if (
                    !is_object($item)
                    || !array_key_exists($index, $sourceValue)
                    || !is_array($sourceValue[$index])
                ) {
                    continue;
                }
                self::hydrateFmtTextProperties($item, $sourceValue[$index]);
            }
        }
    }
}