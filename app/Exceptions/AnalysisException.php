<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when an AI analysis cannot be completed.
 *
 * Carries a stable machine-readable error type so callers can distinguish
 * between rate limits, unavailable models, provider errors, timeouts, and
 * invalid responses instead of silently returning a bogus score of 0.
 */
class AnalysisException extends RuntimeException
{
    public const RATE_LIMIT           = 'RATE_LIMIT';
    public const MODEL_UNAVAILABLE    = 'MODEL_UNAVAILABLE';
    public const AUTH_ERROR           = 'AUTH_ERROR';
    public const INSUFFICIENT_CREDITS = 'INSUFFICIENT_CREDITS';
    public const SERVER_ERROR         = 'SERVER_ERROR';
    public const TIMEOUT              = 'TIMEOUT';
    public const INVALID_RESPONSE     = 'INVALID_RESPONSE';
    public const EMPTY_RESPONSE       = 'EMPTY_RESPONSE';
    public const NO_MODELS_AVAILABLE  = 'NO_MODELS_AVAILABLE';
    public const UNKNOWN              = 'UNKNOWN';

    public function __construct(
        string $message,
        public readonly string $errorType = self::UNKNOWN,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * A short, human-readable message suitable for end users.
     */
    public function friendlyMessage(): string
    {
        return match ($this->errorType) {
            self::RATE_LIMIT           => 'The AI service is rate-limited right now. Please wait a moment and try again.',
            self::MODEL_UNAVAILABLE    => 'The selected AI model is currently unavailable. Please try again.',
            self::AUTH_ERROR           => 'AI authentication failed. Please check your OpenRouter API key.',
            self::INSUFFICIENT_CREDITS => 'AI credits are exhausted. Please top up your OpenRouter balance.',
            self::SERVER_ERROR         => 'The AI service is having issues right now. Please try again later.',
            self::TIMEOUT              => 'The AI service took too long to respond. Please try again.',
            self::INVALID_RESPONSE,
            self::EMPTY_RESPONSE       => 'The AI returned an invalid response. Please try again.',
            self::NO_MODELS_AVAILABLE  => 'No AI models are available right now. Please try again later.',
            default                    => 'AI analysis failed unexpectedly. Please try again.',
        };
    }
}
