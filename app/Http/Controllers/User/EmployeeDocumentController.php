<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DocType;
use App\Models\EmpDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeDocumentController extends Controller
{
    public function index()
    {
        $documents = EmpDocument::where('id_employee', auth()->user()->id_employee)->with('doctype')->get();

        return response()->json([
            'data' => $documents
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_doc_type' => 'required|string|max:255',
            'doc_number' => 'nullable|string|max:255',
            'doc_date' => 'nullable|date',
            'parameter' => 'nullable|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048', // maksimal 2MB
        ]);

        // Simpan file
       // Upload file
       $file = $request->file('file');
       // $filename = time() . '_' . $file->getClientOriginalName();
       // $path = $file->storeAs('documents', $filename, 'public');
       $extension = $file->getClientOriginalExtension();

       $employee = Auth::user()->employee;
       $employeeId = $employee->id;
       $docType = DocType::find($request->id_doc_type);
       $fileName = $docType->label . ($request->parameter ? ('_' . $request->parameter) : '') . '_' .$employee->nip . '.'. $extension;
       $filePath = $file->storeAs(
           'documents/'.$employee->nip,
           $fileName,
           'public'
       );

        $document = new EmpDocument();
        $document->id_employee = auth()->user()->id_employee;
        $document->id_doc_type = $request->id_doc_type;
        $document->doc_number = $request->doc_number;
        $document->file_name = $fileName;
        $document->doc_date = $request->doc_date;
        $document->parameter = $request->parameter;
        $document->file_path = $filePath;
        $document->save();

        return response()->json([
            'message' => 'Document uploaded successfully.',
            'data' => $document
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Approved,Rejected',
        ]);

        $document = EmployeeDocument::findOrFail($id);
        $document->status = $request->status;
        $document->save();

        return response()->json([
            'message' => 'Status updated successfully.',
            'data' => $document
        ]);
    }

    public function destroy($id)
    {
        $document = EmployeeDocument::findOrFail($id);

        // Pastikan hanya dokumen milik pengguna yang bisa dihapus
        if ($document->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Hapus file dari storage
        if (Storage::disk('public')->exists(str_replace('/storage/', '', $document->file_path))) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $document->file_path));
        }

        $document->delete();

        return response()->json(['message' => 'Document deleted successfully.']);
    }
}
