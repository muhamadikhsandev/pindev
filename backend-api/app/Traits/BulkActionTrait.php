<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage, Validator};

trait BulkActionTrait
{
    public function bulkDestroy(Request $request)
    {
        $v = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:' . $this->model->getTable() . ',id',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $items = $this->model->whereIn('id', $request->ids)->get();

            foreach ($items as $item) {
                // Otomatis hapus foto jika ada kolom foto_path
                if (isset($item->foto_path) && $item->foto_path) {
                    Storage::disk('public')->delete($item->foto_path);
                }
                $item->delete();
            }

            DB::commit();

            return response()->json([
                'message' => count($request->ids) . ' data berhasil dihapus masal'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus data masal',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}