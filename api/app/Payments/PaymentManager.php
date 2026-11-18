<?php

namespace App\Payments;

use App\Models\Setting;
use InvalidArgumentException;

class PaymentManager
{
    /** Fields the admin can override from the dashboard (stored in settings as "payments.<key>"). */
    public const EDITABLE = ['enabled', 'account', 'hint'];

    /** Config merged with admin overrides, for every method (enabled or not). */
    public function all(): array
    {
        $out = [];
        foreach (config('payments.methods') as $key => $config) {
            $override = array_intersect_key((array) Setting::get("payments.{$key}", []), array_flip(self::EDITABLE));
            $merged = array_merge($config, $override);
            $merged['enabled'] = filter_var($merged['enabled'], FILTER_VALIDATE_BOOL);
            $out[$key] = $merged;
        }

        return $out;
    }

    /** @return array<string, array> enabled method configs keyed by id */
    public function enabled(): array
    {
        return array_filter($this->all(), fn ($m) => $m['enabled']);
    }

    public function keys(): array
    {
        return array_keys($this->enabled());
    }

    public function driver(string $key): PaymentDriver
    {
        $config = $this->enabled()[$key] ?? throw new InvalidArgumentException("Payment method [$key] is not enabled.");

        return new $config['driver']($key, $config);
    }

    public function publicList(): array
    {
        return array_values(array_map(fn ($k) => $this->driver($k)->publicInfo(), $this->keys()));
    }

    /** Admin view: every method with its editable fields. */
    public function adminList(): array
    {
        return array_values(array_map(fn ($key, $m) => [
            'id' => $key,
            'label' => $m['label'],
            'enabled' => $m['enabled'],
            'account' => $m['account'],
            'hint' => $m['hint'],
            'payer_phone_required' => $m['payer_phone_required'],
        ], array_keys($this->all()), $this->all()));
    }
}
