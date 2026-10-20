<?php
/** Dependency-free runner for the integration test methods. Disposable WordPress only. */
namespace PHPUnit\Framework {
    class TestCase {
        public static int $assertions = 0;
        public static function assertSame($expected, $actual): void { ++self::$assertions; if ($expected !== $actual) { throw new \RuntimeException('Expected ' . var_export($expected, true) . '; got ' . var_export($actual, true)); } }
        public static function assertInstanceOf($class, $value): void { ++self::$assertions; if (!$value instanceof $class) { throw new \RuntimeException('Expected instance of ' . $class . '; got ' . var_export($value, true)); } }
        public static function assertIsArray($value): void { ++self::$assertions; if (!is_array($value)) { throw new \RuntimeException('Expected an array; got ' . var_export($value, true)); } }
        public static function assertFalse($value): void { self::assertSame(false, $value); }
        public static function assertNotNull($value): void { ++self::$assertions; if ($value === null) { throw new \RuntimeException('Unexpected null'); } }
        public static function assertContains($needle, $haystack): void { ++self::$assertions; if (!in_array($needle, $haystack, true)) { throw new \RuntimeException('Missing expected element: ' . $needle); } }
        public static function assertStringNotContainsString($needle, $haystack): void { ++self::$assertions; if (str_contains($haystack, $needle)) { throw new \RuntimeException('Unexpected string: ' . $needle); } }
        public static function assertArrayNotHasKey($key, $array): void { ++self::$assertions; if (array_key_exists($key, $array)) { throw new \RuntimeException('Unexpected key: ' . $key); } }
    }
}
namespace {
    if (PHP_SAPI !== 'cli' || empty($argv[1]) || !is_file($argv[1])) { exit("Usage: php scripts/smoke-tests.php /disposable/wordpress/wp-load.php\n"); }
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['HTTP_HOST'] = 'example.org';
    $_SERVER['REQUEST_URI'] = '/';
    require $argv[1];
    require dirname(__DIR__) . '/tests/ConnectorTest.php';
    $test = new \ConnectorTest();
    $setup = new \ReflectionMethod($test, 'setUp');
    $teardown = new \ReflectionMethod($test, 'tearDown');
    $count = 0;
    $failures = 0;
    foreach (get_class_methods($test) as $method) {
        if (!str_starts_with($method, 'test')) { continue; }
        ++$count;
        try {
            $setup->invoke($test);
            $test->$method();
            echo "PASS $method\n";
        } catch (\Throwable $error) {
            ++$failures;
            echo "FAIL $method: " . $error->getMessage() . "\n";
        } finally {
            $teardown->invoke($test);
        }
    }
    echo "$count tests, " . \PHPUnit\Framework\TestCase::$assertions . " assertions, $failures failures\n";
    exit($failures ? 1 : 0);
}
