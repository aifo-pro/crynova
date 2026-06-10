<?php

namespace App\Services\Blockchain;

use App\Services\HdWalletService;

class DogecoinDriver extends BitcoinDriver
{
    public function __construct(HdWalletService $hdWallet)
    {
        parent::__construct($hdWallet);
        $this->rpcUrl      = config('crynova.doge.node_url');
        $this->rpcUser     = config('crynova.doge.node_user');
        $this->rpcPass     = config('crynova.doge.node_pass');
        $this->explorerUrl = config('crynova.doge.explorer_url');
    }

    public function deriveAddress(int $index): array
    {
        if ($this->hdWallet->hasXpub('dogecoin')) {
            return $this->hdWallet->deriveDogecoin($index);
        }

        $response = $this->rpc('getnewaddress', ["crynova_deposit_{$index}"]);

        return [
            'address' => $response,
            'path'    => "m/44'/3'/0'/0/{$index}",
            'memo'    => null,
        ];
    }
}
