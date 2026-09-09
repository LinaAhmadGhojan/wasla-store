<form action="{{ $action }}" method="post" class="card shadow-sm border-0">
    @csrf
    @if($method !== 'post') @method($method) @endif
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">عنوان الرسالة (للأدمن) *</label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $message->title) }}" required
                    placeholder="مثلاً: فتح الطلبية — استقبال طلبات">
            </div>
            <div class="col-md-4">
                <label class="form-label">النوع *</label>
                <select name="category" class="form-select" required>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" @selected(old('category', $message->category) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">نص الرسالة *</label>
                <textarea name="body" rows="12" class="form-control font-monospace" required
                    placeholder="اكتبي الرسالة هون...">{{ old('body', $message->body) }}</textarea>
                <div class="form-text">
                    استخدمي *نجوم* للخط العريض في واتساب.
                    متغيرات: <code>{group_name}</code> <code>{store}</code> <code>{date}</code> <code>{time}</code>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">ترتيب العرض</label>
                <input type="number" name="sort_order" class="form-control" min="0" max="9999"
                    value="{{ old('sort_order', $message->sort_order ?? 0) }}">
            </div>
            <div class="col-md-3">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                        @checked(old('is_active', $message->is_active ?? true))>
                    <label class="form-check-label" for="is_active">فعّالة</label>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white d-flex gap-2">
        <button type="submit" class="btn btn-primary">حفظ</button>
        <a href="{{ route('admin.whatsapp-messages.index') }}" class="btn btn-secondary">إلغاء</a>
    </div>
</form>
