<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Content\Question;
use App\Models\User\User;
use App\Models\Product\Product;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $product = Product::first();

        if (!$user || !$product) {
            return;
        }

        $question1 = Question::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'parent_id' => null,
            'body' => 'سلام، آیا این محصول رنگ‌بندی‌های دیگری هم دارد؟',
            'is_approved' => true,
            'is_admin_answer' => false,
        ]);

        Question::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'parent_id' => $question1->id,
            'body' => 'سلام، خیر در حال حاضر فقط همین رنگ موجود است.',
            'is_approved' => true,
            'is_admin_answer' => true,
        ]);


        $question2 = Question::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'parent_id' => null,
            'body' => 'کیفیت ساخت این محصول چطوره؟ ارزش خرید داره؟',
            'is_approved' => false,
            'is_admin_answer' => false,
        ]);

        Question::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'parent_id' => $question2->id,
            'body' => 'من خریدم راضی بودم، خیلی جنسش خوبه.',
            'is_approved' => false,
            'is_admin_answer' => false,
        ]);


        Question::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'parent_id' => null,
            'body' => 'گارانتی این محصول شامل چه مواردی میشه؟',
            'is_approved' => true,
            'is_admin_answer' => false,
        ]);
    }
}
