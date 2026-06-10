<?php

namespace App\Services\Blockchain;

use App\Services\HdWalletService;

class LitecoinDriver extends BitcoinDriver
{
    public function __construct(HdWalletService $hdWallet)
    {
        parent::__construct($hdWallet);
        $this->rpcUrl      = config('crynova.ltc.node_url');
        $this->rpcUser     = config('crynova.ltc.node_user');
        $this->rpcPass     = config('crynova.ltc.node_pass');
        $this->explorerUrl = config('crynova.ltc.explorer_url');
    }

    public function deriveAddress(int $index): array
    {
        if ($this->hdWallet->hasXpub('litecoin')) {
            return $this->hdWallet->deriveLitecoin($index);
        }

        $response = $this->rpc('getnewaddress', ["crynova_deposit_{$index}", 'bech32']);

        return [
            'address' => $response,
            'path'    => "m/84'/2'/0'/0/{$index}",
            'memo'    => null,
        ];
    }
}
