<?php
namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Models\StatusAset;

    class StatusAsetController extends Controller
    {
        public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_status'  => 'required|string|max:255',
            'warna_status' => 'nullable|string|max:50',
        ]);

        StatusAset::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status aset berhasil ditambahkan!'
            ]);
        }

        return redirect()->back()->with('success', 'Status aset berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_status'  => 'required|string|max:255',
            'warna_status' => 'nullable|string|max:50',
        ]);

        $status = StatusAset::findOrFail($id);
        $status->update($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status aset berhasil diperbarui!'
            ]);
        }

        return redirect()->back()->with('success', 'Status aset berhasil diperbarui!');
    }

    public function destroy(Request $request, $id)    {
        $status = StatusAset::findOrFail($id);
        $status->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status aset berhasil dihapus!'
            ]);
        }

        return redirect()->back()->with('success', 'Status aset berhasil dihapus!');
    }
    }