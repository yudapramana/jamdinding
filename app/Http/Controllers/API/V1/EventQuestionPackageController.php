<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\EventGroup;
use App\Models\EventQuestionPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EventQuestionPackageController extends Controller
{
    /**
     * Mengimpor paket soal dari file Excel/CSV.
     *
     * Struktur kolom (baris pertama = header):
     *   A: Nomor Paket   (wajib, contoh: "Paket A")
     *   B: Isi Soal      (wajib, 1 baris = 1 sub-soal)
     *
     * Baris dengan Nomor Paket yang sama digabung menjadi satu paket,
     * urutan sub-soal mengikuti urutan baris di file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'event_group_id' => ['required', 'exists:event_groups,id'],
            'file'           => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ]);

        $eventGroupId = $request->input('event_group_id');
        $file = $request->file('file');

        $eventGroup = EventGroup::findOrFail($eventGroupId);
        if (!$eventGroup->has_maqra_drawing) {
            return response()->json([
                'message' => 'Golongan ini tidak dikonfigurasi untuk menggunakan sistem cabut Maqra/Soal.',
            ], 422);
        }

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $rows = $spreadsheet->getActiveSheet()->toArray();

            $errors = [];
            $packages = []; // [package_number => ['row' => int, 'items' => string[]]]

            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Skip header

                if (empty(array_filter($row, fn ($v) => $v !== null && trim((string) $v) !== ''))) continue;

                $packageNumber = trim((string) ($row[0] ?? ''));
                $question      = trim((string) ($row[1] ?? ''));
                $rowNo         = $index + 1;

                if ($packageNumber === '' || $question === '') {
                    $errors[] = "Baris {$rowNo}: Data tidak lengkap (Nomor Paket dan Isi Soal wajib diisi).";
                    continue;
                }

                if (mb_strlen($packageNumber) > 50) {
                    $errors[] = "Baris {$rowNo}: Nomor Paket maksimal 50 karakter.";
                    continue;
                }

                if (!isset($packages[$packageNumber])) {
                    $packages[$packageNumber] = ['row' => $rowNo, 'items' => []];
                }
                $packages[$packageNumber]['items'][] = $question;
            }

            // Nomor paket yang sudah ada di golongan ini dilewati agar tidak ganda
            $existing = array_flip(
                EventQuestionPackage::where('event_group_id', $eventGroupId)
                    ->pluck('package_number')
                    ->all()
            );

            $importedCount = 0;

            foreach ($packages as $packageNumber => $pkg) {
                // Kunci array numerik ("1") otomatis menjadi int di PHP, kembalikan ke string
                $packageNumber = (string) $packageNumber;

                if (isset($existing[$packageNumber])) {
                    $errors[] = "Baris {$pkg['row']}: Paket \"{$packageNumber}\" sudah ada di golongan ini, dilewati.";
                    continue;
                }

                EventQuestionPackage::create([
                    'event_group_id'   => $eventGroupId,
                    'package_number'   => $packageNumber,
                    'question_content' => $this->buildContent($pkg['items']),
                    'is_available'     => true,
                ]);

                $importedCount++;
            }

            $message = "Berhasil mengimpor {$importedCount} paket soal.";
            if (count($errors) > 0) {
                $message .= ' Namun, terdapat beberapa error.';
            }

            return response()->json([
                'success'        => true,
                'message'        => $message,
                'imported_count' => $importedCount,
                'errors'         => $errors,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error import Paket Soal: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal mengimpor file. Pastikan format file sesuai. Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menampilkan daftar paket soal berdasarkan event_group_id
     */
    public function index(Request $request)
    {
        $eventGroupId = $request->get('event_group_id');
        if (!$eventGroupId) {
            return response()->json([
                'message' => 'event_group_id is required.',
            ], 422);
        }

        $search  = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = EventQuestionPackage::query()
            ->where('event_group_id', $eventGroupId);

        if ($search) {
            $query->where('package_number', 'like', "%{$search}%");
        }

        // Urut natural: "Paket 2" tampil sebelum "Paket 10"
        $query->orderByRaw('LENGTH(package_number)')
              ->orderBy('package_number');

        return response()->json([
            'success' => true,
            'data'    => $query->paginate($perPage),
        ]);
    }

    /**
     * Menyimpan paket soal baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_group_id'            => ['required', 'exists:event_groups,id'],
            'package_number'            => [
                'required', 'string', 'max:50',
                Rule::unique('event_question_packages', 'package_number')
                    ->where('event_group_id', $request->input('event_group_id')),
            ],
            'question_content'          => ['required', 'array', 'min:1'],
            'question_content.*.question' => ['required', 'string'],
            'is_available'              => ['required', 'boolean'],
        ], [
            'package_number.unique'              => 'Nomor paket sudah digunakan pada golongan ini.',
            'question_content.required'          => 'Minimal harus ada satu sub-soal.',
            'question_content.*.question.required' => 'Isi sub-soal tidak boleh kosong.',
        ]);

        $eventGroup = EventGroup::findOrFail($validated['event_group_id']);
        if (!$eventGroup->has_maqra_drawing) {
            return response()->json([
                'message' => 'Golongan ini tidak dikonfigurasi untuk menggunakan sistem cabut Maqra/Soal.',
            ], 422);
        }

        $validated['question_content'] = $this->buildContent(
            array_column($validated['question_content'], 'question')
        );

        $package = EventQuestionPackage::create($validated);

        return response()->json([
            'message' => 'Paket soal berhasil ditambahkan.',
            'data'    => $package,
        ], 201);
    }

    /**
     * Memperbarui paket soal
     */
    public function update(Request $request, EventQuestionPackage $eventQuestionPackage)
    {
        $groupId = $request->input('event_group_id', $eventQuestionPackage->event_group_id);

        $validated = $request->validate([
            'event_group_id'            => ['sometimes', 'required', 'exists:event_groups,id'],
            'package_number'            => [
                'required', 'string', 'max:50',
                Rule::unique('event_question_packages', 'package_number')
                    ->where('event_group_id', $groupId)
                    ->ignore($eventQuestionPackage->id),
            ],
            'question_content'          => ['required', 'array', 'min:1'],
            'question_content.*.question' => ['required', 'string'],
            'is_available'              => ['required', 'boolean'],
        ], [
            'package_number.unique'              => 'Nomor paket sudah digunakan pada golongan ini.',
            'question_content.required'          => 'Minimal harus ada satu sub-soal.',
            'question_content.*.question.required' => 'Isi sub-soal tidak boleh kosong.',
        ]);

        $validated['question_content'] = $this->buildContent(
            array_column($validated['question_content'], 'question')
        );

        $eventQuestionPackage->update($validated);

        return response()->json([
            'message' => 'Paket soal berhasil diperbarui.',
            'data'    => $eventQuestionPackage,
        ]);
    }

    /**
     * Menghapus paket soal
     */
    public function destroy(EventQuestionPackage $eventQuestionPackage)
    {
        // Opsional: cegah penghapusan jika paket sudah dicabut peserta
        // $hasBeenDrawn = EventMaqraDrawing::where('event_question_package_id', $eventQuestionPackage->id)->exists();
        // if ($hasBeenDrawn) {
        //     return response()->json(['message' => 'Paket tidak bisa dihapus karena sudah dicabut oleh peserta.'], 422);
        // }

        $eventQuestionPackage->delete();

        return response()->json([
            'message' => 'Paket soal berhasil dihapus.',
        ]);
    }

    /**
     * Susun ulang isi paket menjadi format JSON seragam:
     * [ {"number": 1, "question": "..."}, {"number": 2, "question": "..."} ]
     */
    private function buildContent(array $questions): array
    {
        $content = [];
        foreach (array_values($questions) as $i => $question) {
            $content[] = [
                'number'   => $i + 1,
                'question' => trim((string) $question),
            ];
        }
        return $content;
    }
}