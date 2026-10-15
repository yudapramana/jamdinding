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
    public function import(Request $request)
    {
        $request->validate([
            'event_group_id' => ['required', 'exists:event_groups,id'],
            'round_id'       => ['required', 'integer', 'in:1,2,3', 'exists:rounds,id'], // Validasi 3 pilihan babak
            'file'           => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ]);

        $eventGroupId = $request->input('event_group_id');
        $roundId = $request->input('round_id');
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
            $packages = [];

            foreach ($rows as $index => $row) {
                if ($index === 0) continue; 

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

            $existing = array_flip(
                EventQuestionPackage::where('event_group_id', $eventGroupId)
                    ->where('round_id', $roundId)
                    ->pluck('package_number')
                    ->all()
            );

            $importedCount = 0;

            foreach ($packages as $packageNumber => $pkg) {
                $packageNumber = (string) $packageNumber;

                if (isset($existing[$packageNumber])) {
                    $errors[] = "Baris {$pkg['row']}: Paket \"{$packageNumber}\" sudah ada di babak ini, dilewati.";
                    continue;
                }

                EventQuestionPackage::create([
                    'event_group_id'   => $eventGroupId,
                    'round_id'         => $roundId,
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
                'message' => 'Gagal mengimpor file. Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $eventGroupId = $request->get('event_group_id');
        $roundId = $request->get('round_id');

        if (!$eventGroupId || !$roundId) {
            return response()->json([
                'message' => 'event_group_id dan round_id wajib diisi.',
            ], 422);
        }

        $search  = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = EventQuestionPackage::query()
            ->where('event_group_id', $eventGroupId)
            ->where('round_id', $roundId);

        if ($search) {
            $query->where('package_number', 'like', "%{$search}%");
        }

        $query->orderByRaw('LENGTH(package_number)')
              ->orderBy('package_number');

        return response()->json([
            'success' => true,
            'data'    => $query->paginate($perPage),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_group_id'            => ['required', 'exists:event_groups,id'],
            'round_id'                  => ['required', 'integer', 'in:1,2,3', 'exists:rounds,id'],
            'package_number'            => [
                'required', 'string', 'max:50',
                Rule::unique('event_question_packages', 'package_number')
                    ->where('event_group_id', $request->input('event_group_id'))
                    ->where('round_id', $request->input('round_id')),
            ],
            'question_content'          => ['required', 'array', 'min:1'],
            'question_content.*.question' => ['required', 'string'],
            'is_available'              => ['required', 'boolean'],
        ], [
            'package_number.unique'              => 'Nomor paket sudah digunakan pada golongan dan babak ini.',
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

    public function update(Request $request, EventQuestionPackage $eventQuestionPackage)
    {
        $groupId = $request->input('event_group_id', $eventQuestionPackage->event_group_id);
        $roundId = $request->input('round_id', $eventQuestionPackage->round_id);

        $validated = $request->validate([
            'event_group_id'            => ['sometimes', 'required', 'exists:event_groups,id'],
            'round_id'                  => ['sometimes', 'required', 'integer', 'in:1,2,3', 'exists:rounds,id'],
            'package_number'            => [
                'required', 'string', 'max:50',
                Rule::unique('event_question_packages', 'package_number')
                    ->where('event_group_id', $groupId)
                    ->where('round_id', $roundId)
                    ->ignore($eventQuestionPackage->id),
            ],
            'question_content'          => ['required', 'array', 'min:1'],
            'question_content.*.question' => ['required', 'string'],
            'is_available'              => ['required', 'boolean'],
        ], [
            'package_number.unique'              => 'Nomor paket sudah digunakan pada golongan dan babak ini.',
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

    public function destroy(EventQuestionPackage $eventQuestionPackage)
    {
        $eventQuestionPackage->delete();
        return response()->json([
            'message' => 'Paket soal berhasil dihapus.',
        ]);
    }

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