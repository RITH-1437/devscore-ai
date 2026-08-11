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
    public const AI_RATE_LIMIT           = 'AI_RATE_LIMIT';
    public const AI_MODEL_UNAVAILABLE    = 'AI_MODEL_UNAVAILABLE';
    public const AI_AUTH_ERROR           = 'AI_AUTH_ERROR';
    public const AI_INSUFFICIENT_CREDITS = 'AI_INSUFFICIENT_CREDITS';
    public const AI_SERVER_ERROR         = 'AI_SERVER_ERROR';
    public const AI_TIMEOUT              = 'AI_TIMEOUT';
    public const AI_INVALID_RESPONSE     = 'AI_INVALID_RESPONSE';
    public const AI_EMPTY_RESPONSE       = 'AI_EMPTY_RESPONSE';
    public const AI_NO_MODELS_AVAILABLE  = 'AI_NO_MODELS_AVAILABLE';
    public const AI_CONFIGURATION_ERROR  = 'AI_CONFIGURATION_ERROR';
    public const AI_NETWORK_ERROR        = 'AI_NETWORK_ERROR';
    public const AI_PARSE_ERROR          = 'AI_PARSE_ERROR';
    public const AI_UNKNOWN_ERROR        = 'AI_UNKNOWN_ERROR';

    /** @deprecated Use AI_RATE_LIMIT */
    public const RATE_LIMIT = self::AI_RATE_LIMIT;

    /** @deprecated Use AI_MODEL_UNAVAILABLE */
    public const MODEL_UNAVAILABLE = self::AI_MODEL_UNAVAILABLE;

    /** @deprecated Use AI_AUTH_ERROR */
    public const AUTH_ERROR = self::AI_AUTH_ERROR;

    /** @deprecated Use AI_INSUFFICIENT_CREDITS */
    public const INSUFFICIENT_CREDITS = self::AI_INSUFFICIENT_CREDITS;

    /** @deprecated Use AI_SERVER_ERROR */
    public const SERVER_ERROR = self::AI_SERVER_ERROR;

    /** @deprecated Use AI_TIMEOUT */
    public const TIMEOUT = self::AI_TIMEOUT;

    /** @deprecated Use AI_INVALID_RESPONSE */
    public const INVALID_RESPONSE = self::AI_INVALID_RESPONSE;

    /** @deprecated Use AI_EMPTY_RESPONSE */
    public const EMPTY_RESPONSE = self::AI_EMPTY_RESPONSE;

    /** @deprecated Use AI_NO_MODELS_AVAILABLE */
    public const NO_MODELS_AVAILABLE = self::AI_NO_MODELS_AVAILABLE;

    /** @deprecated Use AI_UNKNOWN_ERROR */
    public const UNKNOWN = self::AI_UNKNOWN_ERROR;

    public function __construct(
        string $message,
        public readonly string $errorType = self::AI_UNKNOWN_ERROR,
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
            self::AI_RATE_LIMIT,
            self::RATE_LIMIT           => 'The AI service is rate-limited right now. Please wait a moment and try again.',
            self::AI_MODEL_UNAVAILABLE,
            self::MODEL_UNAVAILABLE    => 'The selected AI model is currently unavailable. Please try again.',
            self::AI_AUTH_ERROR,
            self::AUTH_ERROR           => 'AI authentication failed. Please check your API key configuration.',
            self::AI_INSUFFICIENT_CREDITS,
            self::INSUFFICIENT_CREDITS => 'AI credits are exhausted. Please top up your provider balance.',
            self::AI_SERVER_ERROR,
            self::SERVER_ERROR         => 'The AI service is having issues right now. Please try again later.',
            self::AI_TIMEOUT,
            self::TIMEOUT              => 'The AI service took too long to respond. Please try again.',
            self::AI_NETWORK_ERROR     => 'Could not reach the AI service. Please check your connection and try again.',
            self::AI_CONFIGURATION_ERROR => 'AI analysis is not configured correctly. Please check your Gemini or OpenRouter settings.',
            self::AI_INVALID_RESPONSE,
            self::AI_EMPTY_RESPONSE,
            self::INVALID_RESPONSE,
            self::EMPTY_RESPONSE       => 'The AI returned an invalid response. Please try again.',
            self::AI_PARSE_ERROR       => 'The AI response could not be parsed. Please try again.',
            self::AI_NO_MODELS_AVAILABLE,
            self::NO_MODELS_AVAILABLE  => 'No AI models are available right now. Please try again later.',
            default                    => 'AI analysis failed unexpectedly. Please try again.',
        };
    }
}
