<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductDocument;
use App\Models\ProductPetType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{

   public function show($slug)
{
    $product = Product::with(['seller', 'productCategory', 'petTypes', 'images', 'documents'])
        ->where('status', 'approved')
        ->where('slug', $slug)
        ->firstOrFail();

    return response()->json([
        'id' => $product->id,
        'slug' => $product->slug,
        'name' => $product->name,
        'price' => $product->price,
        'stock' => $product->stock,
        'inStock' => $product->stock > 0,
        'brand' => $product->brand,
        'description' => $product->description,
        'ingredients' => $product->ingredients,
        'weight' => $product->weight,
        'expiration_date' => $product->expiration_date,

        'seller' => [
            'id' => $product->seller?->id,
            'name' => $product->seller?->name ?? 'Unknown Seller',
            'slug' => $product->seller?->slug,

            // ✅ Add location fields here
            'region' => $product->seller?->region,
            'province' => $product->seller?->province,
            'city' => $product->seller?->city,
            'barangay' => $product->seller?->barangay,
            'street_address' => $product->seller?->street_address,
        ],

        'product_category' => [
            'id' => $product->productCategory?->id,
            'name' => $product->productCategory?->name ?? 'Uncategorized',
        ],

        'pet_types' => $product->petTypes->map(fn($type) => [
            'id' => $type->id,
            'name' => $type->name,
        ]),

        'images' => $product->images->pluck('image_path'),
        'documents' => $product->documents->map(fn($doc) => [
            'document_type' => $doc->document_type,
            'file_path' => $doc->file_path,
        ]),
    ]);
}


    public function store(Request $request)
    {
        // 🔹 Validation
        $request->validate([
            'productName' => 'required|string|max:150',
            'brand' => 'nullable|string|max:100',
            'product_category_id' => 'required|exists:product_categories,id',
            'sku' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'ingredients' => 'nullable|string',
            'expirationDate' => 'nullable|date',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'weight' => 'nullable|numeric',
            'petType' => 'required|json', // array of IDs as JSON
            'productImages.*' => 'image|mimes:jpg,jpeg,png|max:2048',
            'fdaCertificate' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'productLabels' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        DB::beginTransaction();
        try {
            // ✅ Save product
            $product = new Product();
            $product->seller_id = Auth::id();
            $product->name = $request->productName;
            $product->brand = $request->brand;
            $product->product_category_id = $request->product_category_id; // ✅ correct field
            $product->sku = $request->sku;
            $product->description = $request->description;
            $product->ingredients = $request->ingredients;
            $product->expiration_date = $request->expirationDate;
            $product->price = $request->price;
            $product->stock = $request->stock;
            $product->weight = $request->weight;
            $product->status = 'pending';
            $product->save();

            // ✅ Save Pet Types (IDs directly)
            $petTypes = json_decode($request->petType, true);
            if (is_array($petTypes)) {
                foreach ($petTypes as $typeId) {
                    ProductPetType::create([
                        'product_id' => $product->id,
                        'category_id' => $typeId, // already ID, no need to map name
                    ]);
                }
            }

            // ✅ Upload Product Images
            if ($request->hasFile('productImages')) {
                foreach ($request->file('productImages') as $index => $file) {
                    $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                    $file->move(public_path('uploads/products'), $fileName);

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => 'uploads/products/' . $fileName,
                        'is_primary' => $index === 0 ? 1 : 0,
                    ]);
                }
            }

            // ✅ Upload FDA Certificate
            if ($request->hasFile('fdaCertificate')) {
                $fileName = time() . '_fda_' . uniqid() . '_' . $request->file('fdaCertificate')->getClientOriginalName();
                $request->file('fdaCertificate')->move(public_path('uploads/documents'), $fileName);

                ProductDocument::create([
                    'product_id' => $product->id,
                    'document_type' => 'fda_certificate',
                    'file_path' => 'uploads/documents/' . $fileName,
                ]);
            }

            // ✅ Upload Product Labels
            if ($request->hasFile('productLabels')) {
                $fileName = time() . '_label_' . uniqid() . '_' . $request->file('productLabels')->getClientOriginalName();
                $request->file('productLabels')->move(public_path('uploads/documents'), $fileName);

                ProductDocument::create([
                    'product_id' => $product->id,
                    'document_type' => 'product_label',
                    'file_path' => 'uploads/documents/' . $fileName,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Product created successfully',
                'product_id' => $product->id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Product store error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        // Only approved/active products
        $products = Product::with(['seller', 'productCategory', 'petTypes'])
            ->where('status', 'approved')
            ->get();

        // Transform response (so frontend has clean data)
        return $products->map(function ($product) {
            return [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'price' => $product->price,
                'stock' => $product->stock,
                'seller' => $product->seller?->name ?? 'Unknown Seller',
                'category' => $product->productCategory?->name ?? 'Uncategorized',
                'petTypes' => $product->petTypes->pluck('name'),
                'images' => $product->images->pluck('image_path') ?? [], // if you set relation
            ];
        });
    }

    
}
