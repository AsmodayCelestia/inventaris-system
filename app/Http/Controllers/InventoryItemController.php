<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use DataTables;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class InventoryItemController extends Controller
{
    /* ----------------------------------------------------------
       |  HELPER FILTER ARRAY
       ---------------------------------------------------------- */
    private function whereInArray($query, $request, $key)
    {
        $values = $request->input($key);
        if (empty($values)) return;

        if (is_string($values)) {
            $values = explode(',', $values);
        }
        $values = array_filter(array_map('intval', $values));
        if (!empty($values)) {
            $query->whereIn($key, $values);
        }
    }

    /* ----------------------------------------------------------
       |  DATATABLE UTAMA  (server-side)
       ---------------------------------------------------------- */
    public function table(Request $request)
    {
        $query = InventoryItem::with(['brand', 'category', 'type']);

        /* ---------- pencarian global ---------- */
        if ($keyword = $request->input('search.value')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('item_code', 'like', "%{$keyword}%")
                ->orWhere('manufacturer', 'like', "%{$keyword}%");
            });
        }

        /* ---------- filter array ---------- */
        $this->whereInArray($query, $request, 'brand_id');
        $this->whereInArray($query, $request, 'category_id');
        $this->whereInArray($query, $request, 'type_id');

        /* ---------- filter tahun produksi ---------- */
        if ($year = $request->input('manufacture_year')) {
            $query->where('manufacture_year', (int) $year);
        }

        // /* ---------- filter rentang tanggal ---------- */
        // if ($from = $request->input('date_from')) {
        //     $query->whereDate('created_at', '>=', $from);
        // }
        // if ($to = $request->input('date_to')) {
        //     $query->whereDate('created_at', '<=', $to);
        // }

        /* ---------- hitung record ---------- */
        $recordsTotal    = InventoryItem::count();
        $recordsFiltered = (clone $query)->count();

        /* ---------- paginasi ---------- */
        $length = (int) $request->input('per_page', 10);
        $start  = ((int) $request->input('page', 1) - 1) * $length;
        $items  = (clone $query)->offset($start)->limit($length)->get();

        /* ---------- mapping data ---------- */
        $data = $items->map(fn($row) => [
            'id'              => $row->id,
            'item_code'       => $row->item_code,
            'name'            => $row->name,
            'quantity'        => $row->quantity,
            'brand'           => $row->brand->name ?? '-',
            'category'        => $row->category->name ?? '-',
            'type'            => $row->type->name ?? '-',
            'manufacturer'    => $row->manufacturer,
            'manufacture_year'=> $row->manufacture_year,
            'isbn'            => $row->isbn,
            'image_url'       => $row->image_path ? Storage::url($row->image_path) : null,
            'created_at'      => $row->created_at->toDateTimeString(),
            'updated_at'      => $row->updated_at->toDateTimeString(),
        ]);

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    /* ----------------------------------------------------------
       |  RESOURCE METHODS
       ---------------------------------------------------------- */

    public function index(Request $request)
    {
        $query = InventoryItem::with(['brand', 'category', 'type']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('item_code', 'like', "%{$search}%");
            });
        }
        $this->whereInArray($query, $request, 'brand_id');
        $this->whereInArray($query, $request, 'category_id');
        $this->whereInArray($query, $request, 'type_id');

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'quantity'        => 'required|integer|min:0',
            'brand_id'        => 'required|exists:brands,id',
            'category_id'     => 'required|exists:categories,id',
            'type_id'         => 'required|exists:item_types,id',
            'manufacture_year'=> 'nullable|integer|min:1900|max:' . now()->year,
            'isbn'            => 'nullable|string|max:255|unique:inventory_items,isbn',
            'image'           => 'nullable|image|max:2048',
        ]);

        // generate kode
        $last  = InventoryItem::latest('id')->first();
        $next  = $last ? ((int) str_replace('ITM-', '', $last->item_code)) + 1 : 1;
        $validated['item_code'] = 'ITM-' . str_pad($next, 3, '0', STR_PAD_LEFT);

        // manufacturer
        $validated['manufacturer'] = \App\Models\Brand::findOrFail($validated['brand_id'])->name;

        // upload image
        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('inventory_item_images', 'public');
        }

        $item = InventoryItem::create($validated);
        return response()->json($item->load(['brand', 'category', 'type']), 201);
    }

    public function show($id)
    {
        $item = InventoryItem::with(['brand', 'category', 'type'])->findOrFail($id);
        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'quantity'        => 'required|integer|min:0',
            'brand_id'        => 'required|exists:brands,id',
            'category_id'     => 'required|exists:categories,id',
            'type_id'         => 'required|exists:item_types,id',
            'manufacture_year'=> 'nullable|integer|min:1900|max:' . now()->year,
            'isbn'            => 'nullable|string|max:255|unique:inventory_items,isbn,' . $id,
            'image'           => 'nullable|image|max:2048',
        ]);

        $validated['manufacturer'] = \App\Models\Brand::findOrFail($validated['brand_id'])->name;

        if ($request->hasFile('image')) {
            if ($item->image_path) Storage::disk('public')->delete($item->image_path);
            $validated['image_path'] = $request->file('image')->store('inventory_item_images', 'public');
        } elseif ($request->boolean('remove_image')) {
            if ($item->image_path) Storage::disk('public')->delete($item->image_path);
            $validated['image_path'] = null;
        }

        $item->update($validated);
        return response()->json($item->load(['brand', 'category', 'type']));
    }

    public function destroy($id)
    {
        $item = InventoryItem::findOrFail($id);

        // hapus file
        if ($item->image_path) {
            if (str_contains($item->image_path, 'res.cloudinary.com')) {
                $path = parse_url($item->image_path, PHP_URL_PATH);
                $segments = explode('/', trim($path, '/'));
                $publicId = pathinfo(end($segments), PATHINFO_FILENAME);
                $folder   = $segments[count($segments) - 2];
                Cloudinary::destroy("{$folder}/{$publicId}");
            } else {
                Storage::disk('public')->delete($item->image_path);
            }
        }

        $item->delete();
        return response()->json(['message' => 'Master barang berhasil dihapus']);
    }

    /* ----------------------------------------------------------
       |  CUSTOM ENDPOINTS
       ---------------------------------------------------------- */
    public function reserveSlot($id)
    {
        $item = InventoryItem::findOrFail($id);
        $item->increment('quantity');
        return response()->json(['quantity' => $item->quantity]);
    }

    public function increaseQuantity($id)
    {
        $item = InventoryItem::findOrFail($id);
        $item->increment('quantity');
        return response()->json(['quantity' => $item->quantity]);
    }

    public function getEmptySlots($id)
    {
        $item = InventoryItem::findOrFail($id);
        $totalUnit = \App\Models\Inventory::where('inventory_item_id', $item->id)->count();
        $emptySlots = max(0, $item->quantity - $totalUnit);

        return response()->json([
            'inventory_item_id' => $item->id,
            'quantity'          => $item->quantity,
            'total_unit'        => $totalUnit,
            'empty_slots'       => $emptySlots,
        ]);
    }
}