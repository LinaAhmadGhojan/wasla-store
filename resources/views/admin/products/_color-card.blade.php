@php
    $cIndex = $cIndex ?? 0;
    $group = $group ?? ['key' => '', 'name' => '', 'hex' => '#cccccc', 'images' => collect(), 'sizes' => []];
    $open = $open ?? false;
    $imageCount = $group['images']->count();
    $sizeCount = count($group['sizes']);
    $firstVariant = $group['sizes'][0]['variant'] ?? null;
    $colorPrice = $firstVariant ? number_format((float) $firstVariant->price, 2, '.', ',') : '';
    $colorStock = $firstVariant?->stock_qty ?? 10;
@endphp
<div class="accordion-item color-card shadow-sm mb-3" data-color-index="{{ $cIndex }}" data-color-key="{{ $group['key'] }}">
    <h2 class="accordion-header">
        <button class="accordion-button {{ $open ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#colorBody{{ $cIndex }}" aria-expanded="{{ $open ? 'true' : 'false' }}">
            <span class="color-swatch-sm ms-2" style="background: {{ $group['hex'] ?: '#cccccc' }}"></span>
            <span class="color-head-name fw-semibold">{{ $group['name'] ?: 'لون جديد' }}</span>
            <span class="badge rounded-pill text-bg-light border ms-2 color-head-images">{{ $imageCount }} صور</span>
            <span class="badge rounded-pill text-bg-light border color-head-sizes">{{ $sizeCount }} مقاس</span>
            <span class="badge rounded-pill text-bg-warning color-dirty-badge d-none">غير محفوظ</span>
        </button>
    </h2>
    <div id="colorBody{{ $cIndex }}" class="accordion-collapse collapse {{ $open ? 'show' : '' }}" data-bs-parent="#colorList">
        <div class="accordion-body">
            <input type="hidden" class="color-original-key" value="{{ $group['key'] }}">
            <div class="row g-2 mb-3">
                <div class="col-md-5">
                    <label class="form-label small mb-1">اسم اللون</label>
                    <input type="text" class="form-control color-name-field" value="{{ $group['name'] }}" placeholder="مثلاً: أزرق / زهري" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">مفتاح اللون</label>
                    <input type="text" class="form-control color-key-field" dir="ltr" value="{{ $group['key'] }}" placeholder="blue" @if($group['key']) data-locked="1" @endif>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">العينة</label>
                    <input type="color" class="form-control form-control-color w-100 color-hex-field" value="{{ $group['hex'] ?: '#cccccc' }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-danger w-100 remove-color">حذف اللون</button>
                </div>
            </div>

            <label class="form-label fw-bold mb-2">صور هذا اللون</label>
            <div class="gallery-grid mb-2">
                @forelse($group['images'] as $image)
                    <div class="gallery-item" data-image-id="{{ $image->id }}">
                        <img src="{{ $image->url }}" alt="{{ $group['name'] }}">
                        <button type="button" class="gallery-remove" title="حذف الصورة">×</button>
                    </div>
                @empty
                @endforelse
            </div>
            <label class="image-drop">
                <input type="file" class="color-image-input" accept="image/*" multiple hidden>
                <strong>ارفع أكثر من صورة مرة واحدة</strong>
                <span>اضغط أو اسحب الصور هنا — تقدر تشوف المعاينة وتحذف قبل الحفظ</span>
            </label>

            <div class="row g-2 align-items-end mb-3 mt-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold mb-1">سعر هذا اللون (د.إ)</label>
                    <input type="text" inputmode="decimal" dir="ltr" class="form-control color-price-field money-commas price-aed" value="{{ $colorPrice }}" placeholder="مثلاً 46.60">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">≈ للزبون</label>
                    <div class="form-control-plaintext small text-success color-price-syp">{{ $colorPrice !== '' ? $currency->formatCustomer((float) str_replace(',', '', $colorPrice), $product->pricingKind()) : '—' }}</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">المخزون</label>
                    <input type="number" min="0" class="form-control color-stock-field" value="{{ $colorStock }}">
                </div>
                <div class="col-12">
                    <div class="form-text mt-0">للحقائب والإكسسوارات: السعر هون حسب اللون، وبعدين احفظي اللون. المقاسات تحت بس إذا المنتج إلو مقاسات.</div>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-2 mb-2 flex-wrap gap-2">
                <div>
                    <label class="form-label fw-bold mb-0">المقاسات</label>
                    <div class="form-text mt-0 size-scale-hint">تُضاف بالترتيب حسب التصنيف</div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary fill-sizes-btn">كل المقاسات</button>
                    <button type="button" class="btn btn-sm btn-outline-primary add-size-btn">+ المقاس التالي</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle size-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>المقاس</th>
                            <th>السعر د.إ</th>
                            <th>≈ للزبون</th>
                            <th>SKU</th>
                            <th>المخزون</th>
                            <th style="width:7.5rem"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($group['sizes'] as $sIndex => $row)
                            @php $variant = $row['variant'] ?? null; @endphp
                            <tr>
                                <td>
                                    <input type="hidden" class="size-id-field" value="{{ $variant?->id }}">
                                    <input type="text" value="{{ $row['size_name'] }}" class="form-control form-control-sm size-name-field" list="knownSizesList" required>
                                    <input type="hidden" value="{{ $row['size_key'] }}" class="size-key-field">
                                </td>
                                <td>
                                    <input type="text" inputmode="decimal" dir="ltr" value="{{ $variant ? number_format((float) $variant->price, 2, '.', ',') : '' }}" class="form-control form-control-sm price-aed money-commas" required>
                                </td>
                                <td class="small text-success price-syp">
                                    {{ $variant ? $currency->formatCustomer((float) $variant->price, $product->pricingKind()) : '—' }}
                                </td>
                                <td>
                                    <input type="text" value="{{ $variant?->sku }}" class="form-control form-control-sm size-sku-field" dir="ltr">
                                </td>
                                <td>
                                    <input type="number" min="0" value="{{ $variant?->stock_qty ?? 0 }}" class="form-control form-control-sm size-stock-field" required>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary copy-size" title="نسخ هذا المقاس لسطر جديد">نسخ</button>
                                        <button type="button" class="btn btn-outline-danger remove-size">حذف</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="form-text mb-3">أطفال: 0-3M ثم 3-6M… كل 3 أشهر. ملابس نسائي/رجالي: XS → S → M → L → XL → XXL.</p>

            <div class="color-save-bar">
                <button type="button" class="btn btn-primary save-color-btn">حفظ هذا اللون</button>
                <span class="color-save-status small text-muted">يحفظ الصور والمقاسات لهاللون فقط</span>
            </div>
        </div>
    </div>
</div>
