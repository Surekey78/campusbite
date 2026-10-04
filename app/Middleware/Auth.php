<?php
/** Small centralized authorization helpers used by controllers and views. */
function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash_set('info', 'Please sign in to continue.');
        redirect('auth.login');
    }
    return $user;
}

function require_permission(string $permission): array
{
    $user = require_login();
    if (!can($permission)) {
        throw new HttpException(403, 'You do not have permission to do that.');
    }
    return $user;
}

function require_role(string ...$roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        throw new HttpException(403, 'This page is not available for your account.');
    }
    return $user;
}

function require_active_vendor(): array
{
    $user = require_role('VENDOR', 'ADMIN');
    if ($user['role'] === 'ADMIN') {
        return $user;
    }
    $vendor = current_vendor();
    if (!$vendor || $vendor['status'] !== 'ACTIVE') {
        throw new HttpException(403, 'Your vendor storefront must be active before you can use this feature.');
    }
    return $user;
}
