<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerEco\Service\PunchoutGateway\Plugin\FieldMapper;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Spryker\Shared\Kernel\Transfer\AbstractTransfer;

trait TransferPathTraversalTrait
{
    protected const int MAX_TRAVERSAL_DEPTH = 2;

    /**
     * @return \SprykerEco\Service\PunchoutGateway\PunchoutGatewayConfig
     */
    abstract public function getConfig();

    protected function traversePath(?AbstractTransfer $objectTransfer, string $path): mixed
    {
        if ($objectTransfer === null) {
            return null;
        }

        $segments = explode('.', $path);

        foreach ($segments as $segment) {
            if ($objectTransfer === null) {
                return null;
            }

            $getter = sprintf('get%s', ucfirst($segment));

            if (!method_exists($objectTransfer, $getter)) {
                return null;
            }

            $objectTransfer = $objectTransfer->$getter();
        }

        return $objectTransfer;
    }

    /**
     * @return array<string>
     */
    protected function collectPossibleValues(string $prefix, string $rootTransferClass): array
    {
        $results = $this->collectFromClass($rootTransferClass, $prefix, [], 0);
        sort($results);

        return array_values(array_unique($results));
    }

    /**
     * @param array<string> $visited
     *
     * @return array<string>
     */
    protected function collectFromClass(string $fullyQualifiedClassName, string $prefix, array $visited, int $depth): array
    {
        if ($depth >= static::MAX_TRAVERSAL_DEPTH) {
            return [];
        }

        $visited[] = $fullyQualifiedClassName;
        $results = [];

        $reflection = new ReflectionClass($fullyQualifiedClassName);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic()) {
                continue;
            }

            $methodName = $method->getName();

            if (stripos($methodName, 'get') !== 0) {
                continue;
            }

            if (str_ends_with($methodName, 'OrFail')) {
                continue;
            }

            if (in_array($methodName, ['getIterator', 'getArrayCopy', 'getFlags', 'getStorageClass', 'getHash'], true)) {
                continue;
            }

            $fieldName = lcfirst(substr($methodName, 3));

            if ($this->isExcludedFieldPrefix($fieldName)) {
                continue;
            }

            $fieldPath = sprintf('%s.%s', $prefix, $fieldName);
            $docBlock = (string)$method->getDocComment();

            $results = array_merge($results, $this->resolveFieldResults($method, $docBlock, $fieldPath, $visited, $depth));
        }

        return $results;
    }

    /**
     * @param array<string> $visited
     *
     * @return array<string>
     */
    protected function resolveFieldResults(ReflectionMethod $method, string $docBlock, string $fieldPath, array $visited, int $depth): array
    {
        $docBlockResults = $this->resolveFromDocBlock($docBlock, $fieldPath, $visited, $depth);

        if ($docBlockResults !== []) {
            return $docBlockResults;
        }

        $nativeType = $method->getReturnType();

        if (!($nativeType instanceof ReflectionNamedType) || $nativeType->isBuiltin()) {
            return [];
        }

        $className = $nativeType->getName();

        if ($this->isTransferClass($className) && !in_array($className, $visited, true)) {
            return $this->collectFromClass($className, $fieldPath, $visited, $depth + 1);
        }

        return [];
    }

    /**
     * @param array<string> $visited
     *
     * @return array<string>
     */
    protected function resolveFromDocBlock(string $docBlock, string $fieldPath, array $visited, int $depth): array
    {
        if (preg_match('/@return\s+\\\\?ArrayObject<[^>]*\\\\?Generated\\\\Shared\\\\Transfer\\\\(\w+Transfer)/i', $docBlock, $matches)) {
            $elementClass = sprintf('Generated\Shared\Transfer\%s', $matches[1]);

            if (in_array($elementClass, $visited, true)) {
                return [];
            }

            return $this->collectFromClass($elementClass, sprintf('%s.*', $fieldPath), $visited, $depth + 1);
        }

        if (preg_match('/@return\s+\\\\?Generated\\\\Shared\\\\Transfer\\\\(\w+Transfer)/i', $docBlock, $matches)) {
            $transferClass = sprintf('Generated\Shared\Transfer\%s', $matches[1]);

            if (in_array($transferClass, $visited, true)) {
                return [];
            }

            return $this->collectFromClass($transferClass, $fieldPath, $visited, $depth + 1);
        }

        if (preg_match('/@return\s+array/i', $docBlock)) {
            return [sprintf('%s.*', $fieldPath)];
        }

        if ($this->isScalarDocBlock($docBlock)) {
            return [$fieldPath];
        }

        return [];
    }

    protected function isScalarDocBlock(string $docBlock): bool
    {
        if (!preg_match('/@return\s+([^\n]+)/i', $docBlock, $matches)) {
            return false;
        }

        $returnType = trim($matches[1]);
        $parts = array_map('trim', explode('|', $returnType));
        $scalarTypes = ['string', 'int', 'float', 'bool', 'null'];

        foreach ($parts as $part) {
            if (!in_array($part, $scalarTypes, true)) {
                return false;
            }
        }

        return true;
    }

    protected function isTransferClass(string $className): bool
    {
        return str_contains($className, 'Generated\Shared\Transfer\\') || str_ends_with($className, 'Transfer');
    }

    protected function isExcludedFieldPrefix(string $fieldName): bool
    {
        foreach ($this->getConfig()->getNonAutocompleteTransferFieldPrefixes() as $prefix) {
            if (str_starts_with($fieldName, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
