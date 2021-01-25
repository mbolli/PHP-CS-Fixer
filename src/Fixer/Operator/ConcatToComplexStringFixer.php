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

namespace PhpCsFixer\Fixer\Operator;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\FunctionsAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * @author SpacePossum
 */
final class ConcatToComplexStringFixer extends AbstractFixer
{
    private $processed = 0;
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Converts concatenated strings to complex strings.',
            [
                new CodeSample(
                    "<?php\n\$foo = 'bar ' . baz() . ' baz' . \$baz;\n"
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound('.');
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $dotsNum = $tokens->countTokenKind('.');
        $overrides = [];
        /**
         * @var int $i
         * @var Token $token
         */
        for ($i = 0; $i < $tokens->getSize(); $i++) {
            $token = $tokens[$i];
            if (!$token->equals('.') || $this->processed > $dotsNum) {
                continue;
            }

            $tokens->clearAt($i);
            $tokens->removeTrailingWhitespace($i);
            $tokens->removeLeadingWhitespace($i);

            // process left part of the current "." (only at the beginning)
            if ($this->processed == 0) {
                $prevId = $tokens->getPrevMeaningfulToken($i);
                $overrides[] = $this->replaceToken($tokens, $prevId);
                //$i = $override['rangeStart'] + count($override['newTokens']);
            }

            $this->processed++;

            // process right part of the current "."
            $nextId = $tokens->getNextMeaningfulToken($i);
            $overrides[] = $this->replaceToken($tokens, $nextId, $this->processed === $dotsNum);
            //$i = $override['rangeStart'] + count($override['newTokens']);
        }

        $offset = 0;
        foreach ($overrides as $override) {
            $tokens->overrideRange($offset + $override['rangeStart'], $offset + $override['rangeEnd'], $override['newTokens']);
            $offset += count($override['newTokens'])-($override['rangeEnd']-$override['rangeStart']);
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $index
     * @param bool $last
     * @return array
     */
    private function replaceToken(Tokens $tokens, $index, $last = false)
    {
        /** @var Token $token */
        $token = $tokens[$index];
        $rangeStart = $index;
        $rangeEnd = !$last ? $tokens->getNextMeaningfulToken($index) : $rangeStart;
        $newTokens = [new Token('^')];

        if ($this->processed === 0) {
            $newTokens[] = new Token("\"");
        }

        if ($token->isGivenKind(T_STRING)) {
            $closingBrace = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $tokens->getNextMeaningfulToken($index));
            $rangeEnd = $closingBrace;

            $newTokens[] = new Token([T_CURLY_OPEN, "{"]);
            $newTokens[] = new Token([T_DOLLAR_OPEN_CURLY_BRACES, '${']);
            $newTokens[] = $token;
            for ($i = $index+1; $i <= $closingBrace; $i++) {
                $newTokens[] = $tokens[$i];
            }
            $newTokens[] = new Token([CT::T_CURLY_CLOSE, "}"]);
            $newTokens[] = new Token([CT::T_CURLY_CLOSE, "}"]);
        } elseif ($token->isGivenKind(T_VARIABLE)) {
            $newTokens[] = new Token("{");
            $newTokens[] = $token;
            $newTokens[] = new Token("}");
        } elseif ($token->isGivenKind([T_ENCAPSED_AND_WHITESPACE, T_CONSTANT_ENCAPSED_STRING])) {
            $newTokens[] = new Token([T_ENCAPSED_AND_WHITESPACE, trim($token->getContent(), "\"'")]);
        } else {
            $newTokens[] = $token;
        }

        if ($last === true) {
            $newTokens[] = new Token("\"");
        }

        return [
            'newTokens' => $newTokens,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
        ];
    }
}
