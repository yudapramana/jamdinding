<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

function setting($key)
{
    $settings = Cache::rememberForever('settings', function () {
        return Setting::pluck('value', 'key')->all();
    });

    if (! $settings) {
        $settings = config('settings.default');
    }

    return $settings[$key] ?? false;
}

function parseFileName(string $fileName): array {
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    $baseName  = pathinfo($fileName, PATHINFO_FILENAME);
    $parts = explode('_', $baseName);

    $nip = array_pop($parts);
    $parameter = (count($parts) > 1) ? array_pop($parts) : null;
    $label = implode('_', $parts);

    return [
        'label'     => $label,
        'parameter' => $parameter,
        'nip'       => $nip,
        'extension' => $extension,
    ];
}

function buildFileName(string $label, ?string $parameter, string $nip, string $extension): string {
    $fileName = $label;

    if (!empty($parameter)) {
        $fileName .= '_' . $parameter;
    }

    $fileName .= '_' . $nip . '.' . $extension;

    return $fileName;
}