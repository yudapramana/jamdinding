<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class WhatsAppController extends Controller
{
    protected Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri'    => rtrim(config('services.kirimi.base_uri'), '/') . '/',
            'timeout'     => (int) config('services.kirimi.timeout', 15),
            'http_errors' => false, // biar kita handle errornya sendiri
        ]);
    }

    /**
     * Kirim WhatsApp via GET lokal: /api/wa/send?receiver=...&message=...
     * Diteruskan ke Kirimi "send-message-fast" via POST JSON.
     */
    public function sendFastGet(Request $request)
    {
        // Validasi query
        $data = $request->validate([
            'receiver'  => ['required', 'string', 'max:30'],   // 628xxxxxxxxxx (tanpa +)
            'message'   => ['required', 'string', 'max:2000'],
            'media_url' => ['nullable', 'url', 'max:2048'],
            'fileName'  => ['nullable', 'string', 'max:255'],
        ]);

        // Normalisasi 08xxxx -> 628xxxx (opsional tapi praktis)
        $receiver = $data['receiver'];
        if (preg_match('/^0\d+$/', $receiver)) {
            $receiver = '62' . substr($receiver, 1);
        }

        // Payload sesuai dokumentasi "send-message-fast"
        $payload = array_filter([
            'user_code' => config('services.kirimi.user_code'),
            'device_id' => config('services.kirimi.device_id'),
            'receiver'  => $receiver,
            'message'   => $data['message'],
            'media_url' => $data['media_url'] ?? null, // optional
            'fileName'  => $data['fileName']  ?? null, // optional
            'secret'    => config('services.kirimi.secret'),
        ], fn($v) => !is_null($v));

        try {
            $resp = $this->http->post('v1/send-message-fast', [
                'headers' => [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $status  = $resp->getStatusCode();
            $raw     = (string) $resp->getBody();
            $json    = json_decode($raw, true);

            return response()->json([
                'ok'       => $status >= 200 && $status < 300,
                'status'   => $status,
                'provider' => $json ?? $raw,
            ], $status);

        } catch (RequestException $e) {
            $status = $e->getResponse()?->getStatusCode() ?? 500;
            $raw    = $e->getResponse()?->getBody()?->getContents();

            return response()->json([
                'ok'       => false,
                'status'   => $status,
                'error'    => 'RequestException',
                'message'  => $e->getMessage(),
                'provider' => $raw ? json_decode($raw, true) ?? $raw : null,
            ], $status);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'status'  => 500,
                'error'   => 'ServerError',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
