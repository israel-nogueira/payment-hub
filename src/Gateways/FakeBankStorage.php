<?php

namespace IsraelNogueira\PaymentHub\Gateways;

class FakeBankStorage
{
    private string $storagePath;
    
    private const TYPES = [
        'transactions',
        'customers',
        'tokens',
        'wallets',
        'subscriptions',
        'sub_accounts',
        'escrows',
        'payment_links',
        'refunds',
        'transfers'
    ];

    public function __construct(?string $storagePath = null)
    {
        $this->storagePath = $storagePath ?? __DIR__ . '/../../storage/fakebank';
        $this->ensureStorageExists();
    }

    /**
     * Salvar dados
     */
    public function save(string $type, string $id, array $data): bool
    {
        $this->validateType($type);
        
        $all = $this->getAll($type);
        $all[$id] = array_merge($data, [
            'id' => $id,
            'created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return $this->writeFile($type, $all);
    }

    /**
     * Buscar por ID
     */
    public function get(string $type, string $id): ?array
    {
        $this->validateType($type);
        
        $all = $this->getAll($type);
        return $all[$id] ?? null;
    }

    /**
     * Buscar todos
     */
    public function getAll(string $type): array
    {
        $this->validateType($type);
        
        $file = $this->getFilePath($type);
        
        if (!file_exists($file)) {
            return [];
        }
        
        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }

    /**
     * Atualizar dados
     */
    public function update(string $type, string $id, array $data): bool
    {
        $this->validateType($type);
        
        $existing = $this->get($type, $id);
        if (!$existing) {
            return false;
        }
        
        return $this->save($type, $id, array_merge($existing, $data));
    }

    /**
     * Deletar
     */
    public function delete(string $type, string $id): bool
    {
        $this->validateType($type);
        
        $all = $this->getAll($type);
        
        if (!isset($all[$id])) {
            return false;
        }
        
        unset($all[$id]);
        return $this->writeFile($type, $all);
    }

    /**
     * Buscar com filtros
     */
    public function find(string $type, array $filters = []): array
    {
        $all = $this->getAll($type);
        
        if (empty($filters)) {
            return array_values($all);
        }
        
        return array_values(array_filter($all, function($item) use ($filters) {
            foreach ($filters as $key => $value) {
                if (!isset($item[$key]) || $item[$key] !== $value) {
                    return false;
                }
            }
            return true;
        }));
    }

    /**
     * Limpar todos os dados de um tipo
     */
    public function clear(string $type): bool
    {
        $this->validateType($type);
        return $this->writeFile($type, []);
    }

    /**
     * Limpar tudo
     */
    public function clearAll(): bool
    {
        foreach (self::TYPES as $type) {
            $this->clear($type);
        }
        return true;
    }

    /**
     * Validar tipo
     */
    private function validateType(string $type): void
    {
        if (!in_array($type, self::TYPES)) {
            throw new \InvalidArgumentException("Invalid storage type: {$type}");
        }
    }

    /**
     * Garantir que o diretório existe
     */
    private function ensureStorageExists(): void
    {
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
        
        foreach (self::TYPES as $type) {
            $file = $this->getFilePath($type);
            if (!file_exists($file)) {
                file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
            }
        }
    }

    /**
     * Caminho do arquivo
     */
    private function getFilePath(string $type): string
    {
        return $this->storagePath . '/' . $type . '.json';
    }

    /**
     * Escrever arquivo
     */
    private function writeFile(string $type, array $data): bool
    {
        $file = $this->getFilePath($type);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return file_put_contents($file, $json) !== false;
    }
}
