<?php

return [
    // Navigation
    'appointments' => 'المواعيد',
    'appointment' => 'موعد',
    'contacts' => 'جهات الاتصال',
    'contact' => 'جهة اتصال',
    'services' => 'الخدمات',
    'service' => 'خدمة',
    'availability' => 'الجدولة',
    'availability_schedule' => 'جدول الجدولة',
    'availability_schedules' => 'جداول الجدولة',
    
    // Common Labels
    'time' => 'الوقت',
    'duration' => 'المدة',
    'duration_min' => 'المدة (دقيقة)',
    'status' => 'الحالة',
    'date' => 'التاريخ',
    'service_name' => 'الخدمة',
    'client_name' => 'اسم العميل',
    'client_phone' => 'هاتف العميل',
    'client_email' => 'بريد العميل',
    'notes' => 'ملاحظات',
    'active' => 'نشط',
    'active_status' => 'الحالة النشطة',
    'preview' => 'معاينة',
    'max_spots' => 'الحد الأقصى للأماكن',
    'full_name' => 'الاسم الكامل',
    'first_name' => 'الاسم الأول',
    'last_name' => 'اسم العائلة',
    'mobile' => 'الهاتف',
    'email' => 'البريد الإلكتروني',
    'total_appointments' => 'إجمالي المواعيد',
    'created_at' => 'تاريخ الإنشاء',
    
    // Days of Week
    'sunday' => 'الأحد',
    'monday' => 'الإثنين',
    'tuesday' => 'الثلاثاء',
    'wednesday' => 'الأربعاء',
    'thursday' => 'الخميس',
    'friday' => 'الجمعة',
    'saturday' => 'السبت',
    
    // Service Types
    'one_to_one' => 'واحد لواحد',
    'group' => 'مجموعة',
    
    // Status
    'booked' => 'محجوز',
    'canceled' => 'ملغي',
    'completed' => 'مكتمل',
    
    // Sections
    'service_information' => 'معلومات الخدمة',
    'appointment_details' => 'تفاصيل الموعد',
    'client_information' => 'معلومات العميل',
    'schedule_information' => 'معلومات الجدول',
    
    // Form Fields
    'name' => 'الاسم',
    'description' => 'الوصف',
    'slug' => 'الرابط',
    'price' => 'السعر',
    'type' => 'النوع',
    'day_of_week' => 'يوم الأسبوع',
    'start_time' => 'وقت البدء',
    'end_time' => 'وقت الانتهاء',
    'time_slots' => 'الأوقات المتاحة',
    'date_time' => 'التاريخ والوقت',
    
    // Actions
    'show_past_appointments' => 'إظهار المواعيد السابقة',
    'from_date' => 'من تاريخ',
    'until_date' => 'حتى تاريخ',
    'delete_all_slots' => 'حذف جميع الأوقات',
    'delete_all_slots_for_day' => 'حذف جميع الأوقات لهذا اليوم',
    'add_another_time_slot' => 'إضافة وقت آخر',
    'new_time_slot' => 'وقت جديد',
    
    // Helper Texts
    'booking_url_helper' => 'يُستخدم في رابط الحجز: domain-name.coach-name/service-name',
    'select_day_helper' => 'اختر اليوم، ثم أضف عدة أوقات أدناه',
    'group_service_helper' => 'مطلوب للخدمات الجماعية',
    'all_slots_active_helper' => 'سيحدد الحالة النشطة الافتراضية لجميع الأوقات',
    'open_booking_preview' => 'فتح معاينة الحجز في علامة تبويب جديدة',
    
    // Status Messages
    'n_slots' => '{count} وقت',
    'n_active_slots' => '{count} نشط',
    'unknown' => 'غير معروف',
    'all_slots_active_by_default' => 'جميع الأوقات نشطة افتراضياً',
    
    // Widgets
    'total_appointments' => 'إجمالي المواعيد',
    'all_time' => 'كل الوقت',
    'upcoming_appointments' => 'المواعيد القادمة',
    'scheduled' => 'مجدولة',
    'todays_appointments' => 'مواعيد اليوم',
    'today' => 'اليوم',
    'active_services' => 'الخدمات النشطة',
    'most_used_services' => 'الخدمات الأكثر استخداماً',
    'appointments_by_day' => 'المواعيد حسب أيام الأسبوع',
    'top_contacts_by_bookings' => 'أفضل جهات الاتصال حسب الحجوزات',
    'appointments_by_status' => 'المواعيد حسب الحالة',
    'bookings' => 'الحجوزات',
    'whatsapp' => 'واتساب',
    'timezone' => 'المنطقة الزمنية',
    'language' => 'اللغة',
    'english' => 'الإنجليزية',
    'arabic' => 'العربية',
    'cannot_change_status' => 'لا يمكن تغيير الحالة من ملغي إلى محجوز.',
    
    // Owner Panel
    'total_tenants' => 'إجمالي المستأجرين',
    'system_wide' => 'على مستوى النظام',
    'active_tenants' => 'المستأجرون النشطون',
    'trial_tenants' => 'المستأجرون في التجربة',
    'on_trial' => 'في التجربة',
    'suspended' => 'معلق',
    'trial' => 'تجربة',
    'total_users' => 'إجمالي المستخدمين',
    'across_all_tenants' => 'عبر جميع المستأجرين',
    'appointments_per_tenant' => 'المواعيد لكل مستأجر',
    'users' => 'المستخدمون',
    'top_tenants_by_appointments' => 'أفضل المستأجرين حسب المواعيد',
    'switch_to_tenant' => 'التبديل إلى المستأجر',
    'activate' => 'تفعيل',
    'suspend' => 'تعليق',
    'tenant_information' => 'معلومات المستأجر',
    'slug_helper' => 'يُستخدم في روابط المستأجرين',
    'subdomain' => 'النطاق الفرعي',
    'subdomain_helper' => 'النطاق الفرعي الاختياري للمستأجر',
    'trial_ends_at' => 'ينتهي التجربة في',
    'trial_expired' => 'انتهت التجربة',
    'owner_panel' => 'لوحة المالك',
    'tenant_management' => 'إدارة المستأجرين',
    'system_stats' => 'إحصائيات النظام',
];

