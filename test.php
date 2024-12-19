<?php

// Direct echo to make sure basic output works
echo "Starting test...\n";

// Test basic exec functionality
$test = exec('echo "test"');
echo "Basic exec test: $test\n";

// Test cwebp check
$output = [];
$return_var = null;
exec('command -v cwebp', $output, $return_var);

echo "Return var: $return_var\n";
echo "Output: ";
print_r($output);

// Test with shell_exec as alternative
$shell_result = shell_exec('command -v cwebp');
echo "Shell exec result: " . ($shell_result ?: "not found") . "\n";
