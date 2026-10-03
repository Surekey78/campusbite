<?php
final class Order
{
    public static function nextNumber(): string
    {
        // Random suffix keeps the human-readable order number collision-resistant without leaking row IDs.
        return 'CB-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    public static function allowedNext(string $current): array
    {
        return match ($current) {
            'PENDING' => ['CONFIRMED', 'REJECTED', 'CANCELLED'],
            'CONFIRMED' => ['PREPARING', 'CANCELLED'],
            'PREPARING' => ['READY', 'CANCELLED'],
            'READY' => ['COMPLETED'],
            default => [],
        };
    }
}
