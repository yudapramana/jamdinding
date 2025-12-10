<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class PublicEventController extends Controller
{
    /**
     * Ambil daftar event yang akan muncul di landing page.
     */
    public function index(Request $request)
    {
      // Silakan sesuaikan field & filter-nya dengan struktur tabel abang
      $query = Event::query()
          // contoh: hanya event aktif & ditandai publik
          ->where('is_active', true)
          // bisa juga tambah whereDate, dsb. kalau mau
          ->orderBy('start_date', 'desc');

      // optional limit dari query string ?limit=6
      if ($request->filled('limit')) {
          $query->limit((int) $request->get('limit'));
      }

      $events = $query->get();

      // Response bentuk { data: [...] } supaya konsisten
      return response()->json([
          'data' => $events,
      ]);
    }
}
