<?php
/** Shared application constants. Keep business status labels in one place. */
const APP_NAME = 'CampusBite';
const APP_TAGLINE = 'Discover. Order. Pick up.';
const ORDER_STATUSES = ['PENDING', 'CONFIRMED', 'PREPARING', 'READY', 'COMPLETED', 'CANCELLED', 'REJECTED'];
const ORDER_STATUS_LABELS = [
    'PENDING' => 'Awaiting confirmation',
    'CONFIRMED' => 'Confirmed',
    'PREPARING' => 'Preparing',
    'READY' => 'Ready for pickup',
    'COMPLETED' => 'Completed',
    'CANCELLED' => 'Cancelled',
    'REJECTED' => 'Rejected',
];
const MOVEMENT_TYPES = ['PURCHASE', 'SALE', 'RESTOCK', 'ADJUSTMENT', 'WASTE', 'RETURN'];
const PAYMENT_METHOD = 'CASH';
