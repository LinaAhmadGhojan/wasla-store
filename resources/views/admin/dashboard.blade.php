@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 mb-1">لوحة التحكم</h1>
                <p class="text-muted mb-0">وصلة — وساطة تسوق وتوصيل (شراء بالدرهم · عرض بالليرة)</p>
            </div>
            <a href="{{ route('admin.exchange-rate.edit') }}" class="btn btn-primary">
                1 د.إ = {{ number_format($exchangeRate) }} ل.س
                · منتج +{{ number_format($productFee, 0) }} ل.س
                · إكسسوار +{{ number_format($accessoryFee, 0) }} ل.س
            </a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 border-warning border-opacity-50 h-100">
                    <div class="card-body">
                        <span class="text-uppercase text-muted small">طلبات جديدة</span>
                        <h2 class="mt-2 mb-1">{{ $prNew }}</h2>
                        <a href="{{ route('admin.purchase-requests.index', ['status' => 'pending']) }}" class="small">عرض الطلبات →</a>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 border-info border-opacity-50 h-100">
                    <div class="card-body">
                        <span class="text-uppercase text-muted small">بانتظار الدفع</span>
                        <h2 class="mt-2 mb-1">{{ $prAwaitingPayment }}</h2>
                        <a href="{{ route('admin.purchase-requests.index', ['status' => 'quoted']) }}" class="small">عروض الأسعار →</a>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 border-primary border-opacity-50 h-100">
                    <div class="card-body">
                        <span class="text-uppercase text-muted small">قيد الشراء</span>
                        <h2 class="mt-2 mb-1">{{ $prPurchasing }}</h2>
                        <a href="{{ route('admin.purchase-requests.index', ['status' => 'paid']) }}" class="small">متابعة →</a>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card shadow-sm border-0 border-success border-opacity-50 h-100">
                    <div class="card-body">
                        <span class="text-uppercase text-muted small">جاهز / وصل</span>
                        <h2 class="mt-2 mb-1">{{ $prReady }}</h2>
                        <a href="{{ route('admin.purchase-requests.index', ['status' => 'received']) }}" class="small">التسليم →</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h4 class="h6 mb-1">كتالوج المنتجات — ألوان وصور وأسعار</h4>
                        <p class="text-muted small mb-0">نفس التفاصيل الظاهرة للزبون. اضغطي للتعديل.</p>
                    </div>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-primary">كل المنتجات</a>
                </div>
                <div class="row g-3">
                    @forelse($catalogProducts as $product)
                        @php $groups = $product->colorCatalog(); @endphp
                        <div class="col-md-6 col-xl-3">
                            <a href="{{ route('admin.products.edit', $product) }}" class="text-decoration-none text-reset">
                                <div class="card h-100 border-0 shadow-sm">
                                    @if($product->image)
                                        <img src="{{ $product->image }}" alt="" class="card-img-top" style="height:160px;object-fit:cover">
                                    @endif
                                    <div class="card-body">
                                        <strong class="d-block mb-1">{{ $product->name }}</strong>
                                        <div class="small text-muted mb-1">{{ number_format($product->price, 2) }} د.إ</div>
                                        <div class="small text-success mb-2">{{ number_format($product->price_syp) }} ل.س</div>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($groups as $group)
                                                <span class="badge rounded-pill" style="background:{{ $group['hex'] ?: '#1c7282' }}">
                                                    {{ $group['name'] }}
                                                    · {{ $group['images']->count() }} صور
                                                    · {{ count($group['sizes']) }} مقاس
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @empty
                        <p class="text-muted mb-0">ما في منتجات ظاهرة بعد.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h4 class="card-title h6 text-uppercase text-muted">توزيع الطلبات حسب الحالة</h4>
                        <div class="table-responsive mt-3">
                            <table class="table table-sm mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>الحالة</th>
                                        <th>العدد</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($statusLabels as $key => $label)
                                        <tr>
                                            <td>{{ $label }}</td>
                                            <td>{{ (int) ($prCounts[$key] ?? 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h4 class="card-title h6">إجراءات سريعة</h4>
                        <div class="list-group list-group-flush mt-2">
                            <a href="{{ route('admin.purchase-requests.index') }}" class="list-group-item list-group-item-action">🛍 طلبات الشراء (SHEIN…)</a>
                            <a href="{{ route('admin.procurement-batches.index') }}" class="list-group-item list-group-item-action">📋 دفعات الشراء</a>
                            <a href="{{ route('admin.whatsapp-messages.index') }}" class="list-group-item list-group-item-action">💬 رسائل المجموعة</a>
                            <a href="{{ route('admin.products.index') }}" class="list-group-item list-group-item-action">📦 نشر منتجات واتساب</a>
                            <a href="{{ route('admin.exchange-rate.edit') }}" class="list-group-item list-group-item-action">💱 تحديث سعر الصرف</a>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body py-3">
                                <span class="text-muted small">المنتجات</span>
                                <h3 class="mb-0">{{ $productCount }}</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body py-3">
                                <span class="text-muted small">المستخدمون</span>
                                <h3 class="mb-0">{{ $userCount }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
