<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS;

/**
 * Representation of single token.
 * As a token prototype you should understand a single element generated by token_get_all.
 *
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
class Token
{
    /**
     * Content of token prototype.
     * @type string
     */
    public $content;

    /**
     * ID of token prototype, if available.
     * @type int|null
     */
    public $id;

    /**
     * If token prototype is an array.
     * @type bool
     */
    private $isArray;

    /**
     * Line of token prototype occurrence, if available.
     * @type int|null
     */
    public $line;

    /**
     * Constructor.
     *
     * @param string|array $token token prototype
     */
    public function __construct($token)
    {
        if (is_array($token)) {
            $this->isArray = true;
            $this->id = $token[0];
            $this->content = $token[1];
            $this->line = isset($token[2]) ? $token[2] : null;
        } else {
            $this->isArray = false;
            $this->content = $token;
        }
    }

    /**
     * Clear token at given index.
     * Clearing means override token by empty string.
     *
     * @param int $index token index
     */
    public function clear()
    {
        $this->content = '';
        $this->id = null;
        $this->line = null;
        $this->isArray = false;
    }

    /**
     * Get token prototype.
     *
     * @return string|array token prototype
     */
    public function getPrototype()
    {
        if (!$this->isArray) {
            return $this->content;
        }

        return array(
            $this->id,
            $this->content,
            $this->line,
        );
    }

    /**
     * Get token name.
     *
     * @return null|string token name
     */
    public function getName()
    {
        return isset($this->id) ? token_name($this->id) : null;
    }

    /**
     * Generate keywords array contains all keywords that exists in used PHP version.
     *
     * @return array
     */
    public static function getKeywords()
    {
        static $keywords = null;

        if (null === $keywords) {
            $keywords = array();
            $keywordsStrings = array('T_ABSTRACT', 'T_ARRAY', 'T_AS', 'T_BREAK', 'T_CALLABLE', 'T_CASE',
                'T_CATCH', 'T_CLASS', 'T_CLONE', 'T_CONST', 'T_CONTINUE', 'T_DECLARE', 'T_DEFAULT', 'T_DO',
                'T_ECHO', 'T_ELSE', 'T_ELSEIF', 'T_EMPTY', 'T_ENDDECLARE', 'T_ENDFOR', 'T_ENDFOREACH',
                'T_ENDIF', 'T_ENDSWITCH', 'T_ENDWHILE', 'T_EVAL', 'T_EXIT', 'T_EXTENDS', 'T_FINAL',
                'T_FINALLY', 'T_FOR', 'T_FOREACH', 'T_FUNCTION', 'T_GLOBAL', 'T_GOTO', 'T_HALT_COMPILER',
                'T_IF', 'T_IMPLEMENTS', 'T_INCLUDE', 'T_INCLUDE_ONCE', 'T_INSTANCEOF', 'T_INSTEADOF',
                'T_INTERFACE', 'T_ISSET', 'T_LIST', 'T_LOGICAL_AND', 'T_LOGICAL_OR', 'T_LOGICAL_XOR',
                'T_NAMESPACE', 'T_NEW', 'T_PRINT', 'T_PRIVATE', 'T_PROTECTED', 'T_PUBLIC', 'T_REQUIRE',
                'T_REQUIRE_ONCE', 'T_RETURN', 'T_STATIC', 'T_SWITCH', 'T_THROW', 'T_TRAIT', 'T_TRY',
                'T_UNSET', 'T_USE', 'T_VAR', 'T_WHILE', 'T_YIELD'
            );

            foreach ($keywordsStrings as $keywordName) {
                if (defined($keywordName)) {
                    $keyword = constant($keywordName);
                    $keywords[$keyword] = $keyword;
                }
            }
        }

        return $keywords;
    }

    /**
     * Check if token prototype is an array.
     *
     * @return bool is array
     */
    public function isArray()
    {
        return $this->isArray;
    }

    /**
     * Check if token is one of type cast tokens.
     *
     * @return bool
     */
    public function isCast()
    {
        static $castTokens = array(T_ARRAY_CAST, T_BOOL_CAST, T_DOUBLE_CAST, T_INT_CAST, T_OBJECT_CAST, T_STRING_CAST, T_UNSET_CAST, );

        return $this->isGivenKind($castTokens);
    }

    /**
     * Check if token is one of classy tokens: T_CLASS, T_INTERFACE or T_TRAIT.
     *
     * @return bool
     */
    public function isClassy()
    {
        static $classTokens = null;

        if (null === $classTokens) {
            $classTokens = array(T_CLASS, T_INTERFACE);

            if (defined('T_TRAIT')) {
                $classTokens[] = constant('T_TRAIT');
            }
        }

        return $this->isGivenKind($classTokens);
    }

    /**
     * Check if token is one of comment tokens: T_COMMENT or T_DOC_COMMENT.
     *
     * @return bool
     */
    public function isComment()
    {
        static $commentTokens = array(T_COMMENT, T_DOC_COMMENT);

        return $this->isGivenKind($commentTokens);
    }

    /**
     * Check if token is one of given kind.
     *
     * @param  int|array $possibleKind kind or array of kinds
     * @return bool
     */
    public function isGivenKind($possibleKind)
    {
        return $this->isArray && (is_array($possibleKind) ? in_array($this->id, $possibleKind) : $this->id === $possibleKind);
    }

    /**
     * Check if token is a keyword.
     *
     * @return bool
     */
    public function isKeyword()
    {
        $keywords = static::getKeywords();

        return $this->isArray && isset($keywords[$this->id]);
    }

    /**
     * Check if token is a native PHP constant: true, false or null.
     *
     * @return bool
     */
    public function isNativeConstant()
    {
        static $nativeConstantStrings = array('true', 'false', 'null');

        return $this->isArray && in_array(strtolower($this->content), $nativeConstantStrings);
    }

    /**
     * Check if token is a whitespace.
     *
     * @param  array  $opts                array of extra options
     * @param  string $opts['whitespaces'] string determining whitespaces chars, default is " \t\n"
     * @return bool
     */
    public function isWhitespace(array $opts = array())
    {
        $whitespaces = isset($opts['whitespaces']) ? $opts['whitespaces'] : " \t\n";

        if ($this->isArray && !$this->isGivenKind(T_WHITESPACE)) {
            return false;
        }

        return '' === trim($this->content, $whitespaces);
    }
}
