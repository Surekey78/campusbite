<?php
session_start();
define('APP_ROOT', dirname(__DIR__));
define('APP_BASE', '');
require_once APP_ROOT . '/app/Helpers/functions.php';

$postData = [
    'full_name' => 'John Smith',
    'email' => 'john@example.com',
    'phone' => '09123456789',
    'password' => 'SecretPass123',
    'password_confirmation' => 'MismatchPass'
];

assert(detect_invalid_field('That email is already registered.', $postData) === 'email');
assert(detect_invalid_field('Full name is required.', $postData) === 'full_name');
assert(detect_invalid_field('The passwords do not match.', $postData) === 'password');

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = $postData;

flash_set('error', 'The passwords do not match.');

assert(old('full_name') === 'John Smith');
assert(old('email') === 'john@example.com');
assert(old('phone') === '09123456789');
assert(old('password') === '', 'Password must not be preserved');
assert(form_field_error('password') === 'The passwords do not match.');
assert(str_contains(form_field_invalid_attr('password'), 'is-invalid'));

echo "Form preservation test passed.\n";
