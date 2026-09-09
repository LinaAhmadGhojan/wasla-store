<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappBroadcastMessage;
use App\Models\WhatsappGroup;
use App\Services\Whatsapp\WhatsappPublisher;
use Illuminate\Http\Request;

class WhatsappBroadcastController extends Controller
{
    public function index(WhatsappPublisher $publisher)
    {
        $group = WhatsappGroup::defaultGroup();

        return view('admin.whatsapp-messages.index', [
            'messages' => WhatsappBroadcastMessage::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'categories' => WhatsappBroadcastMessage::CATEGORIES,
            'group' => $group,
            'gatewayReady' => $publisher->gatewayReady(),
            'canSendToGroup' => $publisher->canSendToGroup($group),
        ]);
    }

    public function create()
    {
        return view('admin.whatsapp-messages.create', [
            'message' => new WhatsappBroadcastMessage(['is_active' => true]),
            'categories' => WhatsappBroadcastMessage::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        WhatsappBroadcastMessage::create($this->validated($request));

        return redirect()
            ->route('admin.whatsapp-messages.index')
            ->with('success', 'تم حفظ الرسالة.');
    }

    public function edit(WhatsappBroadcastMessage $whatsappMessage)
    {
        return view('admin.whatsapp-messages.edit', [
            'message' => $whatsappMessage,
            'categories' => WhatsappBroadcastMessage::CATEGORIES,
        ]);
    }

    public function update(Request $request, WhatsappBroadcastMessage $whatsappMessage)
    {
        $whatsappMessage->update($this->validated($request));

        return redirect()
            ->route('admin.whatsapp-messages.index')
            ->with('success', 'تم تحديث الرسالة.');
    }

    public function destroy(WhatsappBroadcastMessage $whatsappMessage)
    {
        $whatsappMessage->delete();

        return redirect()
            ->route('admin.whatsapp-messages.index')
            ->with('success', 'تم حذف الرسالة.');
    }

    public function send(WhatsappBroadcastMessage $whatsappMessage, WhatsappPublisher $publisher)
    {
        try {
            $publisher->sendBroadcast($whatsappMessage);

            $groupName = WhatsappGroup::defaultGroup()?->name ?? 'المجموعة';

            return redirect()
                ->route('admin.whatsapp-messages.index')
                ->with('success', 'تم إرسال «'.$whatsappMessage->title.'» إلى «'.$groupName.'» ✓');
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.whatsapp-messages.index')
                ->with('error', 'فشل الإرسال: '.$e->getMessage());
        }
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:'.implode(',', array_keys(WhatsappBroadcastMessage::CATEGORIES)),
            'body' => 'required|string|max:4000',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
