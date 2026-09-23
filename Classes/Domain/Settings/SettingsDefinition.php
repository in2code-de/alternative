<?php

declare(strict_types=1);

namespace In2code\Alternative\Domain\Settings;

use In2code\Alternative\Domain\Model\Setting;
use In2code\Alternative\Utility\ConfigurationUtility;

/**
 * All settings that can be overwritten in the backend module "AI > Alternative"
 */
class SettingsDefinition
{
    /**
     * @return Setting[]
     */
    public function getSettings(): array
    {
        return [
            new Setting(
                key: 'apiKey',
                type: Setting::TYPE_PASSWORD,
                environmentVariable: ConfigurationUtility::ENVIRONMENT_API_KEY,
                adminOnly: true,
            ),
            new Setting(
                key: 'geminiModel',
                default: ConfigurationUtility::DEFAULT_GEMINI_MODEL,
                environmentVariable: ConfigurationUtility::ENVIRONMENT_GEMINI_MODEL,
            ),
            new Setting(
                key: 'thinkingLevel',
                type: Setting::TYPE_SELECT,
                default: ConfigurationUtility::DEFAULT_THINKING_LEVEL,
                options: ConfigurationUtility::THINKING_LEVELS,
            ),
            new Setting(
                key: 'promptPrefix',
                type: Setting::TYPE_TEXTAREA,
            ),
            new Setting(
                key: 'promptPrefixFile',
                adminOnly: true,
            ),
            new Setting(
                key: 'setAlternative',
                type: Setting::TYPE_BOOLEAN,
                default: '1',
            ),
            new Setting(
                key: 'setTitle',
                type: Setting::TYPE_BOOLEAN,
                default: '1',
            ),
            new Setting(
                key: 'setDescription',
                type: Setting::TYPE_BOOLEAN,
                default: '1',
            ),
            new Setting(
                key: 'showButtonInFileList',
                type: Setting::TYPE_BOOLEAN,
                default: '1',
            ),
            new Setting(
                key: 'limitToLanguages',
            ),
            new Setting(
                key: 'maxLengthTitle',
                type: Setting::TYPE_INTEGER,
                default: '50',
            ),
            new Setting(
                key: 'maxLengthAlternative',
                type: Setting::TYPE_INTEGER,
                default: '125',
            ),
            new Setting(
                key: 'maxLengthDescription',
                type: Setting::TYPE_INTEGER,
                default: '255',
            ),
        ];
    }
}
