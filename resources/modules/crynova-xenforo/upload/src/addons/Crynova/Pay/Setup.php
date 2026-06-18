<?php

namespace Crynova\Pay;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\AddOn\StepRunnerUninstallTrait;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1(): void
    {
        $this->db()->insert('xf_payment_provider', [
            'provider_id'    => 'crynova',
            'provider_class' => 'Crynova\Pay:Crynova',
            'addon_id'       => 'Crynova/Pay',
        ], false, 'provider_class = VALUES(provider_class), addon_id = VALUES(addon_id)');
    }

    public function uninstallStep1(): void
    {
        $this->db()->delete('xf_payment_provider', 'provider_id = ?', 'crynova');
    }
}
