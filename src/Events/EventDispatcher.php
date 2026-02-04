<?php

namespace IsraelNogueira\PaymentHub\Events;

final class EventDispatcher
{
    /**
     * @var array<string, array<int, callable(PaymentEventInterface): void>>
     */
    private array $listeners = [];

    /**
     * @param callable(PaymentEventInterface): void $listener
     */
    public function addListener(string $eventName, callable $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }
        
        $this->listeners[$eventName][] = $listener;
    }

    public function dispatch(PaymentEventInterface $event): void
    {
        $eventName = $event->getEventName();
        
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $listener) {
            $listener($event);
        }
    }

    /**
     * @param callable(PaymentEventInterface): void $listener
     */
    public function removeListener(string $eventName, callable $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        $this->listeners[$eventName] = array_values(array_filter(
            $this->listeners[$eventName],
            fn(callable $l): bool => $l !== $listener
        ));
    }

    public function clearListeners(?string $eventName = null): void
    {
        if ($eventName === null) {
            $this->listeners = [];
            return;
        }

        unset($this->listeners[$eventName]);
    }

    public function hasListeners(string $eventName): bool
    {
        return isset($this->listeners[$eventName]) && count($this->listeners[$eventName]) > 0;
    }

    /**
     * @return array<int, callable(PaymentEventInterface): void>
     */
    public function getListeners(string $eventName): array
    {
        return $this->listeners[$eventName] ?? [];
    }
}