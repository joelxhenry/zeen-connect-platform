<?php

namespace App\Support\Traits;

trait HasSettings
{
    /**
     * Get a setting value with optional default.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->settings ?? [];

        return data_get($settings, $key, $default);
    }

    /**
     * Set a single setting value.
     */
    public function setSetting(string $key, mixed $value): static
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;

        return $this;
    }

    /**
     * Set multiple settings at once.
     */
    public function setSettings(array $values): static
    {
        $settings = $this->settings ?? [];
        foreach ($values as $key => $value) {
            data_set($settings, $key, $value);
        }
        $this->settings = $settings;

        return $this;
    }

    /**
     * Check if a setting exists and is not null.
     */
    public function hasSetting(string $key): bool
    {
        return data_get($this->settings ?? [], $key) !== null;
    }

    /**
     * Remove a setting.
     */
    public function forgetSetting(string $key): static
    {
        $settings = $this->settings ?? [];
        unset($settings[$key]);
        $this->settings = $settings;

        return $this;
    }

    /**
     * Get all settings.
     */
    public function getAllSettings(): array
    {
        return $this->settings ?? [];
    }

    /**
     * Merge settings with existing, only updating provided keys.
     */
    public function mergeSettings(array $values): static
    {
        $settings = array_merge($this->settings ?? [], $values);
        $this->settings = $settings;

        return $this;
    }
}
