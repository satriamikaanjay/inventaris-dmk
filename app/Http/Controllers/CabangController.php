<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CabangController extends Controller
{
    public function index()
    {
        // asets_count = jumlah aset di tiap cabang
        $cabangs = Cabang::withCount('asets')->orderBy('nama_cabang')->get();

        return view('masteradmin.cabang', compact('cabangs'));
    }

    public function store(Request $request)
    {
        $this->rapikanInput($request);

        $request->validate([
            'nama_cabang' => ['required', 'string', 'max:100', 'unique:cabangs,nama_cabang'],
        ], $this->pesan());

        Cabang::create(['nama_cabang' => $request->nama_cabang]);

        return redirect()->route('cabang.index')
            ->with('success', 'Cabang "' . $request->nama_cabang . '" berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $cabang = Cabang::findOrFail($id);
        $this->rapikanInput($request);

        $request->validate([
            'nama_cabang' => [
                'required', 'string', 'max:100',
                Rule::unique('cabangs', 'nama_cabang')->ignore($cabang->id),
            ],
        ], $this->pesan());

        $cabang->update(['nama_cabang' => $request->nama_cabang]);

        return redirect()->route('cabang.index')->with('success', 'Cabang berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $cabang = Cabang::withCount('asets')->findOrFail($id);

        // Cabang yang masih punya aset tidak boleh dihapus (supaya aset tidak kehilangan cabang)
        if ($cabang->asets_count > 0) {
            return redirect()->route('cabang.index')->withErrors([
                'Cabang "' . $cabang->nama_cabang . '" masih memiliki ' . $cabang->asets_count . ' aset, jadi tidak bisa dihapus.',
            ]);
        }

        $cabang->delete();

        return redirect()->route('cabang.index')->with('success', 'Cabang berhasil dihapus.');
    }

    private function rapikanInput(Request $request): void
    {
        $request->merge([
            'nama_cabang' => trim(preg_replace('/\s+/', ' ', (string) $request->input('nama_cabang'))),
        ]);
    }

    private function pesan(): array
    {
        return [
            'nama_cabang.required' => 'Nama cabang wajib diisi.',
            'nama_cabang.unique'   => 'Nama cabang sudah ada.',
            'nama_cabang.max'      => 'Nama cabang maksimal 100 karakter.',
        ];
    }
}