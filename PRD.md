# وثيقة متطلبات المنتج (PRD)
# نظام إدارة مخازن المعاهد — Institute Warehouse Management System

**الإصدار:** 2.4.0
**تاريخ الوثيقة:** فبراير 2026
**المطور:** AYMAN RAGAB
**المنصة:** WordPress Plugin
**النوع:** B2B – نظام ERP مخصص للمعاهد التعليمية

---

## جدول المحتويات

1. [ملخص تنفيذي](#1-ملخص-تنفيذي)
2. [نظرة عامة على المنتج](#2-نظرة-عامة-على-المنتج)
3. [المشكلة وفرصة السوق](#3-المشكلة-وفرصة-السوق)
4. [الأهداف ومؤشرات النجاح](#4-الأهداف-ومؤشرات-النجاح)
5. [شخصيات المستخدمين](#5-شخصيات-المستخدمين)
6. [الوحدات الوظيفية والمتطلبات](#6-الوحدات-الوظيفية-والمتطلبات)
7. [قصص المستخدمين (User Stories)](#7-قصص-المستخدمين-user-stories)
8. [متطلبات الأداء وغير الوظيفية](#8-متطلبات-الأداء-وغير-الوظيفية)
9. [البنية التقنية](#9-البنية-التقنية)
10. [تصميم قاعدة البيانات](#10-تصميم-قاعدة-البيانات)
11. [نقاط التكامل والـ APIs](#11-نقاط-التكامل-والـ-apis)
12. [نظام الصلاحيات](#12-نظام-الصلاحيات)
13. [متطلبات الأمان](#13-متطلبات-الأمان)
14. [متطلبات واجهة المستخدم](#14-متطلبات-واجهة-المستخدم)
15. [التقارير والتحليلات](#15-التقارير-والتحليلات)
16. [متطلبات الاختبار](#16-متطلبات-الاختبار)
17. [خارطة الطريق والتطوير المستقبلي](#17-خارطة-الطريق-والتطوير-المستقبلي)
18. [المخاطر والقيود](#18-المخاطر-والقيود)

---

## 1. الملخص التنفيذي

نظام **إدارة مخازن المعاهد** هو WordPress Plugin متكامل يوفر حلاً شاملاً لإدارة المخازن في المؤسسات التعليمية (معاهد، كليات، مراكز تدريب). يعمل النظام كوحدة مستقلة أو مدمجاً مع نظام الموارد البشرية `RSYI HR System`، ويغطي دورة الحياة الكاملة للمخزون: من الشراء والإضافة، إلى الصرف والتقارير، مع نظام اعتماد إلكتروني كامل.

### الخصائص الجوهرية:
- **دورة مخزون كاملة:** إضافة → صرف → تقارير
- **نظام FIFO:** أول ما يدخل أول ما يخرج لدقة التكلفة
- **اعتماد إلكتروني:** توقيع رقمي مع سير عمل متعدد المستويات
- **تكامل HR:** ربط تلقائي مع الأقسام والموظفين
- **متعدد اللغات:** عربي وإنجليزي

---

## 2. نظرة عامة على المنتج

### 2.1 ما هو النظام؟

نظام WordPress Plugin يعمل ضمن لوحة تحكم WordPress ويتميز بـ:

| الجانب | التفاصيل |
|--------|---------|
| **النوع** | WordPress Admin Plugin (بدون Frontend) |
| **اللغة** | PHP 7.4+ |
| **قاعدة البيانات** | MySQL مع جداول مخصصة (prefix: `wp_iw_`) |
| **الاتصال** | WordPress AJAX (admin-ajax.php) |
| **التكامل** | RSYI HR System (اختياري) |
| **إصدار WordPress** | 5.0+ |

### 2.2 مكونات النظام الرئيسية

```
┌─────────────────────────────────────────────────────────────┐
│              Institute Warehouse System v2.4.0              │
├─────────────────────┬───────────────────────────────────────┤
│   Core Modules      │   Support Modules                     │
├─────────────────────┼───────────────────────────────────────┤
│ • Products (أصناف)  │ • Database Management                 │
│ • Add Orders (إضافة)│ • Permissions & Roles                 │
│ • Withdrawals(صرف)  │ • Excel Import                        │
│ • Purchase Requests │ • Multi-language (AR/EN)              │
│ • Reports (تقارير)  │ • Print Permits                       │
│ • Categories        │ • Electronic Signature                │
│ • Suppliers         │ • HR System Integration               │
└─────────────────────┴───────────────────────────────────────┘
```

---

## 3. المشكلة وفرصة السوق

### 3.1 المشكلة الحالية

تعاني المؤسسات التعليمية من:

| المشكلة | التأثير |
|---------|---------|
| إدارة مخزون يدوية (Excel/ورق) | أخطاء بيانات، ضياع وقت، صعوبة المتابعة |
| غياب نظام اعتماد رسمي | صرف مخزون بدون موافقة رسمية |
| صعوبة تتبع حركة الأصناف | عدم معرفة من صرف ماذا ومتى |
| غياب التنبيه عند نقص المخزون | نفاد الأصناف الحيوية دون علم |
| عدم ربط المخزون بالأقسام/الموظفين | صعوبة تحميل الأقسام بتكاليف الصرف |
| عمليات شراء غير منظمة | هدر في الميزانية، تكرار الطلبات |

### 3.2 الحل المقترح

نظام رقمي متكامل يوفر:
- **أرشيف رقمي كامل** لجميع حركات المخزون
- **سير عمل الاعتماد** يضمن الرقابة والمحاسبة
- **تقارير فورية** تدعم القرار الإداري
- **تكامل سلس** مع نظام الموارد البشرية الموجود

---

## 4. الأهداف ومؤشرات النجاح

### 4.1 الأهداف الاستراتيجية

| الهدف | الوصف |
|------|-------|
| **رقمنة إدارة المخزون** | إلغاء الإجراءات الورقية بنسبة 100% |
| **ضبط الصرف** | اشتراط الاعتماد الرسمي قبل أي صرف |
| **الشفافية** | تتبع كامل لكل حركة مخزون مع المستخدم والتوقيت |
| **كفاءة الشراء** | توليد طلبات شراء تلقائية عند وصول المخزون للحد الأدنى |
| **دقة التكلفة** | تطبيق FIFO لحساب تكلفة الصرف بدقة |

### 4.2 مؤشرات الأداء الرئيسية (KPIs)

| المؤشر | القيمة المستهدفة |
|--------|----------------|
| دقة الرصيد المخزني | 99.9% |
| وقت معالجة إذن الصرف | < 24 ساعة |
| وقت توليد التقرير | < 5 ثوانٍ |
| نسبة الأصناف المدارة رقمياً | 100% |
| معدل الأصناف التي نفدت دون تنبيه | 0% |

---

## 5. شخصيات المستخدمين

### 5.1 مدير النظام (System Admin)

| الخاصية | التفاصيل |
|---------|---------|
| **الدور** | WordPress Administrator |
| **المسؤوليات** | تكوين النظام، إدارة الصلاحيات، عرض كل البيانات |
| **الصلاحيات** | كاملة (manage_options) |
| **الاحتياجات** | رؤية شاملة، تقارير مجمعة، إدارة المستخدمين |
| **نقاط الألم** | صعوبة مراقبة أداء المخزون بشكل لحظي |

### 5.2 أمين المخزن (Warehouse Keeper)

| الخاصية | التفاصيل |
|---------|---------|
| **الدور** | مستخدم WordPress بصلاحيات مخصصة |
| **المسؤوليات** | إدخال البيانات، إنشاء الأذون، متابعة المخزون |
| **الصلاحيات** | read_write على الوحدات الأساسية |
| **الاحتياجات** | واجهة سهلة لإنشاء الأذون، تتبع الطلبات |
| **نقاط الألم** | كثرة الإجراءات اليدوية وصعوبة التتبع |

### 5.3 العميد / المدير المعتمد (Approver)

| الخاصية | التفاصيل |
|---------|---------|
| **الدور** | مستخدم WordPress بصلاحية الاعتماد |
| **المسؤوليات** | مراجعة واعتماد أذون الصرف وطلبات الشراء |
| **الصلاحيات** | صلاحية الاعتماد فقط |
| **الاحتياجات** | عرض سريع للطلبات المعلقة، اعتماد بتوقيع إلكتروني |
| **نقاط الألم** | تراكم الطلبات الورقية، صعوبة المراجعة التاريخية |

### 5.4 رئيس القسم (Department Head)

| الخاصية | التفاصيل |
|---------|---------|
| **الدور** | مستخدم HR معرّف في نظام الموارد البشرية |
| **المسؤوليات** | طلب صرف لقسمه، متابعة استهلاك القسم |
| **الصلاحيات** | view على قسمه فقط، create withdrawal |
| **الاحتياجات** | تقرير استهلاك قسمه، معرفة ما صُرف |
| **نقاط الألم** | لا يعرف ماذا صُرف لقسمه ومن أين يطلب |

### 5.5 مسؤول المشتريات (Procurement Officer)

| الخاصية | التفاصيل |
|---------|---------|
| **الدور** | مستخدم WordPress |
| **المسؤوليات** | متابعة طلبات الشراء، التواصل مع الموردين |
| **الصلاحيات** | read_write على طلبات الشراء والموردين |
| **الاحتياجات** | قائمة الموردين، تتبع آخر أسعار الشراء |
| **نقاط الألم** | صعوبة مقارنة أسعار الموردين السابقة |

---

## 6. الوحدات الوظيفية والمتطلبات

---

### 6.1 وحدة إدارة الأصناف (Products Module)

**الملف:** `includes/class-iw-products.php`

#### الوصف
المستودع المركزي لكل أصناف المخزن. كل عملية مخزنية تبدأ وتنتهي هنا.

#### المتطلبات الوظيفية

| الرقم | المتطلب | الأولوية |
|-------|---------|---------|
| PRD-P-001 | إضافة صنف جديد مع بيانات كاملة (اسم، كود، تصنيف، وحدة، حد أدنى/أقصى، سعر) | Must Have |
| PRD-P-002 | تعديل بيانات صنف موجود | Must Have |
| PRD-P-003 | حذف صنف (مع فحص عدم وجود حركات) | Must Have |
| PRD-P-004 | عرض قائمة الأصناف مع الرصيد الحالي | Must Have |
| PRD-P-005 | البحث والتصفية في الأصناف (بالاسم، الكود، التصنيف) | Must Have |
| PRD-P-006 | مزامنة الأرصدة من الحركات الفعلية | Must Have |
| PRD-P-007 | استيراد الأصناف من ملف Excel | Should Have |
| PRD-P-008 | تمييز الأصناف وصلت للحد الأدنى | Must Have |
| PRD-P-009 | تمييز الأصناف المنتهية (رصيد = 0) | Must Have |
| PRD-P-010 | حساب متوسط سعر الشراء (Weighted Average) | Nice to Have |

#### حقول بيانات الصنف

```
name          (مطلوب) - اسم الصنف
sku           (اختياري) - كود/رقم الصنف
category_id   (مطلوب) - التصنيف
unit          (مطلوب) - وحدة القياس (قطعة، كيلو، لتر...)
min_stock     (مطلوب) - الحد الأدنى للتنبيه
max_stock     (اختياري) - الحد الأقصى
price         (اختياري) - السعر المرجعي
current_stock (محسوب تلقائياً) - الرصيد الحالي
```

#### منطق العمل
- `current_stock` = الرصيد الافتتاحي + مجموع الإضافات - مجموع الصرف
- لا يمكن حذف صنف له حركات في جداول `iw_transactions` أو `iw_add_order_items`
- مزامنة الأرصدة يجب أن تكون atomic لضمان الاتساق

---

### 6.2 وحدة الرصيد الافتتاحي (Opening Balance Module)

**الملف:** `includes/class-iw-opening-balance.php`

#### الوصف
نقطة البداية لإدخال الأرصدة الأولية عند بدء استخدام النظام لأول مرة.

#### المتطلبات الوظيفية

| الرقم | المتطلب | الأولوية |
|-------|---------|---------|
| PRD-OB-001 | إدخال رصيد افتتاحي لصنف محدد | Must Have |
| PRD-OB-002 | تحديد تاريخ الرصيد الافتتاحي | Must Have |
| PRD-OB-003 | إدخال سعر الوحدة للرصيد الافتتاحي | Must Have |
| PRD-OB-004 | استيراد الأرصدة الافتتاحية من Excel | Should Have |
| PRD-OB-005 | حذف رصيد افتتاحي مع عكس التأثير على المخزون | Must Have |
| PRD-OB-006 | إعادة ضبط كاملة للأرصدة الافتتاحية | Should Have |
| PRD-OB-007 | منع تكرار رصيد افتتاحي لنفس الصنف (استبدال وليس إضافة) | Must Have |

#### قواعد العمل
- الرصيد الافتتاحي يُسجَّل كـ transaction من نوع `opening_balance`
- لا يمكن إضافة رصيد افتتاحي جديد لصنف له حركات صرف

---

### 6.3 وحدة أذون الإضافة (Add Orders Module)

**الملف:** `includes/class-iw-add-orders.php`

#### الوصف
يتحكم في عمليات إضافة المخزون من الموردين (المشتريات الفعلية).

#### المتطلبات الوظيفية

| الرقم | المتطلب | الأولوية |
|-------|---------|---------|
| PRD-AO-001 | إنشاء إذن إضافة متعدد الأصناف | Must Have |
| PRD-AO-002 | ربط الإذن بمورد محدد | Must Have |
| PRD-AO-003 | إدخال كمية وسعر لكل صنف | Must Have |
| PRD-AO-004 | حساب الإجمالي تلقائياً | Must Have |
| PRD-AO-005 | توليد رقم إذن تسلسلي تلقائي | Must Have |
| PRD-AO-006 | طباعة إذن الإضافة | Must Have |
| PRD-AO-007 | تعديل إذن الإضافة مع إعادة حساب المخزون | Should Have |
| PRD-AO-008 | حذف إذن الإضافة مع عكس التأثير | Should Have |
| PRD-AO-009 | إرفاق رقم الفاتورة/رقم المرجع | Should Have |
| PRD-AO-010 | تحديث رصيد المخزون فور حفظ الإذن | Must Have |
| PRD-AO-011 | تسجيل حركات FIFO لكل بند | Must Have |

#### تدفق العمل
```
إنشاء الإذن
    → إضافة البنود (منتج + كمية + سعر)
    → حفظ الإذن
    → تحديث iw_products.current_stock
    → تسجيل سجلات في iw_transactions (نوع: add)
    → طباعة الإذن
```

#### قواعد توليد رقم الإذن
```
Format: ADD-{YEAR}{MONTH}-{SEQUENCE}
Example: ADD-202602-0001
```

---

### 6.4 وحدة أذون الصرف (Withdrawal Orders Module)

**الملف:** `includes/class-iw-withdrawal-orders.php`

#### الوصف
الوحدة الأكثر تعقيداً في النظام. تتحكم في صرف المخزون مع نظام اعتماد متعدد المستويات.

#### أنواع أذون الصرف

| النوع | الوصف | تأثير المخزون |
|------|-------|--------------|
| `normal` | صرف عادي للقسم | ينقص من الرصيد |
| `custody` | عهدة مؤقتة (للإعارة) | لا ينقص من الرصيد |

#### حالات الإذن (Workflow States)

```
pending → approved → completed
   ↓          ↓
cancelled  cancelled
```

| الحالة | الوصف | من يملك الصلاحية |
|-------|-------|----------------|
| `pending` | منتظر الاعتماد | أمين المخزن/الطالب |
| `approved` | معتمد من العميد | العميد/المدير المعتمد |
| `completed` | تم التنفيذ وخصم المخزون | أمين المخزن |
| `cancelled` | ملغي | صاحب الطلب أو المدير |

#### المتطلبات الوظيفية

| الرقم | المتطلب | الأولوية |
|-------|---------|---------|
| PRD-WO-001 | إنشاء إذن صرف متعدد الأصناف | Must Have |
| PRD-WO-002 | تحديد القسم والموظف الطالب | Must Have |
| PRD-WO-003 | التحقق من توفر الرصيد قبل الإنشاء | Must Have |
| PRD-WO-004 | إرسال الإذن للاعتماد مع إشعار | Must Have |
| PRD-WO-005 | مراجعة وتعديل الكميات من المعتمد | Must Have |
| PRD-WO-006 | اعتماد الإذن مع التوقيع الإلكتروني | Must Have |
| PRD-WO-007 | خصم المخزون باستخدام FIFO عند الإكمال | Must Have |
| PRD-WO-008 | طباعة إذن الصرف مع التوقيع | Must Have |
| PRD-WO-009 | إلغاء الإذن مع عكس التأثير | Must Have |
| PRD-WO-010 | إنشاء إذن عهدة (بدون خصم مخزون) | Should Have |
| PRD-WO-011 | توليد رقم إذن تسلسلي | Must Have |
| PRD-WO-012 | فلترة الأذون بالحالة/التاريخ/القسم | Should Have |

#### خوارزمية FIFO للصرف
```
عند صرف كمية X من صنف P:
1. جلب حركات الإضافة لصنف P مرتبة بالتاريخ تصاعدياً
2. لكل حركة: اقتطع من remaining_qty
3. سجّل transaction جديد (نوع: withdraw)
4. حدّث remaining_qty في كل حركة إضافة متأثرة
5. حدّث current_stock في iw_products
```

---

### 6.5 وحدة طلبات الشراء (Purchase Requests Module)

**الملف:** `includes/class-iw-purchase-requests.php`

#### الوصف
توليد وإدارة طلبات الشراء سواء يدوياً أو تلقائياً بناءً على مستوى المخزون.

#### المتطلبات الوظيفية

| الرقم | المتطلب | الأولوية |
|-------|---------|---------|
| PRD-PR-001 | إنشاء طلب شراء يدوي | Must Have |
| PRD-PR-002 | توليد طلب شراء تلقائي للأصناف وصلت للحد الأدنى | Must Have |
| PRD-PR-003 | تصفية التوليد التلقائي بالتصنيف | Should Have |
| PRD-PR-004 | عرض آخر سعر شراء لكل صنف | Must Have |
| PRD-PR-005 | اعتماد طلب الشراء مع التوقيع الإلكتروني | Must Have |
| PRD-PR-006 | طباعة طلب الشراء | Must Have |
| PRD-PR-007 | تتبع حالة الطلب (pending/approved/completed) | Must Have |
| PRD-PR-008 | ربط طلب الشراء بـ إذن الإضافة بعد الاستلام | Nice to Have |

#### منطق التوليد التلقائي
```
للأصناف حيث:
    current_stock <= min_stock
الكمية المقترحة:
    max_stock - current_stock (أو min_stock إذا لم يحدد max_stock)
السعر المرجعي:
    آخر سعر شراء من iw_add_order_items
```

---

### 6.6 وحدة الموردين (Suppliers Module)

**الملف:** `includes/class-iw-suppliers.php`

#### المتطلبات الوظيفية

| الرقم | المتطلب | الأولوية |
|-------|---------|---------|
| PRD-SUP-001 | إضافة مورد جديد ببيانات كاملة | Must Have |
| PRD-SUP-002 | تعديل بيانات مورد | Must Have |
| PRD-SUP-003 | حذف مورد (مع فحص عدم ارتباطه بأذون) | Must Have |
| PRD-SUP-004 | رفع السجل التجاري والبطاقة الضريبية | Should Have |
| PRD-SUP-005 | توليد رقم مورد تسلسلي | Must Have |
| PRD-SUP-006 | البحث في قائمة الموردين | Should Have |

#### بيانات المورد
```
supplier_number  - رقم تسلسلي تلقائي
name             - اسم المورد (مطلوب)
email            - البريد الإلكتروني
phone_mobile     - رقم الهاتف
address          - العنوان
tax_number       - الرقم الضريبي
commercial_reg   - السجل التجاري
```

---

### 6.7 وحدة التصنيفات (Categories Module)

**الملف:** `includes/class-iw-categories.php`

#### المتطلبات الوظيفية

| الرقم | المتطلب | الأولوية |
|-------|---------|---------|
| PRD-CAT-001 | إضافة تصنيف جديد | Must Have |
| PRD-CAT-002 | تعديل تصنيف | Must Have |
| PRD-CAT-003 | حذف تصنيف (مع منع الحذف إن كان مستخدماً) | Must Have |
| PRD-CAT-004 | استخدام التصنيف لتصفية توليد طلبات الشراء | Must Have |

---

### 6.8 وحدة التقارير (Reports Module)

**الملف:** `includes/class-iw-reports.php`

#### التقارير المتاحة

| التقرير | الوصف | الفلاتر المتاحة |
|--------|-------|----------------|
| **تقرير المخزون الحالي** | قائمة كل الأصناف مع أرصدتها | تصنيف، حالة المخزون |
| **تقرير الأصناف دون الحد الأدنى** | أصناف وصلت للحد التحذيري | تصنيف |
| **تقرير الأصناف المنتهية** | أصناف رصيدها = 0 | تصنيف |
| **تقرير الحركات** | كل عمليات الإضافة والصرف | تاريخ، نوع الحركة، صنف |
| **تقرير استهلاك الأقسام** | ما صرفه كل قسم | قسم، فترة زمنية |
| **تقرير حركة صنف** | تاريخ كامل لصنف واحد | صنف، فترة زمنية |

#### المتطلبات الوظيفية

| الرقم | المتطلب | الأولوية |
|-------|---------|---------|
| PRD-RPT-001 | عرض تقرير المخزون الحالي مع الرصيد | Must Have |
| PRD-RPT-002 | تقرير الأصناف تحت الحد الأدنى | Must Have |
| PRD-RPT-003 | تقرير الأصناف المنتهية تماماً | Must Have |
| PRD-RPT-004 | تقرير حركات المخزون بالفلاتر | Must Have |
| PRD-RPT-005 | تقرير استهلاك القسم الواحد التفصيلي | Must Have |
| PRD-RPT-006 | تقرير حركة الصنف الواحد | Should Have |
| PRD-RPT-007 | تصدير التقارير إلى Excel/PDF | Should Have |
| PRD-RPT-008 | طباعة التقارير مباشرة من المتصفح | Should Have |

---

### 6.9 وحدة الصلاحيات والتوقيع الإلكتروني

**الملف:** `includes/class-iw-permissions.php`

#### المتطلبات الوظيفية

| الرقم | المتطلب | الأولوية |
|-------|---------|---------|
| PRD-PERM-001 | تعيين صلاحيات لكل مستخدم على كل وحدة | Must Have |
| PRD-PERM-002 | 4 مستويات صلاحية (none/view/read/read_write) | Must Have |
| PRD-PERM-003 | رفع صورة التوقيع الإلكتروني لكل مستخدم | Must Have |
| PRD-PERM-004 | عرض التوقيع في الأذون المطبوعة | Must Have |
| PRD-PERM-005 | حماية صفحة الصلاحيات بـ manage_options | Must Have |
| PRD-PERM-006 | ربط الصلاحيات مع أدوار نظام HR | Should Have |

#### مصفوفة الصلاحيات

| الوحدة | none | view | read | read_write |
|--------|------|------|------|------------|
| عرض المخزون | ✗ | ✓ | ✓ | ✓ |
| إضافة مخزون | ✗ | ✗ | ✗ | ✓ |
| صرف مخزون | ✗ | ✗ | ✗ | ✓ |
| التقارير | ✗ | ✓ | ✓ | ✓ |
| إدارة الموردين | ✗ | ✗ | ✗ | ✓ |
| إدارة الأقسام | ✗ | ✓ | ✓ | ✓ |
| الإعدادات | ✗ | ✗ | ✗ | Admin Only |

---

## 7. قصص المستخدمين (User Stories)

### 7.1 أمين المخزن

```
US-WK-001: كأمين مخزن، أريد إضافة مشتريات جديدة بإذن إضافة رسمي
           لكي يتحدث رصيد المخزون تلقائياً.

US-WK-002: كأمين مخزن، أريد إنشاء إذن صرف لقسم معين
           لكي أسجل الصرف رسمياً ويُعتمد.

US-WK-003: كأمين مخزن، أريد رؤية الأصناف التي وصلت للحد الأدنى
           لكي أتخذ إجراء قبل نفادها.

US-WK-004: كأمين مخزن، أريد طباعة إذن الصرف بعد الاعتماد
           لكي يكون توثيقاً رسمياً للعملية.

US-WK-005: كأمين مخزن، أريد استيراد أصناف كثيرة من Excel دفعة واحدة
           لكي أوفر وقت الإدخال اليدوي.
```

### 7.2 العميد / المعتمد

```
US-APP-001: كعميد، أريد مراجعة أذون الصرف المعلقة
            لكي أعتمد أو أرفض كل طلب.

US-APP-002: كعميد، أريد تعديل الكميات المطلوبة قبل الاعتماد
            لكي أتحكم في ما يُصرف فعلياً.

US-APP-003: كعميد، أريد الاعتماد بتوقيعي الإلكتروني
            لكي يظهر توقيعي في الإذن المطبوع.

US-APP-004: كعميد، أريد مراجعة طلبات الشراء التلقائية
            لكي أعتمد الشراء قبل إرساله للموردين.
```

### 7.3 مدير النظام

```
US-ADMIN-001: كمدير نظام، أريد تخصيص صلاحيات كل مستخدم
              لكي يصل كل شخص لما يحتاجه فقط.

US-ADMIN-002: كمدير نظام، أريد تقرير استهلاك الأقسام الشهري
              لكي أحلل تكاليف كل قسم.

US-ADMIN-003: كمدير نظام، أريد رصيد افتتاحي كامل عند بدء النظام
              لكي تكون الأرصدة الأولية صحيحة.
```

---

## 8. متطلبات الأداء وغير الوظيفية

### 8.1 الأداء (Performance)

| المتطلب | المعيار |
|---------|--------|
| وقت تحميل صفحة المنتجات | < 3 ثوانٍ لـ 1000 صنف |
| وقت معالجة AJAX request | < 2 ثانية |
| وقت توليد التقارير | < 5 ثوانٍ |
| وقت استيراد Excel (100 صنف) | < 30 ثانية |
| وقت مزامنة الأرصدة (1000 صنف) | < 60 ثانية |

### 8.2 موثوقية النظام (Reliability)

| المتطلب | المعيار |
|---------|--------|
| دقة حسابات FIFO | 100% (بدون تقريب خاطئ) |
| اتساق البيانات | Database Transactions لجميع العمليات المركبة |
| استعادة من الأخطاء | Rollback تلقائي عند فشل العملية |

### 8.3 قابلية التوسع (Scalability)

| المتطلب | المعيار |
|---------|--------|
| عدد الأصناف المدعومة | 10,000+ صنف |
| عدد الحركات المدعومة | 100,000+ حركة |
| عدد المستخدمين المتزامنين | 50+ |

### 8.4 توافق المتصفحات

| المتصفح | الإصدار المدعوم |
|---------|---------------|
| Chrome | آخر 2 إصدار |
| Firefox | آخر 2 إصدار |
| Edge | آخر 2 إصدار |
| Safari | آخر إصدار |

### 8.5 متطلبات الخادم

| المتطلب | الحد الأدنى |
|---------|-----------|
| PHP | 7.4+ |
| WordPress | 5.0+ |
| MySQL | 5.7+ أو MariaDB 10.3+ |
| PHP Memory Limit | 256 MB |
| Max Execution Time | 120 ثانية (للعمليات الثقيلة) |

---

## 9. البنية التقنية

### 9.1 هيكل الملفات

```
institute-warehouse/
├── institute-warehouse.php          # Main plugin file (Bootstrap)
│   ├── Constants (IW_VERSION, IW_PATH...)
│   ├── Helper functions (iw_hr_*)
│   └── Institute_Warehouse_System (Singleton)
│
├── includes/                        # Core business logic
│   ├── class-iw-database.php        # DB schema management
│   ├── class-iw-permissions.php     # Auth & signature
│   ├── class-iw-products.php        # Products CRUD
│   ├── class-iw-transactions.php    # FIFO engine
│   ├── class-iw-departments.php     # HR integration adapter
│   ├── class-iw-suppliers.php       # Supplier management
│   ├── class-iw-withdrawal-orders.php # Withdrawal workflow
│   ├── class-iw-purchase-requests.php # Purchase workflow
│   ├── class-iw-add-orders.php      # Add orders management
│   ├── class-iw-opening-balance.php # Opening balances
│   ├── class-iw-categories.php      # Categories CRUD
│   ├── class-iw-reports.php         # Reporting engine
│   └── class-iw-excel-import.php    # Data import
│
├── admin/
│   ├── class-iw-admin.php           # View router
│   └── views/                       # Admin page templates
│       ├── dashboard.php
│       ├── products.php
│       ├── add-stock.php
│       ├── withdraw-stock.php
│       ├── purchase-requests.php
│       ├── opening-balance.php
│       ├── departments.php
│       ├── suppliers.php
│       ├── categories.php
│       ├── permissions.php
│       ├── settings.php
│       ├── signature.php
│       ├── print-add-permit.php
│       ├── print-withdraw-permit.php
│       └── reports/
│           ├── stock-report.php
│           ├── low-stock-report.php
│           ├── out-of-stock-report.php
│           ├── transactions-report.php
│           ├── department-consumption-report.php
│           └── product-movement-report.php
│
├── assets/
│   ├── css/admin.css
│   └── js/admin.js
│
└── languages/                       # i18n files (AR/EN)
```

### 9.2 نمط التصميم (Design Patterns)

| النمط | الاستخدام |
|------|----------|
| **Singleton** | `Institute_Warehouse_System::get_instance()` |
| **Repository Pattern** | كل Class تعمل كـ Repository للجدول الخاص بها |
| **Adapter Pattern** | `class-iw-departments.php` للتكامل مع HR |
| **Observer Pattern** | WordPress Hooks (actions/filters) |

### 9.3 تدفق البيانات

```
Browser Request
    → admin-ajax.php
    → wp_ajax_{action}
    → Class Method
    → check_ajax_referer() + capability check
    → Business Logic
    → $wpdb->prepare() + Database
    → wp_send_json_success() / wp_send_json_error()
    → Browser Update (JavaScript)
```

---

## 10. تصميم قاعدة البيانات

### 10.1 مخطط الجداول الكامل (ERD)

```
iw_categories
    id (PK)
    name
    description
    created_at

iw_products
    id (PK)
    name
    sku
    category_id (FK → iw_categories)
    unit
    min_stock
    max_stock
    current_stock
    price
    created_at
    updated_at

iw_suppliers
    id (PK)
    supplier_number
    name
    email
    phone_mobile
    address
    tax_number
    commercial_reg
    created_at

iw_add_orders
    id (PK)
    order_number
    supplier_id (FK → iw_suppliers)
    total_quantity
    total_value
    notes
    created_by (WP user_id)
    created_at

iw_add_order_items
    id (PK)
    order_id (FK → iw_add_orders)
    product_id (FK → iw_products)
    quantity
    unit_price
    total_price

iw_withdrawal_orders
    id (PK)
    order_number
    order_type        ENUM('normal', 'custody')
    department_id     (from HR)
    employee_id       (from HR)
    requested_by      (WP user_id)
    approved_by       (WP user_id)
    status            ENUM('pending','approved','completed','cancelled')
    notes
    approval_notes
    created_at
    approved_at
    completed_at

iw_withdrawal_order_items
    id (PK)
    order_id (FK → iw_withdrawal_orders)
    product_id (FK → iw_products)
    quantity           (الكمية المطلوبة)
    approved_quantity  (الكمية المعتمدة)
    unit_price

iw_purchase_requests
    id (PK)
    request_number
    status            ENUM('pending','approved','completed')
    notes
    approved_by (WP user_id)
    created_by (WP user_id)
    created_at
    approved_at

iw_purchase_request_items
    id (PK)
    request_id (FK → iw_purchase_requests)
    product_id (FK → iw_products)
    quantity
    last_purchase_price

iw_opening_balances
    id (PK)
    product_id (FK → iw_products)
    quantity
    unit_price
    balance_date
    created_by (WP user_id)
    created_at

iw_transactions
    id (PK)
    transaction_type  ENUM('add','withdraw','opening_balance')
    product_id (FK → iw_products)
    quantity
    unit_price
    remaining_qty     (للـ FIFO tracking)
    reference_type    ENUM('add_order','withdrawal_order','opening_balance')
    reference_id      (FK → الجدول المرجعي)
    department_id
    employee_id
    supplier_id
    notes
    created_by (WP user_id)
    created_at

iw_permissions
    id (PK)
    user_id (WP user_id)
    feature
    permission_level  ENUM('none','view','read','read_write')
```

### 10.2 العلاقات بين الجداول

```
iw_categories ──┐
                ├── iw_products ──┬── iw_add_order_items ── iw_add_orders ── iw_suppliers
                │                 ├── iw_withdrawal_order_items ── iw_withdrawal_orders
                │                 ├── iw_purchase_request_items ── iw_purchase_requests
                │                 ├── iw_opening_balances
                │                 └── iw_transactions (FIFO Engine)
                │
                └── iw_purchase_requests (category filter)
```

### 10.3 Indexes المقترحة للأداء

```sql
-- Products
INDEX idx_products_category (category_id)
INDEX idx_products_sku (sku)
INDEX idx_products_stock (current_stock)

-- Transactions (أكثر جدول طلبات)
INDEX idx_transactions_product (product_id)
INDEX idx_transactions_type (transaction_type)
INDEX idx_transactions_remaining (remaining_qty)
INDEX idx_transactions_created (created_at)
INDEX idx_transactions_reference (reference_type, reference_id)

-- Withdrawal Orders
INDEX idx_withdrawal_status (status)
INDEX idx_withdrawal_dept (department_id)
INDEX idx_withdrawal_created (created_at)

-- Permissions
UNIQUE INDEX idx_permissions_user_feature (user_id, feature)
```

---

## 11. نقاط التكامل والـ APIs

### 11.1 AJAX API الداخلية

#### قائمة كاملة بـ AJAX Actions

**Products:**
```
iw_save_product          POST  حفظ/تعديل صنف
iw_delete_product        POST  حذف صنف
iw_get_product           POST  جلب بيانات صنف
iw_get_products_list     POST  قائمة الأصناف
iw_sync_all_stocks       POST  مزامنة الأرصدة
```

**Add Orders:**
```
iw_create_add_order      POST  إنشاء إذن إضافة
iw_get_add_orders        POST  قائمة الأذون
iw_update_add_order      POST  تعديل إذن
iw_delete_add_order      POST  حذف إذن
```

**Withdrawal Orders:**
```
iw_create_withdrawal_order   POST  إنشاء إذن صرف
iw_get_withdrawal_orders     POST  قائمة الأذون
iw_approve_withdrawal_order  POST  اعتماد الإذن
iw_complete_withdrawal_order POST  إكمال الصرف
iw_create_custody_order      POST  إنشاء إذن عهدة
```

**Purchase Requests:**
```
iw_create_purchase_request      POST  إنشاء طلب شراء
iw_get_purchase_requests        POST  قائمة الطلبات
iw_approve_purchase_request     POST  اعتماد الطلب
iw_auto_generate_purchase_requests POST توليد تلقائي
```

**Opening Balance:**
```
iw_save_opening_balance    POST  حفظ رصيد افتتاحي
iw_get_opening_balances    POST  قائمة الأرصدة
iw_delete_opening_balance  POST  حذف رصيد
iw_reset_opening_balance   POST  إعادة ضبط كاملة
```

**Suppliers:**
```
iw_save_supplier     POST  حفظ مورد
iw_create_supplier   POST  إنشاء مورد
iw_delete_supplier   POST  حذف مورد
iw_get_suppliers     POST  قائمة الموردين
iw_get_supplier      POST  جلب مورد
```

**Categories:**
```
iw_get_categories    POST  قائمة التصنيفات
iw_save_category     POST  حفظ تصنيف
iw_delete_category   POST  حذف تصنيف
```

**Departments (from HR):**
```
iw_get_departments           POST  الأقسام
iw_get_employees             POST  الموظفون
iw_get_employees_by_dept     POST  موظفو قسم
```

**Reports:**
```
iw_get_stock_report              POST  تقرير المخزون
iw_get_transactions_report       POST  تقرير الحركات
iw_get_low_stock_report          POST  تقرير الأصناف الناقصة
iw_get_dept_consumption_detail   POST  استهلاك القسم
```

**Permissions:**
```
iw_save_permissions      POST  حفظ الصلاحيات
iw_get_user_permissions  POST  صلاحيات مستخدم
iw_upload_signature      POST  رفع التوقيع
iw_get_my_signature      POST  جلب التوقيع
```

**Import:**
```
iw_import_products   POST  استيراد من Excel
```

### 11.2 تكامل RSYI HR System

#### WordPress Filters المستخدمة

```php
// الحصول على البيانات من نظام HR
apply_filters('rsyi_hr_get_departments', [])
apply_filters('rsyi_hr_get_employees', [])
apply_filters('rsyi_hr_get_department_by_id', null, $dept_id)
apply_filters('rsyi_hr_get_employee_by_id', null, $emp_id)
apply_filters('rsyi_hr_get_employee_by_user_id', null, $user_id)
apply_filters('rsyi_hr_department_employees', [], $dept_id)
apply_filters('rsyi_hr_get_job_titles', [])

// توسيع الأدوار
do_action('rsyi_hr_extend_roles', $roles)
```

#### شرط التشغيل
```php
// التحقق من وجود النظام
function iw_is_hr_active() {
    return function_exists('rsyi_hr_get_departments')
           || has_filter('rsyi_hr_get_departments');
}
```

#### سلوك النظام بدون HR
- لا تعمل وحدة الأقسام والموظفين
- أذون الصرف لا تحتاج قسم/موظف
- التحقق يتم بـ `iw_is_hr_active()` في كل مكان

---

## 12. نظام الصلاحيات

### 12.1 مستويات الصلاحية

| المستوى | الكود | الوصف |
|---------|------|-------|
| بدون صلاحية | `none` | لا يرى الوحدة نهائياً |
| عرض فقط | `view` | يرى البيانات فقط |
| عرض + بعض الإجراءات | `read` | عرض + بعض العمليات المحدودة |
| صلاحية كاملة | `read_write` | إضافة + تعديل + حذف |

### 12.2 الوحدات التي تطبق عليها الصلاحيات

```
iw_view_warehouse          // عرض لوحة التحكم الرئيسية
iw_view_products           // عرض الأصناف
iw_add_stock               // إذن إضافة + رصيد افتتاحي
iw_withdraw_stock          // إذن الصرف
iw_view_reports            // عرض التقارير
iw_manage_suppliers        // إدارة الموردين
iw_manage_departments      // عرض الأقسام
iw_manage_categories       // إدارة التصنيفات
iw_import_data             // استيراد بيانات
iw_approve_orders          // اعتماد الأذون (وحدة خاصة)
```

### 12.3 WordPress Capabilities

```
manage_options             // صلاحيات النظام (WordPress built-in)
```

---

## 13. متطلبات الأمان

### 13.1 حماية AJAX

```php
// في كل AJAX handler يجب:
check_ajax_referer('iw_nonce', 'nonce');    // CSRF Protection
current_user_can('required_capability');     // Authorization Check
```

### 13.2 تنظيف البيانات

```php
// Input Sanitization
sanitize_text_field()      // للنصوص
sanitize_email()           // للبريد الإلكتروني
absint()                   // للأرقام الصحيحة
floatval()                 // للأرقام العشرية
wp_kses_post()             // للـ HTML المسموح

// Output Escaping
esc_html()                 // للنصوص في HTML
esc_attr()                 // للقيم في attributes
esc_url()                  // للـ URLs
wp_json_encode()           // للـ JSON
```

### 13.3 حماية SQL Injection

```php
// استخدام $wpdb->prepare() إلزامي
$wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id);
$wpdb->prepare("SELECT * FROM {$table} WHERE name = %s", $name);
```

### 13.4 حماية ملفات الرفع

```php
// التحقق من نوع الملف
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
// التحقق من الامتداد
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
// حد حجم الملف
$max_size = 2 * 1024 * 1024; // 2MB
```

### 13.5 نقاط الأمان الحرجة

| النقطة | المخاطر | الإجراء |
|--------|--------|--------|
| رفع ملفات التوقيع | رفع ملفات PHP خبيثة | فحص mime type + امتداد |
| استيراد Excel | ملفات ضارة | فحص XLSX فقط |
| AJAX Actions | CSRF | nonce verification |
| SQL Queries | SQL Injection | wpdb->prepare() |
| HTML Output | XSS | esc_html/attr() |

---

## 14. متطلبات واجهة المستخدم

### 14.1 مبادئ التصميم

| المبدأ | التطبيق |
|-------|--------|
| **WordPress Native** | استخدام WordPress UI components |
| **RTL Support** | كامل لدعم اللغة العربية |
| **Responsive** | متوافق مع الأجهزة اللوحية |
| **Print Friendly** | أذون قابلة للطباعة بتنسيق احترافي |

### 14.2 صفحات الإدارة

| الصفحة | الـ Slug | الوصف |
|-------|---------|-------|
| لوحة التحكم | `institute-warehouse` | إحصائيات سريعة وروابط |
| الأصناف | `iw-products` | عرض/إضافة/تعديل الأصناف |
| إذن الإضافة | `iw-add-stock` | إنشاء إذن إضافة |
| طباعة إذن إضافة | `iw-print-add-permit` | صفحة طباعة |
| إذن الصرف | `iw-withdraw-stock` | إنشاء إذن صرف |
| طباعة إذن صرف | `iw-print-withdraw-permit` | صفحة طباعة |
| طلبات الشراء | `iw-purchase-requests` | إدارة طلبات الشراء |
| الرصيد الافتتاحي | `iw-opening-balance` | إدارة الأرصدة الأولية |
| التقارير | `iw-reports` | مركز التقارير |
| تقرير المخزون | `iw-stock-report` | تقرير مفصل |
| الأصناف دون الحد | `iw-low-stock-report` | تقرير تحذيري |
| الأصناف المنتهية | `iw-out-of-stock-report` | تقرير المنتهيات |
| تقرير الحركات | `iw-transactions-report` | سجل الحركات |
| استهلاك الأقسام | `iw-dept-consumption-report` | توزيع التكاليف |
| حركة صنف | `iw-product-movement-report` | تتبع صنف واحد |
| الأقسام | `iw-departments` | عرض بيانات HR |
| الموردين | `iw-suppliers` | إدارة الموردين |
| التصنيفات | `iw-categories` | إدارة التصنيفات |
| الصلاحيات | `iw-permissions` | إدارة صلاحيات المستخدمين |
| التوقيع | `iw-signature` | رفع التوقيع الإلكتروني |
| الإعدادات | `iw-settings` | إعدادات النظام |

### 14.3 متطلبات صفحة الطباعة

يجب أن يحتوي كل إذن مطبوع على:
- شعار المؤسسة
- رقم الإذن والتاريخ
- بيانات الطالب والقسم
- جدول الأصناف (المطلوب / المعتمد / المنصرف)
- توقيع المعتمد الإلكتروني
- توقيع أمين المخزن

---

## 15. التقارير والتحليلات

### 15.1 تقرير المخزون الحالي

**الغرض:** عرض الوضع الحالي لكل المخزون

**الأعمدة:**
```
الصنف | الكود | التصنيف | الوحدة | الرصيد | الحد الأدنى | الحد الأقصى | السعر | الحالة
```

**الفلاتر:**
- التصنيف
- حالة المخزون (طبيعي / تحت الحد / منتهي)

---

### 15.2 تقرير الحركات

**الغرض:** تتبع كل عمليات الإضافة والصرف

**الأعمدة:**
```
التاريخ | النوع | الصنف | الكمية | السعر | المرجع | القسم | الموظف | المستخدم
```

**الفلاتر:**
- نوع الحركة (إضافة / صرف)
- الصنف
- القسم
- فترة زمنية (من / إلى)

---

### 15.3 تقرير استهلاك الأقسام

**الغرض:** تحليل تكاليف كل قسم

**الأعمدة (مجمع):**
```
القسم | عدد الأذون | إجمالي الكميات | إجمالي القيمة
```

**الأعمدة (تفصيلي):**
```
التاريخ | الصنف | الكمية | السعر | الموظف | رقم الإذن
```

---

### 15.4 تقرير حركة الصنف الواحد

**الغرض:** تاريخ كامل لصنف من الإضافة حتى الصرف

**الأعمدة:**
```
التاريخ | النوع | الكمية | الرصيد بعد الحركة | المرجع | الملاحظات
```

---

## 16. متطلبات الاختبار

### 16.1 اختبارات الوحدة (Unit Tests)

| الوحدة | ما يجب اختباره |
|--------|--------------|
| FIFO Engine | دقة خوارزمية الصرف مع remaining_qty |
| Stock Sync | مزامنة الأرصدة من الحركات |
| Permission Check | كل مستويات الصلاحية |
| Auto Purchase Generation | تحديد الأصناف الناقصة |

### 16.2 اختبارات التكامل (Integration Tests)

| السيناريو | الخطوات |
|----------|--------|
| دورة شراء كاملة | إضافة صنف → رصيد افتتاحي → إضافة مشتريات → تقرير |
| دورة صرف كاملة | طلب صرف → اعتماد → تنفيذ → تقرير |
| توليد طلب شراء تلقائي | تعيين حد أدنى → صرف حتى الحد → توليد تلقائي |

### 16.3 اختبارات الأمان

- SQL Injection في كل input
- XSS في حقول النصوص
- CSRF في كل AJAX actions
- رفع ملفات ضارة

### 16.4 اختبارات الأداء

- تحميل 1000 صنف < 3 ثوانٍ
- مزامنة أرصدة 1000 صنف < 60 ثانية
- توليد تقرير الحركات لـ 10000 سجل < 10 ثوانٍ

---

## 17. خارطة الطريق والتطوير المستقبلي

### 17.1 التحسينات المقترحة (Next Version)

| الميزة | الأولوية | الوصف |
|--------|---------|-------|
| **REST API** | High | تحويل AJAX إلى REST API لدعم تطبيقات خارجية |
| **إشعارات بريدية تلقائية** | High | إشعار عند وصول المخزون للحد الأدنى |
| **لوحة إحصائيات متقدمة** | Medium | رسوم بيانية لاستهلاك الأصناف |
| **تصدير PDF** | Medium | تصدير التقارير بـ PDF احترافي |
| **Barcode Scanner** | Medium | مسح الباركود لإضافة أصناف |
| **تعدد المخازن** | Low | دعم أكثر من موقع مخزن |
| **إشعارات الـ Browser** | Low | إشعارات فورية للطلبات الجديدة |
| **تطبيق موبايل** | Future | واجهة موبايل لأمين المخزن |

### 17.2 المقترحات التقنية

| التحسين | الوصف |
|--------|-------|
| **Caching Layer** | Redis/Transient للتقارير الثقيلة |
| **Async Processing** | Background jobs للعمليات الكبيرة |
| **Audit Log** | سجل كامل لكل التغييرات (من غيّر ماذا متى) |
| **Multi-language** | دعم لغات إضافية بسهولة |
| **Unit Tests** | PHPUnit test suite |

---

## 18. المخاطر والقيود

### 18.1 المخاطر التقنية

| المخاطرة | الاحتمالية | التأثير | التخفيف |
|---------|-----------|--------|--------|
| تعطل HR System | متوسطة | عالٍ | النظام يعمل بشكل مستقل |
| فقدان البيانات أثناء مزامنة الأرصدة | منخفضة | عالٍ | Database Transactions |
| تعارض بيانات FIFO | منخفضة | عالٍ | Locking + Transactions |
| استيراد Excel بصيغة خاطئة | عالية | متوسط | تحقق مسبق + رسائل خطأ واضحة |
| أداء بطيء مع البيانات الكبيرة | متوسطة | متوسط | Indexes + Pagination |

### 18.2 القيود الحالية

| القيد | الوصف | الحل المؤقت |
|------|-------|------------|
| لا يوجد REST API | الاتصال عبر AJAX فقط | WordPress AJAX كافٍ للوقت الحالي |
| لا يدعم تعدد المخازن | مخزن واحد فقط | يمكن فصل بـ categories |
| لا توجد إشعارات بريدية | لا تنبيه تلقائي | التقارير اليدوية تعوض |
| الطباعة تعتمد على المتصفح | لا يوجد PDF generation | Print CSS يوفر جودة جيدة |
| التكامل مع HR اختياري | قد يفقد ربط الأقسام | يعمل بدونه مع فقدان ميزة التتبع |

---

## ملحق أ: قاموس المصطلحات

| المصطلح | الوصف |
|---------|-------|
| **FIFO** | First In First Out - أسلوب تسعير المخزون |
| **إذن إضافة** | مستند رسمي لإضافة مشتريات للمخزون |
| **إذن صرف** | مستند رسمي لصرف أصناف من المخزون |
| **إذن عهدة** | صرف مؤقت للاستخدام مع إعادة |
| **الرصيد الافتتاحي** | الكميات الموجودة عند بداية تشغيل النظام |
| **طلب شراء** | طلب رسمي لشراء أصناف من موردين |
| **الحد الأدنى** | الكمية التي تُطلق تنبيه نقص المخزون |
| **الحد الأقصى** | أقصى كمية يجب أن يُخزن من الصنف |
| **التوقيع الإلكتروني** | صورة توقيع المستخدم تظهر في الأذون المطبوعة |

---

## ملحق ب: تسلسل رقم الأذون

| النوع | الصيغة | المثال |
|------|-------|-------|
| إذن إضافة | `ADD-{YYYYMM}-{NNNN}` | ADD-202602-0001 |
| إذن صرف | `WD-{YYYYMM}-{NNNN}` | WD-202602-0042 |
| إذن عهدة | `CST-{YYYYMM}-{NNNN}` | CST-202602-0003 |
| طلب شراء | `PR-{YYYYMM}-{NNNN}` | PR-202602-0015 |
| رقم المورد | `SUP-{NNNN}` | SUP-0001 |

---

*هذه الوثيقة تمثل الحالة الراهنة للنظام في إصداره v2.4.0*
*آخر تحديث: فبراير 2026*
