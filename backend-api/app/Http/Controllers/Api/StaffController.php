<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
/** * CRITICAL FIX: Pastikan ini App\Models\User, 
 * bukan Illuminate\Foundation\Auth\User atau vendor lain.
 */
use App\Models\User; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log; // Import Log untuk debugging

class StaffController extends Controller
{
    public function index()
    {
        $staff = User::whereIn('role', ['admin', 'petugas'])->latest()->get();
        return response()->json(['data' => $staff]);
    }

    public function store(Request $request)
    {
        Log::info("=== DEBUG STORE STAFF ===");
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'nomor_identitas' => 'required|string|unique:users,nomor_identitas',
            'email' => 'nullable|email|max:255',
            'role' => 'required|in:admin,petugas',
            'status' => 'required|in:aktif,nonaktif',
            'password' => 'nullable|string|min:6'
        ]);

        if ($validator->fails()) {
            Log::warning("Validasi Store Gagal", $validator->errors()->toArray());
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'nomor_identitas', 'email', 'role', 'status']);
        $data['password'] = Hash::make($request->password ?: $request->nomor_identitas);

        // Eloquent::create() memicu event 'created' untuk Observer
        $staff = User::create($data);

        Log::info("Staff Berhasil Dibuat ID: {$staff->id}. Memeriksa Loggable Trait...", [
            'has_loggable' => method_exists($staff, 'createLog')
        ]);

        return response()->json(['message' => 'Staff berhasil ditambahkan', 'data' => $staff], 201);
    }

    public function update(Request $request, $id)
{
    Log::info("=== SUPER DEBUG UPDATE START ID: {$id} ===");
    $staff = User::findOrFail($id);

    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|required|string|max:255',
        'nomor_identitas' => 'sometimes|required|string|unique:users,nomor_identitas,' . $id,
        'role' => 'sometimes|required|in:admin,petugas',
        'status' => 'sometimes|required|in:aktif,nonaktif',
    ]);

    if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);

    $data = $request->only(['name', 'nomor_identitas', 'email', 'role', 'status']);
    if ($request->filled('password')) $data['password'] = Hash::make($request->password);

    // DEBUG 1: Cek Trait Loggable
    Log::info("DEBUG 1: Model " . get_class($staff) . " punya method createLog? " . (method_exists($staff, 'createLog') ? 'YA' : 'TIDAK'));

    $staff->fill($data);

    if ($staff->isDirty()) {
        Log::info("DEBUG 2: Perubahan terdeteksi: ", $staff->getDirty());
        
        // DEBUG 3: Cek apakah ada Observer yang terdaftar secara manual
        // Ini untuk memastikan AppServiceProvider tidak skip model ini
        $staff->save(); 
        Log::info("DEBUG 3: staff->save() selesai dijalankan.");
    } else {
        Log::notice("DEBUG 2: Tidak ada perubahan data (Clean).");
    }

    return response()->json(['message' => 'Staff berhasil diperbarui', 'data' => $staff]);
}
    public function destroy($id)
    {
        Log::info("=== DEBUG DELETE STAFF ID: {$id} ===");
        
        $staff = User::findOrFail($id);
        
        if ($staff->foto_profile) {
            Storage::disk('public')->delete($staff->foto_profile);
        }
        
        // delete() memicu event 'deleted' untuk Observer
        $staff->delete();

        Log::info("Staff ID: {$id} berhasil dihapus.");

        return response()->json(['message' => 'Staff berhasil dihapus']);
    }

    public function bulkDestroy(Request $request)
    {
        Log::info("=== DEBUG BULK DELETE STAFF ===");
        
        $ids = $request->ids ?? [];
        $staffs = User::whereIn('id', $ids)->get();
        
        foreach ($staffs as $staff) {
            if ($staff->foto_profile) {
                Storage::disk('public')->delete($staff->foto_profile);
            }
            // Loop delete wajib agar Observer terpancing per-data
            $staff->delete();
        }

        Log::info("Berhasil menghapus " . count($ids) . " staff.");

        return response()->json(['message' => count($ids) . ' staff berhasil dihapus']);
    }
}