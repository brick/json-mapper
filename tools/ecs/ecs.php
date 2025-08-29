<?php

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting\FunctionCommentSniff;
use PhpCsFixer\Fixer\ClassNotation\OrderedTypesFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTypesOrderFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->import(__DIR__ . '/vendor/brick/coding-standard/ecs.php');

    $libRootPath = realpath(__DIR__ . '/../../');

    $ecsConfig->paths(
        [
            $libRootPath . '/src',
            $libRootPath . '/tests',
            __FILE__,
        ],
    );

    $ecsConfig->skip([
        // test classes may contain badly formatted code on purpose
        $libRootPath . '/tests/Classes/*.php',

        // types are sometimes ordered in non-alphabetical order on purpose
        OrderedTypesFixer::class,
        PhpdocTypesOrderFixer::class,

        // fails with multiline param phpdocs
        FunctionCommentSniff::class . '.MissingParamName',
    ]);
};
