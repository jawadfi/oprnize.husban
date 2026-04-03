<x-filament-panels::page>
@php
    $employee   = $this->getEmployee();
    $stats      = $this->getLeaveStats();
    $passport   = $this->getPassportStatus();
    $visa       = $this->getVisaStatus();
    $usedPct    = $stats['entitlement'] > 0
                    ? round(($stats['used'] / $stats['entitlement']) * 100)
                    : 0;
@endphp

<style>
    .profile-grid    { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px; }
    .profile-card    { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; }
    .profile-card h3 { font-size: 15px; font-weight: 700; color: #2b6cb0; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 8px; }
    .info-row        { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f7fafc; font-size: 14px; }
    .info-row:last-child { border-bottom: none; }
    .info-label      { color: #718096; font-size: 13px; }
    .info-value      { font-weight: 600; color: #1a202c; text-align: left; direction: ltr; }
    .status-pill     { display: inline-block; border-radius: 20px; padding: 2px 12px; font-size: 12px; font-weight: 700; }

    /* Balance card */
    .balance-card    { background: linear-gradient(135deg, #1a365d 0%, #2b6cb0 100%); color: white; border-radius: 14px; padding: 28px; margin-bottom: 24px; }
    .balance-grid    { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 20px; margin-top: 20px; }
    .balance-item    { background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2); border-radius: 12px; padding: 18px; text-align: center; }
    .balance-big     { font-size: 36px; font-weight: 800; line-height: 1; margin-bottom: 6px; }
    .balance-label   { font-size: 12px; opacity: .8; }
    .progress-bar    { background: rgba(255,255,255,.2); border-radius: 10px; height: 10px; margin-top: 20px; overflow: hidden; }
    .progress-fill   { background: #68d391; height: 100%; border-radius: 10px; transition: width .5s; }
    .progress-labels { display: flex; justify-content: space-between; font-size: 12px; opacity: .8; margin-top: 6px; }

    /* Alert */
    .alert { border-radius: 10px; padding: 12px 16px; font-size: 14px; margin-top: 16px; }

    /* Leave history mini */
    .leave-row       { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f7fafc; font-size: 13px; }
    .leave-row:last-child { border-bottom: none; }
</style>

{{-- ═══════════════════════════════════════════
     رصيد الإجازات (البطاقة الرئيسية)
═══════════════════════════════════════════ --}}
<div class="balance-card">
    <div style="font-size:22px; font-weight:800;">📊 رصيد الإجازات السنوية / Annual Leave Balance</div>
    <div style="font-size:14px; opacity:.8; margin-top:4px;">{{ now()->year }} — مبني على العقد</div>

    <div class="balance-grid">
        <div class="balance-item">
            <div class="balance-big">{{ $stats['entitlement'] }}</div>
            <div class="balance-label">رصيد التعاقد / Contract Entitlement</div>
        </div>
        <div class="balance-item" style="background:rgba(104,211,145,.15); border-color:rgba(104,211,145,.3);">
            <div class="balance-big" style="color:#68d391;">{{ $stats['remaining'] }}</div>
            <div class="balance-label">المتبقي / Remaining Days</div>
        </div>
        <div class="balance-item" style="background:rgba(252,129,74,.15); border-color:rgba(252,129,74,.3);">
            <div class="balance-big" style="color:#fc814a;">{{ $stats['used'] }}</div>
            <div class="balance-label">المُستخدم هذا العام / Used This Year</div>
        </div>
        @if($stats['pending_count'] > 0)
        <div class="balance-item" style="background:rgba(246,173,85,.15); border-color:rgba(246,173,85,.3);">
            <div class="balance-big" style="color:#f6ad55;">{{ $stats['pending_count'] }}</div>
            <div class="balance-label">طلبات قيد المراجعة / Pending Requests</div>
        </div>
        @endif
    </div>

    {{-- Progress bar --}}
    <div style="margin-top:20px;">
        <div class="progress-bar">
            <div class="progress-fill" style="width:{{ min(100, $usedPct) }}%;"></div>
        </div>
        <div class="progress-labels">
            <span>0</span>
            <span>{{ $usedPct }}% مُستخدم / used</span>
            <span>{{ $stats['entitlement'] }}</span>
        </div>
    </div>
</div>

<div class="profile-grid">

    {{-- ═══════════════════════════════
         البيانات الشخصية
    ═══════════════════════════════ --}}
    <div class="profile-card">
        <h3>👤 البيانات الشخصية / Personal Info</h3>

        <div class="info-row">
            <span class="info-label">الاسم / Name</span>
            <span class="info-value">{{ $employee->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">المسمى الوظيفي / Job Title</span>
            <span class="info-value">{{ $employee->job_title ?? '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">القسم / Department</span>
            <span class="info-value">{{ $employee->department ?? '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">الموقع / Location</span>
            <span class="info-value">{{ $employee->location ?? '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">الجنسية / Nationality</span>
            <span class="info-value">{{ $employee->nationality ?? '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">رقم الموظف / Employee ID</span>
            <span class="info-value">{{ $employee->emp_id ?? '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">رقم الإقامة / Iqama No</span>
            <span class="info-value">{{ $employee->iqama_no ?? '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">رقم الهوية / Identity No</span>
            <span class="info-value">{{ $employee->identity_number ?? '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">تاريخ التوظيف / Hire Date</span>
            <span class="info-value">
                {{ $employee->hire_date ? \Carbon\Carbon::parse($employee->hire_date)->format('d/m/Y') : '—' }}
            </span>
        </div>
    </div>

    {{-- ═══════════════════════════════
         الجواز والتأشيرة
    ═══════════════════════════════ --}}
    <div class="profile-card">
        <h3>🛂 الجواز والتأشيرة / Passport & Visa</h3>

        {{-- Passport --}}
        <div class="info-row">
            <span class="info-label">انتهاء الجواز / Passport Expiry</span>
            <span class="info-value">
                {{ $employee->passport_expiry ? $employee->passport_expiry->format('d/m/Y') : '—' }}
            </span>
        </div>
        <div>
            <span class="status-pill" style="color:{{ $passport['color'] }};background:{{ $passport['bg'] }};border:1px solid {{ $passport['border'] }};">
                {{ $passport['label'] }}
            </span>
        </div>

        <div style="margin-top:16px; border-top:1px solid #e2e8f0; padding-top:16px;">
            <div class="info-row">
                <span class="info-label">انتهاء التأشيرة / Visa Expiry</span>
                <span class="info-value">
                    {{ $employee->visa_expiry ? $employee->visa_expiry->format('d/m/Y') : '—' }}
                </span>
            </div>
            <div>
                <span class="status-pill" style="color:{{ $visa['color'] }};background:{{ $visa['bg'] }};border:1px solid {{ $visa['border'] }};">
                    {{ $visa['label'] }}
                </span>
            </div>
        </div>

        @if($passport['status'] === 'missing' || $passport['status'] === 'expired')
        <div class="alert" style="background:#fff5f5;border:1px solid #fc8181;color:#c53030;margin-top:16px;">
            🚫 <strong>تنبيه:</strong> لا يمكن تقديم إجازة سنوية بدون جواز سارٍ. تواصل مع HR الشركة المؤجرة لتحديث بياناتك.
        </div>
        @endif

        @if($passport['status'] === 'expiring')
        <div class="alert" style="background:#fffbeb;border:1px solid #f6ad55;color:#b7791f;margin-top:16px;">
            ⚠️ جوازك سينتهي قريباً — يُنصح بالتجديد قبل التقديم على إجازة سنوية تتجاوز 6 أشهر قبل انتهاء صلاحيته.
        </div>
        @endif
    </div>

    {{-- ═══════════════════════════════
         رصيد التعاقد السنوي
    ═══════════════════════════════ --}}
    <div class="profile-card">
        <h3>📋 رصيد التعاقد السنوي / Annual Contract Balance</h3>

        <div class="info-row">
            <span class="info-label">الرصيد التعاقدي / Contract Entitlement</span>
            <span class="info-value" style="font-size:20px;color:#2b6cb0;">{{ $stats['entitlement'] }} <small style="font-size:13px;color:#718096;">يوم / day</small></span>
        </div>
        <div class="info-row">
            <span class="info-label">المتبقي / Remaining</span>
            <span class="info-value" style="font-size:20px;color:#276749;">{{ $stats['remaining'] }} <small style="font-size:13px;color:#718096;">يوم / day</small></span>
        </div>
        <div class="info-row">
            <span class="info-label">المُستخدم {{ now()->year }} / Used This Year</span>
            <span class="info-value" style="font-size:20px;color:#c05621;">{{ $stats['used'] }} <small style="font-size:13px;color:#718096;">يوم / day</small></span>
        </div>

        <div style="margin-top:14px; background:#f7fafc; border-radius:8px; padding:14px; font-size:13px; color:#4a5568; line-height:1.8;">
            <strong>ملاحظة:</strong><br>
            — <strong>رصيد التعاقد</strong>: العدد المحدد في عقدك (يحدده HR الشركة المؤجرة)<br>
            — <strong>الرصيد المتبقي</strong>: يُخصم تلقائياً عند الاعتماد النهائي للإجازة السنوية<br>
            — الإجازات الأخرى (مرضية، وفاة، مولود) لا تؤثر في الرصيد
        </div>
    </div>

    {{-- ═══════════════════════════════
         الشركة والفرع
    ═══════════════════════════════ --}}
    <div class="profile-card">
        <h3>🏢 بيانات التوظيف / Employment</h3>

        <div class="info-row">
            <span class="info-label">الشركة المؤجرة / Provider</span>
            <span class="info-value">{{ $employee->company->name ?? '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">الشركة المستأجرة / Client</span>
            <span class="info-value">{{ $employee->currentCompanyAssigned->name ?? '—' }}</span>
        </div>
        @php $branch = $employee->activeBranch(); @endphp
        <div class="info-row">
            <span class="info-label">الفرع الحالي / Current Branch</span>
            <span class="info-value">{{ $branch->name ?? '—' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">البريد الإلكتروني / Email</span>
            <span class="info-value" style="direction:ltr;">{{ $employee->email ?? '—' }}</span>
        </div>
    </div>

</div>

{{-- ═══════════════════════════════════════════
     آخر طلبات الإجازة
═══════════════════════════════════════════ --}}
<div class="profile-card">
    <h3>📅 آخر طلبات الإجازة / Recent Leave Requests</h3>

    @php
        $recentLeaves = $employee->leaveRequests()->with([])->latest()->take(5)->get();
    @endphp

    @if($recentLeaves->isEmpty())
        <p style="color:#718096;font-size:14px;">لا توجد طلبات سابقة / No leave requests yet.</p>
    @else
        @foreach($recentLeaves as $leave)
        @php
            $statusColors = [
                'approved'                    => ['bg'=>'#f0fff4','color'=>'#276749','border'=>'#9ae6b4'],
                'rejected'                    => ['bg'=>'#fff5f5','color'=>'#c53030','border'=>'#fc8181'],
                'pending_supervisor_approval' => ['bg'=>'#faf5ff','color'=>'#553c9a','border'=>'#d6bcfa'],
                'pending_client_approval'     => ['bg'=>'#ebf8ff','color'=>'#2b6cb0','border'=>'#90cdf4'],
                'pending_provider_approval'   => ['bg'=>'#e6fffa','color'=>'#2c7a7b','border'=>'#81e6d9'],
                'pending'                     => ['bg'=>'#fefcbf','color'=>'#744210','border'=>'#f6e05e'],
            ];
            $sc = $statusColors[$leave->status->value] ?? ['bg'=>'#f7fafc','color'=>'#4a5568','border'=>'#e2e8f0'];
        @endphp
        <div class="leave-row">
            <div>
                <strong style="font-size:14px;">{{ \App\Enums\LeaveType::getTranslatedKey($leave->leave_type->value) }}</strong>
                <div style="color:#718096;font-size:12px;">
                    {{ $leave->start_date->format('d/m/Y') }} — {{ $leave->end_date->format('d/m/Y') }}
                    ({{ $leave->days_count }} يوم)
                </div>
            </div>
            <span class="status-pill" style="color:{{ $sc['color'] }};background:{{ $sc['bg'] }};border:1px solid {{ $sc['border'] }};">
                {{ \App\Enums\LeaveRequestStatus::getTranslatedKey($leave->status->value) }}
            </span>
        </div>
        @endforeach
    @endif
</div>

</x-filament-panels::page>
