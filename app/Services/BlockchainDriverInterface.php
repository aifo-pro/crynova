<?php

namespace App\Services;

interface BlockchainDriverInterface
{
    // Derive deposit address at given HD index. Returns ['address'=>..., 'path'=>..., 'memo'=>null]
    public function deriveAddress(int $index): array;

    // Fetch confirmed balance for address (in smallest unit string)
    public function getBalance(string $address): string;

    // Fetch incoming transactions for an address since a given block height
    public function getTransactions(string $address, int $fromBlock = 0, ?\App\Models\Currency $currency = null): array;

    // Broadcast a signed raw transaction hex. Returns tx hash.
    public function broadcast(string $rawTx): string;

    // Get current block height
    public function getBlockHeight(): int;
}
