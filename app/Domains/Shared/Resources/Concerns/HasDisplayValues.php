<?php

namespace App\Domains\Shared\Resources\Concerns;

trait HasDisplayValues
{
    /**
     * Format a money value for display.
     */
    protected function formatMoney(float|int|null $value, string $symbol = '$', int $decimals = 2): string
    {
        return $symbol.number_format($value ?? 0, $decimals);
    }

    /**
     * Format a date for display.
     */
    protected function formatDate(?\DateTimeInterface $date, string $format = 'M j, Y'): ?string
    {
        if (! $date) {
            return null;
        }

        return $date->format($format);
    }

    /**
     * Format a datetime for display.
     */
    protected function formatDateTime(?\DateTimeInterface $date, string $format = 'M j, Y g:i A'): ?string
    {
        return $this->formatDate($date, $format);
    }

    /**
     * Format a time for display.
     */
    protected function formatTime(?\DateTimeInterface $time, string $format = 'g:i A'): ?string
    {
        return $this->formatDate($time, $format);
    }

    /**
     * Get a human-readable time difference.
     */
    protected function formatTimeAgo(?\DateTimeInterface $date): ?string
    {
        if (! $date) {
            return null;
        }

        return $date->diffForHumans();
    }

    /**
     * Format a percentage for display.
     */
    protected function formatPercentage(float|int|null $value, int $decimals = 0): string
    {
        return number_format($value ?? 0, $decimals).'%';
    }

    /**
     * Format a duration in minutes for display.
     */
    protected function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.' min';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return $hours.' hr'.($hours > 1 ? 's' : '');
        }

        return $hours.' hr '.($remainingMinutes > 0 ? $remainingMinutes.' min' : '');
    }
}
