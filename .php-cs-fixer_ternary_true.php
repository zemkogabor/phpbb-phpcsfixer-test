<?php

declare(strict_types = 1);

$finder = PhpCsFixer\Finder::create()
    ->in('phpBB')
    ->in('tests')
    ->name('*.php')
    ->ignoreDotFiles(true);

$config = new PhpCsFixer\Config;
$config->setUsingCache(false);

return $config->setRules([
	'ternary_operator_spaces' => true,
])->setFinder($finder)->setRiskyAllowed(true);
