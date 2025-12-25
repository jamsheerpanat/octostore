<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductAnswer;
use App\Models\ProductQuestion;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index($productId)
    {
        $questions = ProductQuestion::where('product_id', $productId)
             ->where('status', 'approved')
             ->with(['answers' => function($q) {
                 $q->where('status', 'approved');
             }, 'user:id,name'])
             ->latest()
             ->paginate(5);
             
        return response()->json($questions);
    }
    
    public function store(Request $request)
    {
         $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'question' => 'required|string|min:5',
        ]);
        
        $question = ProductQuestion::create([
            'user_id' => $request->user()->id,
            'product_id' => $validated['product_id'],
            'question' => $validated['question'],
            'status' => 'pending'
        ]);
        
        return response()->json(['message' => 'Question submitted for approval', 'data' => $question], 201);
    }
}
