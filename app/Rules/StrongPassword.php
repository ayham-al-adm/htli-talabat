<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

/**
 * The application wide password policy, driven by config/security.php.
 *
 * Use it as a rule instance -- StrongPassword::rules() returns the full rule
 * array ready to drop into a validator:
 *
 *     'password' => StrongPassword::rules(),
 *     'password' => StrongPassword::rules(['sometimes', 'required', 'confirmed']),
 */
class StrongPassword implements ValidationRule
{
    /**
     * Build the rule array for a password field.
     *
     * @param array $prepend Rules placed before the policy (required, confirmed, ...).
     * @return array
     */
    public static function rules(array $prepend = ['required'])
    {
        return array_merge($prepend, ['string', new static()]);
    }

    /**
     * Run the validation rule.
     *
     * @param string $attribute
     * @param mixed $value
     * @param \Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        $config = (array) config('security.password', []);

        $min = (int) ($config['min_length'] ?? 8);
        $max = (int) ($config['max_length'] ?? 72);

        if (mb_strlen($value) < $min) {
            $fail("The :attribute must be at least {$min} characters.");

            return;
        }

        // bcrypt silently truncates past 72 bytes, so anything longer is a trap.
        if ($max > 0 && mb_strlen($value) > $max) {
            $fail("The :attribute may not be greater than {$max} characters.");

            return;
        }

        if (!empty($config['require_mixed_case']) && !preg_match('/(?=.*\p{Ll})(?=.*\p{Lu})/u', $value)) {
            $fail('The :attribute must contain at least one uppercase and one lowercase letter.');

            return;
        }

        if (!empty($config['require_numbers']) && !preg_match('/\pN/u', $value)) {
            $fail('The :attribute must contain at least one number.');

            return;
        }

        if (!empty($config['require_symbols']) && !preg_match('/[\p{Z}\p{S}\p{P}]/u', $value)) {
            $fail('The :attribute must contain at least one symbol.');

            return;
        }

        if (!empty($config['uncompromised']) && $this->hasAppearedInABreach($value)) {
            $fail('The given :attribute has appeared in a data leak. Please choose a different one.');
        }
    }

    /**
     * Check the password against the haveibeenpwned range API.
     *
     * Only the first five characters of the SHA-1 hash leave the server, so the
     * password itself is never transmitted. A failed lookup does not block the
     * user -- the rest of the policy still applied.
     *
     * @param string $value
     * @return bool
     */
    protected function hasAppearedInABreach($value)
    {
        $hash = strtoupper(sha1($value));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);

        try {
            $response = Http::timeout(5)
                ->withHeaders(['Add-Padding' => 'true'])
                ->get('https://api.pwnedpasswords.com/range/' . $prefix);

            if (!$response->successful()) {
                return false;
            }

            foreach (preg_split('/\R/', $response->body()) as $line) {
                [$candidate, $count] = array_pad(explode(':', trim($line), 2), 2, '0');

                if ($candidate === $suffix && (int) $count > 0) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }
}
