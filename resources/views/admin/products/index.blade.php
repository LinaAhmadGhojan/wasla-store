@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">Products</h1>
                <p class="text-muted mb-0">اختاري الوجهة مرة واحدة، ثم اضغطي WhatsApp — إرسال فوري.</p>
            </div>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Create Product</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if(!$canSendToGroup && $gatewayReady)
            <div class="alert alert-warning">
                <strong>المجموعة غير مربوطة.</strong>
                الحساب المربوط لازم يكون عضو بمجموعة «{{ $group?->name }}»، ثم
                <a href="{{ $group ? route('admin.whatsapp-groups.edit', $group) : route('admin.whatsapp-groups.index') }}">اربطي Group Chat ID</a>
                (زر «جلب المجموعات» أو «ربط تلقائي»).
            </div>
        @endif

        @if(!$gatewayReady)
            <div class="alert alert-warning">
                الإرسال التلقائي غير جاهز — <a href="{{ route('admin.whatsapp.connection') }}">اربطي واتساب</a> أولاً.
            </div>
        @endif

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
                <form action="{{ route('admin.products.index') }}" method="get" class="d-flex gap-2 align-items-center">
                    <input type="search" name="q" value="{{ old('q', $search) }}" class="form-control form-control-sm" placeholder="Search products" />
                    <button class="btn btn-secondary btn-sm">Search</button>
                </form>
                <div class="text-muted">Showing {{ $products->count() }} of {{ $products->total() }} products</div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3 border-success border-opacity-25">
            <div class="card-body d-flex flex-wrap gap-3 align-items-center">
                <strong>📲 وجهة الإرسال:</strong>

                <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input wa-target-radio" type="radio" name="wa_target" id="wa_target_phone" value="phone"
                        @checked($defaultSendTarget === 'phone')>
                    <label class="form-check-label" for="wa_target_phone">
                        رقمي @if($recipientPhone)<span dir="ltr">(+{{ $recipientPhone }})</span>@endif
                    </label>
                </div>
                <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input wa-target-radio" type="radio" name="wa_target" id="wa_target_group" value="group"
                        @checked($defaultSendTarget === 'group')
                        @disabled(!$canSendToGroup)>
                    <label class="form-check-label" for="wa_target_group">
                        مجموعة @if($group)«{{ $group->name }}»@endif
                        @if(!$canSendToGroup)
                            <a href="{{ $group ? route('admin.whatsapp-groups.edit', $group) : route('admin.whatsapp-groups.index') }}" class="small">(اربطي)</a>
                        @endif
                    </label>
                </div>

                <span class="text-muted small ms-auto">
                    <strong>WhatsApp</strong> = ضغطة واحدة + صورة ·
                    <strong>إرسال المحدد</strong> = جماعي نص (حد {{ $bulkMaxProducts }})
                </span>
            </div>
        </div>

        <form action="{{ route('admin.products.whatsapp.bulk') }}" method="post" id="bulkWhatsappForm">
            @csrf
            <input type="hidden" name="target" id="bulk_target_input" value="{{ $defaultSendTarget }}">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body d-flex flex-wrap gap-3 align-items-center">
                    <strong class="text-success">نشر جماعي:</strong>
                    <span class="text-muted small" id="selectedCount">0 محدد</span>
                    <button type="submit" class="btn btn-success btn-sm" id="bulkSendBtn" disabled
                        onclick="return confirm('إرسال المنتجات المحددة (نص فقط)؟')">
                        إرسال المحدد
                    </button>
                </div>
            </div>
        </form>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:2rem">
                                <input type="checkbox" class="form-check-input" id="checkAll" title="تحديد الكل">
                            </th>
                            <th></th>
                            <th>المنتج</th>
                            <th>الألوان / المقاسات</th>
                            <th>Store</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>واتساب</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr class="{{ $product->isWhatsappPublished() ? 'table-success' : '' }}">
                                <td>
                                    <input type="checkbox" class="form-check-input product-check" name="product_ids[]"
                                        value="{{ $product->id }}" form="bulkWhatsappForm">
                                </td>
                                <td>
                                    @if($product->image)
                                        <img src="{{ $product->image }}" alt="" width="48" height="48" style="object-fit:cover;border-radius:8px">
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.products.edit', $product) }}" class="fw-semibold text-decoration-none">{{ $product->name }}</a>
                                    @if($product->source_external_id)
                                        <div class="small text-muted">#{{ $product->source_external_id }}</div>
                                    @endif
                                </td>
                                <td>
                                    @php $groups = $product->colorCatalog(); @endphp
                                    @if($groups)
                                        <div class="d-flex flex-wrap gap-1 mb-1">
                                            @foreach($groups as $group)
                                                <span class="badge rounded-pill" style="background:{{ $group['hex'] ?: '#1c7282' }}">{{ $group['name'] }}</span>
                                            @endforeach
                                        </div>
                                        <small class="text-muted">{{ $product->variants_count }} تركيب · {{ $product->images->count() }} صور</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $product->vendor?->store_name ?? 'N/A' }}</td>
                                <td>{{ $product->category?->name ?? 'Uncategorized' }}</td>
                                <td>
                                    {{ number_format($product->price, 2) }} د.إ
                                    <div class="small text-success">{{ number_format($product->price_syp) }} ل.س</div>
                                </td>
                                <td>
                                    @if($product->isWhatsappPublished())
                                        <span class="badge bg-success" title="{{ $product->whatsapp_published_at->format('Y-m-d H:i') }}">
                                            منشور
                                        </span>
                                        <span class="text-muted small d-block">
                                            {{ $product->whatsapp_published_at->diffForHumans() }}
                                            · {{ $product->whatsapp_published_target === 'group' ? 'مجموعة' : 'رقم' }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">لم يُنشر</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $product->is_active ? 'primary' : 'secondary' }}">
                                        {{ $product->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('admin.products.whatsapp.send', $product) }}" method="post"
                                        class="d-inline-block wa-send-form">
                                        @csrf
                                        <input type="hidden" name="target" value="{{ $defaultSendTarget }}" class="wa-target-field">
                                        <button type="submit" class="btn btn-sm btn-success"
                                            @disabled(!$gatewayReady) title="إرسال فوري + صورة">
                                            WhatsApp
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.products.destroy', $product) }}" method="post" class="d-inline-block">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this product?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">No products found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">{{ $products->links('pagination::bootstrap-5') }}</div>
    </div>

    <script>
    const checks = () => document.querySelectorAll('.product-check');
    const checkAll = document.getElementById('checkAll');
    const bulkSendBtn = document.getElementById('bulkSendBtn');
    const selectedCount = document.getElementById('selectedCount');
    const bulkTargetInput = document.getElementById('bulk_target_input');

    const WA_TARGET_KEY = 'wasla_wa_target';

    function selectedTarget() {
        return document.querySelector('input[name="wa_target"]:checked')?.value || 'phone';
    }

    function syncAllTargets() {
        const target = selectedTarget();
        bulkTargetInput.value = target;
        document.querySelectorAll('.wa-target-field').forEach((input) => {
            input.value = target;
        });
        localStorage.setItem(WA_TARGET_KEY, target);
    }

    const savedTarget = localStorage.getItem(WA_TARGET_KEY);
    if (savedTarget === 'group' && document.getElementById('wa_target_group') && !document.getElementById('wa_target_group').disabled) {
        document.getElementById('wa_target_group').checked = true;
    } else if (savedTarget === 'phone' && document.getElementById('wa_target_phone')) {
        document.getElementById('wa_target_phone').checked = true;
    }

    document.querySelectorAll('.wa-target-radio').forEach((radio) => {
        radio.addEventListener('change', syncAllTargets);
    });
    syncAllTargets();

    document.querySelectorAll('.wa-send-form').forEach((form) => {
        form.addEventListener('submit', () => {
            form.querySelector('.wa-target-field').value = selectedTarget();
        });
    });

    function refreshBulkUi() {
        const selected = [...checks()].filter(c => c.checked);
        const n = selected.length;
        const max = @json($bulkMaxProducts);
        selectedCount.textContent = n + ' محدد' + (n > max ? ' (كثير!)' : '');
        bulkSendBtn.disabled = n === 0 || n > max || !@json($gatewayReady);
        selectedCount.classList.toggle('text-danger', n > max);
    }

    checkAll?.addEventListener('change', () => {
        checks().forEach(c => { c.checked = checkAll.checked; });
        refreshBulkUi();
    });

    checks().forEach(c => c.addEventListener('change', refreshBulkUi));
    refreshBulkUi();
    </script>
@endsection
