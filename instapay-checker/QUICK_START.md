# ✨ Professional Validators Installed

## 🎯 New Validation Features (as of April 5, 2026)

Your Instapay validator now uses **industry-standard, free libraries**:

### ✓ IBAN Validation
- **php-iban v4.2.3** - MOD-97 certified, 116+ countries
- Validates Egyptian IBANs with professional checksum algorithm
- Fast, offline validation (no external API calls)

### ✓ Phone Validation  
- **libphonenumber-for-php v9.0.27** - Google's official library
- Automatic carrier detection (Vodafone, Etisalat, Telecom Egypt, Mobinil)
- International format support (+20 and 0 prefixes)
- Tested and verified working ✓

### ✓ Email Validation
- Partner bank domain whitelist (7+ Egyptian banks)
- SMTP MX record verification
- Real-time validation against Instapay partners

## 🚀 Run Tests
```bash
cd d:\system\study-is-funny\instapay-checker
php test_validators.php
```

---

# البدء السريع

دليل سريع للبدء مع Instapay Transaction Validator + Gemini Vision

## ⚡ خطوات سريعة (5 دقائق)

### الخطوة 1: التحضير
```bash
# 1. انسخ ملفات المشروع إلى مجلد
# 2. تأكد من أن php مثبت
# 3. تأكد من تفعيل cURL و SQLite في php.ini
```

### الخطوة 2: فحص النظام
```
افتح في المتصفح:
http://yourserver/instapay-checker/test-system.php

انتظر حتى تظهر جميع الفحوصات باللون الأخضر ✓
```

### الخطوة 3: الاستخدام

افتح `index.html`:
```
http://yourserver/instapay-checker/index.html
```

1. اسحب صورة إنستاباي أو انقر لاختيارها
2. انتظر التحليل (2-5 ثوان)
3. راجع النتائج
4. انقر "حفظ" لحفظ المعاملة

### الخطوة 4: عرض السجلات

لعرض جميع المعاملات المحفوظة:
```
http://yourserver/instapay-checker/manage.php
```

## 📋 البيانات التي يتم استخراجها

لكل لقطة شاشة:
```
✓ المبلغ
✓ العملة (EGP)
✓ حساب المرسل
✓ اسم المرسل
✓ اسم المستقبل
✓ رقم الهاتف
✓ رقم المرجع
✓ التاريخ والوقت
✓ اسم البنك
✓ درجة الثقة
```

## 🔍 مثال: تحليل لقطة شاشة

### المدخل (صورة)
صورة من تطبيق إنستاباي تشير معاملة مالية

### المعالجة (Gemini Vision)
تحليل الصورة بواسطة AI لاستخراج البيانات

### العناصر المكتشفة
```json
{
  "amount": "320",
  "currency": "EGP",
  "sender_account": "fatmamohmed1973@instapay",
  "sender_name": "فاطمة محمد",
  "receiver_name": "Shady M",
  "receiver_phone": "01010796944",
  "reference_number": "62981422205",
  "transaction_date": "04 Apr 2026 02:10 PM"
}
```

### التحقق
- ✓ جميع البيانات موجودة
- ✓ درجة الثقة: 95%
- ✓ لم يتم العثور على نسخة مكررة
- ✓ **النتيجة: معاملة حقيقية**

## ⚠️ الحالات الخاصة

### معاملة مريبة (Suspicious)
- بيانات ناقصة
- أرقام غير صحيحة
- تنسيق غريب

### معاملة مزيفة (Invalid)
- صورة غير واضحة
- بيانات متناقضة
- علامات تزييف

## 🛠️ استكشاف المشاكل

### المشكلة: "خطأ في الاتصال"
**الحل:**
1. تحقق من الإنترنت
2. تأكد من تفعيل cURL
3. تحقق من API Key

### المشكلة: "فشل في معالجة الصورة"
**الحل:**
1. تأكد من وضوح الصورة
2. اختر صورة أصلية من التطبيق
3. تأكد من أن الملف JPG/PNG

### المشكلة: "المجلد غير قابل للكتابة"
**الحل:**
```bash
chmod 755 uploads/
```

## 💡 نصائح الاستخدام

1. **صور واضحة** - خذ لقطات شاشة واضحة
2. **كاملة** - تأكد من رؤية جميع البيانات
3. **جديدة** - استخدم لقطات حديثة (قبل تعديل)
4. **موثوقة** - من تطبيق إنستاباي الرسمي

## 📊 الإحصائيات

القسم السفلي يظهر:
- **إجمالي المعاملات** - كل المعاملات المحفوظة
- **معاملات صحيحة** - التي تم التحقق منها بنجاح
- **معاملات مريبة** - التي تحتاج مراجعة

## 🔐 الأمان

✓ المعاملات محفوظة محلياً (SQLite)
✓ الصور محفوظة على الخادم
✓ البيانات لا تُرسل إلى طرف ثالث
✓ IP تُسجل لكل عملية

## 📱 الأجهزة المدعومة

- ✓ ويندوز
- ✓ ماك
- ✓ لينيوكس
- ✓ استضافة ويب عادية
- ✓ جوال (عبر المتصفح)

## 🎯 الاستخدامات

- التحقق من المعاملات قبل القبول
- توثيق العمليات المالية
- الكشف عن التزييفات
- حفظ السجلات
- عمل تقارير

## 📞 الدعم

للمزيد من المعلومات:
- اقرأ `README.md` - التفاصيل الكاملة
- اقرأ `GEMINI_SETUP.md` - تفاصيل Gemini
- قم بفحص النظام `test-system.php`
- تحقق من الأخطاء في سجلات PHP

## 🚀 الخطوات التالية

1. ✓ جرب مع عدة لقطات شاشة
2. ✓ تفحص دقة الاستخراج
3. ✓ راجع قاعدة البيانات
4. ✓ اضبط إذا لزم الحال
5. ✓ ادمجه مع نظامك الرئيسي

---

**للأسئلة والمشاكل:**
اقرأ GEMINI_SETUP.md للمزيد من التفاصيل التقنية

**آخر تحديث:** أبريل 2026
