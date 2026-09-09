<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsappGroup;
use App\Services\Whatsapp\WhatsappGatewayClient;
use Illuminate\Http\Request;

class WhatsappGroupController extends Controller
{
    public function index()
    {
        return view('admin.whatsapp-groups.index', [
            'groups' => WhatsappGroup::with('adminUser')->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.whatsapp-groups.create', [
            'group' => new WhatsappGroup(['is_active' => true]),
            'users' => User::orderBy('name')->get(),
            'defaultTemplate' => config('whatsapp.default_template'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->syncDefaultFlag($data);

        WhatsappGroup::create($data);

        return redirect()->route('admin.whatsapp-groups.index')->with('success', 'WhatsApp group saved.');
    }

    public function edit(WhatsappGroup $whatsappGroup)
    {
        return view('admin.whatsapp-groups.edit', [
            'group' => $whatsappGroup,
            'users' => User::orderBy('name')->get(),
            'defaultTemplate' => config('whatsapp.default_template'),
        ]);
    }

    public function update(Request $request, WhatsappGroup $whatsappGroup)
    {
        $data = $this->validated($request);
        $this->syncDefaultFlag($data, $whatsappGroup->id);

        $whatsappGroup->update($data);

        return redirect()->route('admin.whatsapp-groups.index')->with('success', 'WhatsApp group updated.');
    }

    public function destroy(WhatsappGroup $whatsappGroup)
    {
        $whatsappGroup->delete();

        return redirect()->route('admin.whatsapp-groups.index')->with('success', 'WhatsApp group deleted.');
    }

    public function syncChatId(WhatsappGroup $whatsappGroup, WhatsappGatewayClient $gateway)
    {
        $status = $gateway->status();
        if (! ($status['ready'] ?? false)) {
            return back()->with('error', 'واتساب غير متصل. اربطيه من WhatsApp Connection أولاً.');
        }

        if ($whatsappGroup->invite_link) {
            $resolved = $gateway->resolveGroupFromInvite($whatsappGroup->invite_link);
            if ($resolved) {
                $whatsappGroup->update(['whatsapp_chat_id' => $resolved['id']]);
                $name = $resolved['name'] ?: $whatsappGroup->name;

                return back()->with('success', 'تم ربط المجموعة «'.$name.'» عبر رابط الدعوة — Chat ID: '.$resolved['id']);
            }
        }

        $remoteGroups = $gateway->listGroups();
        if ($remoteGroups === []) {
            $hint = $whatsappGroup->invite_link
                ? 'تأكدي إنو رابط الدعوة صحيح وإنو الحساب المربوط عضو بالمجموعة «'.$whatsappGroup->name.'».'
                : 'أضيفي رابط دعوة للمجموعة أو تأكدي إنو الحساب المربوط عضو بالمجموعة «'.$whatsappGroup->name.'».';

            return back()->with('error', 'ما قدرنا نربط المجموعة. '.$hint);
        }

        $match = collect($remoteGroups)->first(function (array $remote) use ($whatsappGroup) {
            $a = mb_strtolower(trim($remote['name'] ?? ''));
            $b = mb_strtolower(trim($whatsappGroup->name));

            return $a === $b || str_contains($a, $b) || str_contains($b, $a);
        }) ?? $remoteGroups[0];

        $whatsappGroup->update(['whatsapp_chat_id' => $match['id']]);

        return back()->with('success', 'تم ربط المجموعة «'.$match['name'].'» — Chat ID: '.$match['id']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'invite_link' => 'nullable|url|max:500',
            'whatsapp_chat_id' => 'nullable|string|max:80',
            'admin_phone' => 'nullable|string|max:20',
            'admin_user_id' => 'nullable|exists:users,id',
            'message_template' => 'nullable|string|max:4000',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_default'] = $request->boolean('is_default');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['admin_phone'] = $data['admin_phone']
            ? preg_replace('/\D+/', '', $data['admin_phone'])
            : null;
        $data['whatsapp_chat_id'] = isset($data['whatsapp_chat_id'])
            ? trim((string) $data['whatsapp_chat_id']) ?: null
            : null;

        return $data;
    }

    private function syncDefaultFlag(array &$data, ?int $exceptId = null): void
    {
        if (! ($data['is_default'] ?? false)) {
            return;
        }

        WhatsappGroup::query()
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->update(['is_default' => false]);
    }
}
