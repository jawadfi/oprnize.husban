<x-filament-panels::page>
    <style>
        .entry-tabs { display: flex; gap: 0; border-bottom: 2px solid #e5e7eb; margin-bottom: 1.5rem; }
        .entry-tab {
            padding: 12px 24px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            color: #6b7280;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .entry-tab:hover { color: #076EA7; background: #f0f7ff; }
        .entry-tab.active { color: #076EA7; border-bottom-color: #076EA7; background: #f0f7ff; }
        .entry-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 20px;
            margin-bottom: 16px;
        }
        .dark .entry-card { background: #1f2937; border-color: #374151; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 4px; }
        .form-group label { font-size: 13px; font-weight: 600; color: #374151; }
        .dark .form-group label { color: #d1d5db; }
        .form-group input, .form-group select, .form-group textarea {
            padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px;
            font-size: 14px; background: white; transition: border-color 0.2s;
        }
        .dark .form-group input, .dark .form-group select, .dark .form-group textarea {
            background: #111827; border-color: #4b5563; color: #f9fafb;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none; border-color: #076EA7; box-shadow: 0 0 0 2px rgba(7,110,167,0.1);
        }
        .btn-primary {
            background: #076EA7; color: white; border: none; padding: 10px 24px;
            border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px;
            transition: background 0.2s;
        }
        .btn-primary:hover { background: #065a8c; }
        .btn-danger {
            background: #dc2626; color: white; border: none; padding: 6px 12px;
            border-radius: 6px; font-size: 12px; cursor: pointer;
        }
        .btn-danger:hover { background: #b91c1c; }
        .recurring-toggle {
            display: flex; align-items: center; gap: 10px; margin-top: 8px;
        }
        .recurring-toggle label { font-size: 13px; font-weight: 500; }
        .toggle-switch {
            position: relative; width: 44px; height: 24px; display: inline-block;
        }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: #d1d5db; border-radius: 24px; cursor: pointer; transition: 0.3s;
        }
        .toggle-slider:before {
            content: ""; position: absolute; height: 18px; width: 18px;
            left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: 0.3s;
        }
        .toggle-switch input:checked + .toggle-slider { background: #076EA7; }
        .toggle-switch input:checked + .toggle-slider:before { transform: translateX(20px); }

        /* Existing entries table */
        .entries-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 16px; }
        .entries-table th {
            background: #f3f4f6; padding: 10px; text-align: center;
            font-weight: 600; color: #374151; border-bottom: 2px solid #e5e7eb;
        }
        .dark .entries-table th { background: #374151; color: #d1d5db; border-color: #4b5563; }
        .entries-table td {
            padding: 8px 10px; text-align: center; border-bottom: 1px solid #e5e7eb;
        }
        .dark .entries-table td { border-color: #4b5563; color: #d1d5db; }
        .badge {
            display: inline-block; padding: 2px 10px; border-radius: 12px;
            font-size: 11px; font-weight: 600;
        }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .badge-recurring { background: #e0e7ff; color: #3730a3; }
        .badge-once { background: #f3f4f6; color: #6b7280; }

        /* Timesheet styles */
        .timesheet-container { overflow-x: auto; }
        .timesheet-table { border-collapse: collapse; font-size: 12px; min-width: 100%; }
        .timesheet-table th, .timesheet-table td {
            border: 1px solid #d1d5db; padding: 4px; text-align: center; min-width: 40px;
        }
        .dark .timesheet-table th, .dark .timesheet-table td { border-color: #4b5563; }
        .timesheet-table th {
            background: #f3f4f6; font-weight: 600; position: sticky; top: 0; z-index: 1;
        }
        .dark .timesheet-table th { background: #374151; color: #d1d5db; }
        .timesheet-table th.day-header { min-width: 48px; }
        .timesheet-select {
            width: 100%; padding: 2px; border: none; font-size: 11px;
            text-align: center; background: transparent; cursor: pointer;
            font-weight: 600;
        }
        .dark .timesheet-select { color: #f9fafb; }
        .timesheet-select:focus { outline: 2px solid #076EA7; border-radius: 4px; }
        .status-P { background-color: #ffffff; }
        .status-A { background-color: #fee2e2; }
        .status-L { background-color: #fed7aa; }
        .status-O { background-color: #e9d5ff; }
        .status-X { background-color: #e5e7eb; }
        .dark .status-P { background-color: #1f2937; }
        .dark .status-A { background-color: #7f1d1d; }
        .dark .status-L { background-color: #78350f; }
        .dark .status-O { background-color: #581c87; }
        .dark .status-X { background-color: #374151; }

        /* Summary cards */
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 8px; margin-top: 12px; }
        .summary-card {
            padding: 8px 12px; border-radius: 8px; text-align: center;
            font-size: 12px; font-weight: 600;
        }

        /* Employee search */
        .search-bar {
            display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;
            background: white; padding: 20px; border-radius: 12px;
            border: 1px solid #e5e7eb; margin-bottom: 16px;
        }
        .dark .search-bar { background: #1f2937; border-color: #374151; }
        .employee-info {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 16px; background: #ecfdf5; border-radius: 8px;
            font-weight: 600; color: #065f46; font-size: 14px;
        }
        .dark .employee-info { background: #064e3b; color: #a7f3d0; }
    </style>

    {{-- Tabs - always visible --}}
    <div class="entry-tabs">
        <div class="entry-tab {{ $activeTab === 'overtime' ? 'active' : '' }}"
             wire:click="$set('activeTab', 'overtime')">
            ⏱️ ساعات عمل إضافي / Overtime
        </div>
        <div class="entry-tab {{ $activeTab === 'additions' ? 'active' : '' }}"
             wire:click="$set('activeTab', 'additions')">
            💰 مبالغ إضافية / Additions
        </div>
        <div class="entry-tab {{ $activeTab === 'timesheet' ? 'active' : '' }}"
             wire:click="$set('activeTab', 'timesheet')">
            📅 تايم شيت / Timesheet
        </div>
        @if(!$this->isProvider())
        <div class="entry-tab {{ $activeTab === 'deductions' ? 'active' : '' }}"
             wire:click="$set('activeTab', 'deductions')">
            📉 خصومات / Deductions
        </div>
        @endif
    </div>

    {{-- Salary Data Import (Excel) - Always visible --}}
    <div style="margin-bottom: 16px; display: flex; gap: 12px; align-items: center;">
        <button class="btn-primary" wire:click="toggleSalaryUpload" style="background: {{ $showSalaryUpload ? '#dc2626' : '#059669' }}; padding: 12px 24px;">
            📊 {{ $showSalaryUpload ? 'إلغاء' : 'استيراد بيانات الرواتب / Import Salary Data (Excel)' }}
        </button>
        @if(!$showSalaryUpload)
        <span style="font-size: 12px; color: #6b7280;">
            استورد ملف Excel يحتوي على: Emp.ID, Basic Salary, Housing Allowance, Transportation Allowance, Food Allowance, Other Allowance, Fees
        </span>
        @endif
    </div>

    @if($showSalaryUpload)
    <div class="entry-card" style="border: 2px dashed #059669; background: #ecfdf5; margin-bottom: 16px;">
        <h4 style="margin-bottom: 12px; font-weight: 600; color: #059669;">📊 استيراد بيانات الرواتب من ملف Excel / Import Salary Data from Excel</h4>
        <p style="font-size: 12px; color: #374151; margin-bottom: 12px;">
            <strong>الأعمدة المدعومة:</strong> Emp.ID (أو Nova Emp ID), Basic Salary, Housing Allowance, Transportation Allowance, Food Allowance, Other Allowance, Fees, Hiring Date
        </p>
        <div style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="flex: 0 0 180px;">
                <label>الشهر / Month</label>
                <input type="month" wire:model.live="selectedMonth" value="{{ $selectedMonth }}">
            </div>
            <div class="form-group" style="flex: 1; min-width: 250px;">
                <label>اختر ملف Excel (.xlsx, .xls, .csv)</label>
                <input type="file" wire:model="salaryFile" accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel">
            </div>
            <button class="btn-primary" wire:click="importSalaryData" style="height: 38px; background: #059669;">
                📥 استيراد / Import
            </button>
        </div>
        <div wire:loading wire:target="salaryFile" style="margin-top: 10px; color: #059669; font-size: 13px;">
            ⏳ جاري رفع الملف...
        </div>
        <div wire:loading wire:target="importSalaryData" style="margin-top: 10px; color: #059669; font-size: 13px;">
            ⏳ جاري استيراد البيانات...
        </div>
    </div>
    @endif

    {{-- ===== IMPORT RESULTS PANEL ===== --}}
    @if($showImportResults)
    <div style="margin-bottom: 20px;">

        {{-- Header --}}
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h4 style="font-weight:700; font-size:15px;">
                📋 نتائج الاستيراد / Import Results
                @if(count($importResults) > 0)
                    <span style="background:#d1fae5; color:#065f46; border-radius:12px; padding:2px 10px; font-size:12px; margin-right:8px;">
                        ✅ {{ count($importResults) }} تم استيراده
                    </span>
                @endif
                @if(count($importErrors) > 0)
                    <span style="background:#fee2e2; color:#991b1b; border-radius:12px; padding:2px 10px; font-size:12px; margin-right:8px;">
                        ❌ {{ count($importErrors) }} فشل
                    </span>
                @endif
            </h4>
            <button wire:click="dismissImportResults"
                    style="background:#6b7280; color:white; border:none; padding:6px 16px; border-radius:6px; cursor:pointer; font-size:13px;">
                ✕ إغلاق
            </button>
        </div>

        {{-- SUCCESS TABLE --}}
        @if(count($importResults) > 0)
        <div class="entry-card" style="border:1px solid #6ee7b7; margin-bottom:14px; overflow-x:auto; padding:12px;">
            <h5 style="font-weight:600; color:#065f46; margin-bottom:10px;">✅ السجلات المستوردة / Imported Records</h5>
            <table class="entries-table" style="min-width:700px;">
                <thead>
                    <tr>
                        <th>EmpID</th>
                        <th>الاسم</th>
                        <th>الشهر</th>
                        <th>الراتب الأساسي</th>
                        <th>بدل السكن</th>
                        <th>بدل النقل</th>
                        <th>بدل الطعام</th>
                        <th>بدل آخر</th>
                        <th>الرسوم</th>
                        <th>الإجمالي</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($importResults as $r)
                    <tr>
                        <td>{{ $r['emp_id'] }}</td>
                        <td style="text-align:right;">{{ $r['name'] }}</td>
                        <td>{{ $r['month'] }}</td>
                        <td>{{ number_format($r['basic'] ?? 0, 2) }}</td>
                        <td>{{ number_format($r['housing'] ?? 0, 2) }}</td>
                        <td>{{ number_format($r['transport'] ?? 0, 2) }}</td>
                        <td>{{ number_format($r['food'] ?? 0, 2) }}</td>
                        <td>{{ number_format($r['other'] ?? 0, 2) }}</td>
                        <td>{{ number_format($r['fees'] ?? 0, 2) }}</td>
                        <td><strong>{{ number_format($r['total'] ?? 0, 2) }}</strong></td>
                        <td>
                            <span class="badge {{ $r['action'] === 'created' ? 'badge-approved' : 'badge-pending' }}">
                                {{ $r['action'] === 'created' ? 'جديد' : 'تحديث' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- ERRORS TABLE --}}
        @if(count($importErrors) > 0)
        <div class="entry-card" style="border:1px solid #fca5a5; margin-bottom:14px; overflow-x:auto; padding:12px;">
            <h5 style="font-weight:600; color:#991b1b; margin-bottom:10px;">
                ❌ الأخطاء / Errors — {{ count($importErrors) }} صف لم يُستورد
            </h5>
            <p style="font-size:12px; color:#6b7280; margin-bottom:8px;">
                السبب الأكثر شيوعاً: الموظف غير موجود في النظام — يجب إضافته أولاً عبر صفحة "الموظفين"
            </p>
            <table class="entries-table" style="min-width:500px;">
                <thead>
                    <tr>
                        <th>الصف</th>
                        <th>Emp ID في الملف</th>
                        <th>السبب / Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($importErrors as $err)
                    <tr>
                        <td style="color:#6b7280;">{{ $err['row'] }}</td>
                        <td><strong>{{ $err['emp_id'] }}</strong></td>
                        <td style="color:#dc2626; text-align:right;">{{ $err['reason'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    </div>
    @endif

    {{-- ===================== TAB 1: OVERTIME ===================== --}}
    @if($activeTab === 'overtime')
        {{-- Search Bar inside tab --}}
        <div class="search-bar">
            <div class="form-group" style="flex: 0 0 200px;">
                <label>الرقم الوظيفي / Emp ID</label>
                <input type="text" wire:model="searchEmpId" placeholder="أدخل الرقم الوظيفي..."
                       wire:keydown.enter="searchEmployee">
            </div>
            <div class="form-group" style="flex: 0 0 300px;">
                <label>أو اختر موظف / Or Select Employee</label>
                <select wire:change="selectEmployee($event.target.value)">
                    <option value="">-- اختر موظف --</option>
                    @foreach($this->getEmployees() as $id => $name)
                        <option value="{{ $id }}" @if($selectedEmployeeId == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn-primary" wire:click="searchEmployee" style="height: 38px;">🔍 بحث</button>
            <div class="form-group" style="flex: 0 0 180px;">
                <label>الشهر / Month</label>
                <input type="month" wire:model.live="selectedMonth" value="{{ $selectedMonth }}">
            </div>
            <button class="btn-primary" wire:click="toggleBulkUpload" style="height: 38px; background: {{ $showBulkUpload ? '#dc2626' : '#7c3aed' }};">
                📤 {{ $showBulkUpload ? 'إلغاء الرفع' : 'رفع ملف CSV/Excel' }}
            </button>
            @if($selectedEmployeeName)
                <div class="employee-info">✅ {{ $selectedEmployeeName }}</div>
            @endif
        </div>

        {{-- Bulk Upload Section --}}
        @if($showBulkUpload)
        <div class="entry-card" style="border: 2px dashed #7c3aed; background: #f5f3ff;">
            <h4 style="margin-bottom: 12px; font-weight: 600; color: #7c3aed;">📤 رفع ملف CSV للساعات الإضافية / Bulk Upload Overtime CSV</h4>
            <p style="font-size: 12px; color: #6b7280; margin-bottom: 12px;">الأعمدة المطلوبة: emp_id, hours, rate, amount, notes (اختياري)</p>
            <div style="display: flex; gap: 12px; align-items: flex-end;">
                <div class="form-group" style="flex: 1;">
                    <label>اختر ملف CSV</label>
                    <input type="file" wire:model="bulkFile" accept=".csv,.txt,.xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel">
                </div>
                <button class="btn-primary" wire:click="importBulkEntries" style="height: 38px; background: #7c3aed;">
                    📥 استيراد / Import
                </button>
            </div>
        </div>
        @endif

        @if($selectedEmployeeId)
        <div class="entry-card">
            <h3 style="margin-bottom: 16px; font-size: 16px; font-weight: 700;">
                إضافة ساعات عمل إضافي / Add Overtime Hours
            </h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>عدد الساعات / Hours *</label>
                    <input type="number" step="0.5" min="0" wire:model="overtimeHours" placeholder="0">
                </div>
                <div class="form-group">
                    <label>سعر الساعة / Rate/Hour</label>
                    <input type="number" step="0.01" min="0" wire:model="overtimeRate" placeholder="SAR">
                </div>
                <div class="form-group">
                    <label>المبلغ الإجمالي / Amount</label>
                    <input type="number" step="0.01" min="0" wire:model="overtimeAmount" placeholder="SAR (auto)">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>ملاحظات / Notes</label>
                    <input type="text" wire:model="overtimeNotes" placeholder="ملاحظات إضافية...">
                </div>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 16px;">
                <div class="recurring-toggle">
                    <label class="toggle-switch">
                        <input type="checkbox" wire:model="overtimeRecurring">
                        <span class="toggle-slider"></span>
                    </label>
                    <label>متكرر شهرياً / Monthly Recurring</label>
                    <span style="font-size: 12px; color: #6b7280;">
                        {{ $overtimeRecurring ? '🔄 سيتم تكراره كل شهر' : '1️⃣ مرة واحدة فقط' }}
                    </span>
                </div>
                <button class="btn-primary" wire:click="saveOvertime">
                    ✅ حفظ / Save
                </button>
            </div>
        </div>

        @if(count($existingOvertimes) > 0)
        <div class="entry-card">
            <h4 style="margin-bottom: 8px; font-size: 14px; font-weight: 600;">
                السجلات الحالية / Current Records ({{ count($existingOvertimes) }})
            </h4>
            <table class="entries-table">
                <thead>
                    <tr>
                        <th>الساعات</th>
                        <th>السعر/ساعة</th>
                        <th>المبلغ</th>
                        <th>التكرار</th>
                        <th>الحالة</th>
                        <th>ملاحظات</th>
                        <th>الإجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($existingOvertimes as $ot)
                    <tr>
                        <td>{{ $ot['hours'] }}</td>
                        <td>{{ number_format($ot['rate_per_hour'], 2) }}</td>
                        <td>{{ number_format($ot['amount'], 2) }} SAR</td>
                        <td><span class="badge {{ $ot['is_recurring'] ? 'badge-recurring' : 'badge-once' }}">
                            {{ $ot['is_recurring'] ? 'شهري' : 'مرة واحدة' }}
                        </span></td>
                        <td><span class="badge badge-{{ $ot['status'] }}">{{ $ot['status'] }}</span></td>
                        <td>{{ $ot['notes'] ?? '-' }}</td>
                        <td>
                            @if($ot['status'] === 'pending')
                            <button class="btn-danger" wire:click="deleteOvertime({{ $ot['id'] }})"
                                    wire:confirm="هل أنت متأكد من الحذف؟">🗑</button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        @else
        <div class="entry-card" style="text-align: center; padding: 40px 20px;">
            <div style="font-size: 40px; margin-bottom: 12px;">👤</div>
            <p style="color: #6b7280;">ابحث عن موظف أو اختره من القائمة أعلاه / Search or select an employee above</p>
        </div>
        @endif
    @endif

    {{-- ===================== TAB 2: ADDITIONS ===================== --}}
    @if($activeTab === 'additions')
        {{-- Search Bar inside tab --}}
        <div class="search-bar">
            <div class="form-group" style="flex: 0 0 200px;">
                <label>الرقم الوظيفي / Emp ID</label>
                <input type="text" wire:model="searchEmpId" placeholder="أدخل الرقم الوظيفي..."
                       wire:keydown.enter="searchEmployee">
            </div>
            <div class="form-group" style="flex: 0 0 300px;">
                <label>أو اختر موظف / Or Select Employee</label>
                <select wire:change="selectEmployee($event.target.value)">
                    <option value="">-- اختر موظف --</option>
                    @foreach($this->getEmployees() as $id => $name)
                        <option value="{{ $id }}" @if($selectedEmployeeId == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn-primary" wire:click="searchEmployee" style="height: 38px;">🔍 بحث</button>
            <div class="form-group" style="flex: 0 0 180px;">
                <label>الشهر / Month</label>
                <input type="month" wire:model.live="selectedMonth" value="{{ $selectedMonth }}">
            </div>
            <button class="btn-primary" wire:click="toggleBulkUpload" style="height: 38px; background: {{ $showBulkUpload ? '#dc2626' : '#7c3aed' }};">
                📤 {{ $showBulkUpload ? 'إلغاء الرفع' : 'رفع ملف CSV/Excel' }}
            </button>
            @if($selectedEmployeeName)
                <div class="employee-info">✅ {{ $selectedEmployeeName }}</div>
            @endif
        </div>

        @if($showBulkUpload)
        <div class="entry-card" style="border: 2px dashed #7c3aed; background: #f5f3ff;">
            <h4 style="margin-bottom: 12px; font-weight: 600; color: #7c3aed;">📤 رفع ملف CSV للمبالغ الإضافية / Bulk Upload Additions CSV</h4>
            <p style="font-size: 12px; color: #6b7280; margin-bottom: 12px;">الأعمدة المطلوبة: emp_id, amount, reason (اختياري), description (اختياري)</p>
            <div style="display: flex; gap: 12px; align-items: flex-end;">
                <div class="form-group" style="flex: 1;">
                    <label>اختر ملف CSV</label>
                    <input type="file" wire:model="bulkFile" accept=".csv,.txt,.xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel">
                </div>
                <button class="btn-primary" wire:click="importBulkEntries" style="height: 38px; background: #7c3aed;">
                    📥 استيراد / Import
                </button>
            </div>
        </div>
        @endif

        @if($selectedEmployeeId)
        <div class="entry-card">
            <h3 style="margin-bottom: 16px; font-size: 16px; font-weight: 700;">
                إضافة مبالغ إضافية / Add Additional Amounts
            </h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>المبلغ / Amount (SAR) *</label>
                    <input type="number" step="0.01" min="0" wire:model="additionAmount" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>السبب / Reason</label>
                    <input type="text" wire:model="additionReason" placeholder="مثل: بدل سكن إضافي">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>الوصف / Description</label>
                    <textarea wire:model="additionDescription" rows="2" placeholder="تفاصيل إضافية..."></textarea>
                </div>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 16px;">
                <div class="recurring-toggle">
                    <label class="toggle-switch">
                        <input type="checkbox" wire:model="additionRecurring">
                        <span class="toggle-slider"></span>
                    </label>
                    <label>متكرر شهرياً / Monthly Recurring</label>
                    <span style="font-size: 12px; color: #6b7280;">
                        {{ $additionRecurring ? '🔄 سيتم تكراره كل شهر' : '1️⃣ مرة واحدة فقط' }}
                    </span>
                </div>
                <button class="btn-primary" wire:click="saveAddition">
                    ✅ حفظ / Save
                </button>
            </div>
        </div>

        @if(count($existingAdditions) > 0)
        <div class="entry-card">
            <h4 style="margin-bottom: 8px; font-size: 14px; font-weight: 600;">
                السجلات الحالية / Current Records ({{ count($existingAdditions) }})
            </h4>
            <table class="entries-table">
                <thead>
                    <tr>
                        <th>المبلغ</th>
                        <th>السبب</th>
                        <th>الوصف</th>
                        <th>التكرار</th>
                        <th>الحالة</th>
                        <th>الإجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($existingAdditions as $add)
                    <tr>
                        <td>{{ number_format($add['amount'], 2) }} SAR</td>
                        <td>{{ $add['reason'] ?? '-' }}</td>
                        <td>{{ $add['description'] ?? '-' }}</td>
                        <td><span class="badge {{ $add['is_recurring'] ? 'badge-recurring' : 'badge-once' }}">
                            {{ $add['is_recurring'] ? 'شهري' : 'مرة واحدة' }}
                        </span></td>
                        <td><span class="badge badge-{{ $add['status'] }}">{{ $add['status'] }}</span></td>
                        <td>
                            @if($add['status'] === 'pending')
                            <button class="btn-danger" wire:click="deleteAddition({{ $add['id'] }})"
                                    wire:confirm="هل أنت متأكد من الحذف؟">🗑</button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        @else
        <div class="entry-card" style="text-align: center; padding: 40px 20px;">
            <div style="font-size: 40px; margin-bottom: 12px;">👤</div>
            <p style="color: #6b7280;">ابحث عن موظف أو اختره من القائمة أعلاه / Search or select an employee above</p>
        </div>
        @endif
    @endif

    {{-- ===================== TAB 3: TIMESHEET (ALL EMPLOYEES) ===================== --}}
    @if($activeTab === 'timesheet')
        <div class="search-bar" style="justify-content: flex-start;">
            <div class="form-group" style="flex: 0 0 180px;">
                <label>الشهر / Month</label>
                <input type="month" wire:model.live="selectedMonth" value="{{ $selectedMonth }}">
            </div>
            <div style="font-weight: 600; color: #076EA7; font-size: 15px;">
                📅 {{ $this->getMonthLabel() }} — {{ count($allBranchEmployees) }} موظف
            </div>
        </div>

        <div class="entry-card">
            <h3 style="margin-bottom: 16px; font-size: 16px; font-weight: 700;">
                الحضور والإنصراف الشهري لجميع الموظفين / Monthly Time Sheet - All Employees
            </h3>

            <div class="timesheet-container">
                <table class="timesheet-table">
                    <thead>
                        <tr>
                            <th style="min-width: 40px; position: sticky; left: 0; z-index: 3; background: #f3f4f6;">#</th>
                            <th style="min-width: 150px; position: sticky; left: 40px; z-index: 3; background: #f3f4f6; text-align: right;">الموظف</th>
                            @for($d = 1; $d <= $this->getDaysInMonth(); $d++)
                                @php
                                    $parts = explode('-', $selectedMonth);
                                    $date = \Carbon\Carbon::create($parts[0], $parts[1], $d);
                                    $isFriday = $date->isFriday();
                                    $isSaturday = $date->isSaturday();
                                @endphp
                                <th class="day-header" style="{{ ($isFriday || $isSaturday) ? 'background: #dbeafe;' : '' }}">
                                    <div>{{ $d }}</div>
                                    <div style="font-size: 10px; color: #6b7280;">{{ substr($date->format('D'), 0, 2) }}</div>
                                </th>
                            @endfor
                            <th style="background: #d1fae5; min-width: 45px;">P</th>
                            <th style="background: #fee2e2; min-width: 45px;">A</th>
                            <th style="background: #fed7aa; min-width: 45px;">L</th>
                            <th style="background: #e9d5ff; min-width: 45px;">O</th>
                            <th style="background: #e5e7eb; min-width: 45px;">X</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allBranchEmployees as $idx => $emp)
                        @php $empSummary = $this->getEmployeeTimesheetSummary($emp['id']); @endphp
                        <tr>
                            <td style="position: sticky; left: 0; z-index: 1; background: white; font-size: 11px; color: #6b7280;">{{ $emp['emp_id'] }}</td>
                            <td style="position: sticky; left: 40px; z-index: 1; background: white; font-weight: 600; font-size: 12px; text-align: right; white-space: nowrap;">{{ $emp['name'] }}</td>
                            @for($d = 1; $d <= $this->getDaysInMonth(); $d++)
                                @php $currentStatus = $allTimesheetData[$emp['id']][$d] ?? 'P'; @endphp
                                <td class="status-{{ $currentStatus }}">
                                    <select class="timesheet-select"
                                            wire:model.live="allTimesheetData.{{ $emp['id'] }}.{{ $d }}">
                                        @foreach(App\Enums\AttendanceStatus::cases() as $status)
                                            <option value="{{ $status->value }}">{{ $status->value }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            @endfor
                            <td style="background: #d1fae5; font-weight: 700;">{{ $empSummary['P'] }}</td>
                            <td style="background: #fee2e2; font-weight: 700;">{{ $empSummary['A'] }}</td>
                            <td style="background: #fed7aa; font-weight: 700;">{{ $empSummary['L'] }}</td>
                            <td style="background: #e9d5ff; font-weight: 700;">{{ $empSummary['O'] }}</td>
                            <td style="background: #e5e7eb; font-weight: 700;">{{ $empSummary['X'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Legend --}}
            <div class="summary-grid" style="margin-top: 16px;">
                <div class="summary-card" style="background: #d1fae5; color: #065f46;">P = حاضر / Present</div>
                <div class="summary-card" style="background: #fee2e2; color: #991b1b;">A = غائب / Absent</div>
                <div class="summary-card" style="background: #fed7aa; color: #92400e;">L = إجازة / Leave</div>
                <div class="summary-card" style="background: #e9d5ff; color: #581c87;">O = يوم راحة / Off Day</div>
                <div class="summary-card" style="background: #e5e7eb; color: #374151;">X = مستبعد / Excluded</div>
            </div>

            <div style="text-align: left; margin-top: 16px;">
                <button class="btn-primary" wire:click="saveAllTimesheets">
                    💾 حفظ جميع التايم شيتات / Save All Timesheets
                </button>
            </div>
        </div>
    @endif

    {{-- ===================== TAB 4: DEDUCTIONS (CLIENT ONLY) ===================== --}}
    @if($activeTab === 'deductions' && !$this->isProvider())
        {{-- Search Bar inside tab --}}
        <div class="search-bar">
            <div class="form-group" style="flex: 0 0 200px;">
                <label>الرقم الوظيفي / Emp ID</label>
                <input type="text" wire:model="searchEmpId" placeholder="أدخل الرقم الوظيفي..."
                       wire:keydown.enter="searchEmployee">
            </div>
            <div class="form-group" style="flex: 0 0 300px;">
                <label>أو اختر موظف / Or Select Employee</label>
                <select wire:change="selectEmployee($event.target.value)">
                    <option value="">-- اختر موظف --</option>
                    @foreach($this->getEmployees() as $id => $name)
                        <option value="{{ $id }}" @if($selectedEmployeeId == $id) selected @endif>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn-primary" wire:click="searchEmployee" style="height: 38px;">🔍 بحث</button>
            <div class="form-group" style="flex: 0 0 180px;">
                <label>الشهر / Month</label>
                <input type="month" wire:model.live="selectedMonth" value="{{ $selectedMonth }}">
            </div>
            <button class="btn-primary" wire:click="toggleBulkUpload" style="height: 38px; background: {{ $showBulkUpload ? '#dc2626' : '#7c3aed' }};">
                📤 {{ $showBulkUpload ? 'إلغاء الرفع' : 'رفع ملف CSV/Excel' }}
            </button>
            @if($selectedEmployeeName)
                <div class="employee-info">✅ {{ $selectedEmployeeName }}</div>
            @endif
        </div>

        @if($showBulkUpload)
        <div class="entry-card" style="border: 2px dashed #7c3aed; background: #f5f3ff;">
            <h4 style="margin-bottom: 12px; font-weight: 600; color: #7c3aed;">📤 رفع ملف CSV للخصومات / Bulk Upload Deductions CSV</h4>
            <p style="font-size: 12px; color: #6b7280; margin-bottom: 12px;">الأعمدة المطلوبة: emp_id, amount, type (اختياري), reason (اختياري), description (اختياري)</p>
            <div style="display: flex; gap: 12px; align-items: flex-end;">
                <div class="form-group" style="flex: 1;">
                    <label>اختر ملف CSV</label>
                    <input type="file" wire:model="bulkFile" accept=".csv,.txt,.xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel">
                </div>
                <button class="btn-primary" wire:click="importBulkEntries" style="height: 38px; background: #7c3aed;">
                    📥 استيراد / Import
                </button>
            </div>
        </div>
        @endif

        @if($selectedEmployeeId)
        <div class="entry-card">
            <h3 style="margin-bottom: 16px; font-size: 16px; font-weight: 700;">
                إضافة خصم / Add Deduction
            </h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>نوع الخصم / Type *</label>
                    <select wire:model.live="deductionType">
                        <option value="">-- اختر --</option>
                        @foreach($this->getDeductionTypeOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>سبب الخصم / Reason</label>
                    <select wire:model="deductionReason">
                        <option value="">-- اختر --</option>
                        @foreach($this->getDeductionReasonOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if($deductionType === 'days')
                <div class="form-group">
                    <label>عدد الأيام / Days</label>
                    <input type="number" min="0" wire:model="deductionDays" placeholder="0">
                </div>
                <div class="form-group">
                    <label>سعر اليوم / Daily Rate</label>
                    <input type="number" step="0.01" min="0" wire:model="deductionDailyRate" placeholder="SAR">
                </div>
                @endif
                <div class="form-group">
                    <label>المبلغ / Amount (SAR) *</label>
                    <input type="number" step="0.01" min="0" wire:model="deductionAmount" placeholder="0.00">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>الوصف / Description</label>
                    <textarea wire:model="deductionDescription" rows="2" placeholder="تفاصيل الخصم..."></textarea>
                </div>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 16px;">
                <div class="recurring-toggle">
                    <label class="toggle-switch">
                        <input type="checkbox" wire:model="deductionRecurring">
                        <span class="toggle-slider"></span>
                    </label>
                    <label>متكرر شهرياً / Monthly Recurring</label>
                    <span style="font-size: 12px; color: #6b7280;">
                        {{ $deductionRecurring ? '🔄 سيتم تكراره كل شهر' : '1️⃣ مرة واحدة فقط' }}
                    </span>
                </div>
                <button class="btn-primary" wire:click="saveDeduction">
                    ✅ حفظ / Save
                </button>
            </div>
        </div>

        @if(count($existingDeductions) > 0)
        <div class="entry-card">
            <h4 style="margin-bottom: 8px; font-size: 14px; font-weight: 600;">
                الخصومات الحالية / Current Deductions ({{ count($existingDeductions) }})
            </h4>
            <table class="entries-table">
                <thead>
                    <tr>
                        <th>النوع</th>
                        <th>السبب</th>
                        <th>الأيام</th>
                        <th>المبلغ</th>
                        <th>التكرار</th>
                        <th>الحالة</th>
                        <th>الوصف</th>
                        <th>الإجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($existingDeductions as $ded)
                    <tr>
                        <td>{{ App\Enums\DeductionType::getTranslatedEnum()[$ded['type']] ?? $ded['type'] }}</td>
                        <td>{{ App\Enums\DeductionReason::getTranslatedEnum()[$ded['reason']] ?? $ded['reason'] ?? '-' }}</td>
                        <td>{{ $ded['days'] ?? '-' }}</td>
                        <td>{{ number_format($ded['amount'], 2) }} SAR</td>
                        <td><span class="badge {{ ($ded['is_recurring'] ?? false) ? 'badge-recurring' : 'badge-once' }}">
                            {{ ($ded['is_recurring'] ?? false) ? 'شهري' : 'مرة واحدة' }}
                        </span></td>
                        <td><span class="badge badge-{{ $ded['status'] }}">{{ App\Enums\DeductionStatus::getTranslatedEnum()[$ded['status']] ?? $ded['status'] }}</span></td>
                        <td>{{ $ded['description'] ?? '-' }}</td>
                        <td>
                            @if($ded['status'] === 'pending')
                            <button class="btn-danger" wire:click="deleteDeduction({{ $ded['id'] }})"
                                    wire:confirm="هل أنت متأكد من الحذف؟">🗑</button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        @else
        <div class="entry-card" style="text-align: center; padding: 40px 20px;">
            <div style="font-size: 40px; margin-bottom: 12px;">👤</div>
            <p style="color: #6b7280;">ابحث عن موظف أو اختره من القائمة أعلاه / Search or select an employee above</p>
        </div>
        @endif
    @endif
</x-filament-panels::page>
