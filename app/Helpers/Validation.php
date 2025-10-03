<?php
namespace App\Helpers;

/**
 * Provides declarative validation rules for form payloads.
 */
class Validation
{
    /**
     * Validates input data against a rule set and returns sanitised values with errors.
     *
     * @param array<string, mixed> $data
     * @param array<string, array<int, string>> $rules
     * @return array{data: array<string, mixed>, errors: array<string, string>}
     */
    public static function make(array $data, array $rules): array
    {
        $errors = [];
        $clean = [];
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $cleanValue = is_string($value) ? trim($value) : $value;
            foreach ($fieldRules as $rule) {
                $parts = explode(':', $rule);
                $ruleName = $parts[0];
                $param = $parts[1] ?? null;
                $error = self::applyRule($field, $cleanValue, $ruleName, $param);
                if ($error !== null) {
                    $errors[$field] = $error;
                    break;
                }
            }
            $clean[$field] = $cleanValue;
        }
        return ['data' => $clean, 'errors' => $errors];
    }

    /**
     * Applies a single validation rule and returns an error message if validation fails.
     */
    private static function applyRule(string $field, mixed $value, string $rule, ?string $param): ?string
    {
        switch ($rule) {
            case 'required':
                return ($value === null || $value === '' || (is_array($value) && count($value) === 0))
                    ? ucfirst($field) . ' is required.'
                    : null;
            case 'email':
                return ($value && !filter_var($value, FILTER_VALIDATE_EMAIL))
                    ? 'Please enter a valid email address.'
                    : null;
            case 'date':
                return ($value && !self::isValidDate($value)) ? 'Please provide a valid date.' : null;
            case 'time':
                return ($value && !self::isValidTime($value)) ? 'Please provide a valid time.' : null;
            case 'numeric':
                return ($value !== null && !is_numeric($value)) ? 'This field must be numeric.' : null;
            case 'min':
                return ($value !== null && strlen((string) $value) < (int) $param)
                    ? 'Must be at least ' . (int) $param . ' characters.'
                    : null;
            case 'max':
                return ($value !== null && strlen((string) $value) > (int) $param)
                    ? 'Must be at most ' . (int) $param . ' characters.'
                    : null;
            case 'in':
                $allowed = $param ? explode(',', $param) : [];
                return ($value !== null && !in_array($value, $allowed, true))
                    ? 'Invalid selection.'
                    : null;
            default:
                return null;
        }
    }

    /**
     * Checks whether the supplied string is a valid ISO date.
     */
    private static function isValidDate(string $value): bool
    {
        $dt = date_create($value);
        return $dt !== false && $dt->format('Y-m-d') === $value;
    }

    /**
     * Validates a HH:MM time payload.
     */
    private static function isValidTime(string $value): bool
    {
        return preg_match('/^(2[0-3]|[01]\d):[0-5]\d$/', $value) === 1;
    }
}