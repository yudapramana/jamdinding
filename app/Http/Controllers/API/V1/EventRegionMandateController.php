<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegionMandate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EventRegionMandateController extends Controller
{
    /**
     * GET /api/v1/events/{event}/region-mandates
     *
     * - Role 'pendaftaran'  : hanya melihat mandat milik wilayahnya sendiri.
     * - Role 'superadmin' / 'admin_event' : melihat semua mandat pada event tsb.
     * - Role lain : ditolak (403).
     */
    public function index(Request $request, Event $event)
    {
        $user = Auth::user();
        $slug = $user->role->slug ?? null;

        $query = EventRegionMandate::query()
            ->with(['uploadedBy:id,name', 'approvedBy:id,name'])
            ->where('event_id', $event->id);

        if ($slug === 'pendaftaran') {
            $regionType = $event->getContingentRegionType();
            $regionId   = $user->getRegionId($regionType);

            if (!$regionId) {
                return response()->json([
                    'message' => "Akun Anda tidak punya {$regionType} yang terdaftar.",
                ], 422);
            }

            $query->where('region_type', $regionType)
                  ->where('region_id', $regionId);
        } elseif (!in_array($slug, ['superadmin', 'admin_event'])) {
            abort(403, 'Anda tidak memiliki akses ke data mandat.');
        } else {
            // Admin bisa filter status & search nama wilayah (opsional dari query string)
            if ($request->filled('status')) {
                $query->where('status', $request->get('status'));
            }
        }

        $perPage = (int) ($request->get('per_page') ?? 10);

        $query->orderBy('region_type')->orderBy('region_id');

        $mandates = $query->paginate($perPage);

        // sertakan accessor region_name di setiap item
        $mandates->getCollection()->each(fn ($m) => $m->append('region_name'));

        return response()->json([
            'data' => $mandates,
            'meta' => [
                'region_type_label' => $event->getContingentRegionType(),
                'is_admin_view'     => in_array($slug, ['superadmin', 'admin_event']),
            ],
        ]);
    }

    /**
     * POST /api/v1/events/{event}/region-mandates/upload
     *
     * Hanya role 'pendaftaran' yang boleh upload, dan hanya untuk wilayahnya sendiri.
     */
    public function upload(Request $request, Event $event)
    {
        $user = Auth::user();
        $slug = $user->role->slug ?? null;

        if ($slug !== 'pendaftaran') {
            abort(403, 'Hanya akun pendaftaran yang dapat mengupload mandat.');
        }

        $regionType = $event->getContingentRegionType();
        $regionId   = $user->getRegionId($regionType);

        if (!$regionId) {
            abort(403, "Akun Anda tidak punya {$regionType} yang terdaftar.");
        }

        $data = $request->validate([
            'mandate_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ]);

        $path = $request->file('mandate_file')->store("mandates/{$event->id}", 'public');
        $fileUrl = Storage::disk('public')->url($path);

        $mandate = EventRegionMandate::firstOrCreateFor($event->id, $regionType, $regionId);

        // kalau ada file lama, hapus supaya storage tidak menumpuk
        if ($mandate->mandate_file_url) {
            $this->deleteOldFile($mandate->mandate_file_url);
        }

        $mandate->markUploaded($fileUrl, $user->id);

        if (!empty($data['notes'])) {
            $mandate->update(['notes' => $data['notes']]);
        }

        $mandate->append('region_name');

        return response()->json([
            'message' => 'Mandat berhasil diupload dan menunggu persetujuan.',
            'data'    => $mandate,
        ], 201);
    }

    /**
     * POST /api/v1/region-mandates/{mandate}/approve
     * Hanya superadmin / admin_event.
     */
    public function approve(Request $request, EventRegionMandate $mandate)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $mandate->markApproved(Auth::id(), $data['notes'] ?? null);
        $mandate->append('region_name');

        return response()->json([
            'message' => 'Mandat berhasil disetujui.',
            'data'    => $mandate,
        ]);
    }

    /**
     * POST /api/v1/region-mandates/{mandate}/reject
     * Hanya superadmin / admin_event.
     */
    public function reject(Request $request, EventRegionMandate $mandate)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        $mandate->markRejected(Auth::id(), $data['notes']);
        $mandate->append('region_name');

        return response()->json([
            'message' => 'Mandat ditolak. Wilayah harus mengupload ulang.',
            'data'    => $mandate,
        ]);
    }

    private function authorizeAdmin(): void
    {
        $slug = Auth::user()->role->slug ?? null;

        if (!in_array($slug, ['superadmin', 'admin_event'])) {
            abort(403, 'Anda tidak memiliki izin untuk melakukan aksi ini.');
        }
    }

    private function deleteOldFile(string $fileUrl): void
    {
        // fileUrl berbentuk /storage/mandates/{event_id}/xxxx.pdf
        $relativePath = str_replace('/storage/', '', parse_url($fileUrl, PHP_URL_PATH));
        if ($relativePath && Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }
}