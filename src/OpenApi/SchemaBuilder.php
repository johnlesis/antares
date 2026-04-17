<?php

declare(strict_types=1);

namespace Antares\OpenApi;

use Antares\Validation\Attributes\Email;
use Antares\Validation\Attributes\Min;
use Antares\Validation\Attributes\Max;
use Antares\Validation\Attributes\MinLength;
use Antares\Validation\Attributes\MaxLength;
use Antares\Validation\Attributes\Pattern;
use Antares\Validation\Attributes\ValidationAttribute;

final class SchemaBuilder
{
    public function build(string $dtoClass): array
    {
        $reflection = new \ReflectionClass($dtoClass);
        $parameters = $reflection->getConstructor()->getParameters();

        $properties = [];
        $required   = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();
            
            if ($type === null) {
                $properties[$parameter->getName()] = ['type' => 'string'];
                continue;
            }

            $property = ['type' => $this->mapType($type)];

            if ($type->allowsNull()) {
                $property['nullable'] = true;
            }

            if (!$parameter->isDefaultValueAvailable() && !$type->allowsNull()) {
                $required[] = $name;
            }

            $attributes = $parameter->getAttributes(
                ValidationAttribute::class,
                \ReflectionAttribute::IS_INSTANCEOF
            );

            $property = $this->mapAttributtes($property, $attributes);
            
            $properties[$name] = $property;
        }

        return [
            'type'       => 'object',
            'required'   => $required,
            'properties' => $properties,
        ];
    }

    private function mapType(\ReflectionNamedType $type): string
    {
        if ($type === null) {
            return 'string';
        }
        return match ($type->getName()) {
            'int'    => 'integer',
            'float'  => 'number',
            'bool'   => 'boolean',
            'array'  => 'array',
            default  => 'string',
        };
    }

    private function mapAttributtes(array $property, array $attributes): array
    {
        foreach ($attributes as $attribute) {
                $instance   = $attribute->newInstance();
                $constraint = match(true) {
                    $instance instanceof MinLength => ['minLength' => $instance->minLength],
                    $instance instanceof MaxLength => ['maxLength' => $instance->maxLength],
                    $instance instanceof Min       => ['minimum'   => $instance->min],
                    $instance instanceof Max       => ['maximum'   => $instance->max],
                    $instance instanceof Email     => ['format'    => 'email'],
                    $instance instanceof Pattern   => ['pattern'   => $instance->regex],
                    default                        => [],
                };
                $property = array_merge($property, $constraint);
            }
            return $property;
    }
}
