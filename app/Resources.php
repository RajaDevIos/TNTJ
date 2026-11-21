<?php
declare(strict_types=1);

namespace App;

/**
 * Single source of truth for every admin-manageable module.
 * The REST API (/api/v1/{resource}) and the admin panel UI are both generated from this.
 *
 * Field types: text, textarea, html, phone, number, date, datetime, select, bool, image, file, url, branch
 *   (branch = foreign key to branches.id rendered as a dropdown)
 */
final class Resources
{
    public const BLOOD_GROUPS = ['A+','A-','B+','B-','AB+','AB-','O+','O-','A1+','A1-','A2+','A2B+','Bombay'];

    public static function all(): array
    {
        $order = ['name' => 'sort_order', 'type' => 'number', 'label' => 'வரிசை எண்', 'default' => 0];
        $active = ['name' => 'active', 'type' => 'bool', 'label' => 'தளத்தில் காட்டு', 'default' => 1];
        $approved = ['name' => 'approved', 'type' => 'bool', 'label' => 'அங்கீகரிக்கப்பட்டது', 'default' => 1];

        return [
            'news' => [
                'table' => 'news', 'label' => 'செய்திகள் / பதிவுகள்', 'icon' => '📰', 'group' => 'உள்ளடக்கம்',
                'order' => 'published_at DESC', 'search' => ['title', 'category'],
                'list' => ['title', 'category', 'branch_id', 'published_at', 'published'],
                'public_where' => 'published = 1',
                'fields' => [
                    ['name' => 'title', 'type' => 'text', 'label' => 'தலைப்பு', 'required' => true],
                    ['name' => 'slug', 'type' => 'text', 'label' => 'URL பெயர் (slug)', 'help' => 'காலியாக விட்டால் தானாக உருவாகும்'],
                    ['name' => 'category', 'type' => 'text', 'label' => 'வகை', 'suggest' => ['மாவட்ட செய்திகள்','கிளை செய்திகள்','தாவா','இரத்ததானம்','சமூக சேவை','கட்டுரைகள்','நிகழ்ச்சிகள்']],
                    ['name' => 'branch_id', 'type' => 'branch', 'label' => 'கிளை'],
                    ['name' => 'image', 'type' => 'image', 'label' => 'முகப்புப் படம்'],
                    ['name' => 'body', 'type' => 'html', 'label' => 'உள்ளடக்கம்'],
                    ['name' => 'published_at', 'type' => 'datetime', 'label' => 'வெளியீட்டு நேரம்'],
                    ['name' => 'published', 'type' => 'bool', 'label' => 'வெளியிடு', 'default' => 1],
                ],
            ],
            'events' => [
                'table' => 'events', 'label' => 'மாவட்ட நிகழ்வுகள்', 'icon' => '🗓️', 'group' => 'உள்ளடக்கம்',
                'order' => 'starts_at DESC', 'search' => ['title', 'location', 'speaker'],
                'list' => ['title', 'starts_at', 'location', 'active'], 'public_where' => 'active = 1',
                'fields' => [
                    ['name' => 'title', 'type' => 'text', 'label' => 'நிகழ்வின் பெயர்', 'required' => true],
                    ['name' => 'category', 'type' => 'text', 'label' => 'வகை', 'suggest' => ['பொதுக்கூட்டம்','இரத்ததான முகாம்','தர்பியா','பெண்கள் பயான்','மருத்துவ முகாம்','போராட்டம்']],
                    ['name' => 'starts_at', 'type' => 'datetime', 'label' => 'தேதி & நேரம்', 'required' => true],
                    ['name' => 'location', 'type' => 'text', 'label' => 'இடம்'],
                    ['name' => 'speaker', 'type' => 'text', 'label' => 'சிறப்புரை'],
                    ['name' => 'poster', 'type' => 'image', 'label' => 'போஸ்டர்'],
                    ['name' => 'description', 'type' => 'textarea', 'label' => 'விவரம்'],
                    $active,
                ],
            ],
            'monthly-reports' => [
                'table' => 'monthly_reports', 'label' => 'மாதாந்திர புள்ளி பட்டியல்', 'icon' => '📊', 'group' => 'உள்ளடக்கம்',
                'order' => 'report_month DESC', 'search' => ['title'], 'list' => ['report_month', 'title'],
                'fields' => [
                    ['name' => 'report_month', 'type' => 'date', 'label' => 'மாதம் (1-ஆம் தேதி)', 'required' => true],
                    ['name' => 'title', 'type' => 'text', 'label' => 'தலைப்பு', 'required' => true],
                    ['name' => 'image', 'type' => 'image', 'label' => 'புள்ளி பட்டியல் படம்'],
                    ['name' => 'pdf', 'type' => 'file', 'label' => 'PDF (விருப்பம்)'],
                    ['name' => 'notes', 'type' => 'textarea', 'label' => 'குறிப்பு'],
                ],
            ],
            'downloads' => [
                'table' => 'downloads', 'label' => 'படிவங்கள் & தரவிறக்கம்', 'icon' => '📂', 'group' => 'உள்ளடக்கம்',
                'order' => 'category, sort_order, id', 'search' => ['title', 'category'],
                'list' => ['title', 'category', 'download_count', 'active'], 'public_where' => 'active = 1',
                'fields' => [
                    ['name' => 'title', 'type' => 'text', 'label' => 'தலைப்பு', 'required' => true],
                    ['name' => 'category', 'type' => 'text', 'label' => 'பிரிவு', 'required' => true, 'suggest' => ['படிவங்கள்','மக்தப் விண்ணப்பங்கள்','மக்தப் விதிமுறைகள்','மக்தப் பாடத்திட்டம் (Class C)','அறிக்கைகள்']],
                    ['name' => 'description', 'type' => 'textarea', 'label' => 'விவரம்'],
                    ['name' => 'file_url', 'type' => 'file', 'label' => 'கோப்பு / இணைப்பு', 'required' => true],
                    $order, $active,
                ],
            ],
            'policy-points' => [
                'table' => 'policy_points', 'label' => 'கொள்கை', 'icon' => '📖', 'group' => 'உள்ளடக்கம்',
                'order' => 'sort_order, id', 'search' => ['title'], 'list' => ['title', 'badge', 'sort_order', 'active'],
                'public_where' => 'active = 1',
                'fields' => [
                    ['name' => 'title', 'type' => 'text', 'label' => 'தலைப்பு', 'required' => true],
                    ['name' => 'badge', 'type' => 'text', 'label' => 'குறிச்சொல்'],
                    ['name' => 'body', 'type' => 'textarea', 'label' => 'விளக்கம்', 'required' => true],
                    $order, $active,
                ],
            ],
            'executives' => [
                'table' => 'executives', 'label' => 'மாவட்ட நிர்வாகிகள்', 'icon' => '👥', 'group' => 'நிர்வாகம்',
                'order' => 'sort_order, id', 'search' => ['name', 'role', 'phone'],
                'list' => ['name', 'role', 'phone', 'sort_order', 'active'], 'public_where' => 'active = 1',
                'fields' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'பெயர்', 'required' => true],
                    ['name' => 'role', 'type' => 'text', 'label' => 'பொறுப்பு', 'required' => true, 'suggest' => ['மாவட்ட தலைவர்','மாவட்ட செயலாளர்','மாவட்டப் பொருளாளர்','மாவட்ட துணைத் தலைவர்','மாவட்ட துணைச் செயலாளர்','மாவட்ட மாணவரணி செயலாளர்','மருத்துவரணி செயலாளர்','மாவட்ட தொண்டரணி செயலாளர்','மாவட்ட வர்த்தக அணி செயலாளர்']],
                    ['name' => 'phone', 'type' => 'phone', 'label' => 'தொலைபேசி'],
                    ['name' => 'photo', 'type' => 'image', 'label' => 'புகைப்படம்'],
                    $order, $active,
                ],
            ],
            'daees' => [
                'table' => 'daees', 'label' => 'மாவட்ட தாயிக்கள்', 'icon' => '🎙️', 'group' => 'நிர்வாகம்',
                'order' => 'sort_order, id', 'search' => ['name', 'area', 'phone'],
                'list' => ['name', 'gender', 'qualification', 'phone', 'active'], 'public_where' => 'active = 1',
                'fields' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'பெயர்', 'required' => true],
                    ['name' => 'gender', 'type' => 'select', 'label' => 'பிரிவு', 'options' => ['ஆண்', 'பெண்'], 'default' => 'ஆண்'],
                    ['name' => 'qualification', 'type' => 'text', 'label' => 'கல்வித் தகுதி', 'suggest' => ['M.I.Sc', 'ஆலிம்', 'ஆலிமா', 'ஃபிர்தௌஸி', 'உமரி']],
                    ['name' => 'area', 'type' => 'text', 'label' => 'ஊர் / பகுதி'],
                    ['name' => 'phone', 'type' => 'phone', 'label' => 'தொலைபேசி'],
                    ['name' => 'specialization', 'type' => 'text', 'label' => 'சிறப்புத் துறை'],
                    ['name' => 'photo', 'type' => 'image', 'label' => 'புகைப்படம்'],
                    $order, $active,
                ],
            ],
            'branches' => [
                'table' => 'branches', 'label' => 'கிளைகள்', 'icon' => '🏢', 'group' => 'நிர்வாகம்',
                'order' => 'sort_order, name', 'search' => ['name', 'area'],
                'list' => ['name', 'area', 'sort_order', 'active'], 'public_where' => 'active = 1',
                'fields' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'கிளையின் பெயர்', 'required' => true],
                    ['name' => 'area', 'type' => 'text', 'label' => 'வட்டம் / பகுதி'],
                    ['name' => 'address', 'type' => 'textarea', 'label' => 'மர்கஸ் முகவரி'],
                    ['name' => 'map_url', 'type' => 'url', 'label' => 'Google Maps இணைப்பு'],
                    $order, $active,
                ],
            ],
            'branch-officers' => [
                'table' => 'branch_officers', 'label' => 'கிளை நிர்வாகிகள்', 'icon' => '🧑‍💼', 'group' => 'நிர்வாகம்',
                'order' => 'branch_id, sort_order, id', 'search' => ['name', 'role', 'phone'],
                'list' => ['branch_id', 'role', 'name', 'phone'], 'filter' => 'branch_id',
                'fields' => [
                    ['name' => 'branch_id', 'type' => 'branch', 'label' => 'கிளை', 'required' => true],
                    ['name' => 'role', 'type' => 'text', 'label' => 'பொறுப்பு', 'required' => true, 'suggest' => ['தலைவர்','செயலாளர்','பொருளாளர்','துணைத் தலைவர்','துணைச் செயலாளர்','மருத்துவ அணி','தொண்டர் அணி','மாணவரணி','வர்த்தக அணி']],
                    ['name' => 'name', 'type' => 'text', 'label' => 'பெயர்', 'required' => true],
                    ['name' => 'phone', 'type' => 'phone', 'label' => 'தொலைபேசி'],
                    $order,
                ],
            ],
            'colleges' => [
                'table' => 'colleges', 'label' => 'பெண்கள் இஸ்லாமிய கல்லூரிகள்', 'icon' => '🏫', 'group' => 'கல்வி',
                'order' => 'sort_order, id', 'search' => ['name', 'location'],
                'list' => ['name', 'location', 'admission_open', 'active'], 'public_where' => 'active = 1',
                'fields' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'கல்லூரியின் பெயர்', 'required' => true],
                    ['name' => 'location', 'type' => 'text', 'label' => 'இடம்'],
                    ['name' => 'courses', 'type' => 'text', 'label' => 'படிப்புகள்'],
                    ['name' => 'eligibility', 'type' => 'text', 'label' => 'தகுதி'],
                    ['name' => 'seats', 'type' => 'text', 'label' => 'இடங்கள்'],
                    ['name' => 'facilities', 'type' => 'textarea', 'label' => 'வசதிகள்'],
                    ['name' => 'description', 'type' => 'textarea', 'label' => 'விவரம்'],
                    ['name' => 'phone', 'type' => 'phone', 'label' => 'தொடர்பு எண்'],
                    ['name' => 'photo', 'type' => 'image', 'label' => 'படம்'],
                    ['name' => 'admission_open', 'type' => 'bool', 'label' => 'சேர்க்கை நடைபெறுகிறது', 'default' => 0],
                    $order, $active,
                ],
            ],
            'maktabs' => [
                'table' => 'maktabs', 'label' => 'மக்தப் மதரஸா', 'icon' => '🕌', 'group' => 'கல்வி',
                'order' => 'sort_order, id', 'search' => ['name', 'teacher'],
                'list' => ['name', 'branch_id', 'timing', 'students', 'active'], 'public_where' => 'active = 1',
                'fields' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'மதரஸாவின் பெயர்', 'required' => true],
                    ['name' => 'branch_id', 'type' => 'branch', 'label' => 'கிளை'],
                    ['name' => 'timing', 'type' => 'text', 'label' => 'வகுப்பு நேரம்'],
                    ['name' => 'students', 'type' => 'number', 'label' => 'மாணவர்கள் எண்ணிக்கை'],
                    ['name' => 'teacher', 'type' => 'text', 'label' => 'ஆசிரியர்'],
                    ['name' => 'phone', 'type' => 'phone', 'label' => 'தொடர்பு எண்'],
                    ['name' => 'curriculum', 'type' => 'textarea', 'label' => 'பாடத்திட்டம்'],
                    $order, $active,
                ],
            ],
            'blood-requests' => [
                'table' => 'blood_requests', 'label' => 'இரத்தம் தேவை', 'icon' => '🆘', 'group' => 'சேவைகள்',
                'order' => 'approved ASC, created_at DESC', 'search' => ['patient_name', 'hospital', 'blood_group', 'contact_phone'],
                'list' => ['blood_group', 'patient_name', 'hospital', 'needed_on', 'status', 'approved'],
                'public_where' => "approved = 1 AND status = 'தேவை'", 'pending' => 'approved = 0',
                'fields' => [
                    ['name' => 'patient_name', 'type' => 'text', 'label' => 'நோயாளி பெயர்', 'required' => true],
                    ['name' => 'blood_group', 'type' => 'select', 'label' => 'இரத்த வகை', 'options' => self::BLOOD_GROUPS, 'required' => true],
                    ['name' => 'units', 'type' => 'number', 'label' => 'யூனிட்', 'default' => 1],
                    ['name' => 'hospital', 'type' => 'text', 'label' => 'மருத்துவமனை', 'required' => true],
                    ['name' => 'needed_on', 'type' => 'date', 'label' => 'தேவைப்படும் நாள்'],
                    ['name' => 'contact_name', 'type' => 'text', 'label' => 'தொடர்பாளர் பெயர்', 'required' => true],
                    ['name' => 'contact_phone', 'type' => 'phone', 'label' => 'தொடர்பு எண்', 'required' => true],
                    ['name' => 'notes', 'type' => 'textarea', 'label' => 'குறிப்பு'],
                    ['name' => 'status', 'type' => 'select', 'label' => 'நிலை', 'options' => ['தேவை', 'பெறப்பட்டது', 'முடிந்தது'], 'default' => 'தேவை'],
                    $approved,
                ],
            ],
            'blood-donors' => [
                'table' => 'blood_donors', 'label' => 'இரத்த கொடையாளர்கள்', 'icon' => '🩸', 'group' => 'சேவைகள்',
                'order' => 'approved ASC, blood_group, name', 'search' => ['name', 'area', 'phone', 'blood_group'],
                'list' => ['name', 'blood_group', 'area', 'phone', 'last_donated', 'available', 'approved'],
                'public_where' => 'approved = 1', 'pending' => 'approved = 0',
                'fields' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'பெயர்', 'required' => true],
                    ['name' => 'blood_group', 'type' => 'select', 'label' => 'இரத்த வகை', 'options' => self::BLOOD_GROUPS, 'required' => true],
                    ['name' => 'area', 'type' => 'text', 'label' => 'ஊர்'],
                    ['name' => 'branch_id', 'type' => 'branch', 'label' => 'கிளை'],
                    ['name' => 'phone', 'type' => 'phone', 'label' => 'தொலைபேசி', 'required' => true],
                    ['name' => 'age', 'type' => 'number', 'label' => 'வயது'],
                    ['name' => 'last_donated', 'type' => 'date', 'label' => 'கடைசியாக தானம் செய்த நாள்'],
                    ['name' => 'available', 'type' => 'bool', 'label' => 'தானம் செய்ய தயார்', 'default' => 1],
                    $approved,
                ],
            ],
            'matrimony' => [
                'table' => 'matrimony', 'label' => 'திருமண தகவல்', 'icon' => '💍', 'group' => 'சேவைகள்',
                'order' => 'approved ASC, created_at DESC', 'search' => ['profile_code', 'area', 'education', 'occupation', 'contact_phone'],
                'list' => ['profile_code', 'looking_for', 'age', 'area', 'status', 'approved'],
                'public_where' => "approved = 1 AND status = 'செயலில்'", 'pending' => 'approved = 0',
                'private' => ['contact_name', 'contact_phone'],
                'fields' => [
                    ['name' => 'profile_code', 'type' => 'text', 'label' => 'பதிவு எண்', 'help' => 'காலியாக விட்டால் தானாக உருவாகும்'],
                    ['name' => 'looking_for', 'type' => 'select', 'label' => 'தேவை', 'options' => ['மணமகன் தேவை', 'மணமகள் தேவை'], 'required' => true],
                    ['name' => 'age', 'type' => 'number', 'label' => 'வயது'],
                    ['name' => 'marital_status', 'type' => 'select', 'label' => 'திருமண நிலை', 'options' => ['முதல் திருமணம்', 'மறுமணம்']],
                    ['name' => 'education', 'type' => 'text', 'label' => 'கல்வி'],
                    ['name' => 'occupation', 'type' => 'text', 'label' => 'பணி'],
                    ['name' => 'area', 'type' => 'text', 'label' => 'ஊர்'],
                    ['name' => 'details', 'type' => 'textarea', 'label' => 'விவரம்'],
                    ['name' => 'contact_name', 'type' => 'text', 'label' => 'தொடர்பாளர் (பெற்றோர்/பொறுப்பாளர்)'],
                    ['name' => 'contact_phone', 'type' => 'phone', 'label' => 'தொடர்பு எண்', 'required' => true],
                    ['name' => 'show_phone', 'type' => 'bool', 'label' => 'எண்ணை தளத்தில் காட்டு', 'default' => 0],
                    ['name' => 'status', 'type' => 'select', 'label' => 'நிலை', 'options' => ['செயலில்', 'நிச்சயமானது', 'நீக்கப்பட்டது'], 'default' => 'செயலில்'],
                    $approved,
                ],
            ],
            'links' => [
                'table' => 'links', 'label' => 'அதிகாரப்பூர்வ இணைப்புகள்', 'icon' => '🌐', 'group' => 'அமைப்புகள்',
                'order' => 'sort_order, id', 'search' => ['title', 'url'], 'list' => ['title', 'url', 'category', 'active'],
                'public_where' => 'active = 1',
                'fields' => [
                    ['name' => 'title', 'type' => 'text', 'label' => 'தலைப்பு', 'required' => true],
                    ['name' => 'url', 'type' => 'url', 'label' => 'இணைப்பு', 'required' => true],
                    ['name' => 'category', 'type' => 'text', 'label' => 'பிரிவு', 'suggest' => ['மாநில தலைமை', 'சமூக ஊடகம்', 'மாவட்டம்']],
                    ['name' => 'description', 'type' => 'textarea', 'label' => 'விவரம்'],
                    $order, $active,
                ],
            ],
            'pages' => [
                'table' => 'pages', 'label' => 'பக்கங்கள்', 'icon' => '📄', 'group' => 'அமைப்புகள்',
                'order' => 'slug', 'search' => ['title', 'slug'], 'list' => ['slug', 'title', 'updated_at'],
                'fields' => [
                    ['name' => 'slug', 'type' => 'text', 'label' => 'Slug (policy, about…)', 'required' => true],
                    ['name' => 'title', 'type' => 'text', 'label' => 'தலைப்பு', 'required' => true],
                    ['name' => 'subtitle', 'type' => 'text', 'label' => 'துணைத் தலைப்பு'],
                    ['name' => 'body', 'type' => 'html', 'label' => 'உள்ளடக்கம்'],
                ],
            ],
            'translations' => [
                'table' => 'translations', 'label' => 'மொழிபெயர்ப்புகள் (English / العربية)', 'icon' => '🌐', 'group' => 'அமைப்புகள்',
                'order' => 'lang, id', 'search' => ['source', 'value'], 'list' => ['lang', 'source', 'value', 'updated_at'],
                'fields' => [
                    ['name' => 'lang', 'type' => 'select', 'label' => 'மொழி', 'options' => ['en', 'ar'], 'required' => true, 'default' => 'en'],
                    ['name' => 'source', 'type' => 'textarea', 'label' => 'தமிழ் உரை (தளத்தில் உள்ளபடியே)', 'required' => true, 'help' => 'தளத்தில் உள்ள தமிழ் உரையை அப்படியே ஒட்டவும். அதே உரை வரும் எல்லா இடத்திலும் மொழிபெயர்ப்பு காட்டப்படும்.'],
                    ['name' => 'value', 'type' => 'textarea', 'label' => 'மொழிபெயர்ப்பு', 'required' => true],
                ],
            ],
            'contact-messages' => [
                'table' => 'contact_messages', 'label' => 'தொடர்பு செய்திகள்', 'icon' => '✉️', 'group' => 'சேவைகள்',
                'order' => 'is_read ASC, created_at DESC', 'search' => ['name', 'phone', 'subject', 'message'],
                'list' => ['name', 'phone', 'subject', 'created_at', 'is_read'], 'pending' => 'is_read = 0', 'no_create' => true,
                'fields' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'பெயர்', 'required' => true],
                    ['name' => 'phone', 'type' => 'phone', 'label' => 'தொலைபேசி'],
                    ['name' => 'subject', 'type' => 'text', 'label' => 'பொருள்'],
                    ['name' => 'message', 'type' => 'textarea', 'label' => 'செய்தி', 'required' => true],
                    ['name' => 'is_read', 'type' => 'bool', 'label' => 'படிக்கப்பட்டது', 'default' => 0],
                ],
            ],
        ];
    }

    public static function get(string $key): ?array { return self::all()[$key] ?? null; }
}
