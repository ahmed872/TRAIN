<?php
/**
 * دوال مساعدة للتحقق من صيغ التواريخ وتوحيدها قبل تخزينها.
 */

/**
 * يتأكد إن التاريخ بصيغة YYYY-MM-DD وإنه تاريخ حقيقي موجود فعلًا.
 * بيرجّع التاريخ بعد التوحيد، أو null لو فاضي/غير صالح.
 */
function normalizeDate(?string $value): ?string
{
    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

    // createFromFormat بتقبل تواريخ زي 2024-02-31 وتزحلقها، فبنقارن النتيجة بالمدخل
    if ($date === false || $date->format('Y-m-d') !== $value) {
        return null;
    }

    return $date->format('Y-m-d');
}

/**
 * بيحوّل قيمة حقل datetime-local (بصيغة 2024-01-01T10:30) لصيغة MySQL DATETIME.
 * بيرجّع null لو القيمة فاضية أو غير صالحة.
 */
function normalizeDateTime(?string $value): ?string
{
    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    // المتصفح ممكن يبعت الثواني أو ما يبعتهاش
    foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i', 'Y-m-d H:i:s'] as $format) {
        $date = DateTimeImmutable::createFromFormat('!' . $format, $value);

        // زي normalizeDate بالظبط: createFromFormat بتقبل قيم خارج المدى
        // (زي الساعة 25) وتزحلقها لليوم اللي بعده، فبنقارن النتيجة بالمدخل.
        if ($date !== false && $date->format($format) === $value) {
            return $date->format('Y-m-d H:i:s');
        }
    }

    return null;
}
