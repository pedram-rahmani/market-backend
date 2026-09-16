<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\content\Review;
use App\Models\User\User;
use App\Models\Product\Product;

class ReviewSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();
        $products = Product::all();

        if ($users->isEmpty() || $products->isEmpty()) {
            $this->command->error('لطفاً ابتدا کاربر و محصول بسازید!');
            return;
        }

        $sampleComments = [
            'کیفیت بسیار عالی و طراحی شیکی داره. ارزش خرید بالایی داره.',
            'نسبت به قیمت بد نیست، ارسالش هم خیلی سریع انجام شد.',
            'بسته‌بندی تمیز بود ولی رنگش یکم با عکس تفاوت داشت.',
            'کاملاً راضی هستم، دقیقا همون چیزی بود که انتظار داشتم.',
            'یک هفته است دارم استفاده می‌کنم، عملکردش واقعا خوب بوده.'
        ];

        // روی تمام محصولات چرخش می‌زنیم تا همه محصول‌ها کامنت داشته باشن
        foreach ($products as $product) {
            // برای هر محصول ۲ تا ۴ کاربر تصادفی انتخاب می‌کنیم
            $randomUsers = $users->random(min(rand(2, 4), $users->count()));

            foreach ($randomUsers as $index => $user) {
                $review = Review::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'rating' => rand(3, 5),
                    'comment' => $sampleComments[array_rand($sampleComments)],
                    'is_approved' => true,
                ]);

                // فقط برای بعضی کامنت‌ها (مثلا کامنت اول) عکس تست اضافه می‌کنیم
                if ($index === 0) {
                    $review->media()->create([
                        'file_path' => 'reviews/test_image.jpg',
                        'file_type' => 'image',
                        'disk' => 'public',
                    ]);
                }
            }
        }
    }
}
