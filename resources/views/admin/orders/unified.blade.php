@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4" dir="rtl">
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 mb-1">كل الطلبات</h1>
                <p class="text-muted mb-0">جدول واحد — وصلة / SHEIN / Temu / …</p>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body py-3">
                <form method="get" class="d-flex gap-2 align-items-center flex-wrap">
                    <label class="mb-0">المصدر:</label>
                    <select name="source" class="form-select w-auto" onchange="this.form.submit()">
                        <option value="">الكل</option>
                        <option value="wasla" @selected($source === 'wasla')>وصلة</option>
                        @foreach($platforms as $p)
                            <option value="{{ $p->slug }}" @selected($source === $p->slug)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <label class="mb-0">الحالة:</label>
                    <input type="text" name="status" value="{{ $status }}" class="form-control w-auto" placeholder="مثل pending / quoted">
                    <button class="btn btn-outline-primary">فلتر</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>العميل</th>
                            <th>المصدر</th>
                            <th>الحالة</th>
                            <th>المبلغ</th>
                            <th>التاريخ</th>
                            <th class="text-end">إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>#{{ $row['id'] }}</td>
                                <td>
                                    <div>{{ $row['customer_name'] ?? '—' }}</div>
                                    <small class="text-muted">{{ $row['customer_phone'] }}</small>
                                </td>
                                <td>
                                    @if($row['source_key'] === 'wasla')
                                        <span class="badge text-bg-success">وصلة</span>
                                    @else
                                        <span class="badge text-bg-dark">{{ $row['source'] }}</span>
                                    @endif
                                </td>
                                <td><span class="badge text-bg-info">{{ $row['status_label'] }}</span></td>
                                <td>{{ $row['amount_label'] }}</td>
                                <td class="small text-muted">{{ $row['created_at'] }}</td>
                                <td class="text-end">
                                    <a href="{{ $row['admin_url'] }}" class="btn btn-sm btn-outline-primary">عرض</a>
                                    @if(!empty($row['track_url']))
                                        <a href="{{ $row['track_url'] }}" target="_blank" class="btn btn-sm btn-outline-secondary">تتبع</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد طلبات.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
