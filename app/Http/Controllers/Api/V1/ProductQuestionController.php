<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductQuestion;
use Illuminate\Http\Request;

class ProductQuestionController extends Controller
{
    public function index(Product $product)
    {
        $rows = ProductQuestion::query()
            ->where('product_id', $product->id)
            ->where('is_public', true)
            ->whereNotNull('answer')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (ProductQuestion $q) => [
                'id' => $q->id,
                'question' => $q->question,
                'answer' => $q->answer,
                'answered_at' => $q->answered_at?->toIso8601String(),
                'created_at' => $q->created_at?->toIso8601String(),
            ]);

        return response()->json(['questions' => $rows]);
    }

    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'question' => 'required|string|min:5|max:1000',
        ]);

        $row = ProductQuestion::query()->create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'question' => $data['question'],
            'is_public' => true,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'تم إرسال سؤالك. سيظهر بعد رد المتجر.',
            'id' => $row->id,
        ], 201);
    }
}
