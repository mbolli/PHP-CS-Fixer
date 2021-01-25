<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests\Fixer\Operator;

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * @author Michael Bolli <michael@bolli.us>
 *
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\Operator\ConcatToComplexStringFixer
 */
final class ConcatToComplexStringFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases()
    {
        return [
            'function' => [
                '<?php $foo = "hello {${getName($this)}} {$otherGreeting}!";',
                '<?php $foo = "hello " . getName($this) . " " . $otherGreeting . \'!\';',
            ],
            /*'variable' => [
                '<?php $foo = "a{$b}c";',
                '<?php $foo = "a" . $b . "c";',
            ],
            'class member' => [
                '<?php $foo = "a{$b->c}d";',
                '<?php $foo = "a" . $b->c . "d";',
            ],*/
        ];
    }
}
