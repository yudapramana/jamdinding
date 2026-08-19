<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegionMandate;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
     * File disimpan di disk 'privatedisk' (tidak publik), path relatif disimpan ke DB.
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

        $disk = Storage::disk('privatedisk');

        /** @var UploadedFile $file */
        $file = $request->file('mandate_file');

        if (! $file->isValid()) {
            throw new \RuntimeException('Upload mandate_file gagal.');
        }

        if ($file->getSize() > 2048 * 1024) {
            throw new \RuntimeException('Ukuran mandate_file melebihi 2 MB.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $mime      = $file->getMimeType();

        $allowedExtensions = ['pdf'];

        if (! in_array($extension, $allowedExtensions, true)) {
            throw new \RuntimeException('Ekstensi mandate_file tidak diizinkan.');
        }

        $allowedMimeMap = [
            'pdf'  => ['application/pdf'],
        ];

        if (! in_array($mime, $allowedMimeMap[$extension] ?? [], true)) {
            throw new \RuntimeException('Mime type mandate_file tidak valid.');
        }

        // ===============================
        // VALIDASI ISI FILE (CONTENT-BASED)
        // ===============================
        if ($extension === 'pdf') {
            $fh = fopen($file->getRealPath(), 'rb');
            $header = fread($fh, 4);
            fclose($fh);

            if ($header !== '%PDF') {
                throw new \RuntimeException('PDF mandate_file tidak valid.');
            }
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            if (! @getimagesize($file->getRealPath())) {
                throw new \RuntimeException('File mandate_file bukan image valid.');
            }
        }

        $mandate = EventRegionMandate::firstOrCreateFor($event->id, $regionType, $regionId);

        /* ===============================
         * HAPUS FILE LAMA (JIKA ADA)
         * =============================== */
        if ($mandate->mandate_file_url) {
            $oldPath = ltrim($mandate->mandate_file_url, '/');

            if (str_starts_with($oldPath, "mandates/{$event->id}/") && $disk->exists($oldPath)) {
                $disk->delete($oldPath);
            }
        }

        /* ===============================
         * SIMPAN FILE BARU
         * =============================== */
        $fileName = Str::uuid()->toString() . '.' . $extension;

        $storedPath = $file->storeAs(
            "mandates/{$event->id}",
            $fileName,
            'privatedisk'
        );

        $mandate->markUploaded($storedPath, $user->id);

        if (!empty($data['notes'])) {
            $mandate->update(['notes' => $data['notes']]);
        }

        $mandate->append('region_name');

        $userForLog = Auth::user();

        \Log::channel('security')->info('Event region mandate uploaded', [
            'user_id'    => $userForLog?->id,
            'user_name'  => $userForLog?->name,
            'event_id'   => $event->id,
            'region_type'=> $regionType,
            'region_id'  => $regionId,
            'mandate_id' => $mandate->id,
            'ip'         => request()->ip(),
            'ua'         => substr(request()->userAgent(), 0, 255),
        ]);

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
}