<?php

namespace Flute\Modules\Crynova\Providers;

use Flute\Core\Support\ModuleServiceProvider;
use Flute\Core\Modules\Payments\Events\RegisterPaymentFactoriesEvent;
use Flute\Core\Modules\Payments\Factories\PaymentDriverFactory;
use Flute\Modules\Crynova\Drivers\CrynovaDriver;

class CrynovaProvider extends ModuleServiceProvider
{
    protected ?string $moduleName = 'Crynova';

    public function boot(\DI\Container $container): void
    {
        $this->bootstrapModule();
        $this->registerPaymentGateway();
    }

    /** Register the Crynova driver with the Payments module. */
    protected function registerPaymentGateway(): void
    {
        events()->addDeferredListener(
            RegisterPaymentFactoriesEvent::NAME,
            function ($event) {
                /** @var PaymentDriverFactory $factory */
                $factory = app(PaymentDriverFactory::class);
                $factory->register('crynova', CrynovaDriver::class);
            }
        );
    }
}
