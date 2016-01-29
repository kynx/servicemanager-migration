<?php
/**
 * @license: BSD-3-Clause
 */

namespace Kynx\ServiceManager;

use ReflectionClass;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ServiceManager;

/**
 * Dumb ass class for v2-v3 compatible zend-servicemanager migrations
 */
class Migrator
{
    private $class;

    /**
     * @var ServiceManager
     */
    private $instance;

    /**
     * @var ReflectionClass
     */
    private $reflection;

    public function __construct($class)
    {
        $this->class = $class;
    }

    public function getFactoriesString()
    {
        $factories = $this->sortProperties($this->getFactories());

        return "    protected \$factories = [\n"
        . $this->toPhpString($factories, str_repeat(' ', 8))
        . "   ];\n";
    }

    public function getFactories()
    {
        $reflected = $this->getReflectedProperty('factories');
        $factories = $existing = $this->getParsedProperty('factories')['values'];
        $new = [];

        foreach ($reflected as $key => $value) {
            $value = array_shift($existing);
            if ($this->isFqcn($key)) {
                $normalized = $this->normalize($key);
                if (! $this->hasNormalized($existing, $normalized)) {
                    $new["'" . $normalized . "'"] = $value;
                }
            }
        }

        return $factories + $new;
    }

    private function getReflection()
    {
        if (! $this->reflection) {
            try {
                $this->reflection = new ReflectionClass($this->class);
            } catch (\ReflectionException $e) {
                throw new MigrationException(sprintf("Couldn't reflect '%s'", $this->class));
            }
        }
        return $this->reflection;
    }

    private function getInstance()
    {
        if (! $this->instance) {
            $class = $this->class;
            $reflection = $this->getReflection();
            if ($reflection->isSubclassOf(AbstractPluginManager::class)) {
                $this->instance = new $class(new ServiceManager());
            } else {
                $this->instance = new $class();
            }
        }
        return $this->instance;
    }

    private function getReflectedProperty($name)
    {
        $reflection = $this->getReflection();
        try {
            $property = $reflection->getProperty($name);
            $property->setAccessible(true);
            return $property->getValue($this->getInstance());
        } catch (\ReflectionException $e) {
            return [];
        }
    }

    private function getParsedProperty($name)
    {
        $property = ['raw' => '', 'values' => []];
        $contents = $this->getContents();
        if (preg_match('/protected\s+\$' . $name . '\s*=\s*\[(.*)\];/Us', $contents, $matches)) {
            $property['raw'] = $matches[0];
            $lines = explode("\n", $matches[1]);
            foreach ($lines as $line) {
                $parts = explode('=>', $line);
                if (count($parts) > 1) {
                    $property['values'][trim($parts[0])] = preg_replace('/^\s*(.*)[,\s]+/', '$1', $parts[1]);
                }
            }
        }
        return $property;
    }

    private function getContents()
    {
        return file_get_contents($this->getFilename());
    }

    private function getFilename()
    {
        return $this->getReflection()->getFileName();
    }

    private function isFqcn($string)
    {
        return preg_match('/^[A-Z].*\\\\/', $string);
    }

    private function normalize($fqcn)
    {
        return preg_replace('/[^a-z]/', '', strtolower($fqcn));
    }

    private function hasNormalized($parsed, $normalized)
    {
        return isset($parsed['"' . $normalized . '"']) || isset($parsed["'" . $normalized . "'"]);
    }

    private function sortProperties($properties)
    {
        uksort($properties, function($a, $b) {
            $aIsClass = strstr($a, '::class');
            $bIsClass = strstr($b, '::class');

            if ($aIsClass && !$bIsClass) {
                return -1;
            } elseif (!$aIsClass && $bIsClass) {
                return 1;
            }

            if ($a == $b) return 0;
            return $a < $b ? -1 : 1;
        });
        return $properties;
    }

    private function toPhpString(array $array, $indent)
    {
        $maxLen = 0;
        foreach (array_keys($array) as $key) {
            $len = strlen($key);
            if ($len > $maxLen) {
                $maxLen = $len;
            }
        }

        $string = '';
        foreach ($array as $key => $value) {
            $string .= $indent . str_pad($key, $maxLen) . ' => ' . $value . ",\n";
        }
        return $string;
    }
}
