<?php
final class InputValidator
{
    public static function text($value, int $maxLength, string $label, bool $required = true): string
    {
        if ($value !== null && !is_scalar($value)) throw new InvalidArgumentException($label . ' must be plain text.');
        $value = trim((string) $value);
        if ($required && $value === '') {
            throw new InvalidArgumentException($label . ' is required.');
        }
        if (mb_strlen($value, 'UTF-8') > $maxLength) {
            throw new InvalidArgumentException($label . ' must be ' . $maxLength . ' characters or fewer.');
        }
        return $value;
    }

    public static function email($value): string
    {
        if ($value !== null && !is_scalar($value)) throw new InvalidArgumentException('Enter a valid email address.');
        $email = strtolower(trim((string) $value));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            throw new InvalidArgumentException('Enter a valid email address.');
        }
        return $email;
    }

    public static function positiveInt($value, string $label, int $min = 1, int $max = 9999): int
    {
        $number = filter_var($value, FILTER_VALIDATE_INT);
        if ($number === false || $number < $min || $number > $max) {
            throw new InvalidArgumentException($label . ' must be between ' . $min . ' and ' . $max . '.');
        }
        return (int) $number;
    }

    public static function money($value, string $label = 'Price'): float
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException($label . ' must be a valid amount.');
        }
        $amount = round((float) $value, 2);
        if ($amount < 0.01 || $amount > 100000) {
            throw new InvalidArgumentException($label . ' must be between ₱0.01 and ₱100,000.');
        }
        return $amount;
    }
}
