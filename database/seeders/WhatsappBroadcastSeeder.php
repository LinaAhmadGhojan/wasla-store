<?php

namespace Database\Seeders;

use App\Models\WhatsappBroadcastMessage;
use Illuminate\Database\Seeder;

class WhatsappBroadcastSeeder extends Seeder
{
    public function run(): void
    {
        $messages = [
            [
                'title' => 'فتح الطلبية — استقبال طلبات',
                'category' => 'orders_open',
                'sort_order' => 10,
                'body' => <<<'TXT'
📦 *فتحنا الطلبية!*

عم نستقبل طلباتكم اليوم 🛍️
اكتبوا: اسم المنتج + المقاس + اللون
أو راسلونا خاص للتفاصيل 💚

⏰ الطلبية بتضل مفتوحة لآخر اليوم
TXT,
            ],
            [
                'title' => 'إغلاق الطلبية — اليوم',
                'category' => 'orders_closed',
                'sort_order' => 20,
                'body' => <<<'TXT'
🔒 *منسكر الطلبية لليوم*

شكراً للجميع على ثقتكم 🙏
بكرا منفتح طلبية جديدة إن شاء الله 🌙

أي استفسار عن طلب سابق — اكتبوا هون
TXT,
            ],
            [
                'title' => 'تحديث الشحن والتوصيل',
                'category' => 'shipping',
                'sort_order' => 30,
                'body' => <<<'TXT'
🚚 *تحديث الشحن*

عم نتابع الشحنات ومنرسل أرقام التتبع 📍
أي سؤال عن طلبك — اكتبه هون ومنرد عليك

{store} — {date}
TXT,
            ],
            [
                'title' => 'وصلة معكم — تحفيز',
                'category' => 'branding',
                'sort_order' => 40,
                'body' => <<<'TXT'
✨ *{store} معكم*

من الطلب لعندك — بخطوة واحدة 💚
ثقتكم بتعني كتير ومنشتغل لنقدّم الأفضل

تابعوا المجموعة — في عروض ومنتجات جديدة دايماً 🔥
TXT,
            ],
            [
                'title' => 'مكافأة إضافة 100 شخص',
                'category' => 'incentive',
                'sort_order' => 50,
                'body' => <<<'TXT'
🎁 *عرض خاص!*

يلي بضيف *100 شخص* على مجموعة «{group_name}»:
🏆 مكافأة *20,000 ل.س*

📲 ابعتولنا سكرين بالإضافات على الخاص للتأكيد
العرض محدود — لا تفوتوا الفرصة!
TXT,
            ],
            [
                'title' => 'ترحيب بالأعضاء الجدد',
                'category' => 'welcome',
                'sort_order' => 60,
                'body' => <<<'TXT'
👋 *أهلاً فيكن بـ {group_name}!*

هون مننشر أحدث المنتجات والعروض 🛍️
فعلوا التنبيهات 🔔 ما تفوتكم فرصة

أي سؤال — اكتبوا هون، فريق {store} معكن 💚
TXT,
            ],
            [
                'title' => 'آخر فرصة — الطلبية بتسكر',
                'category' => 'orders_open',
                'sort_order' => 15,
                'body' => <<<'TXT'
⏰ *آخر فرصة!*

الطلبية بتسكر بكرا — اكتبوا طلباتكن قبل ما نبلّش الشحن 📦

ما تترددوا — اكتبوا «بدي» + صورة المنتج 💬
TXT,
            ],
            [
                'title' => 'شكر وتشجيع',
                'category' => 'motivational',
                'sort_order' => 70,
                'body' => <<<'TXT'
⭐ *شكراً إنكن معنا!*

كل طلب منكن بخلينا نكبر ونقدّم أحسن 💪
استمروا بالمشاركة — في مفاجآت جاية 🎁

{store} — دايماً معكم 💚
TXT,
            ],
            [
                'title' => 'تحدي مشاركة المنتجات',
                'category' => 'incentive',
                'sort_order' => 55,
                'body' => <<<'TXT'
🔥 *تحدي الأسبوع*

أكتر شخص يشارك *5 منتجات* من المجموعة:
🎯 خصم *15%* على طلبته القادمة!

شاركوا مع أصدقائكن وخبرونا بالخاص 🏆
TXT,
            ],
            [
                'title' => 'استفسارات وحالات شراء',
                'category' => 'general',
                'sort_order' => 80,
                'body' => <<<'TXT'
💬 *عم نستقبل استفساراتكم*

حابين تطلبوا؟ حابين شرح عن منتج؟
اكتبوا هون أو راسلونا خاص 📲

فريق {store} جاهز يساعدكن — {date}
TXT,
            ],
        ];

        foreach ($messages as $message) {
            WhatsappBroadcastMessage::updateOrCreate(
                ['title' => $message['title']],
                $message + ['is_active' => true],
            );
        }
    }
}
