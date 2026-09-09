<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Push\FcmPushService;
use Illuminate\Console\Command;

class FcmTestCommand extends Command
{
    protected $signature = 'fcm:test {user : User ID} {--title=تجربة وصلة} {--body=هذا إشعار تجريبي من السيرفر}';

    protected $description = 'إرسال إشعار FCM تجريبي لمستخدم';

    public function handle(FcmPushService $fcm): int
    {
        if (! $fcm->enabled()) {
            $this->error('FCM غير مفعّل. راجع docs/FCM-SETUP.md وضبط .env + ملف credentials.');

            return self::FAILURE;
        }

        $user = User::query()->find($this->argument('user'));
        if (! $user) {
            $this->error('المستخدم غير موجود.');

            return self::FAILURE;
        }

        $result = $fcm->sendToUser(
            $user,
            (string) $this->option('title'),
            (string) $this->option('body'),
            ['type' => 'test', 'order_id' => '']
        );

        $this->info(json_encode($result, JSON_UNESCAPED_UNICODE));

        return ($result['sent'] ?? 0) > 0 ? self::SUCCESS : self::FAILURE;
    }
}
