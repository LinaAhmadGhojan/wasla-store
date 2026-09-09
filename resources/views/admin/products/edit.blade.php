@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4 product-editor" dir="rtl">
        <div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
            <div>
                <h1 class="h3 mb-1">تعديل المنتج</h1>
                <p class="text-muted mb-0">بيانات المنتج مرة، وكل لون ينحفظ لحاله: صور + مقاسات</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ url('/product/'.$product->id) }}" class="btn btn-outline-primary" target="_blank">عرض كزبون</a>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">رجوع للمنتجات</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <strong>ما انحفظ — أصلحي الحقول:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 sticky-lg-top" style="top:1rem">
                    <div class="card-body">
                        <div class="preview-hero mb-3">
                            @if($product->image)
                                <img src="{{ $product->image }}" alt="{{ $product->name }}" id="heroPreview">
                            @else
                                <div class="preview-empty" id="heroPreviewEmpty">لا صورة</div>
                            @endif
                        </div>
                        <h2 class="h5 mb-1">{{ $product->name }}</h2>
                        <p class="small text-muted mb-2">
                            {{ $product->brand?->name }}
                            @if($product->category) · {{ $product->category->name }} @endif
                        </p>
                        @if($product->source_external_id)
                            <p class="small mb-1">Goods ID: <code>{{ $product->source_external_id }}</code></p>
                        @endif
                        <p class="mb-1"><strong>{{ $currency->formatAed((float) $product->price) }}</strong></p>
                        <p class="text-success small mb-1" id="heroCustomerPrice">
                            للزبون {{ $currency->formatCustomer((float) $product->price, $product->pricingKind()) }}
                        </p>
                        <p class="small text-muted mb-3" id="heroFormula">
                            {{ number_format((float) $product->price, 2) }} × {{ number_format($exchangeRate) }} + {{ number_format($product->pricingKind() === 'accessory' ? $accessoryFee : $productFee, 0) }}
                        </p>
                        <div class="d-flex flex-wrap gap-1 mb-2" id="colorBadges">
                            @foreach($colorCatalog as $group)
                                <span class="badge rounded-pill" style="background:{{ $group['hex'] ?: '#1c7282' }};color:#fff">
                                    {{ $group['name'] }} · {{ $group['images']->count() }} صور · {{ count($group['sizes']) }} مقاس
                                </span>
                            @endforeach
                        </div>
                        <p class="small text-muted mb-0">{{ $product->variants->count() }} تركيب لون×مقاس</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <form action="{{ route('admin.products.update', $product) }}" method="post" id="productCatalogForm">
                    @csrf
                    @method('put')
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <h3 class="h6 text-uppercase text-muted mb-3">بيانات المنتج</h3>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">الاسم</label>
                                    <input type="text" name="name" value="{{ old('name', $product->name) }}" class="form-control" required />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Slug</label>
                                    <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" class="form-control" />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">المتجر</label>
                                    <select name="vendor_id" class="form-select" required>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}" @selected(old('vendor_id', $product->vendor_id) == $vendor->id)>{{ $vendor->store_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">التصنيف</label>
                                    <select name="category_id" id="productCategory" class="form-select">
                                        <option value="">بدون</option>
                                        @foreach($categories as $category)
                                            @php
                                                $catSlugs = collect([
                                                    $category->slug,
                                                    $category->parent?->slug,
                                                    $category->parent?->parent?->slug,
                                                ])->filter()->implode(' ');
                                            @endphp
                                            <option value="{{ $category->id }}" data-slugs="{{ $catSlugs }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">الماركة</label>
                                    <select name="brand_id" class="form-select">
                                        <option value="">بدون</option>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) == $brand->id)>{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">سعر أساسي (د.إ)</label>
                                    <input type="text" name="price" inputmode="decimal" dir="ltr" class="form-control money-commas" value="{{ old('price', number_format((float) $product->price, 2, '.', ',')) }}" required />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">سعر عرض (د.إ)</label>
                                    <input type="text" name="sale_price" inputmode="decimal" dir="ltr" class="form-control money-commas" value="{{ old('sale_price', $product->sale_price !== null ? number_format((float) $product->sale_price, 2, '.', ',') : '') }}" />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">نوع التسعير</label>
                                    <select name="pricing_type" id="pricingType" class="form-select">
                                        <option value="product" @selected(old('pricing_type', $product->pricingKind()) === 'product')>منتج (+{{ number_format($productFee, 0) }} ل.س)</option>
                                        <option value="accessory" @selected(old('pricing_type', $product->pricingKind()) === 'accessory')>إكسسوار (+{{ number_format($accessoryFee, 0) }} ل.س)</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">مميّز</label>
                                    <select name="is_featured" class="form-select">
                                        <option value="0" @selected(!old('is_featured', $product->is_featured))>لا</option>
                                        <option value="1" @selected(old('is_featured', $product->is_featured))>نعم</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">الحالة</label>
                                    <select name="is_active" class="form-select" required>
                                        <option value="1" @selected(old('is_active', $product->is_active) == 1)>ظاهر للزبون</option>
                                        <option value="0" @selected(old('is_active', $product->is_active) == 0)>مخفي</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">قابل للإرجاع</label>
                                    <select name="allow_return" class="form-select">
                                        <option value="1" @selected(old('allow_return', $product->allow_return ?? true))>نعم</option>
                                        <option value="0" @selected(!old('allow_return', $product->allow_return ?? true))>لا</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">أيام الإرجاع</label>
                                    <input type="number" name="return_days" min="1" max="90" class="form-control" placeholder="افتراضي 7" value="{{ old('return_days', $product->return_days) }}" />
                                    <div class="form-text">من الاستلام — فارغ = إعدادات عامة</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">قابل للاستبدال</label>
                                    <select name="allow_exchange" class="form-select">
                                        <option value="1" @selected(old('allow_exchange', $product->allow_exchange ?? true))>نعم</option>
                                        <option value="0" @selected(!old('allow_exchange', $product->allow_exchange ?? true))>لا</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">أيام الاستبدال</label>
                                    <input type="number" name="exchange_days" min="1" max="90" class="form-control" placeholder="افتراضي 7" value="{{ old('exchange_days', $product->exchange_days) }}" />
                                    <div class="form-text">من الاستلام — فارغ = إعدادات عامة</div>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">رابط المصدر (SHEIN)</label>
                                    <input type="url" name="source_url" value="{{ old('source_url', $product->source_url) }}" class="form-control" dir="ltr" />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">رقم المنتج الخارجي</label>
                                    <input type="text" name="source_external_id" value="{{ old('source_external_id', $product->source_external_id) }}" class="form-control" dir="ltr" />
                                </div>
                                <div class="col-12">
                                    <label class="form-label">الوصف</label>
                                    <textarea name="description" rows="3" class="form-control">{{ old('description', $product->description) }}</textarea>
                                </div>
                                @include('admin.products._details-editor')
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">حفظ بيانات المنتج</button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h3 class="h5 mb-0">الألوان</h3>
                    <button type="button" class="btn btn-sm btn-primary" id="addColorBtn">+ لون جديد</button>
                </div>
                <p class="text-muted small mb-3">افتحي اللون → حطي سعر اللون (حقائب) أو المقاسات (ملابس) → ارفعي الصور → احفظي هذا اللون.</p>

                <div class="accordion" id="colorList">
                    @forelse($colorCatalog as $cIndex => $group)
                        @include('admin.products._color-card', [
                            'cIndex' => $cIndex,
                            'group' => $group,
                            'currency' => $currency,
                            'exchangeRate' => $exchangeRate,
                            'open' => $cIndex === 0,
                        ])
                    @empty
                        <div class="alert alert-light border" id="noColorsHint">ما في ألوان بعد. أضيفي لون للبدء.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <datalist id="knownSizesList"></datalist>

    <template id="colorCardTpl">
        @include('admin.products._color-card', [
            'cIndex' => '__C__',
            'group' => [
                'key' => '',
                'name' => '',
                'hex' => '#cccccc',
                'images' => collect(),
                'sizes' => [],
            ],
            'currency' => $currency,
            'exchangeRate' => $exchangeRate,
            'open' => true,
        ])
    </template>

    <style>
        .product-editor .preview-hero {
            aspect-ratio: 1;
            border-radius: 1rem;
            overflow: hidden;
            background: linear-gradient(135deg, #eef9fa, #f7fcfd);
        }
        .product-editor .preview-hero img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .product-editor .preview-empty { height: 220px; display: flex; align-items: center; justify-content: center; color: #9aabaf; }
        .color-card { border: 1px solid rgba(15,90,107,.12); border-radius: 1.1rem; overflow: hidden; }
        .color-card .accordion-button { gap: .5rem; flex-wrap: wrap; }
        .color-card .accordion-button:not(.collapsed) { background: #f5fbfc; color: inherit; box-shadow: none; }
        .color-swatch-sm { width: 22px; height: 22px; border-radius: .4rem; border: 1px solid rgba(15,90,107,.25); flex-shrink: 0; display: inline-block; }
        .gallery-grid { display: flex; gap: .5rem; flex-wrap: wrap; }
        .gallery-item { position: relative; width: 92px; }
        .gallery-item img { width: 92px; height: 92px; object-fit: cover; border-radius: .6rem; display: block; cursor: pointer; }
        .gallery-remove {
            position: absolute; top: 4px; inset-inline-end: 4px;
            width: 22px; height: 22px; border: 0; border-radius: 999px;
            background: rgba(168,38,38,.92); color: #fff; line-height: 1; font-size: 1rem;
        }
        .gallery-item.is-pending::after {
            content: 'جديدة';
            position: absolute; bottom: 4px; inset-inline-start: 4px;
            background: rgba(15,90,107,.9); color: #fff; font-size: .65rem;
            padding: 1px 6px; border-radius: 999px;
        }
        .image-drop {
            display: flex; flex-direction: column; gap: .15rem;
            border: 1.5px dashed rgba(15,90,107,.35); border-radius: .9rem;
            padding: .9rem 1rem; cursor: pointer; background: #f8fdfe; color: #35555d;
        }
        .image-drop strong { color: #1c7282; }
        .image-drop.is-drag { background: #e7f7fb; border-color: #1c7282; }
        .size-table input { min-width: 0; }
        .color-save-bar {
            display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
            margin-top: 1rem; padding-top: .9rem; border-top: 1px solid rgba(15,90,107,.1);
        }
    </style>
    <script>
    const RATE = @json($exchangeRate);
    const PRODUCT_FEE = @json((float) $productFee);
    const ACCESSORY_FEE = @json((float) $accessoryFee);
    const COLOR_SAVE_URL = @json(route('admin.products.colors.update', $product));
    const COLOR_DELETE_URL = @json(route('admin.products.colors.destroy', $product));
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const SIZE_PRESETS = {
        kids: @json(\App\Support\SizeScale::KIDS),
        letters: @json(\App\Support\SizeScale::LETTERS),
        women_shoes: @json(\App\Support\SizeScale::WOMEN_SHOES),
        men_shoes: @json(\App\Support\SizeScale::MEN_SHOES),
    };
    let colorCount = {{ count($colorCatalog) }};

    function latinSlug(value) {
        return String(value || '').toLowerCase().trim().replace(/\s+/g, '-').replace(/[^a-z0-9\-]/g, '');
    }

    function currentSizeScale() {
        const slugs = document.getElementById('productCategory')?.selectedOptions?.[0]?.dataset?.slugs || '';
        const s = slugs.toLowerCase();
        if (s.includes('kids')) {
            return { key: 'kids', label: 'أطفال — كل 3 أشهر ثم سنوي', sizes: SIZE_PRESETS.kids };
        }
        if (s.includes('shoe') && (s.includes('women') || s.includes('نساء'))) {
            return { key: 'women_shoes', label: 'أحذية نسائية', sizes: SIZE_PRESETS.women_shoes };
        }
        if (s.includes('shoe') && (s.includes('men') || s.includes('رجال'))) {
            return { key: 'men_shoes', label: 'أحذية رجالية', sizes: SIZE_PRESETS.men_shoes };
        }
        if (s.includes('shoe')) {
            return { key: 'women_shoes', label: 'أحذية', sizes: SIZE_PRESETS.women_shoes };
        }
        return { key: 'letters', label: 'ملابس — XS إلى XXL', sizes: SIZE_PRESETS.letters };
    }

    function nextSizeName(existing) {
        const used = new Set((existing || []).map((name) => String(name || '').trim().toUpperCase()).filter(Boolean));
        return currentSizeScale().sizes.find((size) => !used.has(size.toUpperCase())) || '';
    }

    function refreshSizeScaleUi() {
        const scale = currentSizeScale();
        document.querySelectorAll('.size-scale-hint').forEach((el) => {
            el.textContent = scale.label + ' — تُضاف بالترتيب';
        });
        const list = document.getElementById('knownSizesList');
        if (list) {
            list.innerHTML = scale.sizes.map((size) => `<option value="${esc(size)}"></option>`).join('');
        }
    }

    function existingSizeNames(card) {
        return [...(card?.querySelectorAll('.size-name-field') || [])].map((input) => input.value);
    }

    function newSizePayload(card) {
        const rows = [...card.querySelectorAll('tbody tr')];
        const last = rows[rows.length - 1];
        const copied = last ? readSizeRow(last) : {
            price: card.querySelector('.color-price-field')?.value || document.querySelector('input[name="price"]')?.value || '',
            stock_qty: card.querySelector('.color-stock-field')?.value || 10,
        };
        copied.size_name = nextSizeName(existingSizeNames(card));
        copied.id = '';
        copied.sku = '';
        return copied;
    }

    function fillKnownSizes(card) {
        const scale = currentSizeScale();
        const existing = existingSizeNames(card).map((name) => name.trim().toUpperCase());
        const last = card.querySelector('tbody tr:last-child');
        const base = last ? readSizeRow(last) : {
            price: card.querySelector('.color-price-field')?.value || document.querySelector('input[name="price"]')?.value || '',
            stock_qty: card.querySelector('.color-stock-field')?.value || 10,
        };
        scale.sizes.forEach((size) => {
            if (existing.includes(size.toUpperCase())) return;
            addSizeRow(card, { ...base, size_name: size, id: '', sku: '' });
            existing.push(size.toUpperCase());
        });
    }

    function parseMoney(value) {
        return Number(String(value || '').replace(/[,،\s]/g, '')) || 0;
    }

    function formatCommas(value, decimals) {
        const n = parseMoney(value);
        return n.toLocaleString('en-US', {
            minimumFractionDigits: decimals ?? 0,
            maximumFractionDigits: decimals ?? 0,
        });
    }

    function currentFee() {
        return document.getElementById('pricingType')?.value === 'accessory' ? ACCESSORY_FEE : PRODUCT_FEE;
    }

    function formatSyp(aed) {
        return Math.round(Math.round(parseMoney(aed) * RATE) + currentFee()).toLocaleString('en-US') + ' ل.س';
    }

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[ch]));
    }

    function refreshSyp(scope) {
        (scope || document).querySelectorAll('.price-aed').forEach((input) => {
            if (input.classList.contains('color-price-field')) {
                const preview = input.closest('.color-card')?.querySelector('.color-price-syp');
                if (preview) preview.textContent = input.value ? formatSyp(input.value) : '—';
                return;
            }
            const cell = input.closest('tr')?.querySelector('.price-syp');
            if (cell) cell.textContent = formatSyp(input.value);
        });
        const hero = document.getElementById('heroCustomerPrice');
        const formula = document.getElementById('heroFormula');
        const base = document.querySelector('input[name="price"]');
        if (hero && base) hero.textContent = 'للزبون ' + formatSyp(base.value);
        if (formula && base) {
            formula.textContent = formatCommas(base.value, 2) + ' × ' + formatCommas(RATE, 0) + ' + ' + formatCommas(currentFee(), 0);
        }
    }

    function markDirty(card) {
        card?.querySelector('.color-dirty-badge')?.classList.remove('d-none');
    }

    function markClean(card) {
        card?.querySelector('.color-dirty-badge')?.classList.add('d-none');
    }

    function updateColorHeader(card) {
        const name = card.querySelector('.color-name-field')?.value?.trim() || 'لون جديد';
        const hex = card.querySelector('.color-hex-field')?.value || '#cccccc';
        const images = card.querySelectorAll('.gallery-item').length;
        const sizes = card.querySelectorAll('tbody tr').length;
        card.querySelector('.color-head-name').textContent = name;
        card.querySelector('.color-swatch-sm').style.background = hex;
        card.querySelector('.color-head-images').textContent = images + ' صور';
        card.querySelector('.color-head-sizes').textContent = sizes + ' مقاس';
    }

    function sizeRowHtml(data = {}) {
        const price = data.price === undefined || data.price === '' ? '' : formatCommas(data.price, 2);
        const syp = price ? formatSyp(price) : '—';
        return `<tr>
            <td>
                <input type="hidden" class="size-id-field" value="${esc(data.id || '')}">
                <input type="text" class="form-control form-control-sm size-name-field" value="${esc(data.size_name || '')}" list="knownSizesList" placeholder="XS / 0-3M" required>
                <input type="hidden" class="size-key-field" value="${esc(data.size_key || '')}">
            </td>
            <td><input type="text" inputmode="decimal" dir="ltr" class="form-control form-control-sm price-aed money-commas" value="${esc(price)}" required></td>
            <td class="small text-success price-syp">${esc(data.price_syp || syp)}</td>
            <td><input type="text" class="form-control form-control-sm size-sku-field" dir="ltr" value="${esc(data.sku || '')}"></td>
            <td><input type="number" min="0" class="form-control form-control-sm size-stock-field" value="${esc(data.stock_qty ?? 10)}" required></td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-primary copy-size" title="نسخ هذا المقاس لسطر جديد">نسخ</button>
                    <button type="button" class="btn btn-outline-danger remove-size">حذف</button>
                </div>
            </td>
        </tr>`;
    }

    function readSizeRow(row) {
        return {
            size_name: row.querySelector('.size-name-field')?.value || '',
            size_key: '',
            price: row.querySelector('.price-aed')?.value || '',
            sku: '',
            stock_qty: row.querySelector('.size-stock-field')?.value || 10,
        };
    }

    function addSizeRow(card, data, afterRow) {
        const html = sizeRowHtml(data);
        if (afterRow) {
            afterRow.insertAdjacentHTML('afterend', html);
        } else {
            card.querySelector('tbody').insertAdjacentHTML('beforeend', html);
        }
        refreshSyp(card);
        updateColorHeader(card);
        markDirty(card);
    }

    function copySize(row) {
        const card = row.closest('.color-card');
        const data = readSizeRow(row);
        data.size_name = nextSizeName(existingSizeNames(card));
        data.id = '';
        data.sku = '';
        addSizeRow(card, data, row);
    }

    function galleryItemHtml(image, pending) {
        return `<div class="gallery-item ${pending ? 'is-pending' : ''}" ${image.id ? `data-image-id="${esc(image.id)}"` : ''} ${pending ? `data-pending-index="${image.index}"` : ''}>
            <img src="${esc(image.url)}" alt="">
            <button type="button" class="gallery-remove" title="حذف الصورة">×</button>
        </div>`;
    }

    function addPendingFiles(card, fileList) {
        card._pending ??= [];
        Array.from(fileList || []).forEach((file) => {
            if (!file.type.startsWith('image/')) return;
            if (file.size > 5 * 1024 * 1024) {
                setStatus(card, 'الصورة أكبر من 5MB: ' + file.name, false);
                return;
            }
            const index = card._pending.length;
            card._pending.push(file);
            card.querySelector('.gallery-grid').insertAdjacentHTML('beforeend', galleryItemHtml({
                url: URL.createObjectURL(file),
                index,
            }, true));
        });
        updateColorHeader(card);
        markDirty(card);
    }

    function setStatus(card, message, ok) {
        const el = card.querySelector('.color-save-status');
        if (!el) return;
        el.textContent = message;
        el.classList.toggle('text-success', !!ok);
        el.classList.toggle('text-danger', ok === false);
        el.classList.toggle('text-muted', ok == null);
    }

    function hydrateColor(card, color) {
        if (!color) return;
        card.dataset.colorKey = color.key || '';
        const original = card.querySelector('.color-original-key');
        const keyInput = card.querySelector('.color-key-field');
        const nameInput = card.querySelector('.color-name-field');
        const hexInput = card.querySelector('.color-hex-field');
        if (original) original.value = color.key || '';
        if (keyInput) {
            keyInput.value = color.key || '';
            keyInput.dataset.locked = '1';
        }
        if (nameInput) nameInput.value = color.name || '';
        if (hexInput && color.hex) hexInput.value = color.hex;
        card._pending = [];
        card._deleteImages = [];
        const grid = card.querySelector('.gallery-grid');
        grid.innerHTML = (color.images || []).map((image) => galleryItemHtml(image, false)).join('');
        const tbody = card.querySelector('tbody');
        tbody.innerHTML = (color.sizes || []).map((size) => sizeRowHtml(size)).join('');
        const firstSize = (color.sizes || [])[0];
        const colorPrice = card.querySelector('.color-price-field');
        const colorStock = card.querySelector('.color-stock-field');
        if (colorPrice && firstSize?.price) colorPrice.value = formatCommas(firstSize.price, 2);
        if (colorStock && firstSize?.stock_qty != null) colorStock.value = firstSize.stock_qty;
        refreshSyp(card);
        updateColorHeader(card);
        markClean(card);
        if (color.images?.[0]?.url) {
            const hero = document.getElementById('heroPreview');
            if (hero) hero.src = color.images[0].url;
        }
    }

    function initCard(card) {
        if (card._ready) return;
        card._ready = true;
        card._pending = [];
        card._deleteImages = [];
        updateColorHeader(card);
    }

    function serializeColor(card) {
        const fd = new FormData();
        const name = card.querySelector('.color-name-field')?.value?.trim() || '';
        const key = card.querySelector('.color-key-field')?.value?.trim() || latinSlug(name);
        fd.append('name', name);
        fd.append('key', key);
        fd.append('original_key', card.querySelector('.color-original-key')?.value || card.dataset.colorKey || '');
        fd.append('hex', card.querySelector('.color-hex-field')?.value || '');
        let index = 0;
        card.querySelectorAll('tbody tr').forEach((row) => {
            const sizeName = row.querySelector('.size-name-field')?.value?.trim();
            if (!sizeName) return;
            fd.append(`sizes[${index}][id]`, row.querySelector('.size-id-field')?.value || '');
            fd.append(`sizes[${index}][size_name]`, sizeName);
            fd.append(`sizes[${index}][size_key]`, latinSlug(sizeName) || 'os');
            fd.append(`sizes[${index}][price]`, String(row.querySelector('.price-aed')?.value || '').replace(/[,،\s]/g, ''));
            fd.append(`sizes[${index}][sku]`, row.querySelector('.size-sku-field')?.value || '');
            fd.append(`sizes[${index}][stock_qty]`, row.querySelector('.size-stock-field')?.value || '0');
            index += 1;
        });
        if (index === 0) {
            const price = String(card.querySelector('.color-price-field')?.value || '').replace(/[,،\s]/g, '');
            fd.append('sizes[0][size_name]', 'مقاس واحد');
            fd.append('sizes[0][size_key]', 'os');
            fd.append('sizes[0][price]', price);
            fd.append('sizes[0][stock_qty]', card.querySelector('.color-stock-field')?.value || '10');
        }
        (card._pending || []).forEach((file) => fd.append('images[]', file));
        (card._deleteImages || []).forEach((id) => fd.append('delete_images[]', id));
        return fd;
    }

    async function saveColor(card) {
        const name = card.querySelector('.color-name-field')?.value?.trim();
        if (!name) {
            setStatus(card, 'اكتبي اسم اللون أولاً', false);
            return;
        }
        const hasSizes = [...card.querySelectorAll('.size-name-field')].some((input) => input.value.trim());
        const colorPrice = parseMoney(card.querySelector('.color-price-field')?.value);
        if (!hasSizes && colorPrice <= 0) {
            setStatus(card, 'حطي سعر هذا اللون بالدرهم', false);
            card.querySelector('.color-price-field')?.focus();
            return;
        }
        const btn = card.querySelector('.save-color-btn');
        btn.disabled = true;
        setStatus(card, 'عم ينحفظ…', null);
        try {
            const response = await fetch(COLOR_SAVE_URL, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: serializeColor(card),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const firstError = payload.errors ? Object.values(payload.errors)[0]?.[0] : payload.message;
                throw new Error(firstError || 'ما انحفظ اللون');
            }
            hydrateColor(card, payload.color);
            setStatus(card, payload.message || 'تم الحفظ', true);
        } catch (error) {
            setStatus(card, error.message || 'فشل الحفظ', false);
        } finally {
            btn.disabled = false;
        }
    }

    async function deleteColor(card) {
        const key = card.querySelector('.color-original-key')?.value || card.dataset.colorKey;
        if (!key) {
            card.remove();
            return;
        }
        const fd = new FormData();
        fd.append('key', key);
        fd.append('_method', 'DELETE');
        const response = await fetch(COLOR_DELETE_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: fd,
        });
        if (!response.ok) {
            setStatus(card, 'ما انحذف اللون', false);
            return;
        }
        card.remove();
    }

    document.querySelectorAll('.color-card').forEach(initCard);

    document.getElementById('addColorBtn').addEventListener('click', () => {
        document.getElementById('noColorsHint')?.remove();
        const html = document.getElementById('colorCardTpl').innerHTML.replaceAll('__C__', String(colorCount));
        const wrap = document.createElement('div');
        wrap.innerHTML = html;
        const card = wrap.querySelector('.color-card');
        document.getElementById('colorList').appendChild(card);
        initCard(card);
        addSizeRow(card, newSizePayload(card));
        colorCount += 1;
        const collapse = card.querySelector('.accordion-collapse');
        if (window.bootstrap?.Collapse) {
            window.bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false }).show();
        } else {
            collapse.classList.add('show');
        }
        card.querySelector('.color-name-field')?.focus();
    });

    document.getElementById('colorList').addEventListener('click', (e) => {
        const card = e.target.closest('.color-card');
        if (e.target.classList.contains('add-size-btn')) {
            addSizeRow(card, newSizePayload(card));
        }
        if (e.target.classList.contains('fill-sizes-btn')) {
            fillKnownSizes(card);
        }
        if (e.target.classList.contains('copy-size')) {
            copySize(e.target.closest('tr'));
        }
        if (e.target.classList.contains('remove-size')) {
            e.target.closest('tr').remove();
            updateColorHeader(card);
            markDirty(card);
        }
        if (e.target.classList.contains('remove-color')) {
            if (!confirm('حذف هذا اللون مع صوره ومقاساته؟')) return;
            deleteColor(card);
        }
        if (e.target.classList.contains('save-color-btn')) {
            saveColor(card);
        }
        if (e.target.classList.contains('gallery-remove')) {
            const item = e.target.closest('.gallery-item');
            if (item.dataset.imageId) {
                card._deleteImages.push(item.dataset.imageId);
            }
            const pendingIndex = item.dataset.pendingIndex;
            item.remove();
            if (pendingIndex !== undefined) {
                card._pending.splice(Number(pendingIndex), 1);
                card.querySelectorAll('.gallery-item.is-pending').forEach((el, i) => {
                    el.dataset.pendingIndex = String(i);
                });
            }
            updateColorHeader(card);
            markDirty(card);
        }
        if (e.target.matches('.gallery-item img')) {
            const hero = document.getElementById('heroPreview');
            if (hero) hero.src = e.target.getAttribute('src');
        }
    });

    document.getElementById('colorList').addEventListener('change', (e) => {
        const card = e.target.closest('.color-card');
        if (!card) return;
        if (e.target.classList.contains('color-image-input')) {
            addPendingFiles(card, e.target.files);
            e.target.value = '';
        }
    });

    document.getElementById('colorList').addEventListener('input', (e) => {
        const card = e.target.closest('.color-card');
        if (!card) return;
        if (e.target.classList.contains('color-name-field')) {
            const keyInput = card.querySelector('.color-key-field');
            if (keyInput && !keyInput.dataset.locked) {
                const slug = latinSlug(e.target.value);
                if (slug) keyInput.value = slug;
            }
            updateColorHeader(card);
        }
        if (e.target.classList.contains('color-hex-field')) updateColorHeader(card);
        if (e.target.classList.contains('price-aed')) {
            refreshSyp(card);
            markDirty(card);
        }
        if (e.target.classList.contains('size-name-field')) {
            const key = e.target.closest('tr')?.querySelector('.size-key-field');
            const slug = latinSlug(e.target.value);
            if (key && slug) key.value = slug;
        }
        markDirty(card);
    });

    document.getElementById('colorList').addEventListener('dragover', (e) => {
        if (!e.target.closest('.image-drop')) return;
        e.preventDefault();
        e.target.closest('.image-drop').classList.add('is-drag');
    });
    document.getElementById('colorList').addEventListener('dragleave', (e) => {
        e.target.closest('.image-drop')?.classList.remove('is-drag');
    });
    document.getElementById('colorList').addEventListener('drop', (e) => {
        const drop = e.target.closest('.image-drop');
        if (!drop) return;
        e.preventDefault();
        drop.classList.remove('is-drag');
        addPendingFiles(drop.closest('.color-card'), e.dataTransfer.files);
    });

    document.getElementById('productCatalogForm').addEventListener('submit', () => {
        document.querySelectorAll('#productCatalogForm .money-commas').forEach((input) => {
            input.value = String(input.value || '').replace(/[,،\s]/g, '');
        });
        reindexSpecs();
        reindexChartRows();
    });

    function specRowHtml(label = '', value = '') {
        return `<tr>
            <td><input type="text" class="form-control form-control-sm spec-label" value="${esc(label)}" placeholder="مثلاً: خط العنق"></td>
            <td><input type="text" class="form-control form-control-sm spec-value" value="${esc(value)}" placeholder="مثلاً: الأشرطة السباغيتي"></td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-primary copy-spec">نسخ</button>
                    <button type="button" class="btn btn-outline-danger remove-spec">حذف</button>
                </div>
            </td>
        </tr>`;
    }

    function reindexSpecs() {
        document.querySelectorAll('#specsTable tbody tr').forEach((row, i) => {
            const label = row.querySelector('.spec-label');
            const value = row.querySelector('.spec-value');
            if (label) label.name = `specs[${i}][label]`;
            if (value) value.name = `specs[${i}][value]`;
        });
    }

    function chartColCount() {
        return document.querySelectorAll('#sizeChartTable thead .chart-col').length;
    }

    function chartRowHtml(values = []) {
        const cols = chartColCount();
        let cells = '';
        for (let i = 0; i < cols; i++) {
            cells += `<td><input type="text" class="form-control form-control-sm" dir="ltr" value="${esc(values[i] || '')}"></td>`;
        }
        return `<tr>${cells}<td>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-primary copy-chart-row">نسخ</button>
                <button type="button" class="btn btn-outline-danger remove-chart-row">حذف</button>
            </div>
        </td></tr>`;
    }

    function reindexChartRows() {
        document.querySelectorAll('#sizeChartTable tbody tr').forEach((row, r) => {
            row.querySelectorAll('td input').forEach((input) => {
                input.name = `size_chart[rows][${r}][]`;
            });
        });
    }

    function readChartRow(row) {
        return [...row.querySelectorAll('td input')].map((input) => input.value);
    }

    document.getElementById('addSpecBtn')?.addEventListener('click', () => {
        const tbody = document.querySelector('#specsTable tbody');
        const last = tbody.querySelector('tr:last-child');
        tbody.insertAdjacentHTML('beforeend', specRowHtml(
            last?.querySelector('.spec-label')?.value || '',
            last?.querySelector('.spec-value')?.value || ''
        ));
        reindexSpecs();
    });

    document.getElementById('specsTable')?.addEventListener('click', (e) => {
        if (e.target.classList.contains('copy-spec')) {
            const row = e.target.closest('tr');
            row.insertAdjacentHTML('afterend', specRowHtml(
                row.querySelector('.spec-label')?.value || '',
                row.querySelector('.spec-value')?.value || ''
            ));
            reindexSpecs();
        }
        if (e.target.classList.contains('remove-spec')) {
            const tbody = e.target.closest('tbody');
            e.target.closest('tr').remove();
            if (!tbody.querySelector('tr')) tbody.insertAdjacentHTML('beforeend', specRowHtml());
            reindexSpecs();
        }
    });

    function existingChartSizes() {
        return [...document.querySelectorAll('#sizeChartTable tbody tr')].map((row) => row.querySelector('td input')?.value || '');
    }

    document.getElementById('addChartRowBtn')?.addEventListener('click', () => {
        const tbody = document.querySelector('#sizeChartTable tbody');
        const last = tbody.querySelector('tr:last-child');
        const values = last ? readChartRow(last) : [];
        values[0] = nextSizeName(existingChartSizes());
        tbody.insertAdjacentHTML('beforeend', chartRowHtml(values));
        reindexChartRows();
    });

    document.getElementById('fillChartSizesBtn')?.addEventListener('click', () => {
        const tbody = document.querySelector('#sizeChartTable tbody');
        const last = tbody.querySelector('tr:last-child');
        const base = last ? readChartRow(last) : [];
        const existing = existingChartSizes().map((name) => name.trim().toUpperCase());
        currentSizeScale().sizes.forEach((size) => {
            if (existing.includes(size.toUpperCase())) return;
            const values = [...base];
            values[0] = size;
            tbody.insertAdjacentHTML('beforeend', chartRowHtml(values));
            existing.push(size.toUpperCase());
        });
        reindexChartRows();
    });

    document.getElementById('addChartColBtn')?.addEventListener('click', () => {
        const table = document.getElementById('sizeChartTable');
        const actionTh = table.querySelector('thead th:last-child');
        actionTh.insertAdjacentHTML('beforebegin', `<th>
            <div class="d-flex gap-1">
                <input type="text" name="size_chart[columns][]" class="form-control form-control-sm chart-col" placeholder="عمود">
                <button type="button" class="btn btn-sm btn-outline-danger remove-chart-col">×</button>
            </div>
        </th>`);
        table.querySelectorAll('tbody tr').forEach((row) => {
            const actionTd = row.querySelector('td:last-child');
            actionTd.insertAdjacentHTML('beforebegin', '<td><input type="text" class="form-control form-control-sm" dir="ltr"></td>');
        });
        reindexChartRows();
    });

    document.getElementById('sizeChartTable')?.addEventListener('click', (e) => {
        if (e.target.classList.contains('copy-chart-row')) {
            const row = e.target.closest('tr');
            const values = readChartRow(row);
            values[0] = nextSizeName(existingChartSizes());
            row.insertAdjacentHTML('afterend', chartRowHtml(values));
            reindexChartRows();
        }
        if (e.target.classList.contains('remove-chart-row')) {
            e.target.closest('tr').remove();
            reindexChartRows();
        }
        if (e.target.classList.contains('remove-chart-col')) {
            const th = e.target.closest('th');
            const index = [...th.parentNode.children].indexOf(th);
            th.remove();
            document.querySelectorAll('#sizeChartTable tbody tr').forEach((row) => {
                row.children[index]?.remove();
            });
            reindexChartRows();
        }
    });

    document.getElementById('pricingType')?.addEventListener('change', () => refreshSyp());
    document.getElementById('productCategory')?.addEventListener('change', refreshSizeScaleUi);
    refreshSizeScaleUi();

    document.addEventListener('blur', (e) => {
        if (!e.target.classList?.contains('money-commas') || !e.target.value) return;
        const decimals = (e.target.classList.contains('price-aed') || e.target.name === 'price' || e.target.name === 'sale_price') ? 2 : 0;
        e.target.value = formatCommas(e.target.value, decimals);
        refreshSyp(e.target.closest('.color-card') || document);
    }, true);
    </script>
@endsection
