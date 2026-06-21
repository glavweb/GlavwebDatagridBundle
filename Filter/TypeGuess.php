<?php

namespace Glavweb\DatagridBundle\Filter;

/**
 * Class TypeGuess.
 *
 * Contains a guessed class name and a list of options for creating an instance
 * of that class.
 *
 * Copied from Symfony\Component\Form\Guess
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TypeGuess
{
    /**
     * Marks an instance with a value that is extremely likely to be correct.
     */
    public const int VERY_HIGH_CONFIDENCE = 3;

    /**
     * Marks an instance with a value that is very likely to be correct.
     */
    public const int HIGH_CONFIDENCE = 2;

    /**
     * Marks an instance with a value that is likely to be correct.
     */
    public const int MEDIUM_CONFIDENCE = 1;

    /**
     * Marks an instance with a value that may be correct.
     */
    public const int LOW_CONFIDENCE = 0;

    /**
     * The confidence about the correctness of the value.
     *
     * One of VERY_HIGH_CONFIDENCE, HIGH_CONFIDENCE, MEDIUM_CONFIDENCE
     * and LOW_CONFIDENCE.
     */
    private readonly int $confidence;

    /**
     * Constructor.
     *
     * @param string $type       The guessed field type
     * @param array  $options    The options for creating instances of the
     *                           guessed class
     * @param int    $confidence The confidence that the guessed class name
     *                           is correct
     *
     * @throws \InvalidArgumentException if the given value of confidence is unknown
     */
    public function __construct(
        /**
         * The guessed field type.
         */
        private readonly string $type,
        /**
         * The guessed options for creating an instance of the guessed class.
         */
        private readonly array $options,
        int $confidence,
    ) {
        if (self::VERY_HIGH_CONFIDENCE !== $confidence && self::HIGH_CONFIDENCE !== $confidence
            && self::MEDIUM_CONFIDENCE !== $confidence && self::LOW_CONFIDENCE !== $confidence) {
            throw new \InvalidArgumentException('The confidence should be one of the constants defined in Guess.');
        }

        $this->confidence = $confidence;
    }

    /**
     * Returns the guess most likely to be correct from a list of guesses.
     *
     * If there are multiple guesses with the same, highest confidence, the
     * returned guess is any of them.
     *
     * @param typeGuess[] $guesses An array of guesses
     */
    public static function getBestGuess(array $guesses): ?self
    {
        $result = null;
        $maxConfidence = -1;

        foreach ($guesses as $guess) {
            if ($maxConfidence < $confidence = $guess->getConfidence()) {
                $maxConfidence = $confidence;
                $result = $guess;
            }
        }

        return $result;
    }

    /**
     * Returns the confidence that the guessed value is correct.
     *
     * @return int One of the constants VERY_HIGH_CONFIDENCE, HIGH_CONFIDENCE,
     *             MEDIUM_CONFIDENCE and LOW_CONFIDENCE
     */
    public function getConfidence(): int
    {
        return $this->confidence;
    }

    /**
     * Returns the guessed field type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns the guessed options for creating instances of the guessed type.
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
