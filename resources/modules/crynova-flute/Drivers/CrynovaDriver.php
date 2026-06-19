<?php

namespace Flute\Modules\Crynova\Drivers;

use Flute\Core\Modules\Payments\Drivers\AbstractOmnipayDriver;
use Flute\Modules\Crynova\Gateway\CrynovaGateway;

/**
 * Flute payment driver for Crynova. Delegates payment processing to the
 * Omnipay-compatible CrynovaGateway; Flute core handles creation, the redirect
 * and the callback automatically.
 */
class CrynovaDriver extends AbstractOmnipayDriver
{
    /** Fully-qualified Omnipay gateway class used as the adapter. */
    protected $adapter = CrynovaGateway::class;

    /** Human-readable name shown in the admin panel. */
    protected $name = 'Crynova';

    /** Admin settings template (Blade view, registered by the module). */
    protected $settingsView = 'Crynova::settings';

    /** Validation rules for the admin settings form. */
    public function getValidationRules(): array
    {
        return [
            'apiKey'        => 'required|string',
            'webhookSecret' => 'required|string',
            'apiBase'       => 'nullable|string',
        ];
    }

    public function validateSettings(array $settings): bool
    {
        return ! empty($settings['apiKey']) && ! empty($settings['webhookSecret']);
    }

    public function getSettings(): array
    {
        return [
            'apiKey'        => '',
            'webhookSecret' => '',
            'apiBase'       => 'https://crynova.io/api/v1',
            'testMode'      => false,
        ];
    }
}
