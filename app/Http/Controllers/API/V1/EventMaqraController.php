<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\EventMaqra;
use App\Models\EventGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EventMaqraController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'event_group_id' => ['required', 'exists:event_groups,id'],
            'round_id'       => ['required', 'integer', 'in:1,2,3', 'exists:rounds,id'], // Validasi 3 babak
            'file'           => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'],
        ]);

        $eventGroupId = $request->input('event_group_id');
        $roundId = $request->input('round_id');
        $file = $request->file('file');

        $eventGroup = EventGroup::findOrFail($eventGroupId);
        if (!$eventGroup->has_maqra_drawing) {
            return response()->json(['message' => 'Golongan ini tidak menggunakan sistem cabut Maqra.'], 422);
        }

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $importedCount = 0;
            $errors = [];

            // Ambil data maqra yang sudah ada di database untuk round & group ini agar bisa dicek duplikatnya secara cepat
            $existingMaqras = EventMaqra::where('event_group_id', $eventGroupId)
                ->where('round_id', $roundId)
                ->get()
                ->keyBy(fn ($item) => "{$item->surah_number}-{$item->start_ayah}-{$item->end_ayah}")
                ->all();

            foreach ($rows as $index => $row) {
                if ($index === 0) continue; 
                if (empty(array_filter($row, fn ($v) => $v !== null && trim((string) $v) !== ''))) continue;

                $surahNumber = isset($row[0]) && $row[0] !== '' ? (int) $row[0] : null;
                $startAyah   = isset($row[1]) && $row[1] !== '' ? (int) $row[1] : null;
                $endAyah     = isset($row[2]) && $row[2] !== '' ? (int) $row[2] : null;
                $juz         = isset($row[3]) && $row[3] !== '' ? (int) $row[3] : null;
                $page        = isset($row[4]) && $row[4] !== '' ? (int) $row[4] : null;
                $rowNo       = $index + 1;

                if (!$surahNumber || !$startAyah || !$endAyah) {
                    $errors[] = "Baris {$rowNo}: Data tidak lengkap.";
                    continue; 
                }

                $surah = config("quran.surahs.{$surahNumber}");
                if (!$surah) {
                    $errors[] = "Baris {$rowNo}: Nomor Surat {$surahNumber} tidak valid.";
                    continue;
                }

                if ($endAyah < $startAyah) {
                    $errors[] = "Baris {$rowNo}: Ayat Akhir lebih kecil dari Ayat Mulai.";
                    continue;
                }

                // Cek apakah kombinasi surat & ayat sudah ada di database atau sudah diimport di baris sebelumnya
                $key = "{$surahNumber}-{$startAyah}-{$endAyah}";
                if (isset($existingMaqras[$key])) {
                    $errors[] = "Baris {$rowNo}: Maqra Surat {$surah['name']} (Ayat {$startAyah} - {$endAyah}) sudah ada pada babak ini.";
                    continue;
                }

                // Masukkan ke array pengecekan lokal agar file excel yang punya baris kembar antar baris juga terdeteksi
                $existingMaqras[$key] = true;

                EventMaqra::create([
                    'event_group_id' => $eventGroupId,
                    'round_id'       => $roundId,
                    'surah_number'   => $surahNumber,
                    'surah_name'     => $surah['name'],
                    'start_ayah'     => $startAyah,
                    'end_ayah'       => $endAyah,
                    'juz'            => $juz,
                    'page'           => $page,
                    'is_available'   => true,
                ]);

                $importedCount++;
            }

            $message = "Berhasil mengimpor {$importedCount} data maqra.";
            if (count($errors) > 0) $message .= " Namun, terdapat beberapa error.";

            return response()->json([
                'success' => true,
                'message' => $message,
                'imported_count' => $importedCount,
                'errors'  => $errors 
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error import Maqra: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal mengimpor file. Error: ' . $e->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        $eventGroupId = $request->get('event_group_id');
        $roundId = $request->get('round_id');

        if (!$eventGroupId || !$roundId) {
            return response()->json(['message' => 'event_group_id dan round_id wajib diisi.'], 422);
        }

        $search = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = EventMaqra::query()
            ->where('event_group_id', $eventGroupId)
            ->where('round_id', $roundId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('surah_name', 'like', "%{$search}%")
                  ->orWhere('surah_number', 'like', "%{$search}%");
            });
        }

        $query->orderBy('surah_number')->orderBy('start_ayah');
        $data = $query->paginate($perPage);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'event_group_id' => ['required', 'exists:event_groups,id'],
            'round_id'       => ['required', 'integer', 'in:1,2,3', 'exists:rounds,id'], // Validasi 3 babak
            'surah_number'   => ['required', 'integer', 'min:1', 'max:114'],
            'surah_name'     => ['required', 'string', 'max:150'],
            'start_ayah'     => [
                'required', 'integer', 'min:1',
                // Validasi agar kombinasi surah_number, start_ayah, dan end_ayah unik per event_group_id dan round_id
                Rule::unique('event_maqras')->where(function ($query) use ($request) {
                    return $query->where('event_group_id', $request->event_group_id)
                                 ->where('round_id', $request->round_id)
                                 ->where('end_ayah', $request->end_ayah);
                }),
            ],
            'end_ayah'       => ['required', 'integer', 'gte:start_ayah'],
            'juz'            => ['nullable', 'integer', 'min:1', 'max:30'],
            'page'           => ['nullable', 'integer', 'min:1', 'max:604'],
            'is_available'   => ['required', 'boolean'],
        ], [
            'start_ayah.unique' => 'Maqra dengan rentang surat dan ayat tersebut sudah terdaftar pada golongan dan babak ini.',
        ]);

        $eventGroup = EventGroup::findOrFail($validatedData['event_group_id']);
        if (!$eventGroup->has_maqra_drawing) {
            return response()->json(['message' => 'Golongan tidak menggunakan sistem cabut Maqra.'], 422);
        }

        $maqra = EventMaqra::create($validatedData);

        return response()->json([
            'message' => 'Data maqra berhasil ditambahkan.',
            'data'    => $maqra,
        ], 201);
    }

    public function update(Request $request, EventMaqra $eventMaqra)
    {
        $groupId = $request->input('event_group_id', $eventMaqra->event_group_id);
        $roundId = $request->input('round_id', $eventMaqra->round_id);

        $validatedData = $request->validate([
            'event_group_id' => ['sometimes', 'required', 'exists:event_groups,id'],
            'round_id'       => ['sometimes', 'required', 'integer', 'in:1,2,3', 'exists:rounds,id'], // Validasi 3 babak
            'surah_number'   => ['required', 'integer', 'min:1', 'max:114'],
            'surah_name'     => ['required', 'string', 'max:150'],
            'start_ayah'     => [
                'required', 'integer', 'min:1',
                Rule::unique('event_maqras')->where(function ($query) use ($groupId, $roundId, $request) {
                    return $query->where('event_group_id', $groupId)
                                 ->where('round_id', $roundId)
                                 ->where('end_ayah', $request->end_ayah);
                })->ignore($eventMaqra->id),
            ],
            'end_ayah'       => ['required', 'integer', 'gte:start_ayah'],
            'juz'            => ['nullable', 'integer', 'min:1', 'max:30'],
            'page'           => ['nullable', 'integer', 'min:1', 'max:604'],
            'is_available'   => ['required', 'boolean'],
        ], [
            'start_ayah.unique' => 'Maqra dengan rentang surat dan ayat tersebut sudah terdaftar pada golongan dan babak ini.',
        ]);

        $eventMaqra->update($validatedData);

        return response()->json([
            'message' => 'Data maqra berhasil diperbarui.',
            'data'    => $eventMaqra,
        ]);
    }

    public function destroy(EventMaqra $eventMaqra)
    {
        $eventMaqra->delete();
        return response()->json(['message' => 'Data maqra berhasil dihapus.']);
    }
}